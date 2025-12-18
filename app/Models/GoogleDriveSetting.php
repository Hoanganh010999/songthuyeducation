<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleDriveSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'client_id',
        'client_secret',
        'refresh_token',
        'access_token',
        'token_expires_at',
        'school_drive_folder_id',
        'school_drive_folder_name',
        'syllabus_folder_id',
        'syllabus_folder_name',
        'lesson_plan_folder_id',
        'lesson_plan_folder_name',
        'is_active',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
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
     * Check if access token is expired
     */
    public function isTokenExpired()
    {
        if (!$this->token_expires_at) {
            return true;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Scope: Active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
