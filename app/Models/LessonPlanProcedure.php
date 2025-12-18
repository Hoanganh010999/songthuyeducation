<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlanProcedure extends Model
{
    protected $fillable = [
        'lesson_plan_stage_id',
        'stage_content',
        // Instructions section
        'instructions',
        'icqs',
        'instruction_timing',
        'instruction_interaction',
        // Task section
        'task_completion',
        'monitoring_points',
        'task_timing',
        'task_interaction',
        // Feedback section
        'feedback',
        'feedback_timing',
        'feedback_interaction',
        // Problems and Solutions
        'learner_problems',
        'task_problems',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'learner_problems' => 'array',
        'task_problems' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the stage that owns this procedure
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(LessonPlanStage::class, 'lesson_plan_stage_id');
    }

    /**
     * Get total timing for this procedure
     */
    public function getTotalTimingAttribute(): int
    {
        return ($this->instruction_timing ?? 0) +
               ($this->task_timing ?? 0) +
               ($this->feedback_timing ?? 0);
    }

    /**
     * Interaction pattern options
     */
    public static function getInteractionPatterns(): array
    {
        return [
            'T-Ss' => 'Teacher to Students',
            'Ss-T' => 'Students to Teacher',
            'SS-Ss' => 'Student to Students (pair/group work)',
            'Ss-text' => 'Students to Text/Material',
            'T-S' => 'Teacher to Individual Student',
        ];
    }
}
