<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonPlanSession extends Model
{
    protected $fillable = [
        'lesson_plan_id', 'session_number', 'lesson_title',
        'lesson_objectives', 'lesson_content', 'lesson_plan_url',
        'materials_url', 'homework_url', 'valuation_form_id', 'additional_resources',
        'duration_minutes', 'sort_order', 'notes',
        'google_drive_folder_id', 'materials_folder_id', 'homework_folder_id', 'lesson_plans_folder_id',
        // TECP Lesson Plan fields
        'teacher_name', 'lesson_focus', 'level', 'lesson_date', 'tp_number',
        'communicative_outcome', 'linguistic_aim', 'productive_subskills_focus', 'receptive_subskills_focus',
        'teaching_aspects_to_improve', 'improvement_methods',
        'framework_shape',
        'language_area', 'examples_of_language', 'context', 'concept_checking_methods', 'concept_checking_in_lesson'
    ];

    protected $casts = [
        'additional_resources' => 'array',
    ];

    protected $appends = [
        'materials_count',
        'homework_count',
    ];

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    public function valuationForm(): BelongsTo
    {
        return $this->belongsTo(ValuationForm::class);
    }

    /**
     * Get all lesson plan blocks for this session
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(LessonPlanBlock::class)->orderBy('sort_order');
    }

    public function scopeForLessonPlan($query, $lessonPlanId)
    {
        return $query->where('lesson_plan_id', $lessonPlanId)->orderBy('session_number');
    }

    /**
     * Get materials count from Google Drive
     */
    public function getMaterialsCountAttribute()
    {
        if (!$this->materials_folder_id) {
            return 0;
        }

        return GoogleDriveItem::where('parent_id', $this->materials_folder_id)
            ->where('type', 'file')
            ->where('is_trashed', false)
            ->count();
    }

    /**
     * Get homework count from Google Drive
     */
    public function getHomeworkCountAttribute()
    {
        if (!$this->homework_folder_id) {
            return 0;
        }

        return GoogleDriveItem::where('parent_id', $this->homework_folder_id)
            ->where('type', 'file')
            ->where('is_trashed', false)
            ->count();
    }
}
