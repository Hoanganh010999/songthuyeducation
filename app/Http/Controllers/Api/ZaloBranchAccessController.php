<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZaloAccount;
use App\Models\ZaloAccountBranch;
use App\Models\Branch;
use App\Services\ZaloBranchPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ZaloBranchAccessController extends Controller
{
    protected $permissionService;

    public function __construct(ZaloBranchPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all branches with access to a Zalo account
     *
     * GET /api/zalo/branch-access?account_id=16
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasPermission('zalo.manage_multi_branch_access') && !$user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản lý multi-branch access.',
            ], 403);
        }

        $accountId = $request->input('account_id');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        // Check if account exists
        $account = ZaloAccount::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Zalo account not found',
            ], 404);
        }

        // Get all branches with access
        $branchAccess = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->with('branch')
            ->get()
            ->map(function ($access) {
                return [
                    'id' => $access->id,
                    'branch_id' => $access->branch_id,
                    'branch_name' => $access->branch->name ?? 'Unknown',
                    'role' => $access->role,
                    'permissions' => [
                        'can_send_to_customers' => $access->can_send_to_customers,
                        'can_send_to_teachers' => $access->can_send_to_teachers,
                        'can_send_to_class_groups' => $access->can_send_to_class_groups,
                        'can_send_to_friends' => $access->can_send_to_friends,
                        'can_send_to_groups' => $access->can_send_to_groups,
                        'view_all_friends' => $access->view_all_friends,
                        'view_all_groups' => $access->view_all_groups,
                        'view_all_conversations' => $access->view_all_conversations,
                    ],
                    'created_at' => $access->created_at,
                    'updated_at' => $access->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'phone' => $account->phone,
                    'owner_branch_id' => $account->branch_id,
                ],
                'branches' => $branchAccess,
            ],
        ]);
    }

    /**
     * Grant branch access to a Zalo account
     *
     * POST /api/zalo/branch-access
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasPermission('zalo.manage_multi_branch_access') && !$user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản lý multi-branch access.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:zalo_accounts,id',
            'branch_id' => 'required|exists:branches,id',
            'permissions' => 'sometimes|array',
            'permissions.can_send_to_customers' => 'sometimes|boolean',
            'permissions.can_send_to_teachers' => 'sometimes|boolean',
            'permissions.can_send_to_class_groups' => 'sometimes|boolean',
            'permissions.can_send_to_friends' => 'sometimes|boolean',
            'permissions.can_send_to_groups' => 'sometimes|boolean',
            'permissions.view_all_friends' => 'sometimes|boolean',
            'permissions.view_all_groups' => 'sometimes|boolean',
            'permissions.view_all_conversations' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');

        // Check if access already exists
        $existing = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Branch already has access to this account. Use PUT to update permissions.',
            ], 409);
        }

        // Get account to determine role
        $account = ZaloAccount::find($accountId);
        $isOwner = ($account->branch_id == $branchId);

        // Create access
        $permissions = $request->input('permissions', []);

        $access = ZaloAccountBranch::create([
            'zalo_account_id' => $accountId,
            'branch_id' => $branchId,
            'role' => $isOwner ? 'owner' : 'shared',
            'can_send_to_customers' => $isOwner ? true : ($permissions['can_send_to_customers'] ?? false),
            'can_send_to_teachers' => $isOwner ? true : ($permissions['can_send_to_teachers'] ?? false),
            'can_send_to_class_groups' => $isOwner ? true : ($permissions['can_send_to_class_groups'] ?? false),
            'can_send_to_friends' => $isOwner ? true : ($permissions['can_send_to_friends'] ?? false),
            'can_send_to_groups' => $isOwner ? true : ($permissions['can_send_to_groups'] ?? false),
            'view_all_friends' => $isOwner ? true : ($permissions['view_all_friends'] ?? false),
            'view_all_groups' => $isOwner ? true : ($permissions['view_all_groups'] ?? false),
            'view_all_conversations' => $isOwner ? true : ($permissions['view_all_conversations'] ?? false),
        ]);

        Log::info('[ZaloBranchAccess] Branch access granted', [
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'role' => $access->role,
            'granted_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch access granted successfully',
            'data' => $access,
        ], 201);
    }

    /**
     * Update branch permissions
     *
     * PUT /api/zalo/branch-access/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasPermission('zalo.manage_multi_branch_access') && !$user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản lý multi-branch access.',
            ], 403);
        }

        $access = ZaloAccountBranch::find($id);

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Branch access not found',
            ], 404);
        }

        // Don't allow modifying owner permissions
        if ($access->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify owner branch permissions',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.can_send_to_customers' => 'sometimes|boolean',
            'permissions.can_send_to_teachers' => 'sometimes|boolean',
            'permissions.can_send_to_class_groups' => 'sometimes|boolean',
            'permissions.can_send_to_friends' => 'sometimes|boolean',
            'permissions.can_send_to_groups' => 'sometimes|boolean',
            'permissions.view_all_friends' => 'sometimes|boolean',
            'permissions.view_all_groups' => 'sometimes|boolean',
            'permissions.view_all_conversations' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissions = $request->input('permissions');
        $access->update($permissions);

        Log::info('[ZaloBranchAccess] Permissions updated', [
            'access_id' => $id,
            'account_id' => $access->zalo_account_id,
            'branch_id' => $access->branch_id,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully',
            'data' => $access->fresh(),
        ]);
    }

    /**
     * Revoke branch access
     *
     * DELETE /api/zalo/branch-access/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasPermission('zalo.manage_multi_branch_access') && !$user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản lý multi-branch access.',
            ], 403);
        }

        $access = ZaloAccountBranch::find($id);

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Branch access not found',
            ], 404);
        }

        // Don't allow deleting owner access
        if ($access->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke owner branch access',
            ], 403);
        }

        Log::info('[ZaloBranchAccess] Branch access revoked', [
            'access_id' => $id,
            'account_id' => $access->zalo_account_id,
            'branch_id' => $access->branch_id,
            'revoked_by' => $user->id,
        ]);

        $access->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch access revoked successfully',
        ]);
    }

    /**
     * Assign friends/groups/conversations to specific branches
     *
     * POST /api/zalo/branch-access/assign-items
     */
    public function assignItems(Request $request)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasPermission('zalo.manage_multi_branch_access') && !$user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản lý multi-branch access.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:zalo_accounts,id',
            'branch_id' => 'required|exists:branches,id',
            'item_type' => 'required|in:friends,groups,conversations',
            'item_ids' => 'required|array',
            'item_ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');
        $itemType = $request->input('item_type');
        $itemIds = $request->input('item_ids');

        // Verify branch has access to account
        $branchAccess = ZaloAccountBranch::where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$branchAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Branch does not have access to this account',
            ], 403);
        }

        // Update assigned_branch_id for items
        $modelClass = match($itemType) {
            'friends' => \App\Models\ZaloFriend::class,
            'groups' => \App\Models\ZaloGroup::class,
            'conversations' => \App\Models\ZaloConversation::class,
        };

        $updated = $modelClass::where('zalo_account_id', $accountId)
            ->whereIn('id', $itemIds)
            ->update(['assigned_branch_id' => $branchId]);

        Log::info('[ZaloBranchAccess] Items assigned to branch', [
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'item_type' => $itemType,
            'count' => $updated,
            'assigned_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$updated} {$itemType} to branch",
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }
}
