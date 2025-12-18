<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'homework_assignment_id',
        'session_id',
        'student_id',
        'status',
        'submitted_at',
        'submission_link',
        'unit_folder_link', // ✅ Link to unit folder in Google Drive
        'grade',
        'teacher_feedback',
        'graded_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'grade' => 'integer',
    ];

    public function homeworkAssignment(): BelongsTo
    {
        return $this->belongsTo(HomeworkAssignment::class, 'homework_assignment_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers()
    {
        return $this->hasMany(HomeworkSubmissionAnswer::class, 'submission_id');
    }

    /**
     * Get total score for this submission
     */
    public function getTotalScoreAttribute()
    {
        return $this->answers()->sum('points_earned');
    }

    /**
     * Get total possible score
     */
    public function getTotalPossibleAttribute()
    {
        return $this->answers()->sum('points_possible');
    }

    /**
     * Get score percentage
     */
    public function getScorePercentageAttribute()
    {
        $total = $this->total_possible;
        if ($total == 0) {
            return 0;
        }

        return round(($this->total_score / $total) * 100, 2);
    }
}
