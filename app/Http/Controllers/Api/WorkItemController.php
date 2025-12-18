<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use App\Models\WorkAssignment;
use App\Models\WorkActivityLog;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkItemController extends Controller
{
    /**
     * Display a listing of work items
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = WorkItem::with(['creator', 'branch', 'parent', 'assignments.user', 'tags']);

        // Permission-based filtering
        if ($user->can('work_items.view_all')) {
            // Can see all work items in their branch
            $query->forBranch($user->primary_branch_id);
        } elseif ($user->can('work_items.view_department')) {
            // Can see department work items
            $departmentIds = [$user->department_id];
            // If user is department head, include sub-departments
            if ($user->department && $user->department->head_id == $user->id) {
                $subDepartments = Department::where('parent_id', $user->department_id)
                    ->pluck('id')
                    ->toArray();
                $departmentIds = array_merge($departmentIds, $subDepartments);
            }

            $query->where(function ($q) use ($user, $departmentIds) {
                $q->whereHas('assignments', function ($q) use ($departmentIds) {
                    $q->whereIn('department_id', $departmentIds);
                })->orWhereHas('assignments', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });
        } else {
            // Can only see own work items
            $query->forUser($user->id);
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('work_tags.id', $request->tag_id);
            });
        }

        if ($request->filled('overdue')) {
            $query->overdue();
        }

        if ($request->filled('due_soon')) {
            $query->dueSoon();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $items = $query->paginate($perPage);

        return response()->json($items);
    }

    /**
     * Store a newly created work item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:project,task',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:work_items,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'metadata' => 'nullable|array',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:work_tags,id',
            'assignments' => 'nullable|array',
            'assignments.*.user_id' => 'nullable|exists:users,id',
            'assignments.*.department_id' => 'nullable|exists:departments,id',
            'assignments.*.role' => 'required|in:executor,assigner,observer,supporter',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();

            // Validate parent if type is task
            if ($request->type === 'task' && !$request->parent_id) {
                return response()->json(['error' => 'Task must have a parent project'], 422);
            }

            // Create work item
            $workItem = WorkItem::create([
                'code' => WorkItem::generateCode($request->type),
                'type' => $request->type,
                'parent_id' => $request->parent_id,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'status' => 'pending',
                'estimated_hours' => $request->estimated_hours,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'metadata' => $request->metadata,
                'created_by' => $user->id,
                'branch_id' => $user->primary_branch_id,
            ]);

            // Attach tags
            if ($request->filled('tag_ids')) {
                $workItem->tags()->sync($request->tag_ids);
            }

            // Create assignments
            if ($request->filled('assignments')) {
                foreach ($request->assignments as $assignment) {
                    WorkAssignment::create([
                        'work_item_id' => $workItem->id,
                        'user_id' => $assignment['user_id'] ?? null,
                        'department_id' => $assignment['department_id'] ?? null,
                        'role' => $assignment['role'],
                        'is_department_assignment' => isset($assignment['department_id']),
                        'assigned_by' => $user->id,
                        'status' => 'pending',
                    ]);
                }
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'created',
                $user,
                null,
                $workItem->toArray(),
                ['message' => 'Work item created']
            );

            DB::commit();

            return response()->json([
                'message' => 'Work item created successfully',
                'data' => $workItem->load(['creator', 'assignments.user', 'tags'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create work item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified work item
     */
    public function show($id)
    {
        $workItem = WorkItem::with([
            'creator',
            'branch',
            'parent',
            'children',
            'assignments.user',
            'assignments.department',
            'discussions.user',
            'submissions.reviewer',
            'attachments.uploader',
            'tags'
        ])->findOrFail($id);

        // Check permission
        $user = auth()->user();
        if (!$this->canViewWorkItem($user, $workItem)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($workItem);
    }

    /**
     * Update the specified work item
     */
    public function update(Request $request, $id)
    {
        $workItem = WorkItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'metadata' => 'nullable|array',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:work_tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldValues = $workItem->toArray();
            $user = auth()->user();

            // Update work item
            $workItem->update($request->only([
                'title', 'description', 'priority', 'estimated_hours',
                'start_date', 'due_date', 'metadata'
            ]));

            // Update tags if provided
            if ($request->has('tag_ids')) {
                $workItem->tags()->sync($request->tag_ids);
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'updated',
                $user,
                $oldValues,
                $workItem->fresh()->toArray(),
                ['fields_changed' => array_keys($request->all())]
            );

            DB::commit();

            return response()->json([
                'message' => 'Work item updated successfully',
                'data' => $workItem->load(['creator', 'assignments.user', 'tags'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update work item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified work item
     */
    public function destroy($id)
    {
        $workItem = WorkItem::findOrFail($id);

        DB::beginTransaction();
        try {
            // Check if has children
            if ($workItem->children()->count() > 0) {
                return response()->json(['error' => 'Cannot delete work item with children'], 422);
            }

            // Log activity before deletion
            WorkActivityLog::logActivity(
                $workItem,
                'deleted',
                auth()->user(),
                $workItem->toArray(),
                null,
                ['message' => 'Work item deleted']
            );

            $workItem->delete();

            DB::commit();

            return response()->json(['message' => 'Work item deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete work item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign users/departments to work item
     */
    public function assign(Request $request, $id)
    {
        $workItem = WorkItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'assignments' => 'required|array',
            'assignments.*.user_id' => 'nullable|exists:users,id',
            'assignments.*.department_id' => 'nullable|exists:departments,id',
            'assignments.*.role' => 'required|in:executor,assigner,observer,supporter',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $newAssignments = [];

            foreach ($request->assignments as $assignment) {
                $newAssignment = WorkAssignment::create([
                    'work_item_id' => $workItem->id,
                    'user_id' => $assignment['user_id'] ?? null,
                    'department_id' => $assignment['department_id'] ?? null,
                    'role' => $assignment['role'],
                    'is_department_assignment' => isset($assignment['department_id']),
                    'assigned_by' => $user->id,
                    'status' => 'pending',
                ]);

                $newAssignments[] = $newAssignment;
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'assigned',
                $user,
                null,
                ['assignments' => $newAssignments],
                ['count' => count($newAssignments)]
            );

            // Update work item status if it's pending
            if ($workItem->status === 'pending') {
                $workItem->update(['status' => 'assigned']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Assignments created successfully',
                'data' => $workItem->load(['assignments.user', 'assignments.department'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create assignments: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update work item status
     */
    public function updateStatus(Request $request, $id)
    {
        $workItem = WorkItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,assigned,in_progress,submitted,revision_requested,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $workItem->status;
            $user = auth()->user();

            $workItem->update(['status' => $request->status]);

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'status_changed',
                $user,
                ['status' => $oldStatus],
                ['status' => $request->status],
                ['notes' => $request->notes]
            );

            DB::commit();

            return response()->json([
                'message' => 'Status updated successfully',
                'data' => $workItem
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get work item activity timeline
     */
    public function timeline($id)
    {
        $workItem = WorkItem::findOrFail($id);

        // Check permission
        $user = auth()->user();
        if (!$this->canViewWorkItem($user, $workItem)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = $workItem->activityLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Check if user can view work item
     */
    private function canViewWorkItem($user, $workItem)
    {
        if ($user->can('work_items.view_all')) {
            return $workItem->branch_id == $user->primary_branch_id;
        }

        if ($user->can('work_items.view_department')) {
            $departmentIds = [$user->department_id];
            if ($user->department && $user->department->head_id == $user->id) {
                $subDepartments = Department::where('parent_id', $user->department_id)
                    ->pluck('id')
                    ->toArray();
                $departmentIds = array_merge($departmentIds, $subDepartments);
            }

            return $workItem->assignments()
                ->where(function ($q) use ($user, $departmentIds) {
                    $q->where('user_id', $user->id)
                      ->orWhereIn('department_id', $departmentIds);
                })
                ->exists();
        }

        // view_own permission
        return $workItem->assignments()->where('user_id', $user->id)->exists();
    }
}
