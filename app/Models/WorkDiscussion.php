<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkDiscussion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'work_item_id', 'user_id', 'parent_id',
        'content', 'mentions', 'is_internal'
    ];

    protected $casts = [
        'mentions' => 'array',
        'is_internal' => 'boolean',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkDiscussion::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(WorkDiscussion::class, 'parent_id');
    }

    public function attachments()
    {
        return $this->morphMany(WorkAttachment::class, 'attachable');
    }

    /**
     * Scopes
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }
}
