<?php

namespace App\Models\Examination;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'test_question_id',
        'answer',
        'audio_file_path',
        'audio_duration',
        'text_answer',
        'word_count',
        'is_correct',
        'points_earned',
        'max_points',
        'correct_parts',
        'total_parts',
        'feedback',
        'graded_by',
        'graded_at',
        'grading_criteria',
        'time_spent',
        'answered_at',
        'changes_count',
    ];

    protected $casts = [
        'answer' => 'array',
        'grading_criteria' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'max_points' => 'decimal:2',
        'correct_parts' => 'integer',
        'total_parts' => 'integer',
        'audio_duration' => 'integer',
        'word_count' => 'integer',
        'time_spent' => 'integer',
        'changes_count' => 'integer',
        'graded_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    // Relationships
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function testQuestion(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // Helpers
    public function isAnswered(): bool
    {
        return $this->answer !== null || $this->text_answer !== null || $this->audio_file_path !== null;
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    public function needsManualGrading(): bool
    {
        return $this->question->requiresManualGrading() && !$this->isGraded();
    }

    public function getScorePercentage(): ?float
    {
        if ($this->max_points === null || $this->max_points == 0) {
            return null;
        }

        return ($this->points_earned / $this->max_points) * 100;
    }

    public function grade(float $points, ?string $feedback = null, ?int $gradedBy = null): self
    {
        $this->points_earned = $points;
        $this->feedback = $feedback;
        $this->graded_by = $gradedBy;
        $this->graded_at = now();
        $this->is_correct = $points >= ($this->max_points ?? $this->question->points ?? 1);
        $this->save();

        // Re-calculate submission score
        $this->submission->autoGrade();

        return $this;
    }

    public function gradeIeltsWriting(array $criteria, ?string $feedback = null, ?int $gradedBy = null): self
    {
        // IELTS Writing criteria: Task Achievement, Coherence, Lexical, Grammar
        $this->grading_criteria = [
            'task_achievement' => $criteria['task_achievement'] ?? 0,
            'coherence_cohesion' => $criteria['coherence_cohesion'] ?? 0,
            'lexical_resource' => $criteria['lexical_resource'] ?? 0,
            'grammatical_range' => $criteria['grammatical_range'] ?? 0,
        ];

        // Calculate average band score
        $scores = array_values($this->grading_criteria);
        $average = array_sum($scores) / count($scores);
        $bandScore = round($average * 2) / 2; // Round to nearest 0.5

        $this->points_earned = $bandScore;
        $this->feedback = $feedback;
        $this->graded_by = $gradedBy;
        $this->graded_at = now();
        $this->save();

        // Re-calculate submission score
        $this->submission->autoGrade();

        return $this;
    }

    public function updateAnswer($answer): self
    {
        $this->answer = $answer;
        $this->answered_at = now();
        $this->increment('changes_count');
        $this->save();

        return $this;
    }

    public function updateTextAnswer(string $text): self
    {
        $this->text_answer = $text;
        $this->word_count = str_word_count($text);
        $this->answered_at = now();
        $this->increment('changes_count');
        $this->save();

        return $this;
    }

    public function setAudioResponse(string $filePath, int $duration): self
    {
        $this->audio_file_path = $filePath;
        $this->audio_duration = $duration;
        $this->answered_at = now();
        $this->increment('changes_count');
        $this->save();

        return $this;
    }

    // Accessor for band_score (stored in points_earned for IELTS)
    public function getBandScoreAttribute(): ?float
    {
        return $this->points_earned;
    }

    // Mutator for band_score (stores in points_earned)
    public function setBandScoreAttribute($value): void
    {
        $this->attributes['points_earned'] = $value;
    }
}
