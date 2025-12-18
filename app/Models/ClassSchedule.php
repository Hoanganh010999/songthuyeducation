<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    protected $fillable = [
        'class_id', 'subject_id', 'teacher_id', 'room_id', 'study_period_id',
        'day_of_week', 'lesson_number', 'start_time', 'end_time',
        'effective_from', 'effective_to', 'is_active', 'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function studyPeriod(): BelongsTo
    {
        return $this->belongsTo(StudyPeriod::class);
    }

    // Check if teacher has conflicting schedule
    public static function hasTeacherConflict($teacherId, $dayOfWeek, $studyPeriodId, $lessonNumber, $excludeId = null)
    {
        $query = self::where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('study_period_id', $studyPeriodId)
            ->where('lesson_number', $lessonNumber)
            ->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Get teacher's full schedule
    public static function getTeacherSchedule($teacherId, $dayOfWeek = null)
    {
        $query = self::where('teacher_id', $teacherId)->where('is_active', true);

        if ($dayOfWeek) {
            $query->where('day_of_week', $dayOfWeek);
        }

        return $query->with(['class', 'subject', 'room', 'studyPeriod'])
            ->orderBy('day_of_week')
            ->orderBy('study_period_id')
            ->orderBy('lesson_number')
            ->get();
    }
}
