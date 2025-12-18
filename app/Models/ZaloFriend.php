<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZaloFriend extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zalo_account_id',
        'branch_id',
        'assigned_branch_id',
        'zalo_user_id',
        'name',
        'phone',
        'avatar_url',
        'avatar_path',
        'bio',
        'metadata',
        'last_seen_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    // Scopes

    /**
     * Scope: Friends for a specific Zalo account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    /**
     * Scope: Friends accessible by a specific branch
     * Using assigned_branch_id for branch-based access control
     * - assigned_branch_id = NULL → global (visible to all)
     * - assigned_branch_id = specific → only that branch can see
     */
    public function scopeForBranch($query, $branchId)
    {
        if (!$branchId) {
            return $query;
        }

        return $query->where(function($q) use ($branchId) {
            $q->where('assigned_branch_id', $branchId)
              ->orWhereNull('assigned_branch_id'); // Include global friends
        });
    }

    /**
     * Scope: Friends assigned to a specific branch
     */
    public function scopeAssignedToBranch($query, $branchId)
    {
        return $query->where('assigned_branch_id', $branchId);
    }

    /**
     * Scope: Friends accessible by user based on permissions
     * - assigned_branch_id = NULL → global (visible to all shared branches)
     * - assigned_branch_id = specific → only that branch can see
     */
    public function scopeAccessibleBy($query, $user)
    {
        // Super-admin or users with view permission see all
        if ($user->hasRole('super-admin') || $user->hasPermission('zalo.view')) {
            return $query;
        }

        // Users with all_conversation_management see all friends
        if ($user->hasPermission('zalo.all_conversation_management')) {
            return $query;
        }

        // Users with view_all_branches_friends can see ALL friends (no branch filtering)
        if ($user->hasPermission('zalo.view_all_branches_friends')) {
            return $query;
        }

        // Get user's branches
        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

        // Show friends assigned to user's branches, or global friends
        return $query->where(function ($q) use ($userBranchIds) {
            $q->whereNull('assigned_branch_id') // Global friends (visible to all)
              ->orWhereIn('assigned_branch_id', $userBranchIds);
        });
    }

    /**
     * Scope: Friends accessible by a branch (considering permissions)
     * - If view_all_friends: all friends of the account
     * - Otherwise: only friends assigned to this branch
     */
    public function scopeAccessibleByBranch($query, $accountId, $branchId, $viewAll = false)
    {
        $query->where('zalo_account_id', $accountId);

        if (!$viewAll) {
            $query->where(function($q) use ($branchId) {
                $q->where('assigned_branch_id', $branchId)
                  ->orWhereNull('assigned_branch_id');
            });
        }

        return $query;
    }

    // Relationships

    public function zaloAccount()
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }
}
