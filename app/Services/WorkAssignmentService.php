<?php

namespace App\Services;

use App\Models\WorkItem;
use App\Models\WorkAssignment;
use App\Models\WorkActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkAssignmentService
{
    /**
     * Create assignments for work item
     */
    public function createAssignments(WorkItem $workItem, array $assignments, User $assigner): array
    {
        DB::beginTransaction();
        try {
            $newAssignments = [];

            foreach ($assignments as $assignment) {
                $newAssignment = WorkAssignment::create([
                    'work_item_id' => $workItem->id,
                    'user_id' => $assignment['user_id'] ?? null,
                    'department_id' => $assignment['department_id'] ?? null,
                    'role' => $assignment['role'],
                    'is_department_assignment' => isset($assignment['department_id']),
                    'assigned_by' => $assigner->id,
                    'status' => 'pending',
                ]);

                $newAssignments[] = $newAssignment;
            }

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'assigned',
                $assigner,
                null,
                ['assignments' => $newAssignments],
                ['count' => count($newAssignments)]
            );

            // Update work item status if it's pending
            if ($workItem->status === 'pending') {
                $workItem->update(['status' => 'assigned']);
            }

            DB::commit();

            return $newAssignments;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Accept assignment
     */
    public function acceptAssignment(WorkAssignment $assignment, User $user): WorkAssignment
    {
        if ($assignment->user_id !== $user->id) {
            throw new \Exception('User is not assigned to this work item');
        }

        if ($assignment->status !== 'pending') {
            throw new \Exception('Assignment is not pending');
        }

        DB::beginTransaction();
        try {
            $assignment->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $assignment->workItem,
                'assignment_accepted',
                $user,
                ['status' => 'pending'],
                ['status' => 'accepted'],
                ['role' => $assignment->role]
            );

            DB::commit();

            return $assignment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Decline assignment
     */
    public function declineAssignment(WorkAssignment $assignment, User $user, ?string $reason = null): WorkAssignment
    {
        if ($assignment->user_id !== $user->id) {
            throw new \Exception('User is not assigned to this work item');
        }

        if ($assignment->status !== 'pending') {
            throw new \Exception('Assignment is not pending');
        }

        DB::beginTransaction();
        try {
            $assignment->update([
                'status' => 'declined',
                'declined_at' => now(),
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $assignment->workItem,
                'assignment_declined',
                $user,
                ['status' => 'pending'],
                ['status' => 'declined'],
                [
                    'role' => $assignment->role,
                    'reason' => $reason
                ]
            );

            DB::commit();

            return $assignment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Request support
     */
    public function requestSupport(WorkItem $workItem, User $executor, User $supporter, ?string $message = null): WorkAssignment
    {
        // Check if executor is actually an executor
        $executorAssignment = $workItem->assignments()
            ->where('user_id', $executor->id)
            ->where('role', 'executor')
            ->first();

        if (!$executorAssignment) {
            throw new \Exception('User is not an executor for this work item');
        }

        DB::beginTransaction();
        try {
            // Create support assignment
            $supportAssignment = WorkAssignment::create([
                'work_item_id' => $workItem->id,
                'user_id' => $supporter->id,
                'role' => 'supporter',
                'assigned_by' => $executor->id,
                'status' => 'pending',
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'support_requested',
                $executor,
                null,
                ['supporter_id' => $supporter->id],
                ['message' => $message]
            );

            DB::commit();

            return $supportAssignment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove assignment
     */
    public function removeAssignment(WorkAssignment $assignment, User $user): void
    {
        DB::beginTransaction();
        try {
            // Log activity before deletion
            WorkActivityLog::logActivity(
                $assignment->workItem,
                'assignment_removed',
                $user,
                [
                    'user_id' => $assignment->user_id,
                    'role' => $assignment->role
                ],
                null
            );

            $assignment->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get user's assignments
     */
    public function getUserAssignments(User $user, ?string $role = null, ?string $status = null)
    {
        $query = WorkAssignment::with(['workItem', 'assignedBy'])
            ->where('user_id', $user->id);

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check if user has specific role in work item
     */
    public function hasRole(WorkItem $workItem, User $user, string $role): bool
    {
        return $workItem->assignments()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->where('status', 'accepted')
            ->exists();
    }
}
