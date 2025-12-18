<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class ZaloAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'zalo_id',
        'branch_id',
        'assigned_to',
        'cookie',
        'imei',
        'user_agent',
        'avatar_url',
        'avatar_path',
        'is_active',
        'is_connected',
        'is_primary',
        'status',
        'last_sync_at',
        'last_login_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_connected' => 'boolean',
        'is_primary' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'cookie', // Hide encrypted cookie
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function friends()
    {
        return $this->hasMany(ZaloFriend::class);
    }

    public function groups()
    {
        return $this->hasMany(ZaloGroup::class);
    }

    public function messages()
    {
        return $this->hasMany(ZaloMessage::class);
    }

    public function accountBranches()
    {
        return $this->hasMany(ZaloAccountBranch::class);
    }

    public function sharedBranches()
    {
        return $this->accountBranches()->shared();
    }

    /**
     * Branches that have access to this account (via zalo_account_branches pivot table)
     * Used for multi-branch account sharing
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'zalo_account_branches', 'zalo_account_id', 'branch_id')
            ->withTimestamps();
    }

    public function ownerBranch()
    {
        return $this->accountBranches()->owners()->first();
    }

    // Accessors & Mutators
    public function getCookieAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            $decrypted = Crypt::decryptString($value);
            // Try to decode JSON if it's a JSON string
            $decoded = json_decode($decrypted, true);
            return $decoded !== null ? $decoded : $decrypted;
        } catch (\Exception $e) {
            \Log::warning('[ZaloAccount] Failed to decrypt cookie', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function setCookieAttribute($value)
    {
        if (!$value) {
            $this->attributes['cookie'] = null;
            return;
        }
        
        try {
            // Convert array/object to JSON string before encrypting
            $cookieString = is_string($value) ? $value : json_encode($value);
            $this->attributes['cookie'] = Crypt::encryptString($cookieString);
        } catch (\Exception $e) {
            \Log::error('[ZaloAccount] Failed to encrypt cookie', ['error' => $e->getMessage()]);
            $this->attributes['cookie'] = null;
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeConnected($query)
    {
        return $query->where('is_connected', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        if (!$branchId) {
            return $query;
        }

        return $query->where(function($q) use ($branchId) {
            // Check direct branch_id
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id')
              // Also check zalo_account_branches pivot table (multi-branch support)
              ->orWhereHas('branches', function ($branchQuery) use ($branchId) {
                  $branchQuery->where('branches.id', $branchId);
              });
        });
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: Zalo accounts mà user có quyền xem
     * - Super-admin: xem tất cả
     * - Admin: xem tất cả trong branch của mình
     * - User có branches: xem accounts được gán cho mình HOẶC cùng branch
     * - User không có branch: chỉ xem accounts được gán cho mình
     *
     * Note: Accounts không có branch_id (null) sẽ không hiển thị khi filter theo branch
     * nhưng vẫn hiển thị nếu được gán cho user đó
     */
    public function scopeAccessibleBy($query, $user)
    {
        // Super-admin xem tất cả
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        // Lấy branches của user
        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

        if (empty($userBranchIds)) {
            // Không có branch → chỉ xem accounts được gán cho mình
            return $query->where('assigned_to', $user->id);
        }

        // Có branches → xem accounts theo 3 cách:
        // 1. Được gán cho mình (assigned_to)
        // 2. Có branch_id trùng với branches của user
        // 3. Có record trong zalo_account_branches cho branches của user (multi-branch support)
        return $query->where(function ($q) use ($user, $userBranchIds) {
            $q->where('assigned_to', $user->id)
              ->orWhereIn('branch_id', $userBranchIds)
              ->orWhereHas('branches', function ($branchQuery) use ($userBranchIds) {
                  // belongsToMany query operates on related model (branches table)
                  $branchQuery->whereIn('branches.id', $userBranchIds);
              });
        });
    }

    /**
     * Scope: Filter accounts based on management permissions
     * - Super-admin OR users with zalo.manage_accounts → see ALL accounts
     * - Other users → see ONLY primary account
     */
    public function scopeBasedOnManagePermission($query, $user, $branchId = null)
    {
        // Super-admin or users with manage_accounts permission can see all accounts
        if ($user->hasRole('super-admin') || $user->hasPermission('zalo.manage_accounts')) {
            if ($branchId) {
                return $query->forBranch($branchId);
            }
            return $query;
        }

        // Other users can only see primary account
        $primaryQuery = $query->where('is_primary', true);

        if ($branchId) {
            return $primaryQuery->forBranch($branchId);
        }

        return $primaryQuery;
    }

    /**
     * Scope: Get primary account for a branch
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
