<?php

namespace App\Services;

use App\Models\WorkItem;
use App\Models\WorkSubmission;
use App\Models\WorkActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkSubmissionService
{
    /**
     * Submit work
     */
    public function submit(
        WorkItem $workItem,
        User $executor,
        ?string $description = null,
        ?string $notes = null,
        array $attachmentIds = []
    ): WorkSubmission {
        // Check if user is an executor
        $executorAssignment = $workItem->assignments()
            ->where('user_id', $executor->id)
            ->where('role', 'executor')
            ->first();

        if (!$executorAssignment) {
            throw new \Exception('Only executors can submit work');
        }

        DB::beginTransaction();
        try {
            // Get submission number
            $submissionNumber = $workItem->submissions()->count() + 1;

            // Calculate actual hours if not already set
            if (!$workItem->actual_hours && $workItem->start_date) {
                $startTime = Carbon::parse($workItem->start_date);
                $endTime = now();
                $actualHours = $startTime->diffInHours($endTime);
                $workItem->update(['actual_hours' => $actualHours]);
            }

            // Create submission
            $submission = WorkSubmission::create([
                'work_item_id' => $workItem->id,
                'submitted_by' => $executor->id,
                'submission_number' => $submissionNumber,
                'description' => $description,
                'notes' => $notes,
                'status' => 'pending_review',
            ]);

            // Update work item status
            $workItem->update(['status' => 'submitted']);

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                'work_submitted',
                $executor,
                ['status' => $workItem->status],
                ['status' => 'submitted'],
                [
                    'submission_id' => $submission->id,
                    'submission_number' => $submissionNumber
                ]
            );

            DB::commit();

            return $submission->load('submitter');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Review submission
     */
    public function review(
        WorkSubmission $submission,
        User $reviewer,
        int $qualityRating,
        ?string $reviewNotes = null,
        ?array $evaluationData = null
    ): WorkSubmission {
        if ($submission->status !== 'pending_review') {
            throw new \Exception('Submission is not pending review');
        }

        DB::beginTransaction();
        try {
            $submission->update([
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'quality_rating' => $qualityRating,
                'review_notes' => $reviewNotes,
                'evaluation_data' => $evaluationData,
                'status' => 'reviewed',
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $submission->workItem,
                'work_reviewed',
                $reviewer,
                ['status' => 'pending_review'],
                ['status' => 'reviewed'],
                [
                    'submission_id' => $submission->id,
                    'quality_rating' => $qualityRating
                ]
            );

            DB::commit();

            return $submission->load(['submitter', 'reviewer']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve submission
     */
    public function approve(
        WorkSubmission $submission,
        User $approver,
        ?string $approvalNotes = null
    ): WorkSubmission {
        if ($submission->status !== 'reviewed') {
            throw new \Exception('Submission must be reviewed first');
        }

        DB::beginTransaction();
        try {
            $submission->update([
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $approvalNotes,
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
                $approver,
                ['status' => 'submitted'],
                ['status' => 'completed'],
                [
                    'submission_id' => $submission->id,
                    'completion_rate' => $completionRate
                ]
            );

            DB::commit();

            return $submission->load(['submitter', 'reviewer', 'approver']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Request revision
     */
    public function requestRevision(
        WorkSubmission $submission,
        User $reviewer,
        string $revisionNotes
    ): WorkSubmission {
        DB::beginTransaction();
        try {
            $submission->update([
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $revisionNotes,
                'status' => 'revision_requested',
            ]);

            // Update work item status
            $submission->workItem->update(['status' => 'revision_requested']);

            // Log activity
            WorkActivityLog::logActivity(
                $submission->workItem,
                'revision_requested',
                $reviewer,
                ['status' => 'submitted'],
                ['status' => 'revision_requested'],
                [
                    'submission_id' => $submission->id,
                    'revision_notes' => $revisionNotes
                ]
            );

            DB::commit();

            return $submission->load(['submitter', 'reviewer']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate quality metrics for a work item
     */
    public function calculateQualityMetrics(WorkItem $workItem): array
    {
        $submissions = $workItem->submissions;

        if ($submissions->count() === 0) {
            return [
                'total_submissions' => 0,
                'avg_quality_rating' => 0,
                'first_time_approval' => false,
            ];
        }

        // Average quality rating
        $avgRating = $submissions->whereNotNull('quality_rating')->avg('quality_rating');

        // Check if approved on first submission
        $firstTimeApproval = $submissions->count() === 1 &&
                            $submissions->first()->status === 'approved';

        return [
            'total_submissions' => $submissions->count(),
            'avg_quality_rating' => round($avgRating ?? 0, 2),
            'first_time_approval' => $firstTimeApproval,
            'revision_count' => $submissions->where('status', 'revision_requested')->count(),
        ];
    }

    /**
     * Get pending submissions for reviewer
     */
    public function getPendingSubmissions(User $reviewer)
    {
        return WorkSubmission::with(['workItem', 'submitter'])
            ->where('status', 'pending_review')
            ->whereHas('workItem.assignments', function ($q) use ($reviewer) {
                $q->where('user_id', $reviewer->id)
                  ->where('role', 'assigner');
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
