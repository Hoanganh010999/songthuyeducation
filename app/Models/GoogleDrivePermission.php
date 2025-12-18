<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleDrivePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_drive_item_id',
        'google_permission_id',
        'role',
        'is_verified',
        'verified_at',
        'synced_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the user that has this permission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Google Drive item (file/folder) this permission is for
     */
    public function item()
    {
        return $this->belongsTo(GoogleDriveItem::class, 'google_drive_item_id');
    }

    /**
     * Scope: only verified permissions
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: permissions for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: permissions for folders only
     */
    public function scopeFoldersOnly($query)
    {
        return $query->whereHas('item', function ($q) {
            $q->where('type', 'folder');
        });
    }

    /**
     * Scope: permissions that need verification
     */
    public function scopeNeedVerification($query)
    {
        return $query->where('is_verified', false)
            ->orWhere('synced_at', '<', now()->subHours(24));
    }
}
