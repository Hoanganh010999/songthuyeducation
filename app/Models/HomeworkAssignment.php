<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'title',
        'description',
        'deadline',
        'hw_ids',
        'assigned_to',
        'status',
        'assigned_by',
        'branch_id',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'hw_ids' => 'array',
        'assigned_to' => 'array',
    ];

    /**
     * Relationships
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get homework templates from the bank
     */
    public function homeworkBank()
    {
        return HomeworkBank::whereIn('id', $this->hw_ids ?? [])->get();
    }

    /**
     * Get all exercises from assigned homework
     */
    public function exercises()
    {
        $homeworkBanks = $this->homeworkBank();
        $exercises = collect();

        foreach ($homeworkBanks as $homework) {
            $exercises = $exercises->merge($homework->exercises);
        }

        return $exercises;
    }

    public function calendarEvent()
    {
        return $this->morphOne(CalendarEvent::class, 'eventable');
    }

    /**
     * Check if assignment is for all students
     */
    public function isForAllStudents()
    {
        return empty($this->assigned_to);
    }

    /**
     * Check if user is assigned to this homework
     */
    public function isAssignedTo($userId)
    {
        if ($this->isForAllStudents()) {
            // Check if user is in class
            // Note: class_students.student_id stores user_id, not students.id
            return \App\Models\ClassStudent::where('class_id', $this->class_id)
                ->where('student_id', $userId)
                ->where('status', 'active')
                ->exists();
        }

        return in_array($userId, $this->assigned_to ?? []);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // All students (assigned_to is null)
            $q->whereNull('assigned_to')
              // Or specifically assigned to this user
              ->orWhereJsonContains('assigned_to', $userId);
        });
    }
    
    /**
     * Get submission status for a specific user
     */
    public function getSubmissionStatusFor($userId)
    {
        // Query by homework_assignment_id to uniquely identify this homework
        $submission = HomeworkSubmission::where('homework_assignment_id', $this->id)
            ->where('student_id', $userId)
            ->first();
            
        return $submission ? $submission->status : 'not_submitted';
    }
}

