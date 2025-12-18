<?php

namespace App\Services;

use App\Models\WorkItem;
use App\Models\WorkActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkItemService
{
    /**
     * Create a new work item
     */
    public function create(array $data, User $creator): WorkItem
    {
        DB::beginTransaction();
        try {
            // Validate parent if type is task
            if ($data['type'] === 'task' && empty($data['parent_id'])) {
                throw new \Exception('Task must have a parent project');
            }

            // Create work item
            $workItem = WorkItem::create([
                'code' => WorkItem::generateCode($data['type']),
                'type' => $data['type'],
                'parent_id' => $data['parent_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => 'pending',
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $creator->id,
                'branch_id' => $creator->branch_id,
            ]);

            // Attach tags
            if (!empty($data['tag_ids'])) {
                $workItem->tags()->sync($data['tag_ids']);
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'created',
                $creator,
                null,
                $workItem->toArray(),
                ['message' => 'Work item created']
            );

            DB::commit();

            return $workItem->load(['creator', 'tags']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update work item
     */
    public function update(WorkItem $workItem, array $data, User $user): WorkItem
    {
        DB::beginTransaction();
        try {
            $oldValues = $workItem->toArray();

            // Update work item
            $workItem->update(array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ], fn($value) => $value !== null));

            // Update tags if provided
            if (isset($data['tag_ids'])) {
                $workItem->tags()->sync($data['tag_ids']);
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'updated',
                $user,
                $oldValues,
                $workItem->fresh()->toArray(),
                ['fields_changed' => array_keys(array_filter($data, fn($v) => $v !== null))]
            );

            DB::commit();

            return $workItem->load(['creator', 'tags']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete work item
     */
    public function delete(WorkItem $workItem, User $user): void
    {
        DB::beginTransaction();
        try {
            // Check if has children
            if ($workItem->children()->count() > 0) {
                throw new \Exception('Cannot delete work item with children');
            }

            // Log activity before deletion
            WorkActivityLog::logActivity(
                $workItem,
                'deleted',
                $user,
                $workItem->toArray(),
                null,
                ['message' => 'Work item deleted']
            );

            $workItem->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update work item status
     */
    public function updateStatus(WorkItem $workItem, string $status, User $user, ?string $notes = null): WorkItem
    {
        DB::beginTransaction();
        try {
            $oldStatus = $workItem->status;

            $workItem->update(['status' => $status]);

            // Set completed_at if status is completed
            if ($status === 'completed' && !$workItem->completed_at) {
                $workItem->update(['completed_at' => now()]);
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'status_changed',
                $user,
                ['status' => $oldStatus],
                ['status' => $status],
                ['notes' => $notes]
            );

            DB::commit();

            return $workItem;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate work item progress
     */
    public function calculateProgress(WorkItem $workItem): float
    {
        if ($workItem->type === 'project') {
            // For projects, calculate based on children tasks
            $children = $workItem->children;

            if ($children->count() === 0) {
                return 0;
            }

            $totalProgress = 0;
            foreach ($children as $child) {
                if ($child->status === 'completed') {
                    $totalProgress += 100;
                } elseif ($child->status === 'in_progress' || $child->status === 'submitted') {
                    $totalProgress += 50;
                } elseif ($child->status === 'assigned') {
                    $totalProgress += 25;
                }
            }

            return round($totalProgress / $children->count(), 2);
        }

        // For tasks, calculate based on status
        return match ($workItem->status) {
            'completed' => 100,
            'submitted' => 90,
            'revision_requested' => 70,
            'in_progress' => 50,
            'assigned' => 25,
            default => 0,
        };
    }

    /**
     * Check if user can view work item
     */
    public function canUserView(WorkItem $workItem, User $user): bool
    {
        // View all permission
        if ($user->can('work_items.view_all') && $workItem->branch_id == $user->branch_id) {
            return true;
        }

        // View department permission
        if ($user->can('work_items.view_department')) {
            $departmentIds = [$user->department_id];

            // If user is department head, include sub-departments
            if ($user->department && $user->department->head_id == $user->id) {
                $subDepartments = \App\Models\Department::where('parent_id', $user->department_id)
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

        // View own permission
        return $workItem->assignments()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user can edit work item
     */
    public function canUserEdit(WorkItem $workItem, User $user): bool
    {
        // Creator can always edit
        if ($workItem->created_by == $user->id) {
            return true;
        }

        // User with edit permission and is assigned
        if ($user->can('work_items.edit')) {
            return $workItem->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }
}
