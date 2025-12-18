<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZaloGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zalo_account_id',
        'branch_id',
        'assigned_branch_id',
        'department_id',
        'zalo_group_id',
        'name',
        'description',
        'avatar_url',
        'avatar_path',
        'members_count',
        'admin_ids',
        'creator_id',
        'type',
        'version',
        'metadata',
        'last_sync_at',
    ];

    protected $casts = [
        'admin_ids' => 'array',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
        'version' => 'string', // Version is stored as string to support long numbers
    ];

    // Relationships

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * The branch this group is assigned to for access control
     */
    public function assignedBranch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function members()
    {
        return $this->hasMany(ZaloGroupMember::class);
    }

    public function zaloAccount()
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    // Scopes

    /**
     * Scope: Groups for a specific Zalo account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    /**
     * Scope: Groups accessible by a specific branch
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
              ->orWhereNull('assigned_branch_id'); // Include global groups
        });
    }

    /**
     * Scope: Groups assigned to a specific department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        if (!$departmentId) {
            return $query;
        }

        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope: Groups accessible by user based on permissions
     * - assigned_branch_id = NULL → global (visible to all shared branches)
     * - assigned_branch_id = specific → only that branch can see
     */
    public function scopeAccessibleBy($query, $user)
    {
        // Super-admin or users with view_groups permission see all
        if ($user->hasRole('super-admin') || $user->hasPermission('zalo.view_groups')) {
            return $query;
        }

        // Users with all_conversation_management see all groups
        if ($user->hasPermission('zalo.all_conversation_management')) {
            return $query;
        }

        // Users with view_all_branches_groups can see ALL groups (no branch filtering)
        if ($user->hasPermission('zalo.view_all_branches_groups')) {
            return $query;
        }

        // Get user's branches and departments
        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
        $userDepartmentIds = $user->departments()
            ->where(function ($q) {
                $q->where('department_user.is_head', true)
                  ->orWhere('department_user.is_deputy', true);
            })
            ->pluck('departments.id')
            ->toArray();

        // Show groups assigned to user's branches/departments, or global groups
        return $query->where(function ($q) use ($userBranchIds, $userDepartmentIds) {
            $q->whereNull('assigned_branch_id') // Global groups (visible to all)
              ->orWhereIn('assigned_branch_id', $userBranchIds)
              ->orWhereIn('department_id', $userDepartmentIds);
        });
    }

    /**
     * Scope: Global groups (not assigned to any branch or department)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('branch_id')->whereNull('department_id');
    }

    /**
     * Scope: Groups assigned to a specific branch
     */
    public function scopeAssignedToBranch($query, $branchId)
    {
        return $query->where('assigned_branch_id', $branchId);
    }

    /**
     * Scope: Groups accessible by a branch (considering permissions)
     * - If view_all_groups: all groups of the account
     * - Otherwise: only groups assigned to this branch
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
}
