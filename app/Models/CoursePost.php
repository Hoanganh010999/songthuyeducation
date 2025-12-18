<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CoursePost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class_id',
        'user_id',
        'post_type',
        'content',
        'is_pinned',
        'comments_enabled',
        'likes_count',
        'comments_count',
        'branch_id',
        // Event fields
        'event_start_date',
        'event_end_date',
        'event_location',
        'is_all_day',
        'event_attendees',
        // Homework/additional metadata
        'metadata',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'comments_enabled' => 'boolean',
        'is_all_day' => 'boolean',
        'event_start_date' => 'datetime',
        'event_end_date' => 'datetime',
        'event_attendees' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CoursePostComment::class, 'post_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(CoursePostMedia::class, 'post_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(CoursePostLike::class, 'likeable');
    }

    public function calendarEvent(): MorphOne
    {
        return $this->morphOne(CalendarEvent::class, 'eventable');
    }

    // Helper methods
    public function isEvent(): bool
    {
        return !is_null($this->event_start_date);
    }

    public function hasCalendarEvent(): bool
    {
        return $this->calendarEvent()->exists();
    }
    public function isLikedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function incrementLikesCount(): void
    {
        $this->increment('likes_count');
    }

    public function decrementLikesCount(): void
    {
        $this->decrement('likes_count');
    }

    public function incrementCommentsCount(): void
    {
        $this->increment('comments_count');
    }

    public function decrementCommentsCount(): void
    {
        $this->decrement('comments_count');
    }
}
