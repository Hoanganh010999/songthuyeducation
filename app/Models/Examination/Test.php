<?php

namespace App\Models\Examination;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'instructions',
        'type',
        'subtype',
        'time_limit',
        'passing_score',
        'total_points',
        'max_attempts',
        'shuffle_questions',
        'shuffle_options',
        'show_results',
        'show_answers',
        'show_explanation',
        'allow_review',
        'require_camera',
        'prevent_copy',
        'prevent_tab_switch',
        'available_from',
        'available_until',
        'thumbnail_url',
        'cover_image_url',
        'tags',
        'settings',
        'branch_id',
        'created_by',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
        'settings' => 'array',
        'time_limit' => 'integer',
        'passing_score' => 'decimal:2',
        'total_points' => 'decimal:2',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_answers' => 'boolean',
        'show_explanation' => 'boolean',
        'allow_review' => 'boolean',
        'require_camera' => 'boolean',
        'prevent_copy' => 'boolean',
        'prevent_tab_switch' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    // Types
    const TYPE_IELTS = 'ielts';
    const TYPE_CAMBRIDGE = 'cambridge';
    const TYPE_TOEIC = 'toeic';
    const TYPE_CUSTOM = 'custom';
    const TYPE_PLACEMENT = 'placement';
    const TYPE_QUIZ = 'quiz';
    const TYPE_PRACTICE = 'practice';

    // Subtypes
    const SUBTYPE_IELTS_ACADEMIC = 'academic';
    const SUBTYPE_IELTS_GENERAL = 'general';
    const SUBTYPE_CAMBRIDGE_KET = 'ket';
    const SUBTYPE_CAMBRIDGE_PET = 'pet';
    const SUBTYPE_CAMBRIDGE_FCE = 'fce';
    const SUBTYPE_CAMBRIDGE_CAE = 'cae';
    const SUBTYPE_CAMBRIDGE_CPE = 'cpe';

    // Statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function sections(): HasMany
    {
        return $this->hasMany(TestSection::class)->orderBy('sort_order');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class)->orderBy('sort_order');
    }

    public function questions()
    {
        return $this->hasManyThrough(
            Question::class,
            TestQuestion::class,
            'test_id',
            'id',
            'id',
            'question_id'
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(TestAnalytics::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function isAvailable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) return false;
        if ($this->available_from && $this->available_from > now()) return false;
        if ($this->available_until && $this->available_until < now()) return false;
        return true;
    }

    public function calculateTotalPoints(): float
    {
        return $this->testQuestions->sum(function ($tq) {
            return $tq->points ?? $tq->question->points ?? 1;
        });
    }

    public function getQuestionCount(): int
    {
        return $this->testQuestions()->count();
    }

    public function isIelts(): bool
    {
        return $this->type === self::TYPE_IELTS;
    }

    public function isCambridge(): bool
    {
        return $this->type === self::TYPE_CAMBRIDGE;
    }

    public function duplicate(): self
    {
        $newTest = $this->replicate(['uuid']);
        $newTest->uuid = (string) Str::uuid();
        $newTest->title = $this->title . ' (Copy)';
        $newTest->status = self::STATUS_DRAFT;
        $newTest->save();

        // Duplicate sections
        foreach ($this->sections as $section) {
            $newSection = $section->replicate();
            $newSection->test_id = $newTest->id;
            $newSection->save();

            // Duplicate questions in section
            foreach ($this->testQuestions()->where('section_id', $section->id)->get() as $tq) {
                $newTq = $tq->replicate();
                $newTq->test_id = $newTest->id;
                $newTq->section_id = $newSection->id;
                $newTq->save();
            }
        }

        // Duplicate questions without section
        foreach ($this->testQuestions()->whereNull('section_id')->get() as $tq) {
            $newTq = $tq->replicate();
            $newTq->test_id = $newTest->id;
            $newTq->save();
        }

        return $newTest;
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_IELTS => 'IELTS',
            self::TYPE_CAMBRIDGE => 'Cambridge',
            self::TYPE_TOEIC => 'TOEIC',
            self::TYPE_CUSTOM => 'Custom',
            self::TYPE_PLACEMENT => 'Placement Test',
            self::TYPE_QUIZ => 'Quiz',
            self::TYPE_PRACTICE => 'Practice',
        ];
    }
}
