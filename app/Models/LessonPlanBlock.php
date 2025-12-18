<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonPlanBlock extends Model
{
    protected $fillable = [
        'lesson_plan_session_id',
        'block_number',
        'block_title',
        'block_description',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the session that owns this block
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonPlanSession::class, 'lesson_plan_session_id');
    }

    /**
     * Get all stages for this block
     */
    public function stages(): HasMany
    {
        return $this->hasMany(LessonPlanStage::class)->orderBy('sort_order');
    }

    /**
     * Get the total duration of all stages in this block
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->stages->sum('total_timing');
    }
}
