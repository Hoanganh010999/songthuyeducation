<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrialStudent extends Model
{
    protected $fillable = [
        'trialable_type',
        'trialable_id',
        'class_id',
        'class_lesson_session_id',
        'registered_by',
        'registered_at',
        'status',
        'feedback',
        'feedback_by',
        'feedback_at',
        'rating',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'feedback_at' => 'datetime',
        'rating' => 'integer',
    ];

    /**
     * Polymorphic: Customer or CustomerChild
     */
    public function trialable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Class relationship
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Lesson session relationship
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'class_lesson_session_id');
    }

    /**
     * Registered by (User)
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Feedback by (User)
     */
    public function feedbackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'attended']);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('class_lesson_session_id', $sessionId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Get trial student name
     */
    public function getTrialStudentNameAttribute(): string
    {
        return $this->trialable?->name ?? 'Unknown';
    }

    /**
     * Get trial student type display
     */
    public function getTrialStudentTypeAttribute(): string
    {
        if ($this->trialable_type === 'App\Models\Customer') {
            return 'Khách hàng';
        }
        return 'Con của KH';
    }
}
