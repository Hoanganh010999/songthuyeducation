<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkSubmission;
use App\Models\WorkItem;
use App\Models\WorkActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkSubmissionController extends Controller
{
    /**
     * Submit work product
     */
    public function submit(Request $request, $workItemId)
    {
        $workItem = WorkItem::findOrFail($workItemId);
        $user = auth()->user();

        // Check if user is an executor
        $executorAssignment = $workItem->assignments()
            ->where('user_id', $user->id)
            ->where('role', 'executor')
            ->first();

        if (!$executorAssignment) {
            return response()->json(['error' => 'Only executors can submit work'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachment_ids' => 'nullable|array',
            'attachment_ids.*' => 'exists:work_attachments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Get submission number
            $submissionNumber = $workItem->submissions()->count() + 1;

            // Calculate actual hours if not already set
            if (!$workItem->actual_hours && $workItem->start_date) {
                $startTime = \Carbon\Carbon::parse($workItem->start_date);
                $endTime = now();
                $actualHours = $startTime->diffInHours($endTime);
                $workItem->update(['actual_hours' => $actualHours]);
            }

            // Create submission
            $submission = WorkSubmission::create([
                'work_item_id' => $workItemId,
                'submitted_by' => $user->id,
                'submission_number' => $submissionNumber,
                'description' => $request->description,
                'notes' => $request->notes,
                'status' => 'pending_review',
            ]);

            // Update work item status
            $workItem->update(['status' => 'submitted']);

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'work_submitted',
                $user,
                ['status' => $workItem->status],
                ['status' => 'submitted'],
                [
                    'submission_id' => $submission->id,
                    'submission_number' => $submissionNumber
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Work submitted successfully',
                'data' => $submission->load('submitter')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit work: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Review a submission
     */
    public function review(Request $request, $submissionId)
    {
        $submission = WorkSubmission::with('workItem')->findOrFail($submissionId);
        $user = auth()->user();

        // Check if user is an assigner or has review permission
        $isAssigner = $submission->workItem->assignments()
            ->where('user_id', $user->id)
            ->where('role', 'assigner')
            ->exists();

        if (!$isAssigner && !$user->can('work_items.review')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($submission->status !== 'pending_review') {
            return response()->json(['error' => 'Submission is not pending review'], 422);
        }

        $validator = Validator::make($request->all(), [
            'quality_rating' => 'required|integer|min:1|max:5',
            'review_notes' => 'nullable|string',
            'evaluation_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $submission->update([
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'quality_rating' => $request->quality_rating,
                'review_notes' => $request->review_notes,
                'evaluation_data' => $request->evaluation_data,
                'status' => 'reviewed',
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $submission->workItem,
                'work_reviewed',
                $user,
                ['status' => 'pending_review'],
                ['status' => 'reviewed'],
                [
                    'submission_id' => $submission->id,
                    'quality_rating' => $request->quality_rating
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Submission reviewed successfully',
                'data' => $submission->load(['submitter', 'reviewer'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to review submission: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve a submission
     */
    public function approve(Request $request, $submissionId)
    {
        $submission = WorkSubmission::with('workItem')->findOrFail($submissionId);
        $user = auth()->user();

        // Check if user has approve permission
        if (!$user->can('work_items.approve')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($submission->status !== 'reviewed') {
            return response()->json(['error' => 'Submission must be reviewed first'], 422);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $submission->update([
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
                'status' => 'approved',
            ]);

            // Update work item status to completed
            $submission->workItem->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate completion rate
            $completionRate = $submission->workItem->calculateCompletionRate();

            // Log activity
            WorkActivityLog::logActivity(
                $submission->workItem,
                'work_approved',
                $user,
                ['status' => 'submitted'],
                ['status' => 'completed'],
                [
                    'submission_id' => $submission->id,
                    'completion_rate' => $completionRate
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Submission approved successfully',
                'data' => $submission->load(['submitter', 'reviewer', 'approver'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to approve submission: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Request revision for a submission
     */
    public function requestRevision(Request $request, $submissionId)
    {
        $submission = WorkSubmission::with('workItem')->findOrFail($submissionId);
        $user = auth()->user();

        // Check if user is an assigner or has review permission
        $isAssigner = $submission->workItem->assignments()
            ->where('user_id', $user->id)
            ->where('role', 'assigner')
            ->exists();

        if (!$isAssigner && !$user->can('work_items.review')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'revision_notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $submission->update([
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_notes' => $request->revision_notes,
                'status' => 'revision_requested',
            ]);

            // Update work item status
            $submission->workItem->update(['status' => 'revision_requested']);

            // Log activity
            WorkActivityLog::logActivity(
                $submission->workItem,
                'revision_requested',
                $user,
                ['status' => 'submitted'],
                ['status' => 'revision_requested'],
                [
                    'submission_id' => $submission->id,
                    'revision_notes' => $request->revision_notes
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Revision requested successfully',
                'data' => $submission->load(['submitter', 'reviewer'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to request revision: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get submissions for a work item
     */
    public function index($workItemId)
    {
        $workItem = WorkItem::findOrFail($workItemId);

        $submissions = $workItem->submissions()
            ->with(['submitter', 'reviewer', 'approver'])
            ->orderBy('submission_number', 'desc')
            ->get();

        return response()->json($submissions);
    }
}
