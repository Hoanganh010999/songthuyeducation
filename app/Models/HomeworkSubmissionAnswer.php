<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkSubmissionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'exercise_id',
        'answer',
        'answer_text',
        'points_earned',
        'points_possible',
        'is_correct',
        'auto_feedback',
        'teacher_feedback',
        'time_spent',
        'attempt_count',
        'metadata',
        'annotations',
        'grading_notes',
    ];

    protected $casts = [
        'answer' => 'array',
        'points_earned' => 'decimal:2',
        'points_possible' => 'decimal:2',
        'is_correct' => 'boolean',
        'time_spent' => 'integer',
        'attempt_count' => 'integer',
        'metadata' => 'array',
        'annotations' => 'array',
    ];

    /**
     * Relationships
     */
    public function submission()
    {
        return $this->belongsTo(HomeworkSubmission::class, 'submission_id');
    }

    public function exercise()
    {
        return $this->belongsTo(HomeworkExercise::class, 'exercise_id');
    }

    /**
     * Scopes
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Check if answer is graded
     */
    public function isGraded()
    {
        return $this->points_earned !== null;
    }

    /**
     * Get score percentage
     */
    public function getScorePercentageAttribute()
    {
        if ($this->points_possible == 0) {
            return 0;
        }

        return round(($this->points_earned / $this->points_possible) * 100, 2);
    }
}
