<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkBank extends Model
{
    protected $table = 'homework_bank';

    protected $fillable = [
        'lesson_plan_session_id',
        'subject_id',
        'grade_level',
        'created_by',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lesson plan session that owns this homework template
     */
    public function lessonPlanSession()
    {
        return $this->belongsTo(LessonPlanSession::class);
    }

    /**
     * Get the subject that this homework belongs to
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the user who created this homework template
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the exercises associated with this homework template
     */
    public function exercises()
    {
        return $this->belongsToMany(HomeworkExercise::class, 'homework_bank_exercises', 'homework_bank_id', 'exercise_id')
            ->withPivot(['points', 'sort_order', 'is_required', 'section'])
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by subject
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope to filter by grade level
     */
    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }
}
