<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the branch that owns the subject
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all teachers assigned to this subject
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_teacher')
            ->withPivot('is_head', 'start_date', 'end_date', 'status')
            ->withTimestamps()
            ->orderByPivot('is_head', 'desc'); // Trưởng bộ môn lên đầu
    }

    /**
     * Get the head teacher (Trưởng bộ môn) of this subject
     */
    public function headTeacher()
    {
        return $this->teachers()->wherePivot('is_head', true)->first();
    }

    /**
     * Get active teachers only
     */
    public function activeTeachers(): BelongsToMany
    {
        return $this->teachers()->wherePivot('status', 'active');
    }

    /**
     * Scope to get only active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get all classes teaching this subject
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_subject')
            ->withPivot([
                'teacher_id',
                'periods_per_week',
                'room_number',
                'notes',
                'status'
            ])
            ->withTimestamps();
    }

    /**
     * Check if a user is the head teacher of this subject
     */
    public function isHeadTeacher(User $user): bool
    {
        $headTeacher = $this->headTeacher();
        return $headTeacher && $headTeacher->id === $user->id;
    }

    /**
     * Check if a user is a teacher of this subject (including head)
     */
    public function isTeacher(User $user): bool
    {
        return $this->teachers()->where('users.id', $user->id)->exists();
    }

    /**
     * Get all classes where this subject is taught by a specific teacher
     */
    public function getClassesForTeacher(User $user)
    {
        return $this->classes()
            ->wherePivot('teacher_id', $user->id)
            ->get();
    }

    /**
     * Get all schedules/classes this subject is taught in
     * For head teacher: all classes
     * For regular teacher: only their assigned classes
     */
    public function getViewableClassesForTeacher(User $user)
    {
        if ($this->isHeadTeacher($user)) {
            // Head teacher can view all classes teaching this subject
            return $this->classes()->get();
        }

        // Regular teacher can only view their assigned classes
        return $this->getClassesForTeacher($user);
    }
}
