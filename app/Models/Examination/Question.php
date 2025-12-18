<?php

namespace App\Models\Examination;

use App\Models\User;
use App\Models\Branch;
use App\Models\QuestionTag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'category_id',
        'subject_id',
        'subject_category_id',
        'skill',
        'difficulty',
        'type',
        'title',
        'content',
        'explanation',
        'correct_answer',
        'audio_track_id',
        'passage_id',
        'image_url',
        'media',
        'points',
        'time_limit',
        'partial_credit',
        'tags',
        'settings',
        'branch_id',
        'created_by',
        'status',
    ];

    protected $casts = [
        'content' => 'array',
        'correct_answer' => 'array',
        'media' => 'array',
        'tags' => 'array',
        'settings' => 'array',
        'points' => 'decimal:2',
        'time_limit' => 'integer',
        'partial_credit' => 'boolean',
    ];

    // Question types
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_MULTIPLE_RESPONSE = 'multiple_response';
    const TYPE_FILL_BLANKS = 'fill_blanks';
    const TYPE_FILL_BLANKS_DRAG = 'fill_blanks_drag';
    const TYPE_MATCHING = 'matching';
    const TYPE_DRAG_DROP = 'drag_drop';
    const TYPE_ORDERING = 'ordering';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_TRUE_FALSE_NG = 'true_false_ng';
    const TYPE_ESSAY = 'essay';
    const TYPE_SHORT_ANSWER = 'short_answer';
    const TYPE_AUDIO_RESPONSE = 'audio_response';
    const TYPE_HOTSPOT = 'hotspot';
    const TYPE_LABELING = 'labeling';
    const TYPE_SENTENCE_COMPLETION = 'sentence_completion';
    const TYPE_SUMMARY_COMPLETION = 'summary_completion';
    const TYPE_NOTE_COMPLETION = 'note_completion';
    const TYPE_TABLE_COMPLETION = 'table_completion';
    const TYPE_FLOW_CHART = 'flow_chart';
    const TYPE_MATCHING_HEADINGS = 'matching_headings';
    const TYPE_MATCHING_FEATURES = 'matching_features';
    const TYPE_MATCHING_SENTENCE_ENDINGS = 'matching_sentence_endings';

    // Skills
    const SKILL_LISTENING = 'listening';
    const SKILL_READING = 'reading';
    const SKILL_WRITING = 'writing';
    const SKILL_SPEAKING = 'speaking';
    const SKILL_GRAMMAR = 'grammar';
    const SKILL_VOCABULARY = 'vocabulary';
    const SKILL_GENERAL = 'general';

    // Difficulties
    const DIFFICULTY_EASY = 'easy';
    const DIFFICULTY_MEDIUM = 'medium';
    const DIFFICULTY_HARD = 'hard';
    const DIFFICULTY_EXPERT = 'expert';

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
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class, 'subject_id');
    }

    public function subjectCategory(): BelongsTo
    {
        return $this->belongsTo(ExamSubjectCategory::class, 'subject_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function audioTrack(): BelongsTo
    {
        return $this->belongsTo(AudioTrack::class, 'audio_track_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class);
    }

    public function submissionAnswers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(QuestionAnalytics::class);
    }

    public function questionTags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class, 'question_question_tag', 'question_id', 'tag_id')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBySkill($query, string $skill)
    {
        return $query->where('skill', $skill);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, int $categoryId, bool $includeChildren = true)
    {
        if ($includeChildren) {
            $category = QuestionCategory::find($categoryId);
            if ($category) {
                $ids = array_merge([$categoryId], $category->getAllChildIds());
                return $query->whereIn('category_id', $ids);
            }
        }
        return $query->where('category_id', $categoryId);
    }

    // Helpers
    public function isAutoGradable(): bool
    {
        return !in_array($this->type, [
            self::TYPE_ESSAY,
            self::TYPE_AUDIO_RESPONSE,
        ]);
    }

    public function requiresManualGrading(): bool
    {
        return in_array($this->type, [
            self::TYPE_ESSAY,
            self::TYPE_AUDIO_RESPONSE,
        ]);
    }

    public function getCorrectOptions(): \Illuminate\Support\Collection
    {
        return $this->options->where('is_correct', true);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            self::TYPE_MULTIPLE_RESPONSE => 'Multiple Response',
            self::TYPE_FILL_BLANKS => 'Fill in the Blanks',
            self::TYPE_FILL_BLANKS_DRAG => 'Fill Blanks (Drag & Drop)',
            self::TYPE_MATCHING => 'Matching',
            self::TYPE_DRAG_DROP => 'Drag & Drop',
            self::TYPE_ORDERING => 'Ordering',
            self::TYPE_TRUE_FALSE => 'True/False',
            self::TYPE_TRUE_FALSE_NG => 'True/False/Not Given',
            self::TYPE_ESSAY => 'Essay',
            self::TYPE_SHORT_ANSWER => 'Short Answer',
            self::TYPE_AUDIO_RESPONSE => 'Audio Response',
            self::TYPE_HOTSPOT => 'Hotspot',
            self::TYPE_LABELING => 'Labeling',
            self::TYPE_SENTENCE_COMPLETION => 'Sentence Completion',
            self::TYPE_SUMMARY_COMPLETION => 'Summary Completion',
            self::TYPE_NOTE_COMPLETION => 'Note Completion',
            self::TYPE_TABLE_COMPLETION => 'Table Completion',
            self::TYPE_FLOW_CHART => 'Flow Chart',
            self::TYPE_MATCHING_HEADINGS => 'Matching Headings',
            self::TYPE_MATCHING_FEATURES => 'Matching Features',
            self::TYPE_MATCHING_SENTENCE_ENDINGS => 'Matching Sentence Endings',
        ];
    }

    public static function getSkills(): array
    {
        return [
            self::SKILL_LISTENING => 'Listening',
            self::SKILL_READING => 'Reading',
            self::SKILL_WRITING => 'Writing',
            self::SKILL_SPEAKING => 'Speaking',
            self::SKILL_GRAMMAR => 'Grammar',
            self::SKILL_VOCABULARY => 'Vocabulary',
            self::SKILL_GENERAL => 'General',
        ];
    }

    public static function getDifficulties(): array
    {
        return [
            self::DIFFICULTY_EASY => 'Easy',
            self::DIFFICULTY_MEDIUM => 'Medium',
            self::DIFFICULTY_HARD => 'Hard',
            self::DIFFICULTY_EXPERT => 'Expert',
        ];
    }
}
