<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkItem extends Model
{
    use SoftDeletes;

    const TYPE_PROJECT = 'project';
    const TYPE_TASK = 'task';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVISION_REQUESTED = 'revision_requested';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'type', 'parent_id', 'code', 'title', 'description',
        'priority', 'status', 'start_date', 'due_date', 'completed_at',
        'estimated_hours', 'actual_hours',
        'branch_id', 'department_id', 'created_by', 'assigned_by',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'parent_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkAssignment::class);
    }

    public function executors()
    {
        return $this->assignments()->where('role', 'executor')->with('user');
    }

    public function observers()
    {
        return $this->assignments()->where('role', 'observer')->with('user');
    }

    public function supporters()
    {
        return $this->assignments()->where('role', 'supporter')->with('user');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(WorkDiscussion::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(WorkSubmission::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(WorkActivityLog::class);
    }

    public function attachments()
    {
        return $this->morphMany(WorkAttachment::class, 'attachable');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WorkTag::class, 'work_item_tag', 'work_item_id', 'work_tag_id');
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('assignments', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeForBranch($query, $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $branchIds = $user->branches->pluck('id');
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeDueSoon($query, $days = 3)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Helpers
     */
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date < now() &&
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function calculateCompletionRate(): float
    {
        if ($this->type === self::TYPE_PROJECT) {
            $totalTasks = $this->children()->count();
            if ($totalTasks === 0) return 0;

            $completedTasks = $this->children()->where('status', self::STATUS_COMPLETED)->count();
            return round(($completedTasks / $totalTasks) * 100, 2);
        }

        return $this->status === self::STATUS_COMPLETED ? 100 : 0;
    }

    public function getPriorityWeight(): float
    {
        return match($this->priority) {
            self::PRIORITY_URGENT => 4.0,
            self::PRIORITY_HIGH => 3.0,
            self::PRIORITY_MEDIUM => 2.0,
            self::PRIORITY_LOW => 1.0,
            default => 1.0
        };
    }

    /**
     * Auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workItem) {
            if (empty($workItem->code)) {
                $workItem->code = self::generateCode($workItem->type);
            }
        });
    }

    public static function generateCode($type): string
    {
        $prefix = $type === self::TYPE_PROJECT ? 'WP' : 'WT';
        $year = date('Y');

        $lastItem = self::withTrashed()
                        ->where('code', 'like', "{$prefix}-{$year}-%")
                        ->orderBy('code', 'desc')
                        ->first();

        $number = 1;
        if ($lastItem) {
            $parts = explode('-', $lastItem->code);
            $number = intval($parts[2] ?? 0) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_ASSIGNED => 'Đã phân công',
            self::STATUS_IN_PROGRESS => 'Đang thực hiện',
            self::STATUS_SUBMITTED => 'Đã nộp',
            self::STATUS_REVISION_REQUESTED => 'Yêu cầu chỉnh sửa',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Thấp',
            self::PRIORITY_MEDIUM => 'Trung bình',
            self::PRIORITY_HIGH => 'Cao',
            self::PRIORITY_URGENT => 'Khẩn cấp',
        ];
    }
}
