<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloAccountBranch extends Model
{
    protected $fillable = [
        'zalo_account_id',
        'branch_id',
        'role',
        'can_send_to_customers',
        'can_send_to_teachers',
        'can_send_to_class_groups',
        'can_send_to_friends',
        'can_send_to_groups',
        'view_all_friends',
        'view_all_groups',
        'view_all_conversations',
    ];

    protected $casts = [
        'can_send_to_customers' => 'boolean',
        'can_send_to_teachers' => 'boolean',
        'can_send_to_class_groups' => 'boolean',
        'can_send_to_friends' => 'boolean',
        'can_send_to_groups' => 'boolean',
        'view_all_friends' => 'boolean',
        'view_all_groups' => 'boolean',
        'view_all_conversations' => 'boolean',
    ];

    /**
     * Get the Zalo account that owns this branch access.
     */
    public function zaloAccount()
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    /**
     * Get the branch that has access to this Zalo account.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope: Get owner branches for an account
     */
    public function scopeOwners($query)
    {
        return $query->where('role', 'owner');
    }

    /**
     * Scope: Get shared branches for an account
     */
    public function scopeShared($query)
    {
        return $query->where('role', 'shared');
    }

    /**
     * Scope: Get branches for a specific Zalo account
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    /**
     * Scope: Get accounts for a specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Check if this is an owner branch
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if this is a shared branch
     */
    public function isShared(): bool
    {
        return $this->role === 'shared';
    }

    /**
     * Check if can send to a specific target type
     */
    public function canSendTo(string $targetType): bool
    {
        // Owner can always send
        if ($this->isOwner()) {
            return true;
        }

        // Check specific permission for shared branches
        return match($targetType) {
            'customers' => $this->can_send_to_customers,
            'teachers' => $this->can_send_to_teachers,
            'class_groups' => $this->can_send_to_class_groups,
            'friends' => $this->can_send_to_friends,
            'groups' => $this->can_send_to_groups,
            default => false,
        };
    }

    /**
     * Check if can view all data of a specific type
     */
    public function canViewAll(string $dataType): bool
    {
        // Owner can always view all
        if ($this->isOwner()) {
            return true;
        }

        // Check specific permission for shared branches
        return match($dataType) {
            'friends' => $this->view_all_friends,
            'groups' => $this->view_all_groups,
            'conversations' => $this->view_all_conversations,
            default => false,
        };
    }
}
