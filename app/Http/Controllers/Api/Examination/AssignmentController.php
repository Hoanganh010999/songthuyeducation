<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Assignment;
use App\Models\Examination\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Assignment::with(['test', 'creator'])
            ->withCount(['targets', 'submissions'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->test_id, fn($q, $testId) => $q->where('test_id', $testId))
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            // Note: Examination module is global - no branch filtering
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc');

        $perPage = min($request->per_page ?? 20, 100);
        $assignments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Get assignments for current user (student view).
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $query = Assignment::with(['test'])
            ->forUser($userId)
            ->active()
            ->withCount(['submissions as my_attempts' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->when($request->status === 'pending', function ($q) use ($userId) {
                $q->whereDoesntHave('submissions', function ($sq) use ($userId) {
                    $sq->where('user_id', $userId)
                        ->whereIn('status', ['submitted', 'graded']);
                });
            })
            ->when($request->status === 'completed', function ($q) use ($userId) {
                $q->whereHas('submissions', function ($sq) use ($userId) {
                    $sq->where('user_id', $userId)
                        ->whereIn('status', ['submitted', 'graded']);
                });
            })
            ->orderBy('due_date', 'asc');

        $assignments = $query->get()->map(function ($assignment) use ($userId) {
            $latestSubmission = $assignment->submissions()
                ->where('user_id', $userId)
                ->latest()
                ->first();

            return [
                'id' => $assignment->id,
                'uuid' => $assignment->uuid,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'test' => [
                    'id' => $assignment->test->id,
                    'title' => $assignment->test->title,
                    'type' => $assignment->test->type,
                    'time_limit' => $assignment->getEffectiveTimeLimit(),
                ],
                'start_date' => $assignment->start_date,
                'due_date' => $assignment->due_date,
                'is_past_due' => $assignment->isPastDue(),
                'is_available' => $assignment->isAvailable(),
                'max_attempts' => $assignment->getEffectiveMaxAttempts(),
                'my_attempts' => $assignment->my_attempts,
                'can_attempt' => $assignment->canUserAttempt($userId),
                'latest_submission' => $latestSubmission ? [
                    'id' => $latestSubmission->id,
                    'status' => $latestSubmission->status,
                    'score' => $latestSubmission->score,
                    'percentage' => $latestSubmission->percentage,
                    'submitted_at' => $latestSubmission->submitted_at,
                    'allow_special_view' => $latestSubmission->allow_special_view ?? false,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Store a newly created assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_id' => 'required|exists:tests,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'late_submission' => 'nullable|boolean',
            'late_penalty' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'grading_type' => 'nullable|in:auto,manual,mixed',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:draft,active,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $assignment = Assignment::create([
                'uuid' => (string) Str::uuid(),
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'test_id' => $request->test_id,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'late_submission' => $request->boolean('late_submission', false),
                'late_penalty' => $request->late_penalty ?? 0,
                'late_days_allowed' => $request->late_days_allowed ?? 0,
                'assign_type' => $request->assign_type ?? 'user',
                'max_attempts' => $request->max_attempts,
                'time_limit' => $request->time_limit,
                'grading_type' => $request->grading_type ?? 'auto',
                'passing_score' => $request->passing_score,
                'branch_id' => null, // Examination module is global
                'created_by' => auth()->id(),
                'status' => $request->status ?? 'draft',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully',
                'data' => $assignment->load('test'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified assignment.
     */
    public function show(string $id): JsonResponse
    {
        $assignment = Assignment::with([
            'test.sections',
            'test.testQuestions',
            'creator',
            'targets.targetable',
        ])->findOrFail($id);

        $assignment->statistics = $assignment->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:draft,active,closed,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignment->update($request->only([
            'title', 'description', 'instructions', 'start_date', 'due_date',
            'late_submission', 'late_penalty', 'late_days_allowed',
            'max_attempts', 'time_limit', 'grading_type', 'passing_score',
            'notify_on_assign', 'notify_on_due', 'notify_on_grade', 'status',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => $assignment->fresh(),
        ]);
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(string $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        // Check if has submissions
        if ($assignment->submissions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete assignment with submissions. Archive it instead.',
            ], 400);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully',
        ]);
    }

    /**
     * Assign to users.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $assigned = 0;
            foreach ($request->user_ids as $userId) {
                // Skip if already assigned
                $exists = $assignment->targets()
                    ->where('targetable_type', User::class)
                    ->where('targetable_id', $userId)
                    ->exists();

                if (!$exists) {
                    $assignment->assignToUser($userId, auth()->id());
                    $assigned++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assigned} users assigned successfully",
                'data' => ['assigned_count' => $assigned],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error assigning users: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get submissions for an assignment.
     */
    public function submissions(Request $request, string $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $user = auth()->user();
        $userId = $user->id;

        // Check permissions
        $canGrade = $user->hasPermission('examination.submissions.grade') || 
                    $user->hasPermission('examination.grading.view');
        $canViewSubmissions = $user->hasPermission('examination.submissions.view');
        $hasSpecialView = $user->hasPermission('examination.submissions.special_view');

        $query = $assignment->submissions()
            ->with(['user'])
            ->when($request->status, fn($q, $status) => $q->byStatus($status))
            ->when($request->user_id, fn($q, $userId) => $q->where('user_id', $userId));

        // Filter by permission: Only show submissions user can view
        // If user has grade permission OR view submissions permission → see all
        if (!$canGrade && !$canViewSubmissions) {
            // User không có quyền grade VÀ không có quyền view submissions → chỉ xem được:
            // 1. Submissions của chính họ
            // 2. Submissions có allow_special_view = true VÀ user có quyền special_view
            $query->where(function ($q) use ($userId, $hasSpecialView) {
                $q->where('user_id', $userId); // Own submissions
                
                if ($hasSpecialView) {
                    $q->orWhere(function ($sq) {
                        $sq->where('allow_special_view', true);
                    });
                }
            });
        }
        // If canGrade = true OR canViewSubmissions = true → xem được tất cả (không filter)

        $query->orderBy($request->sort_by ?? 'submitted_at', $request->sort_order ?? 'desc');

        $perPage = min($request->per_page ?? 20, 100);
        $submissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $submissions,
        ]);
    }

    /**
     * Get assignment statistics.
     */
    public function statistics(string $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $assignment->getStatistics(),
        ]);
    }
}
