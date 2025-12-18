<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\ZaloAccountBranch;
use Illuminate\Support\Facades\Log;

/**
 * Service to check multi-branch permissions for Zalo accounts
 *
 * This service handles permission checks when multiple branches share the same Zalo account.
 * It ensures that shared branches respect their permission settings.
 */
class ZaloBranchPermissionService
{
    /**
     * Check if a branch can send to a specific target type
     *
     * @param int $accountId Zalo account ID
     * @param int $branchId Branch ID
     * @param string $targetType One of: customers, teachers, class_groups, friends, groups
     * @return bool
     */
    public function canSendTo(int $accountId, int $branchId, string $targetType): bool
    {
        $accountBranch = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$accountBranch) {
            // If no relationship exists, deny by default
            Log::warning('[ZaloBranchPermission] No branch access found', [
                'account_id' => $accountId,
                'branch_id' => $branchId,
                'target_type' => $targetType,
            ]);
            return false;
        }

        return $accountBranch->canSendTo($targetType);
    }

    /**
     * Check if a branch can view all data of a specific type
     *
     * @param int $accountId Zalo account ID
     * @param int $branchId Branch ID
     * @param string $dataType One of: friends, groups, conversations
     * @return bool
     */
    public function canViewAll(int $accountId, int $branchId, string $dataType): bool
    {
        $accountBranch = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$accountBranch) {
            // If no relationship exists, deny view all
            return false;
        }

        return $accountBranch->canViewAll($dataType);
    }

    /**
     * Get the role of a branch for an account
     *
     * @param int $accountId Zalo account ID
     * @param int $branchId Branch ID
     * @return string|null 'owner', 'shared', or null if no relationship
     */
    public function getBranchRole(int $accountId, int $branchId): ?string
    {
        $accountBranch = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        return $accountBranch?->role;
    }

    /**
     * Check if branch is the owner of the account
     *
     * @param int $accountId Zalo account ID
     * @param int $branchId Branch ID
     * @return bool
     */
    public function isOwner(int $accountId, int $branchId): bool
    {
        return $this->getBranchRole($accountId, $branchId) === 'owner';
    }

    /**
     * Get all permissions for a branch on an account
     *
     * @param int $accountId Zalo account ID
     * @param int $branchId Branch ID
     * @return array Associative array of permissions
     */
    public function getAllPermissions(int $accountId, int $branchId): array
    {
        $accountBranch = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$accountBranch) {
            return [
                'role' => null,
                'can_send_to_customers' => false,
                'can_send_to_teachers' => false,
                'can_send_to_class_groups' => false,
                'can_send_to_friends' => false,
                'can_send_to_groups' => false,
                'view_all_friends' => false,
                'view_all_groups' => false,
                'view_all_conversations' => false,
            ];
        }

        return [
            'role' => $accountBranch->role,
            'can_send_to_customers' => $accountBranch->can_send_to_customers,
            'can_send_to_teachers' => $accountBranch->can_send_to_teachers,
            'can_send_to_class_groups' => $accountBranch->can_send_to_class_groups,
            'can_send_to_friends' => $accountBranch->can_send_to_friends,
            'can_send_to_groups' => $accountBranch->can_send_to_groups,
            'view_all_friends' => $accountBranch->view_all_friends,
            'view_all_groups' => $accountBranch->view_all_groups,
            'view_all_conversations' => $accountBranch->view_all_conversations,
        ];
    }

    /**
     * Validate if account can be used from current branch context
     * Throws exception if not allowed
     *
     * @param ZaloAccount $account
     * @param int $branchId
     * @param string $targetType
     * @throws \Exception
     */
    public function validateOrFail(ZaloAccount $account, int $branchId, string $targetType): void
    {
        if (!$this->canSendTo($account->id, $branchId, $targetType)) {
            throw new \Exception(
                "Branch {$branchId} does not have permission to send to {$targetType} using account {$account->id}"
            );
        }
    }

    /**
     * Get all branches that have access to an account
     *
     * @param int $accountId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBranchesWithAccess(int $accountId)
    {
        return ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->with('branch')
            ->get();
    }

    /**
     * Update permissions for a branch on an account
     *
     * @param int $accountId
     * @param int $branchId
     * @param array $permissions
     * @return bool
     */
    public function updatePermissions(int $accountId, int $branchId, array $permissions): bool
    {
        $accountBranch = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$accountBranch) {
            Log::error('[ZaloBranchPermission] Cannot update permissions - no relationship found', [
                'account_id' => $accountId,
                'branch_id' => $branchId,
            ]);
            return false;
        }

        // Don't allow changing owner's permissions
        if ($accountBranch->isOwner()) {
            Log::warning('[ZaloBranchPermission] Cannot modify owner permissions', [
                'account_id' => $accountId,
                'branch_id' => $branchId,
            ]);
            return false;
        }

        $accountBranch->update($permissions);

        Log::info('[ZaloBranchPermission] Permissions updated', [
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'permissions' => $permissions,
        ]);

        return true;
    }
}
