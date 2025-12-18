<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Services\CalendarEventService;

class ClassLessonSession extends Model
{
    protected $fillable = [
        'class_id', 'lesson_plan_id', 'lesson_plan_session_id',
        'class_schedule_id', 'teacher_id', 'session_number', 'scheduled_date', 'actual_date',
        'start_time', 'end_time', 'status', 'cancellation_reason',
        'rescheduled_to', 'notes',
        'lesson_title', 'lesson_objectives', 'lesson_content',
        'lesson_plan_url', 'materials_url', 'homework_url', 'duration_minutes',
        'valuation_form_id',
        'lesson_plans_folder_id', 'materials_folder_id', 'homework_folder_id'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    /**
     * Teacher assigned to this specific session (overrides class_schedule teacher)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the effective teacher for this session
     * Priority: session teacher > class_schedule teacher > homeroom teacher
     */
    public function getEffectiveTeacher()
    {
        if ($this->teacher_id && $this->teacher) {
            return $this->teacher;
        }

        if ($this->classSchedule && $this->classSchedule->teacher) {
            return $this->classSchedule->teacher;
        }

        if ($this->class && $this->class->homeroomTeacher) {
            return $this->class->homeroomTeacher;
        }

        return null;
    }

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    public function lessonPlanSession(): BelongsTo
    {
        return $this->belongsTo(LessonPlanSession::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function rescheduledSession(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'rescheduled_to');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'session_id');
    }

    public function sessionComments(): HasMany
    {
        return $this->hasMany(SessionComment::class, 'session_id');
    }

    public function valuationForm(): BelongsTo
    {
        return $this->belongsTo(ValuationForm::class);
    }

    /**
     * Calendar event relationship (polymorphic)
     */
    public function calendarEvent(): MorphOne
    {
        return $this->morphOne(CalendarEvent::class, 'eventable');
    }

    /**
     * Lifecycle hooks - Auto-sync với Calendar
     */
    protected static function booted()
    {
        // Sau khi tạo buổi học → tạo calendar event
        static::created(function ($session) {
            try {
                app(CalendarEventService::class)->syncClassSessionToCalendar($session);
            } catch (\Exception $e) {
                \Log::error('Failed to sync class session to calendar on create: ' . $e->getMessage());
            }
        });

        // Sau khi cập nhật → sync calendar event
        static::updated(function ($session) {
            try {
                app(CalendarEventService::class)->syncClassSessionToCalendar($session);
            } catch (\Exception $e) {
                \Log::error('Failed to sync class session to calendar on update: ' . $e->getMessage());
            }
        });

        // Sau khi xóa → xóa calendar event
        static::deleted(function ($session) {
            try {
                $session->calendarEvent?->delete();
            } catch (\Exception $e) {
                \Log::error('Failed to delete calendar event on session delete: ' . $e->getMessage());
            }
        });
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString());
    }

    /**
     * Trial students for this session
     */
    public function trialStudents()
    {
        return $this->hasMany(TrialStudent::class, 'class_lesson_session_id');
    }

    /**
     * Active trial students (registered or attended)
     */
    public function activeTrialStudents()
    {
        return $this->trialStudents()->active();
    }

    /**
     * Count active trial students
     */
    public function getTrialStudentsCountAttribute(): int
    {
        return $this->activeTrialStudents()->count();
    }
}
