<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkActivityLog extends Model
{
    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'work_item_id', 'user_id', 'action_type',
        'old_value', 'new_value', 'metadata',
        'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'metadata' => 'array',
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

    /**
     * Scopes
     */
    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Static helper to log activity
     */
    public static function logActivity(
        WorkItem $workItem,
        string $actionType,
        ?User $user = null,
        $oldValue = null,
        $newValue = null,
        array $metadata = []
    ): self {
        return self::create([
            'work_item_id' => $workItem->id,
            'user_id' => $user?->id ?? auth()->id(),
            'action_type' => $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
