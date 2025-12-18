<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkAssignment extends Model
{
    const ROLE_EXECUTOR = 'executor';
    const ROLE_ASSIGNER = 'assigner';
    const ROLE_OBSERVER = 'observer';
    const ROLE_SUPPORTER = 'supporter';

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'work_item_id', 'user_id', 'role',
        'assigned_at', 'assigned_by',
        'status', 'decline_reason',
        'department_id', 'is_department_assignment'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_department_assignment' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Helpers
     */
    public function isExecutor(): bool
    {
        return $this->role === self::ROLE_EXECUTOR;
    }

    public function isAssigner(): bool
    {
        return $this->role === self::ROLE_ASSIGNER;
    }

    public function isObserver(): bool
    {
        return $this->role === self::ROLE_OBSERVER;
    }

    public function isSupporter(): bool
    {
        return $this->role === self::ROLE_SUPPORTER;
    }

    public static function getRoles(): array
    {
        return [
            self::ROLE_EXECUTOR => 'Người thực hiện',
            self::ROLE_ASSIGNER => 'Người giao việc',
            self::ROLE_OBSERVER => 'Người quan sát',
            self::ROLE_SUPPORTER => 'Người hỗ trợ',
        ];
    }
}
