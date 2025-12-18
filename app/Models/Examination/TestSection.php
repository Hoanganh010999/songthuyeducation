<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'title',
        'description',
        'instructions',
        'skill',
        'time_limit',
        'total_points',
        'sort_order',
        'audio_track_id',
        'passage_id',
        'media',
        'settings',
    ];

    protected $casts = [
        'media' => 'array',
        'settings' => 'array',
        'time_limit' => 'integer',
        'total_points' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Skills
    const SKILL_LISTENING = 'listening';
    const SKILL_READING = 'reading';
    const SKILL_WRITING = 'writing';
    const SKILL_SPEAKING = 'speaking';
    const SKILL_USE_OF_ENGLISH = 'use_of_english';
    const SKILL_GENERAL = 'general';

    // Relationships
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class, 'section_id')->orderBy('sort_order');
    }

    public function audioTrack(): BelongsTo
    {
        return $this->belongsTo(AudioTrack::class, 'audio_track_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    // Helpers
    public function getQuestionCount(): int
    {
        return $this->testQuestions()->count();
    }

    public function calculateTotalPoints(): float
    {
        return $this->testQuestions->sum(function ($tq) {
            return $tq->points ?? $tq->question->points ?? 1;
        });
    }

    public static function getSkills(): array
    {
        return [
            self::SKILL_LISTENING => 'Listening',
            self::SKILL_READING => 'Reading',
            self::SKILL_WRITING => 'Writing',
            self::SKILL_SPEAKING => 'Speaking',
            self::SKILL_USE_OF_ENGLISH => 'Use of English',
            self::SKILL_GENERAL => 'General',
        ];
    }
}
