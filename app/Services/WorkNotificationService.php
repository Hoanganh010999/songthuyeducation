<?php

namespace App\Services;

use App\Models\WorkItem;
use App\Models\WorkAssignment;
use App\Models\WorkSubmission;
use App\Models\User;
use App\Models\Notification;

class WorkNotificationService
{
    /**
     * Notify user about new assignment
     */
    public function notifyAssignment(WorkAssignment $assignment): void
    {
        if (!$assignment->user_id) {
            return; // Department assignment, skip individual notification
        }

        $workItem = $assignment->workItem;

        Notification::create([
            'user_id' => $assignment->user_id,
            'type' => 'work_assigned',
            'title' => 'Công việc mới được giao',
            'message' => "Bạn được giao vai trò {$this->getRoleLabel($assignment->role)} trong công việc: {$workItem->title}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'role' => $assignment->role,
                'assigned_by' => $assignment->assignedBy?->name,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify assigner about assignment acceptance
     */
    public function notifyAssignmentAccepted(WorkAssignment $assignment): void
    {
        if (!$assignment->assigned_by) {
            return;
        }

        $workItem = $assignment->workItem;

        Notification::create([
            'user_id' => $assignment->assigned_by,
            'type' => 'assignment_accepted',
            'title' => 'Công việc được chấp nhận',
            'message' => "{$assignment->user->name} đã chấp nhận vai trò {$this->getRoleLabel($assignment->role)} trong công việc: {$workItem->title}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'role' => $assignment->role,
                'user_name' => $assignment->user->name,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify assigner about assignment decline
     */
    public function notifyAssignmentDeclined(WorkAssignment $assignment, ?string $reason = null): void
    {
        if (!$assignment->assigned_by) {
            return;
        }

        $workItem = $assignment->workItem;

        Notification::create([
            'user_id' => $assignment->assigned_by,
            'type' => 'assignment_declined',
            'title' => 'Công việc bị từ chối',
            'message' => "{$assignment->user->name} đã từ chối vai trò {$this->getRoleLabel($assignment->role)} trong công việc: {$workItem->title}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'role' => $assignment->role,
                'user_name' => $assignment->user->name,
                'reason' => $reason,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify support request
     */
    public function notifySupportRequest(WorkItem $workItem, User $supporter, User $executor, ?string $message = null): void
    {
        Notification::create([
            'user_id' => $supporter->id,
            'type' => 'support_requested',
            'title' => 'Yêu cầu hỗ trợ công việc',
            'message' => "{$executor->name} yêu cầu bạn hỗ trợ công việc: {$workItem->title}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'executor_name' => $executor->name,
                'message' => $message,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify assigners about work submission
     */
    public function notifyWorkSubmitted(WorkSubmission $submission): void
    {
        $workItem = $submission->workItem;

        // Get all assigners
        $assigners = $workItem->assignments()
            ->where('role', 'assigner')
            ->with('user')
            ->get();

        foreach ($assigners as $assignment) {
            if (!$assignment->user_id) {
                continue;
            }

            Notification::create([
                'user_id' => $assignment->user_id,
                'type' => 'work_submitted',
                'title' => 'Công việc đã nộp',
                'message' => "{$submission->submitter->name} đã nộp sản phẩm cho công việc: {$workItem->title}",
                'data' => [
                    'work_item_id' => $workItem->id,
                    'work_item_code' => $workItem->code,
                    'submission_id' => $submission->id,
                    'submission_number' => $submission->submission_number,
                    'submitter_name' => $submission->submitter->name,
                ],
                'read_at' => null,
            ]);
        }
    }

    /**
     * Notify executor about review
     */
    public function notifyWorkReviewed(WorkSubmission $submission): void
    {
        Notification::create([
            'user_id' => $submission->submitted_by,
            'type' => 'work_reviewed',
            'title' => 'Sản phẩm đã được đánh giá',
            'message' => "Sản phẩm của bạn cho công việc {$submission->workItem->title} đã được đánh giá. Điểm chất lượng: {$submission->quality_rating}/5",
            'data' => [
                'work_item_id' => $submission->workItem->id,
                'work_item_code' => $submission->workItem->code,
                'submission_id' => $submission->id,
                'quality_rating' => $submission->quality_rating,
                'reviewer_name' => $submission->reviewer?->name,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify about work approval
     */
    public function notifyWorkApproved(WorkSubmission $submission): void
    {
        // Notify executor
        Notification::create([
            'user_id' => $submission->submitted_by,
            'type' => 'work_approved',
            'title' => 'Sản phẩm được phê duyệt',
            'message' => "Chúc mừng! Sản phẩm của bạn cho công việc {$submission->workItem->title} đã được phê duyệt.",
            'data' => [
                'work_item_id' => $submission->workItem->id,
                'work_item_code' => $submission->workItem->code,
                'submission_id' => $submission->id,
                'approver_name' => $submission->approver?->name,
            ],
            'read_at' => null,
        ]);

        // Notify all participants
        $participants = $submission->workItem->assignments()
            ->whereIn('role', ['assigner', 'observer'])
            ->with('user')
            ->get();

        foreach ($participants as $assignment) {
            if (!$assignment->user_id || $assignment->user_id == $submission->submitted_by) {
                continue;
            }

            Notification::create([
                'user_id' => $assignment->user_id,
                'type' => 'work_completed',
                'title' => 'Công việc hoàn thành',
                'message' => "Công việc {$submission->workItem->title} đã được hoàn thành và phê duyệt.",
                'data' => [
                    'work_item_id' => $submission->workItem->id,
                    'work_item_code' => $submission->workItem->code,
                    'executor_name' => $submission->submitter->name,
                ],
                'read_at' => null,
            ]);
        }
    }

    /**
     * Notify about revision request
     */
    public function notifyRevisionRequested(WorkSubmission $submission): void
    {
        Notification::create([
            'user_id' => $submission->submitted_by,
            'type' => 'revision_requested',
            'title' => 'Yêu cầu chỉnh sửa',
            'message' => "Sản phẩm của bạn cho công việc {$submission->workItem->title} cần chỉnh sửa.",
            'data' => [
                'work_item_id' => $submission->workItem->id,
                'work_item_code' => $submission->workItem->code,
                'submission_id' => $submission->id,
                'review_notes' => $submission->review_notes,
                'reviewer_name' => $submission->reviewer?->name,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify about upcoming deadline
     */
    public function notifyUpcomingDeadline(WorkItem $workItem, User $user): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'deadline_reminder',
            'title' => 'Sắp đến hạn',
            'message' => "Công việc {$workItem->title} sắp đến hạn vào {$workItem->due_date}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'due_date' => $workItem->due_date,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify about overdue work
     */
    public function notifyOverdue(WorkItem $workItem, User $user): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'work_overdue',
            'title' => 'Công việc quá hạn',
            'message' => "Công việc {$workItem->title} đã quá hạn từ {$workItem->due_date}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'due_date' => $workItem->due_date,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Notify about mention in discussion
     */
    public function notifyMention(WorkItem $workItem, User $mentionedUser, User $author, string $content): void
    {
        Notification::create([
            'user_id' => $mentionedUser->id,
            'type' => 'mentioned_in_discussion',
            'title' => 'Bạn được nhắc đến',
            'message' => "{$author->name} đã nhắc đến bạn trong thảo luận về công việc: {$workItem->title}",
            'data' => [
                'work_item_id' => $workItem->id,
                'work_item_code' => $workItem->code,
                'author_name' => $author->name,
                'content_preview' => substr($content, 0, 100),
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Get role label in Vietnamese
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'executor' => 'người thực hiện',
            'assigner' => 'người giao việc',
            'observer' => 'người quan sát',
            'supporter' => 'người hỗ trợ',
            default => $role,
        };
    }
}
