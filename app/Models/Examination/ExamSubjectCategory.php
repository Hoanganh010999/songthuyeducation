<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubjectCategory extends Model
{
    protected $fillable = [
        'subject_id',
        'name',
        'code',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The subject this category belongs to
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class, 'subject_id');
    }

    /**
     * Questions in this category
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subject_category_id');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get full name with subject
     */
    public function getFullNameAttribute(): string
    {
        return $this->subject ? "{$this->subject->name} - {$this->name}" : $this->name;
    }
}
