<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'code', 'building', 'floor', 'capacity',
        'room_type', 'facilities', 'is_available', 'is_active',
        'sort_order', 'description'
    ];

    protected $casts = [
        'facilities' => 'array',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Check if room is available at specific time
    public function isAvailableAt($dayOfWeek, $studyPeriodId, $lessonNumber, $excludeScheduleId = null)
    {
        $query = $this->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('study_period_id', $studyPeriodId)
            ->where('lesson_number', $lessonNumber)
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return !$query->exists();
    }
}
