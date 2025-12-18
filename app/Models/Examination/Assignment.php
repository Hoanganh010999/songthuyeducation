<?php

namespace App\Models\Examination;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'instructions',
        'test_id',
        'start_date',
        'due_date',
        'late_submission',
        'late_penalty',
        'late_days_allowed',
        'assign_type',
        'max_attempts',
        'time_limit',
        'shuffle_questions',
        'shuffle_options',
        'grading_type',
        'passing_score',
        'notify_on_assign',
        'notify_on_due',
        'notify_on_grade',
        'settings',
        'branch_id',
        'created_by',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'late_submission' => 'boolean',
        'late_penalty' => 'decimal:2',
        'late_days_allowed' => 'integer',
        'max_attempts' => 'integer',
        'time_limit' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'passing_score' => 'decimal:2',
        'notify_on_assign' => 'boolean',
        'notify_on_due' => 'boolean',
        'notify_on_grade' => 'boolean',
    ];

    // Assign types
    const ASSIGN_TYPE_USER = 'user';
    const ASSIGN_TYPE_CLASS = 'class';
    const ASSIGN_TYPE_BRANCH = 'branch';
    const ASSIGN_TYPE_COURSE = 'course';
    const ASSIGN_TYPE_ALL = 'all';

    // Grading types
    const GRADING_AUTO = 'auto';
    const GRADING_MANUAL = 'manual';
    const GRADING_MIXED = 'mixed';

    // Statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
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
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AssignmentTarget::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get assigned users
    public function assignedUsers()
    {
        return $this->targets()
            ->where('targetable_type', User::class)
            ->with('targetable');
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
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('targets', function ($q) use ($userId) {
            $q->where('targetable_type', User::class)
                ->where('targetable_id', $userId);
        })->orWhere('assign_type', self::ASSIGN_TYPE_ALL);
    }

    // Helpers
    public function isAvailable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) return false;
        if ($this->start_date && $this->start_date > now()) return false;
        return true;
    }

    public function isPastDue(): bool
    {
        return $this->due_date && $this->due_date < now();
    }

    public function isLateSubmissionAllowed(): bool
    {
        if (!$this->late_submission) return false;
        if (!$this->due_date) return true;

        $lateDueDate = $this->due_date->addDays($this->late_days_allowed);
        return now() <= $lateDueDate;
    }

    public function getEffectiveTimeLimit(): ?int
    {
        return $this->time_limit ?? $this->test->time_limit;
    }

    public function getEffectiveMaxAttempts(): int
    {
        return $this->max_attempts ?? $this->test->max_attempts ?? 1;
    }

    public function getUserAttemptCount(int $userId): int
    {
        return $this->submissions()
            ->where('user_id', $userId)
            ->count();
    }

    public function canUserAttempt(int $userId): bool
    {
        return $this->getUserAttemptCount($userId) < $this->getEffectiveMaxAttempts();
    }

    public function assignToUser(int $userId, ?int $assignedBy = null): AssignmentTarget
    {
        return $this->targets()->create([
            'targetable_type' => User::class,
            'targetable_id' => $userId,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    public function assignToUsers(array $userIds, ?int $assignedBy = null): void
    {
        foreach ($userIds as $userId) {
            $this->assignToUser($userId, $assignedBy);
        }
    }

    public function getStatistics(): array
    {
        $submissions = $this->submissions;
        $submitted = $submissions->whereIn('status', ['submitted', 'graded']);

        return [
            'total_assigned' => $this->targets()->count(),
            'total_submitted' => $submitted->count(),
            'total_graded' => $submissions->where('status', 'graded')->count(),
            'average_score' => $submitted->avg('percentage'),
            'highest_score' => $submitted->max('percentage'),
            'lowest_score' => $submitted->min('percentage'),
            'pass_rate' => $submitted->count() > 0
                ? ($submitted->where('passed', true)->count() / $submitted->count()) * 100
                : 0,
        ];
    }
}
