<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HomeworkExercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subject_id',
        'subject_category_id',
        'skill',
        'difficulty',
        'type',
        'title',
        'content',
        'explanation',
        'correct_answer',
        'instructions',
        'hints',
        'solution',
        'answer_key',
        'points',
        'time_limit',
        'branch_id',
        'created_by',
        'tags',
        'metadata',
        'settings',
        'status',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'content' => 'array',
        'correct_answer' => 'array',
        'answer_key' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'points' => 'decimal:2',
        'time_limit' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject()
    {
        return $this->belongsTo(\App\Models\Examination\ExamSubject::class, 'subject_id');
    }

    public function subjectCategory()
    {
        return $this->belongsTo(\App\Models\Examination\ExamSubjectCategory::class, 'subject_category_id');
    }

    public function options()
    {
        return $this->hasMany(HomeworkExerciseOption::class, 'exercise_id');
    }

    public function assignments()
    {
        return $this->belongsToMany(HomeworkAssignment::class, 'homework_assignment_exercises', 'exercise_id', 'assignment_id')
            ->withPivot('sort_order', 'points', 'section', 'is_required')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function submissionAnswers()
    {
        return $this->hasMany(HomeworkSubmissionAnswer::class, 'exercise_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeBySkill($query, $skill)
    {
        return $query->where('skill', $skill);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeBySubjectCategory($query, $categoryId)
    {
        return $query->where('subject_category_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('instructions', 'like', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getCorrectAnswersAttribute()
    {
        if ($this->type === 'multiple_choice') {
            return $this->options()->where('is_correct', true)->pluck('label')->toArray();
        }

        return $this->answer_key;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    /**
     * Check if exercise has options (for multiple choice, matching, etc.)
     */
    public function hasOptions()
    {
        return in_array($this->type, ['multiple_choice', 'true_false', 'matching', 'ordering']);
    }

    /**
     * Get formatted difficulty for display
     */
    public function getDifficultyLabelAttribute()
    {
        return ucfirst($this->difficulty);
    }

    /**
     * Get formatted type for display
     */
    public function getTypeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }
}
