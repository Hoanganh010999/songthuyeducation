<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSubmission extends Model
{
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_REVISION_REQUESTED = 'revision_requested';
    const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'work_item_id', 'submitted_by', 'submission_number',
        'description', 'status',
        'reviewed_by', 'reviewed_at', 'review_notes', 'quality_rating',
        'evaluation_data'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'evaluation_data' => 'array',
    ];

    /**
     * Relationships
     */
    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attachments()
    {
        return $this->morphMany(WorkAttachment::class, 'attachable');
    }

    /**
     * Scopes
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Helpers
     */
    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function needsRevision(): bool
    {
        return $this->status === self::STATUS_REVISION_REQUESTED;
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING_REVIEW => 'Chờ đánh giá',
            self::STATUS_REVISION_REQUESTED => 'Yêu cầu chỉnh sửa',
            self::STATUS_APPROVED => 'Đã phê duyệt',
        ];
    }
}
