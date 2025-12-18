<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkExerciseOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'label',
        'content',
        'is_correct',
        'sort_order',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function exercise()
    {
        return $this->belongsTo(HomeworkExercise::class, 'exercise_id');
    }

    /**
     * Scopes
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
