<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonPlanStage extends Model
{
    protected $fillable = [
        'lesson_plan_block_id',
        'stage_number',
        'stage_name',
        'stage_aim',
        'total_timing',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the block that owns this stage
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(LessonPlanBlock::class, 'lesson_plan_block_id');
    }

    /**
     * Get the main procedure for this stage (one-to-one)
     */
    public function procedure()
    {
        return $this->hasOne(LessonPlanProcedure::class)->orderBy('sort_order');
    }

    /**
     * Get all procedures for this stage
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(LessonPlanProcedure::class)->orderBy('sort_order');
    }

    /**
     * Get the first (main) procedure for this stage
     */
    public function mainProcedure()
    {
        return $this->procedures()->first();
    }
}
