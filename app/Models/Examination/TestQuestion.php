<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'section_id',
        'question_id',
        'sort_order',
        'points',
        'is_required',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'points' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TestSection::class, 'section_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // Helpers
    public function getEffectivePoints(): float
    {
        return $this->points ?? $this->question->points ?? 1;
    }
}
