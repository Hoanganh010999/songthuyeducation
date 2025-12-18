<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'student_id',
        'journal_date',
        'content',
        'status',
        'graded_at',
        'graded_by',
        'annotations',
        'ai_feedback',
        'score',
        'branch_id',
    ];

    protected $casts = [
        // Don't cast journal_date to avoid timezone conversion issues
        // Keep it as string so it returns "2025-12-13" not "2025-12-13T17:00:00.000000Z"
        'graded_at' => 'datetime',
        'annotations' => 'array',
        'score' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Scopes
     */
    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeUngraded($query)
    {
        return $query->whereIn('status', ['draft', 'submitted']);
    }

    /**
     * Check if journal can be edited
     */
    public function canEdit(): bool
    {
        return $this->status !== 'graded';
    }

    /**
     * Check if journal is graded
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Mark as graded
     */
    public function markAsGraded($graderId, $score, $annotations = null, $aiFeedback = null)
    {
        $this->update([
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => $graderId,
            'score' => $score,
            'annotations' => $annotations,
            'ai_feedback' => $aiFeedback,
        ]);
    }
}
