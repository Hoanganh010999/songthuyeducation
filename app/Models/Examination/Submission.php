<?php

namespace App\Models\Examination;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Submission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'assignment_id',
        'practice_test_id',
        'user_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'time_spent',
        'time_remaining',
        'score',
        'max_score',
        'percentage',
        'passed',
        'band_score',
        'skill_scores',
        'status',
        'allow_special_view',
        'feedback',
        'graded_by',
        'graded_at',
        'published_at',
        'published_by',
        'tab_switches',
        'focus_lost_count',
        'activity_log',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'skill_scores' => 'array',
        'activity_log' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'published_at' => 'datetime',
        'time_spent' => 'integer',
        'time_remaining' => 'integer',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'band_score' => 'decimal:1',
        'passed' => 'boolean',
        'allow_special_view' => 'boolean',
        'attempt_number' => 'integer',
        'tab_switches' => 'integer',
        'focus_lost_count' => 'integer',
    ];

    // Statuses
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADING = 'grading';
    const STATUS_GRADED = 'graded';
    const STATUS_LATE = 'late';
    const STATUS_EXPIRED = 'expired';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->attempt_number)) {
                $model->attempt_number = Submission::where('assignment_id', $model->assignment_id)
                    ->where('user_id', $model->user_id)
                    ->count() + 1;
            }
        });
    }

    // Relationships
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function practiceTest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PracticeTest::class, 'practice_test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    public function scopeNeedsGrading($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_GRADING]);
    }

    // Helpers
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    public function isPublished(): bool
    {
        return !is_null($this->published_at);
    }

    public function canBePublished(): bool
    {
        return $this->isGraded() && !$this->isPublished();
    }

    public function canBeUnpublished(): bool
    {
        return $this->isPublished();
    }

    public function canBeModified(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if a user can view this submission
     */
    public function canBeViewedBy(User $user): bool
    {
        // Owner can always view their own submission
        if ($this->user_id === $user->id) {
            return true;
        }

        // Users with grading permission can view all submissions
        if ($user->hasPermission('examination.submissions.grade') || $user->hasPermission('examination.grading.view')) {
            return true;
        }

        // If allow_special_view is enabled, users with special_view permission can view
        if ($this->allow_special_view && $user->hasPermission('examination.submissions.special_view')) {
            return true;
        }

        return false;
    }

    public function start(): self
    {
        $this->started_at = now();
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();

        return $this;
    }

    public function submit(): self
    {
        $this->submitted_at = now();
        $this->time_spent = $this->started_at
            ? now()->diffInSeconds($this->started_at)
            : null;

        // Check if late
        if ($this->assignment->due_date && now() > $this->assignment->due_date) {
            $this->status = self::STATUS_LATE;
        } else {
            $this->status = self::STATUS_SUBMITTED;
        }

        $this->save();

        // Auto-grade if possible
        $this->autoGrade();

        return $this;
    }

    public function autoGrade(): void
    {
        $totalScore = 0;
        $maxScore = 0;
        $answeredCount = 0;

        foreach ($this->answers as $answer) {
            $question = $answer->question;
            $maxPoints = $answer->max_points ?? $question->points ?? 1;
            $maxScore += $maxPoints;

            if ($question->isAutoGradable()) {
                $pointsEarned = $this->gradeAnswer($answer);
                $answer->points_earned = $pointsEarned;
                $answer->save();
                $totalScore += $pointsEarned;
                $answeredCount++;
            }
        }

        $this->score = $totalScore;
        $this->max_score = $maxScore;
        $this->percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
        $this->passed = $this->assignment->passing_score
            ? $this->percentage >= $this->assignment->passing_score
            : null;

        // Check if all questions are auto-gradable
        $needsManualGrading = $this->answers->contains(function ($answer) {
            return $answer->question->requiresManualGrading() && $answer->graded_at === null;
        });

        if (!$needsManualGrading) {
            $this->status = self::STATUS_GRADED;
            $this->graded_at = now();
        }

        // Calculate IELTS band score if applicable
        if ($this->assignment->test->isIelts()) {
            $this->calculateIeltsBandScore();
        }

        $this->save();
    }

    protected function gradeAnswer(SubmissionAnswer $answer): float
    {
        $question = $answer->question;
        $correctAnswer = $question->correct_answer;
        $userAnswer = $answer->answer;
        $maxPoints = $answer->max_points ?? $question->points ?? 1;

        if ($userAnswer === null) {
            return 0;
        }

        switch ($question->type) {
            case Question::TYPE_MULTIPLE_CHOICE:
            case Question::TYPE_TRUE_FALSE:
                $isCorrect = $userAnswer == $correctAnswer;
                $answer->is_correct = $isCorrect;
                return $isCorrect ? $maxPoints : 0;

            case Question::TYPE_MULTIPLE_RESPONSE:
                if (!is_array($userAnswer) || !is_array($correctAnswer)) {
                    return 0;
                }
                sort($userAnswer);
                sort($correctAnswer);
                $isCorrect = $userAnswer === $correctAnswer;
                $answer->is_correct = $isCorrect;

                if ($question->partial_credit) {
                    $correct = count(array_intersect($userAnswer, $correctAnswer));
                    $wrong = count(array_diff($userAnswer, $correctAnswer));
                    $total = count($correctAnswer);
                    $score = max(0, $correct - $wrong);
                    $answer->correct_parts = $correct;
                    $answer->total_parts = $total;
                    return ($score / $total) * $maxPoints;
                }

                return $isCorrect ? $maxPoints : 0;

            case Question::TYPE_FILL_BLANKS:
            case Question::TYPE_SHORT_ANSWER:
                $userAnswerNormalized = strtolower(trim($userAnswer));
                $correctAnswers = is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
                $correctAnswers = array_map(fn($a) => strtolower(trim($a)), $correctAnswers);
                $isCorrect = in_array($userAnswerNormalized, $correctAnswers);
                $answer->is_correct = $isCorrect;
                return $isCorrect ? $maxPoints : 0;

            case Question::TYPE_MATCHING:
            case Question::TYPE_ORDERING:
                if (!is_array($userAnswer) || !is_array($correctAnswer)) {
                    return 0;
                }
                $isCorrect = $userAnswer === $correctAnswer;
                $answer->is_correct = $isCorrect;

                if ($question->partial_credit) {
                    $correct = 0;
                    $total = count($correctAnswer);
                    foreach ($correctAnswer as $key => $value) {
                        if (isset($userAnswer[$key]) && $userAnswer[$key] === $value) {
                            $correct++;
                        }
                    }
                    $answer->correct_parts = $correct;
                    $answer->total_parts = $total;
                    return ($correct / $total) * $maxPoints;
                }

                return $isCorrect ? $maxPoints : 0;

            default:
                return 0;
        }
    }

    protected function calculateIeltsBandScore(): void
    {
        // This is a simplified version - in production, you'd use the ielts_band_scores table
        $testType = $this->assignment->test->subtype ?? 'academic';
        $skillScores = [];

        foreach ($this->answers->groupBy(fn($a) => $a->question->skill) as $skill => $answers) {
            $rawScore = $answers->sum('points_earned');
            $maxRaw = $answers->sum('max_points');

            // Get band score from conversion table
            $bandScore = IeltsBandScore::where('test_type', $testType)
                ->where('skill', $skill)
                ->where('raw_score_min', '<=', $rawScore)
                ->where('raw_score_max', '>=', $rawScore)
                ->value('band_score');

            $skillScores[$skill] = $bandScore ?? round(($rawScore / $maxRaw) * 9, 1);
        }

        $this->skill_scores = $skillScores;

        // Calculate overall band score (average of 4 skills, rounded to nearest 0.5)
        if (count($skillScores) > 0) {
            $average = array_sum($skillScores) / count($skillScores);
            $this->band_score = round($average * 2) / 2; // Round to nearest 0.5
        }
    }

    public function logActivity(string $action, array $data = []): void
    {
        $log = $this->activity_log ?? [];
        $log[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->activity_log = $log;
        $this->save();
    }

    public function incrementTabSwitch(): void
    {
        $this->increment('tab_switches');
        $this->logActivity('tab_switch');
    }

    public function incrementFocusLost(): void
    {
        $this->increment('focus_lost_count');
        $this->logActivity('focus_lost');
    }

    public function getRemainingTime(): ?int
    {
        $timeLimit = $this->assignment->getEffectiveTimeLimit();
        if (!$timeLimit || !$this->started_at) {
            return null;
        }

        $elapsed = now()->diffInSeconds($this->started_at);
        $remaining = ($timeLimit * 60) - $elapsed;

        return max(0, $remaining);
    }
}
