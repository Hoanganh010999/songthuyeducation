<?php

namespace App\Models;

use App\Models\Examination\Test;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Note: User and Branch are in the same namespace (App\Models), so they don't need explicit imports

class PracticeTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'reading_test_id',
        'writing_test_id',
        'listening_test_id',
        'speaking_test_id',
        'difficulty',
        'is_active',
        'duration',
        'order',
        'created_by',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the reading test
     */
    public function readingTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'reading_test_id');
    }

    /**
     * Get the writing test
     */
    public function writingTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'writing_test_id');
    }

    /**
     * Get the listening test
     */
    public function listeningTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'listening_test_id');
    }

    /**
     * Get the speaking test
     */
    public function speakingTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'speaking_test_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to only active tests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by difficulty
     */
    public function scopeDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Check if practice test is complete (has all 4 tests)
     */
    public function isComplete(): bool
    {
        return $this->reading_test_id
            && $this->writing_test_id
            && $this->listening_test_id
            && $this->speaking_test_id;
    }
}
