<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkAssignment;
use App\Models\WorkActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkAssignmentController extends Controller
{
    /**
     * Accept an assignment
     */
    public function accept($id)
    {
        $assignment = WorkAssignment::with('workItem')->findOrFail($id);
        $user = auth()->user();

        // Check if user is the assigned person
        if ($assignment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($assignment->status !== 'pending') {
            return response()->json(['error' => 'Assignment is not pending'], 422);
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

            return response()->json([
                'message' => 'Assignment accepted successfully',
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to accept assignment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Decline an assignment
     */
    public function decline(Request $request, $id)
    {
        $assignment = WorkAssignment::with('workItem')->findOrFail($id);
        $user = auth()->user();

        // Check if user is the assigned person
        if ($assignment->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($assignment->status !== 'pending') {
            return response()->json(['error' => 'Assignment is not pending'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
                    'reason' => $request->reason
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Assignment declined successfully',
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to decline assignment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Request support for work item
     */
    public function requestSupport(Request $request, $workItemId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        // Check if user is an executor
        $executorAssignment = WorkAssignment::where('work_item_id', $workItemId)
            ->where('user_id', $user->id)
            ->where('role', 'executor')
            ->first();

        if (!$executorAssignment) {
            return response()->json(['error' => 'Only executors can request support'], 403);
        }

        DB::beginTransaction();
        try {
            // Create support assignment
            $supportAssignment = WorkAssignment::create([
                'work_item_id' => $workItemId,
                'user_id' => $request->user_id,
                'role' => 'supporter',
                'assigned_by' => $user->id,
                'status' => 'pending',
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $executorAssignment->workItem,
                'support_requested',
                $user,
                null,
                ['supporter_id' => $request->user_id],
                ['message' => $request->message]
            );

            DB::commit();

            return response()->json([
                'message' => 'Support request sent successfully',
                'data' => $supportAssignment->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to request support: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove an assignment
     */
    public function destroy($id)
    {
        $assignment = WorkAssignment::with('workItem')->findOrFail($id);
        $user = auth()->user();

        // Only assigner or admin can remove assignments
        if (!$user->can('work_items.assign')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

            return response()->json(['message' => 'Assignment removed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to remove assignment: ' . $e->getMessage()], 500);
        }
    }
}
