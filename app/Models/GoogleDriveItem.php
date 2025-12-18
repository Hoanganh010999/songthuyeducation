<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleDriveItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'google_id',
        'name',
        'type',
        'mime_type',
        'parent_id',
        'size',
        'web_view_link',
        'web_content_link',
        'thumbnail_link',
        'icon_link',
        'google_created_at',
        'google_modified_at',
        'created_by',
        'updated_by',
        'is_trashed',
        'trashed_at',
        'permissions',
        'metadata',
    ];

    protected $casts = [
        'google_created_at' => 'datetime',
        'google_modified_at' => 'datetime',
        'trashed_at' => 'datetime',
        'is_trashed' => 'boolean',
        'size' => 'integer',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: Creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relationship: Parent folder
     */
    public function parent()
    {
        return $this->belongsTo(GoogleDriveItem::class, 'parent_id', 'google_id');
    }

    /**
     * Relationship: Children (files/folders inside this folder)
     */
    public function children()
    {
        return $this->hasMany(GoogleDriveItem::class, 'parent_id', 'google_id');
    }

    /**
     * Scope: Only files
     */
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }

    /**
     * Scope: Only folders
     */
    public function scopeFolders($query)
    {
        return $query->where('type', 'folder');
    }

    /**
     * Scope: Not trashed
     */
    public function scopeNotTrashed($query)
    {
        return $query->where('is_trashed', false);
    }

    /**
     * Scope: In folder
     */
    public function scopeInFolder($query, $folderId = null)
    {
        return $query->where('parent_id', $folderId);
    }

    /**
     * Scope: Root items (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        if (!$this->size) return 'N/A';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if item is a folder
     */
    public function isFolder()
    {
        return $this->type === 'folder';
    }

    /**
     * Check if item is a file
     */
    public function isFile()
    {
        return $this->type === 'file';
    }

    /**
     * Relationship: Permissions assigned to users for this item
     */
    public function userPermissions()
    {
        return $this->hasMany(GoogleDrivePermission::class, 'google_drive_item_id');
    }

    /**
     * Check if a user has permission to access this item
     */
    public function hasUserPermission($userId)
    {
        return $this->userPermissions()
            ->where('user_id', $userId)
            ->verified()
            ->exists();
    }
}
