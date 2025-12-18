<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'academic_year',
        'capacity',
        'hourly_rate',
        'current_students',
        'homeroom_teacher_id',
        'zalo_account_id',
        'zalo_group_id',
        'zalo_group_name',
        'subject_id',
        'lesson_plan_id',
        'google_drive_folder_id',
        'google_drive_folder_name',
        'semester_id',
        'start_date',
        'end_date',
        'total_sessions',
        'completed_sessions',
        'room_number',
        'status',
        'sort_order',
        'is_active'
    ];

    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Homeroom teacher (Giáo viên chủ nhiệm)
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /**
     * Main subject of the class (Môn học chính của lớp)
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Subjects taught in this class
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
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
     * Get all teachers teaching this class
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_subject', 'class_id', 'teacher_id')
            ->withPivot(['subject_id', 'periods_per_week', 'status'])
            ->withTimestamps();
    }

    /**
     * Semester relationship
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Lesson plan relationship
     */
    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    /**
     * Class schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    /**
     * Lesson sessions
     */
    public function lessonSessions(): HasMany
    {
        return $this->hasMany(ClassLessonSession::class, 'class_id');
    }

    /**
     * Students in this class
     */
    public function students(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    /**
     * Active students
     */
    public function activeStudents(): HasMany
    {
        return $this->students()->where('status', 'active');
    }

    /**
     * Check if a user can view this class's schedule
     * Logic:
     * - Homeroom teacher can view full schedule
     * - Subject teachers can only view their own subject schedule
     * - Head teachers of subjects can view all schedules of their subject
     */
    public function canUserViewSchedule(User $user): bool
    {
        // If user is homeroom teacher
        if ($this->homeroom_teacher_id === $user->id) {
            return true;
        }

        // If user teaches any subject in this class
        $teachesInClass = $this->subjects()
            ->wherePivot('teacher_id', $user->id)
            ->exists();

        if ($teachesInClass) {
            return true;
        }

        // If user is head teacher of any subject in this class
        $subjectIds = $this->subjects()->pluck('subjects.id');
        $isHeadTeacher = Subject::whereIn('id', $subjectIds)
            ->whereHas('headTeacher', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();

        return $isHeadTeacher;
    }

    /**
     * Get subjects that a teacher can view schedule for in this class
     * - If homeroom teacher: all subjects
     * - If regular teacher: only their subjects
     * - If head teacher of a subject: all classes teaching that subject
     */
    public function getViewableSubjectsForTeacher(User $user)
    {
        // Homeroom teacher sees all
        if ($this->homeroom_teacher_id === $user->id) {
            return $this->subjects;
        }

        // Get subjects where user is the assigned teacher
        $teachingSubjects = $this->subjects()
            ->wherePivot('teacher_id', $user->id)
            ->get();

        // Get subjects where user is head teacher
        $headSubjects = $this->subjects()
            ->whereHas('headTeacher', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->get();

        return $teachingSubjects->merge($headSubjects)->unique('id');
    }
}
