<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZaloAccount;
use App\Models\ZaloConversation;
use App\Models\ZaloFriend;
use App\Models\ZaloGroup;
use App\Models\ZaloGroupMember;
use App\Models\ZaloMessage;
use App\Models\ZaloMessageReaction;
use App\Services\ZaloNotificationService;
use App\Services\ZaloCacheService;
use App\Services\ZaloAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ZaloController extends Controller
{
    protected $zalo;
    protected $cacheService;
    protected $avatarService;

    public function __construct(
        ZaloNotificationService $zalo,
        ZaloCacheService $cacheService,
        ZaloAvatarService $avatarService
    ) {
        $this->zalo = $zalo;
        $this->cacheService = $cacheService;
        $this->avatarService = $avatarService;
    }

    /**
     * Get branch ID from request (for multi-branch support)
     */
    protected function getBranchId(Request $request): ?int
    {
        $user = $request->user();

        // Priority: 1) Selected branch from frontend, 2) User's primary_branch_id, 3) User's first branch
        return $request->input('branch_id')
            ?? $user->primary_branch_id
            ?? optional($user->branches()->first())->id;
    }

    /**
     * Validate account existence and return account or error response
     * This prevents spam requests for non-existent accounts
     */
    protected function validateAccountExists(?int $accountId)
    {
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required',
            ], 400);
        }

        $account = ZaloAccount::find($accountId);

        if (!$account) {
            Log::warning('[ZaloController] Request for non-existent account', ['account_id' => $accountId]);
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
                'account_id' => $accountId,
            ], 404);
        }

        return $account;
    }

    /**
     * Check if account is actually connected (WebSocket session + database)
     * This checks REAL connection status, not just database flag
     */
    protected function isAccountConnected(ZaloAccount $account): bool
    {
        try {
            // Check if THIS account's session is ready in zalo-service (WebSocket)
            return $this->zalo->isReady($account->id);
        } catch (\Exception $e) {
            // If error checking session, fall back to database flag
            Log::warning('[ZaloController] Failed to check session status, using database flag', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return $account->is_connected ?? false;
        }
    }

    /**
     * Get branch permission for a specific account and branch
     * Returns the permission value or null if not found
     */
    protected function getBranchPermissionValue(int $accountId, int $branchId, string $permission): ?bool
    {
        $branchAccess = \DB::table('zalo_account_branches')
            ->where('zalo_account_id', $accountId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$branchAccess) {
            // If no branch access record, check if this is the owner branch
            $account = ZaloAccount::find($accountId);
            if ($account && $account->branch_id == $branchId) {
                // Owner branch has full permissions by default
                return true;
            }
            return null;
        }

        return (bool) ($branchAccess->{$permission} ?? false);
    }

    /**
     * Check if user has a specific branch permission for the account
     * Returns true if user has permission, false otherwise
     */
    protected function checkBranchPermission(Request $request, ZaloAccount $account, string $permission): bool
    {
        $user = $request->user();

        // Super-admin always has full access (bypass all branch restrictions)
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check system-level permissions that bypass ALL branch filtering
        // Map account-level permission to system-level permission
        $systemPermissionMap = [
            'view_all_conversations' => 'zalo.view_all_branches_conversations',
            'view_all_groups' => 'zalo.view_all_branches_groups',
            'view_all_friends' => 'zalo.view_all_branches_friends',
        ];

        if (isset($systemPermissionMap[$permission]) && $user->can($systemPermissionMap[$permission])) {
            return true;
        }

        // Also check all_conversation_management permission (full access)
        if ($user->can('zalo.all_conversation_management')) {
            return true;
        }

        $branchId = $this->getBranchId($request);

        if (!$branchId) {
            // No branch context, allow if account has no primary branch (global account)
            if ($account->branch_id === null) {
                return true;
            }
            // Use account's primary branch
            $branchId = $account->branch_id;
        }

        // Check if this is the account's owner branch (primary branch always has full access)
        if ($account->branch_id == $branchId) {
            return true;
        }

        // Check the specific permission in zalo_account_branches
        $hasPermission = $this->getBranchPermissionValue($account->id, $branchId, $permission);

        return $hasPermission === true;
    }

    /**
     * Check Zalo service status
     */
    public function status(Request $request)
    {
        // MULTI-SESSION: Get account_id from request to check specific session
        $accountId = $request->input('account_id');

        return response()->json([
            'isReady' => $this->zalo->isReady($accountId),
            'timestamp' => now()->toISOString(),
            'account_id' => $accountId, // Return for debugging
        ]);
    }

    /**
     * Receive session status notification from zalo-service
     * Called when session expires or disconnects
     */
    public function sessionStatus(Request $request)
    {
        $accountId = $request->input('account_id');
        $status = $request->input('status'); // 'expired', 'disconnected', 'connected'
        $message = $request->input('message');
        $timestamp = $request->input('timestamp');

        Log::warning('[ZaloController] Session status notification received', [
            'account_id' => $accountId,
            'status' => $status,
            'message' => $message,
            'timestamp' => $timestamp,
        ]);

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required'
            ], 400);
        }

        // Find the account
        $account = ZaloAccount::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }

        // Update account status based on notification
        if ($status === 'expired' || $status === 'disconnected') {
            $account->is_connected = false;
            $account->last_error = $message;
            $account->save();

            // Send notification to admin users
            $this->notifyAdminOfSessionExpiry($account, $status, $message);
        } elseif ($status === 'connected') {
            $account->is_connected = true;
            $account->last_error = null;
            $account->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Session status updated',
            'account_id' => $accountId,
            'new_status' => $account->is_connected
        ]);
    }

    /**
     * Notify admin users when a Zalo session expires
     */
    private function notifyAdminOfSessionExpiry($account, $status, $message)
    {
        try {
            // Broadcast via Socket.IO to notify frontend
            $this->emitToSocketIO("zalo:account:{$account->id}", 'session_expired', [
                'account_id' => $account->id,
                'account_name' => $account->name ?? $account->phone,
                'status' => $status,
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ]);

            Log::info('[ZaloController] Session expiry notification sent', [
                'account_id' => $account->id,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to send session expiry notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get sync progress for friends and groups
     */
    public function getSyncProgress(Request $request)
    {
        $accountId = $request->input('account_id');

        // Validate account existence - prevents spam for non-existent accounts
        $result = $this->validateAccountExists($accountId);
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result; // Return error response
        }
        $account = $result; // Account exists

        $cacheKey = "zalo_sync_progress_{$accountId}";
        $progress = Cache::get($cacheKey, [
            'friends' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => 'Chưa bắt đầu', 'completed' => false],
            'groups' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => 'Chưa bắt đầu', 'completed' => false],
        ]);

        // ⚡ AUTO-TRIGGER: If sync hasn't started, trigger it now in background
        $syncLockKey = "zalo_sync_lock_{$accountId}";
        $friendsNotStarted = ($progress['friends']['current'] ?? 0) === 0 && !($progress['friends']['completed'] ?? false);
        $groupsNotStarted = ($progress['groups']['current'] ?? 0) === 0 && !($progress['groups']['completed'] ?? false);

        // 🔥 NEW: Check if there's an error state, don't auto-trigger if error exists
        $hasError = ($progress['friends']['error'] ?? false) || ($progress['groups']['error'] ?? false);

        if (($friendsNotStarted || $groupsNotStarted) && !Cache::has($syncLockKey) && !$hasError) {
            // Set lock to prevent multiple sync triggers (expires in 10 minutes for rate limit cooldown)
            Cache::put($syncLockKey, true, 600);

            Log::info('[ZaloController] ⚡ Auto-triggering sync for account', ['account_id' => $accountId]);

            // Trigger sync in background using fastcgi_finish_request or ignore_user_abort
            if (function_exists('fastcgi_finish_request')) {
                // Send response first, then sync in background (if using PHP-FPM)
                register_shutdown_function(function () use ($accountId) {
                    $this->triggerBackgroundSync($accountId);
                });
            } else {
                // Alternative: Use ignore_user_abort for Apache
                ignore_user_abort(true);
                set_time_limit(0);

                // Send response immediately
                response()->json([
                    'success' => true,
                    'data' => [
                        'friends' => $progress['friends'],
                        'groups' => $progress['groups'],
                        'overall_percent' => 0,
                        'completed' => false,
                        'syncing' => true,
                    ],
                ])->send();

                // Continue with sync after response sent
                $this->triggerBackgroundSync($accountId);
                exit;
            }
        }

        // Calculate overall progress
        $friendsPercent = $progress['friends']['percent'] ?? 0;
        $groupsPercent = $progress['groups']['percent'] ?? 0;
        $overallPercent = ($friendsPercent + $groupsPercent) / 2;

        $allCompleted = ($progress['friends']['completed'] ?? false) && ($progress['groups']['completed'] ?? false);

        return response()->json([
            'success' => true,
            'data' => [
                'friends' => $progress['friends'],
                'groups' => $progress['groups'],
                'overall_percent' => round($overallPercent),
                'completed' => $allCompleted,
            ],
        ]);
    }

    /**
     * Trigger sync in background
     */
    private function triggerBackgroundSync($accountId)
    {
        try {
            $account = \App\Models\ZaloAccount::find($accountId);
            if (!$account) {
                Log::error('[ZaloController] Account not found for background sync', ['account_id' => $accountId]);
                return;
            }

            Log::info('[ZaloController] 🚀 Starting background sync', ['account_id' => $accountId]);

            // Sync friends
            try {
                $this->syncFriends($account);
                Log::info('[ZaloController] ✅ Background sync: Friends completed');
            } catch (\Exception $e) {
                Log::error('[ZaloController] ⚠️ Background sync: Friends failed', ['error' => $e->getMessage()]);
            }

            // Sync groups
            try {
                $this->syncGroups($account);
                Log::info('[ZaloController] ✅ Background sync: Groups completed');
            } catch (\Exception $e) {
                Log::error('[ZaloController] ⚠️ Background sync: Groups failed', ['error' => $e->getMessage()]);
            }

            Log::info('[ZaloController] 🎉 Background sync completed');
        } catch (\Exception $e) {
            Log::error('[ZaloController] ❌ Background sync exception', ['error' => $e->getMessage()]);
        } finally {
            // Clear the lock
            Cache::forget("zalo_sync_lock_{$accountId}");
        }
    }

    /**
     * Get unread message counts
     * Supports 3 levels:
     * 1. Total unread across all accounts (no params)
     * 2. Unread for specific account (account_id)
     * 3. Unread for specific conversation (account_id + recipient_id)
     */
    public function getUnreadCounts(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');

        try {
            if ($accountId && $recipientId) {
                // Level 3: Specific conversation
                // Check if account is connected (WebSocket session + database)
                $account = ZaloAccount::find($accountId);
                if (!$account || !$this->isAccountConnected($account)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại.',
                        'is_connected' => false,
                        'data' => [
                            'unread_count' => 0,
                            'account_id' => $accountId,
                            'recipient_id' => $recipientId,
                        ],
                    ], 403);
                }

                // 🔥 PERMISSION CHECK: Only count if user can view this conversation
                $conversation = \App\Models\ZaloConversation::where('zalo_account_id', $accountId)
                    ->where('recipient_id', $recipientId)
                    ->accessibleBy($user)
                    ->first();

                if (!$conversation) {
                    // User doesn't have permission to view this conversation
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'unread_count' => 0,
                            'account_id' => $accountId,
                            'recipient_id' => $recipientId,
                        ],
                    ]);
                }

                $count = ZaloMessage::where('zalo_account_id', $accountId)
                    ->where('recipient_id', $recipientId)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->count();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'unread_count' => $count,
                        'account_id' => $accountId,
                        'recipient_id' => $recipientId,
                    ],
                ]);
            } elseif ($accountId) {
                // Level 2: Specific account
                // Check if account is connected (WebSocket session + database)
                $account = ZaloAccount::find($accountId);
                if (!$account || !$this->isAccountConnected($account)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại.',
                        'is_connected' => false,
                        'data' => [
                            'total_unread' => 0,
                            'account_id' => $accountId,
                            'by_conversation' => [],
                        ],
                    ], 403);
                }

                // 🔥 PERMISSION FILTERING: Only get accessible conversation IDs
                $accessibleConversationRecipientIds = \App\Models\ZaloConversation::where('zalo_account_id', $accountId)
                    ->accessibleBy($user)
                    ->pluck('recipient_id')
                    ->toArray();

                // Only count unread messages from accessible conversations
                $count = ZaloMessage::where('zalo_account_id', $accountId)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->whereIn('recipient_id', $accessibleConversationRecipientIds)
                    ->count();

                // Also get per-conversation breakdown (only accessible conversations)
                $byConversation = ZaloMessage::where('zalo_account_id', $accountId)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->whereIn('recipient_id', $accessibleConversationRecipientIds)
                    ->selectRaw('recipient_id, recipient_name, recipient_type, COUNT(*) as unread_count')
                    ->groupBy('recipient_id', 'recipient_name', 'recipient_type')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_unread' => $count,
                        'account_id' => $accountId,
                        'by_conversation' => $byConversation,
                    ],
                ]);
            } else {
                // Level 1: Total across all accounts - ONLY connected accounts & accessible conversations
                // Filter by branch if provided
                $branchId = $request->input('branch_id');
                
                $connectedAccountsQuery = ZaloAccount::where('is_connected', true);
                
                if ($branchId) {
                    $connectedAccountsQuery->where('branch_id', $branchId);
                }
                
                $connectedAccountIds = $connectedAccountsQuery->pluck('id');

                // 🔥 PERMISSION FILTERING: Get all accessible conversations for this user
                $accessibleConversations = \App\Models\ZaloConversation::whereIn('zalo_account_id', $connectedAccountIds)
                    ->accessibleBy($user)
                    ->select('zalo_account_id', 'recipient_id')
                    ->get();

                // Group by account_id for filtering
                $accessibleByAccount = [];
                foreach ($accessibleConversations as $conv) {
                    $accountId = $conv->zalo_account_id;
                    if (!isset($accessibleByAccount[$accountId])) {
                        $accessibleByAccount[$accountId] = [];
                    }
                    $accessibleByAccount[$accountId][] = $conv->recipient_id;
                }

                // Count unread messages only from accessible conversations
                $totalCount = 0;
                $byAccount = [];

                foreach ($accessibleByAccount as $accountId => $recipientIds) {
                    $count = ZaloMessage::where('zalo_account_id', $accountId)
                        ->where('type', 'received')
                        ->whereNull('read_at')
                        ->whereIn('recipient_id', $recipientIds)
                        ->count();

                    if ($count > 0) {
                        $byAccount[] = [
                            'zalo_account_id' => $accountId,
                            'unread_count' => $count,
                        ];
                        $totalCount += $count;
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_unread' => $totalCount,
                        'by_account' => $byAccount,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Error getting unread counts', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread counts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $messageIds = $request->input('message_ids'); // Optional: specific message IDs

        if (!$accountId || !$recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and recipient_id are required',
            ], 400);
        }

        try {
            $query = ZaloMessage::where('zalo_account_id', $accountId)
                ->where('recipient_id', $recipientId)
                ->where('type', 'received')
                ->whereNull('read_at');

            // If specific message IDs provided, only mark those
            if ($messageIds && is_array($messageIds) && count($messageIds) > 0) {
                $query->whereIn('id', $messageIds);
            }

            $updated = $query->update([
                'read_at' => now(),
                'status' => 'read',
            ]);

            Log::info('[ZaloController] Marked messages as read', [
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'updated_count' => $updated,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'marked_count' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Error marking messages as read', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh account info from Zalo service
     * This can be called to update account name/avatar if missing
     */
    public function refreshAccountInfo(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required',
            ], 400);
        }
        
        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission to access it.',
            ], 404);
        }
        
        // Check permission: user chỉ có thể refresh account được gán cho mình, trừ admin/super-admin
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && $account->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền refresh thông tin tài khoản Zalo này.',
            ], 403);
        }
        
        try {
            // Get account info from zalo-service (MULTI-SESSION: pass accountId)
            $accountInfo = $this->zalo->getAccountInfo($accountId);

            Log::info('[ZaloController] Refresh account info - accountInfo received', [
                'account_id' => $accountId,
                'has_accountInfo' => !empty($accountInfo),
                'has_name' => isset($accountInfo['name']) && !empty($accountInfo['name']),
                'has_avatar_url' => isset($accountInfo['avatar_url']) && !empty($accountInfo['avatar_url']),
                'name' => $accountInfo['name'] ?? null,
                'avatar_url_preview' => isset($accountInfo['avatar_url']) ? substr($accountInfo['avatar_url'], 0, 50) . '...' : null,
            ]);
            
            if (!$accountInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get account information from Zalo service. Please ensure Zalo service is connected.',
                ], 400);
            }
            

            // ===== DUPLICATE DETECTION =====
            // Check if this Zalo account already exists in another branch
            $zaloId = $accountInfo['zalo_account_id'] ?? null;
            $phone = $accountInfo['phone'] ?? null;
            $existingAccount = null;

            // Try by zalo_id first (if available and not temporary)
            if (!empty($zaloId) && !str_starts_with($zaloId, 'temp_') && !str_starts_with($zaloId, 'cookie_')) {
                $existingAccount = ZaloAccount::where('zalo_id', $zaloId)
                    ->where('id', '!=', $account->id) // Exclude current account
                    ->first();
                if ($existingAccount) {
                    Log::info('[ZaloController] Found duplicate account by zalo_id', [
                        'existing_account_id' => $existingAccount->id,
                        'existing_branch_id' => $existingAccount->branch_id,
                        'new_account_id' => $account->id,
                        'new_branch_id' => $account->branch_id,
                        'zalo_id' => $zaloId,
                    ]);
                }
            }

            // If not found and phone available, try by phone number
            if (!$existingAccount && !empty($phone)) {
                $existingAccount = ZaloAccount::where('phone', $phone)
                    ->where('id', '!=', $account->id)
                    ->whereNotNull('phone')
                    ->first();
                if ($existingAccount) {
                    Log::info('[ZaloController] Found duplicate account by phone', [
                        'existing_account_id' => $existingAccount->id,
                        'existing_branch_id' => $existingAccount->branch_id,
                        'new_account_id' => $account->id,
                        'new_branch_id' => $account->branch_id,
                        'phone' => $phone,
                    ]);
                }
            }

            // If duplicate found, handle multi-branch access
            $isDuplicate = false;
            if ($existingAccount) {
                $isDuplicate = true;
                Log::info('[ZaloController] Duplicate detected - will update existing account with new session');

                // IMPORTANT: Update existing account with NEW session data
                // The new session will invalidate the old session
                $existingUpdateData = [];

                // Update cookie with new session
                if (!empty($accountInfo['cookie'])) {
                    $existingUpdateData['cookie'] = encrypt(json_encode($accountInfo['cookie']));
                    Log::info('[ZaloController] Updating existing account with new session cookie');
                }

                // Update zalo_id if missing
                if (!empty($zaloId) && empty($existingAccount->zalo_id)) {
                    $existingUpdateData['zalo_id'] = $zaloId;
                }

                // Update name if provided
                if (!empty($accountInfo['name'])) {
                    $existingUpdateData['name'] = $accountInfo['name'];
                }

                // Update phone if provided
                if (!empty($phone)) {
                    $existingUpdateData['phone'] = $phone;
                }

                // Update avatar if provided
                if (!empty($accountInfo['avatar_url'])) {
                    $existingUpdateData['avatar_url'] = $accountInfo['avatar_url'];
                }

                // Mark as active and update login time
                $existingUpdateData['is_active'] = true;
                $existingUpdateData['is_connected'] = true;
                $existingUpdateData['last_login_at'] = now();

                // Apply updates to existing account
                $existingAccount->update($existingUpdateData);
                Log::info('[ZaloController] Updated existing account with new session', [
                    'account_id' => $existingAccount->id,
                    'updated_fields' => array_keys($existingUpdateData),
                ]);

                // Create branch access record if not exists (with full permissions by default)
                $branchId = $this->getBranchId($request);
                if ($branchId && $branchId !== $existingAccount->branch_id) {
                    \DB::table('zalo_account_branches')->insertOrIgnore([
                        'zalo_account_id' => $existingAccount->id,
                        'branch_id' => $branchId,
                        'role' => 'shared',
                        'can_send_message' => true,
                        'view_all_friends' => true,
                        'view_all_groups' => true,
                        'view_all_conversations' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('[ZaloController] Created branch access link with full permissions', [
                        'zalo_account_id' => $existingAccount->id,
                        'branch_id' => $branchId,
                    ]);
                }

                // Permanently delete the newly created blank account (use forceDelete to bypass soft delete)
                $account->forceDelete();
                Log::info('[ZaloController] Permanently deleted duplicate blank account', ['deleted_account_id' => $account->id]);

                // Use the existing account for the rest of the flow
                $account = $existingAccount;
                Log::info('[ZaloController] Using existing account with updated session', ['account_id' => $account->id]);

                // IMPORTANT: Return early to prevent branch_id from being overwritten
                // The duplicate account should keep its original branch_id
                // Multi-branch access is handled via zalo_account_branches table
                return response()->json([
                    'success' => true,
                    'message' => 'Account info refreshed (multi-branch access)',
                    'data' => [
                        'id' => $account->id,
                        'name' => $account->name,
                        'phone' => $account->phone,
                        'avatar_url' => $this->avatarService->getAvatarUrl($account),
                    ],
                ]);
            }

            // Continue normal update flow for NEW accounts (not duplicates)
            // Update account with new info
            $updateData = [];

            // CRITICAL: Update zalo_id if not set (for new accounts)
            if (!empty($accountInfo['zalo_account_id']) && empty($account->zalo_id)) {
                $updateData['zalo_id'] = $accountInfo['zalo_account_id'];
                Log::info('[ZaloController] Will update zalo_id', ['zalo_id' => $accountInfo['zalo_account_id']]);
            }

            // CRITICAL: Update cookie (for session persistence)
            if (!empty($accountInfo['cookie'])) {
                $updateData['cookie'] = encrypt(json_encode($accountInfo['cookie']));
                Log::info('[ZaloController] Will update cookie');
            }

            // Update branch_id from current user context
            $branchId = $this->getBranchId($request);
            if ($branchId && (!$account->branch_id || $account->branch_id !== $branchId)) {
                $updateData['branch_id'] = $branchId;
                Log::info('[ZaloController] Will update branch_id', ['branch_id' => $branchId]);
            }

            if (!empty($accountInfo['name']) && $accountInfo['name'] !== $account->name) {
                $updateData['name'] = $accountInfo['name'];
                Log::info('[ZaloController] Will update name', ['new_name' => $accountInfo['name']]);
            } elseif (empty($account->name) && !empty($account->zalo_id)) {
                $updateData['name'] = 'Zalo Account ' . substr($account->zalo_id, -6);
                Log::info('[ZaloController] Using fallback name', ['fallback_name' => $updateData['name']]);
            }

            if (!empty($accountInfo['phone']) && $accountInfo['phone'] !== $account->phone) {
                $updateData['phone'] = $accountInfo['phone'];
            }

            if (!empty($accountInfo['avatar_url']) && $accountInfo['avatar_url'] !== $account->avatar_url) {
                $updateData['avatar_url'] = $accountInfo['avatar_url'];
                Log::info('[ZaloController] Will update avatar_url', ['avatar_url_preview' => substr($accountInfo['avatar_url'], 0, 50) . '...']);
            } else {
                Log::warning('[ZaloController] No avatar_url in accountInfo', [
                    'accountInfo_keys' => array_keys($accountInfo),
                    'has_avatar_url' => isset($accountInfo['avatar_url']),
                    'avatar_url_value' => $accountInfo['avatar_url'] ?? 'null',
                ]);
            }

            // 🔥 FIX: Deactivate ALL other accounts before activating this one
            // This ensures ONLY ONE account is active at a time
            ZaloAccount::accessibleBy($user)->where('id', '!=', $account->id)->update(['is_active' => false]);

            // Mark as active, connected, and update last_login_at
            $updateData['is_active'] = true;
            $updateData['is_connected'] = true; // Mark as connected after successful login
            $updateData['status'] = 'active'; // Mark as active (no longer pending)
            $updateData['last_login_at'] = now();

            if (!empty($updateData)) {
                $account->update($updateData);

                Log::info('[ZaloController] Account info refreshed', [
                    'account_id' => $account->id,
                    'updated_fields' => array_keys($updateData),
                ]);
            }

            // 🚀 Sync friends and groups after successful login
            Log::info('[ZaloController] 🚀 Starting sync after new account login...');
            try {
                // Sync friends (non-blocking, best effort)
                try {
                    $this->syncFriends($account);
                    Log::info('[ZaloController] ✅ Friends synced successfully');
                } catch (\Exception $e) {
                    Log::error('[ZaloController] ⚠️  Failed to sync friends', [
                        'error' => $e->getMessage(),
                    ]);
                }

                // Sync groups (non-blocking, best effort)
                try {
                    $this->syncGroups($account);
                    Log::info('[ZaloController] ✅ Groups synced successfully');
                } catch (\Exception $e) {
                    Log::error('[ZaloController] ⚠️  Failed to sync groups', [
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('[ZaloController] 🎉 Sync completed after new account login!');
            } catch (\Exception $e) {
                // Don't fail refresh if sync fails
                Log::error('[ZaloController] ⚠️  Exception during sync', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account info refreshed',
                'data' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'phone' => $account->phone,
                    'avatar_url' => $this->avatarService->getAvatarUrl($account),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Refresh account info exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh account info: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize Zalo with QR code
     */
    public function initialize(Request $request)
    {
        $user = $request->user();
        $forceNew = $request->boolean('forceNew', false) || $request->boolean('force_new', false);
        $accountId = $request->input('account_id');

        Log::info('[ZaloController] Initialize endpoint called', [
            'forceNew' => $forceNew,
            'accountId' => $accountId,
            'user_id' => $user->id,
        ]);

        // Clean up old pending records (older than 10 minutes) - orphan records
        // Use DB query to permanently delete (bypass soft delete)
        $branchId = $this->getBranchId($request);
        $cleanupQuery = \DB::table('zalo_accounts')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(10));

        // If branch is specified, clean up for that branch; otherwise clean up all orphans
        if ($branchId) {
            $cleanupQuery->where('branch_id', $branchId);
        }

        $deletedCount = $cleanupQuery->delete();

        if ($deletedCount > 0) {
            Log::info('[ZaloController] Cleaned up old pending accounts', [
                'deleted_count' => $deletedCount,
                'branch_id' => $branchId,
            ]);
        }

        // MULTI-SESSION: If no accountId provided and forceNew=true, create new account
        if (!$accountId && $forceNew) {
            Log::info('[ZaloController] Creating new Zalo account for multi-session');

            // Create new account record with pending status
            $account = ZaloAccount::create([
                'branch_id' => $branchId ?: ($user->primary_branch_id ?? null),
                'assigned_to' => $user->id,
                'is_active' => false, // Will be activated after successful login
                'status' => 'pending', // Pending until login completes
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $accountId = $account->id;

            Log::info('[ZaloController] New account created', [
                'account_id' => $accountId,
            ]);
        }

        // If still no accountId, use the first active account (backward compatibility)
        if (!$accountId) {
            $account = ZaloAccount::accessibleBy($user)
                ->where('is_active', true)
                ->first();

            if (!$account) {
                // No active account found, create new one with pending status
                $account = ZaloAccount::create([
                    'branch_id' => $branchId ?: ($user->primary_branch_id ?? null),
                    'assigned_to' => $user->id,
                    'is_active' => false,
                    'status' => 'pending', // Pending until login completes
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $accountId = $account->id;
        }

        // Call zalo service with accountId
        $result = $this->zalo->initialize($accountId, $forceNew);

        Log::info('[ZaloController] Initialize result', [
            'success' => $result['success'] ?? false,
            'hasQrCode' => isset($result['qrCode']),
            'alreadyLoggedIn' => $result['alreadyLoggedIn'] ?? false,
            'account_id' => $accountId,
        ]);

        // Add accountId to response for frontend
        $result['account_id'] = $accountId;

        return response()->json($result);
    }

    /**
     * Cancel login and clean up pending account
     */
    public function cancelLogin(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');

        Log::info('[ZaloController] Cancel login requested', [
            'account_id' => $accountId,
            'user_id' => $user->id,
        ]);

        if ($accountId) {
            // Check if user can access this account
            $account = ZaloAccount::accessibleBy($user)
                ->where('id', $accountId)
                ->where('status', 'pending')
                ->first();

            if ($account) {
                // Permanently delete the pending account
                $account->forceDelete();
                Log::info('[ZaloController] Pending account permanently deleted', [
                    'account_id' => $accountId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pending account cancelled and deleted',
                ]);
            }
        }

        // Also clean up any old pending accounts for the user's branches (permanently)
        $branchId = $this->getBranchId($request);
        $cleanupQuery = \DB::table('zalo_accounts')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5));

        if ($branchId) {
            $cleanupQuery->where('branch_id', $branchId);
        }

        $cleanedUp = $cleanupQuery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login cancelled',
            'cleaned_up' => $cleanedUp,
        ]);
    }

    /**
     * Get stats
     */
    public function stats()
    {
        // TODO: Implement from database
        return response()->json([
            'messagesToday' => 0,
            'totalFriends' => 0,
        ]);
    }

    /**
     * Get friends list - Load from database first, then sync from API
     * Với phân quyền: chỉ xem friends của accounts mà user có quyền
     */
    public function getFriends(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $sync = $request->boolean('sync', false);
        $branchId = $request->input('branch_id');

        // Get active account với phân quyền
        if ($accountId) {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
        } else {
            $query = ZaloAccount::active()->accessibleBy($user);
            if ($branchId) {
                $query->forBranch($branchId);
            }
            $account = $query->first();
        }

        if (!$account) {
            return response()->json([
                'success' => true,
                'message' => 'No active Zalo account found. Please add an account first.',
                'data' => [],
                'cached' => false,
            ]);
        }

        // Check if account is connected (WebSocket session + database)
        if (!$this->isAccountConnected($account)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại để xem danh sách bạn bè.',
                'data' => [],
                'is_connected' => false,
                'account_name' => $account->name,
            ], 403);
        }

        // Check branch permission: view_all_friends
        if (!$this->checkBranchPermission($request, $account, 'view_all_friends')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem danh sách bạn bè của tài khoản này.',
                'data' => [],
                'permission_denied' => true,
            ], 403);
        }

        // Check if user should bypass branch filtering
        $bypassBranchFilter = $user->hasRole('super-admin')
            || $user->can('zalo.view_all_branches_friends')
            || $user->can('zalo.all_conversation_management');

        // Load from database first (friends are now shared via zalo_id)
        $friendsQuery = ZaloFriend::forAccount($account->id);

        // Filter by branch if specified AND user doesn't have bypass permission
        if ($branchId && !$bypassBranchFilter) {
            $friendsQuery->where(function($q) use ($branchId) {
                $q->where('assigned_branch_id', $branchId)
                  ->orWhereNull('assigned_branch_id'); // Include global friends
            });
        }

        $friends = $friendsQuery->get()
            ->map(function ($friend) {
                return [
                    'id' => $friend->zalo_user_id,
                    'name' => $friend->name,
                    'phone' => $friend->phone,
                    'avatar_url' => $this->avatarService->getAvatarUrl($friend),
                    'avatar_path' => $friend->avatar_path,
                    'bio' => $friend->bio,
                ];
            });

        // If sync requested or no cached data, sync from API
        if ($sync || $friends->isEmpty()) {
            try {
                // MULTI-SESSION: Pass account ID to get friends for the correct account
                $friendsFromApi = $this->zalo->getFriends($account->id);
                if (!empty($friendsFromApi)) {
                    $this->cacheService->syncFriends($account, $friendsFromApi);

                    // Reload from database after sync with same branch filtering logic
                    $reloadQuery = ZaloFriend::forAccount($account->id);

                    // Apply same branch filter as initial load
                    if ($branchId && !$bypassBranchFilter) {
                        $reloadQuery->where(function($q) use ($branchId) {
                            $q->where('assigned_branch_id', $branchId)
                              ->orWhereNull('assigned_branch_id');
                        });
                    }

                    $friends = $reloadQuery->get()
                        ->map(function ($friend) {
                            return [
                                'id' => $friend->zalo_user_id,
                                'name' => $friend->name,
                                'phone' => $friend->phone,
                                'avatar_url' => $this->avatarService->getAvatarUrl($friend),
                                'avatar_path' => $friend->avatar_path,
                                'bio' => $friend->bio,
                            ];
                        });
                }
            } catch (\Exception $e) {
                Log::error('[Zalo] Failed to sync friends', ['error' => $e->getMessage()]);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $friends->values(),
            'cached' => !$sync,
        ]);
    }

    /**
     * Get groups list - Load from database first, then sync from API
     * Với phân quyền: chỉ xem groups của accounts mà user có quyền
     */
    public function getGroups(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $sync = $request->boolean('sync', false);
        $branchId = $request->input('branch_id');

        // Get active account với phân quyền
        if ($accountId) {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
        } else {
            $query = ZaloAccount::active()->accessibleBy($user);
            if ($branchId) {
                $query->forBranch($branchId);
            }
            $account = $query->first();
        }

        if (!$account) {
            return response()->json([
                'success' => true,
                'message' => 'No active Zalo account found. Please add an account first.',
                'data' => [],
                'cached' => false,
            ]);
        }

        // Check if account is connected (WebSocket session + database)
        if (!$this->isAccountConnected($account)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại để xem danh sách nhóm.',
                'data' => [],
                'is_connected' => false,
                'account_name' => $account->name,
            ], 403);
        }

        // Check branch permission: view_all_groups
        if (!$this->checkBranchPermission($request, $account, 'view_all_groups')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem danh sách nhóm của tài khoản này.',
                'data' => [],
                'permission_denied' => true,
            ], 403);
        }

        // Check if user should bypass branch filtering
        $user = $request->user();
        $bypassBranchFilter = $user->hasRole('super-admin')
            || $user->can('zalo.view_all_branches_groups')
            || $user->can('zalo.all_conversation_management');

        // Load groups for this specific account
        Log::info('[ZaloController] getGroups - loading groups for account', [
            'account_id' => $account->id,
            'branch_id' => $branchId,
            'bypass_branch_filter' => $bypassBranchFilter,
        ]);

        // Load from database first with members count
        $groupsQuery = ZaloGroup::where('zalo_account_id', $account->id)
            ->withCount('members');

        // Filter by branch if specified AND user doesn't have bypass permission
        if ($branchId && !$bypassBranchFilter) {
            $groupsQuery->where(function($q) use ($branchId) {
                $q->where('assigned_branch_id', $branchId)
                  ->orWhereNull('assigned_branch_id'); // Include global groups
            });
        }

        $groups = $groupsQuery->get();

        Log::info('[ZaloController] Groups loaded', [
            'count' => $groups->count(),
            'account_id' => $account->id,
        ]);

        // Load conversations for these groups to get last_message_preview
        $groupIds = $groups->pluck('zalo_group_id')->toArray();
        $conversations = [];
        if (!empty($groupIds)) {
            $conversations = ZaloConversation::where('zalo_account_id', $account->id)
                ->whereIn('recipient_id', $groupIds)
                ->where('recipient_type', 'group')
                ->get()
                ->keyBy('recipient_id');
        }

        // Collect all department IDs
        $departmentIds = $groups->pluck('department_id')->filter()->unique()->toArray();

        $departments = [];
        if (!empty($departmentIds)) {
            $departments = \App\Models\Department::whereIn('id', $departmentIds)
                ->get()
                ->keyBy('id');
        }

        // Load branches for groups that have branch_id
        $branchIds = $groups->pluck('branch_id')->filter()->unique()->toArray();
        $branches = [];
        if (!empty($branchIds)) {
            $branches = \App\Models\Branch::whereIn('id', $branchIds)
                ->get()
                ->keyBy('id');
        }

        $groups = $groups->map(function ($group) use ($conversations, $branches, $departments) {
            $conversation = $conversations[$group->zalo_group_id] ?? null;

            return [
                'id' => $group->zalo_group_id,
                'name' => $group->name,
                'description' => $group->description,
                'avatar_url' => $this->avatarService->getAvatarUrl($group),
                'avatar_path' => $group->avatar_path,
                'members_count' => $group->members_count ?? 0,
                'admin_ids' => $group->admin_ids,
                'creator_id' => $group->creator_id,
                'type' => $group->type,
                'branch_id' => $group->branch_id,
                'branch' => $group->branch_id && isset($branches[$group->branch_id]) ? [
                    'id' => $branches[$group->branch_id]->id,
                    'name' => $branches[$group->branch_id]->name,
                    'code' => $branches[$group->branch_id]->code,
                ] : null,
                'department_id' => $group->department_id,
                'department' => $group->department_id && isset($departments[$group->department_id]) ? [
                    'id' => $departments[$group->department_id]->id,
                    'name' => $departments[$group->department_id]->name,
                ] : null,
                // Add conversation data
                'last_message_preview' => $conversation ? $conversation->last_message_preview : null,
                'last_message_at' => $conversation ? $conversation->last_message_at : null,
                'unread_count' => $conversation ? $conversation->unread_count : 0,
            ];
        });
        
        // If sync requested or no cached data, sync from API
        if ($sync || $groups->isEmpty()) {
            try {
                Log::info('[ZaloController] Syncing groups from API', [
                    'account_id' => $account->id,
                    'sync' => $sync,
                    'cached_count' => $groups->count(),
                ]);

                // MULTI-SESSION: Pass account ID to get groups for the correct account
                $groupsFromApi = $this->zalo->getGroups($account->id);
                
                Log::info('[ZaloController] Groups from API received', [
                    'count' => count($groupsFromApi),
                    'first_group_sample' => $groupsFromApi[0] ?? null,
                ]);
                
                if (!empty($groupsFromApi)) {
                    try {
                        $syncResult = $this->cacheService->syncGroups($account, $groupsFromApi);
                        Log::info('[ZaloController] Groups sync completed', $syncResult);
                    } catch (\Exception $syncError) {
                        Log::error('[ZaloController] Groups sync failed', [
                            'error' => $syncError->getMessage(),
                            'trace' => $syncError->getTraceAsString(),
                        ]);
                        // Continue to reload from database even if sync failed
                    }
                } else {
                    Log::warning('[ZaloController] No groups from API', [
                        'account_id' => $account->id,
                    ]);
                }
                
                // Reload from database after sync (avatars may have been downloaded)
                $groups = ZaloGroup::forAccount($account->id)
                    ->get()
                    ->map(function ($group) {
                        return [
                            'id' => $group->zalo_group_id,
                            'name' => $group->name,
                            'description' => $group->description,
                            'avatar_url' => $this->avatarService->getAvatarUrl($group),
                            'avatar_path' => $group->avatar_path,
                            'members_count' => $group->members_count,
                            'admin_ids' => $group->admin_ids,
                            'creator_id' => $group->creator_id,
                            'type' => $group->type,
                        ];
                    });
            } catch (\Exception $e) {
                Log::error('[ZaloController] Failed to sync groups', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Return empty array on error instead of failing completely
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $groups->values(),
            'cached' => !$sync,
        ]);
    }

    /**
     * Get group members
     */
    public function getGroupMembers(Request $request, $groupId)
    {
        Log::info('🔥🔥🔥 [ZaloController] getGroupMembers called!', [
            'groupId' => $groupId,
            'user_id' => $request->user()?->id,
            'request_params' => $request->all(),
        ]);
        
        try {
            $user = $request->user();
            $accountId = $request->input('account_id');
            $sync = $request->boolean('sync', false);
            
            // Get active account
            if ($accountId) {
                $account = ZaloAccount::accessibleBy($user)->find($accountId);
            } else {
                $account = ZaloAccount::active()->accessibleBy($user)->first();
            }
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active Zalo account found',
                ], 404);
            }

            // Check if account is connected (WebSocket session + database)
            if (!$this->isAccountConnected($account)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại để xem thành viên nhóm.',
                    'data' => [],
                    'is_connected' => false,
                    'account_name' => $account->name,
                ], 403);
            }

            // Use single account only (no multi-branch sharing)
            $accountIds = [$account->id];

            // 🔧 FIX: Use raw SQL to avoid BigInt/String data type issues with Eloquent
            $groupRecord = \DB::selectOne(
                "SELECT id, members_count FROM zalo_groups
                 WHERE zalo_account_id = ? AND zalo_group_id = ?
                 LIMIT 1",
                [$account->id, $groupId]
            );

            if (!$groupRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found',
                ], 404);
            }

            $group = ZaloGroup::find($groupRecord->id); // Load full model using DB ID
            
            if (!$group) {
                Log::error('❌ [ZaloController] Group model not found after DB query', [
                    'group_record_id' => $groupRecord->id ?? 'null',
                    'group_id' => $groupId,
                    'account_id' => $account->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Group record not found',
                ], 404);
            }
            
            // Load from database first
            $members = $group->members()->get()->map(function ($member) {
                return [
                    'id' => $member->zalo_user_id,
                    'display_name' => $member->display_name,
                    'avatar_url' => $member->avatar_url,
                    'avatar_path' => $member->avatar_path,
                    'is_admin' => $member->is_admin,
                ];
            });
            
            Log::info('🔍 [ZaloController] Cached members from DB', [
                'count' => $members->count(),
                'first_member' => $members->first(),
                'all_display_names' => $members->pluck('display_name')->toArray(),
            ]);
            
            // Check if members need sync (empty, Unknown, "User XXXX" pattern names, or missing avatars)
            $needsSync = $members->isEmpty() || $members->contains(function ($member) {
                return empty($member['display_name'])
                    || $member['display_name'] === 'Unknown'
                    || preg_match('/^User \d+$/', $member['display_name']) // Match "User 1234" pattern
                    || empty($member['avatar_url']); // ✅ FIX: Also sync if avatar is missing
            });
            
            Log::info('🔍 [ZaloController] Sync check', [
                'sync_requested' => $sync,
                'needs_sync' => $needsSync,
                'will_sync' => $sync || $needsSync,
            ]);
            
            // If sync requested or needs sync, fetch from API
            if ($sync || $needsSync) {
                try {
                    Log::info('[ZaloController] Fetching group members from zalo-service', [
                        'group_id' => $groupId,
                        'account_id' => $account->id,
                    ]);
                    
                    // Call zalo-service API
                    $response = Http::timeout(60)->withHeaders([
                        'X-API-Key' => config('services.zalo.api_key'),
                    ])->get(config('services.zalo.base_url') . '/api/group/members/' . $groupId, [
                        'accountId' => $account->id,
                    ]);
                    
                    if (!$response->successful() || !$response->json('success')) {
                        throw new \Exception('Failed to fetch members from zalo-service: ' . ($response->json('message') ?? 'Unknown error'));
                    }
                    
                    $membersFromApi = $response->json('data', []);
                    
                    Log::info('[ZaloController] Group members from zalo-service received', [
                        'count' => is_array($membersFromApi) ? count($membersFromApi) : 0,
                        'first_member_sample' => $membersFromApi[0] ?? null,
                    ]);
                    
                    if (!empty($membersFromApi) && is_array($membersFromApi)) {
                        // Sync to database
                        foreach ($membersFromApi as $memberData) {
                            $userId = $memberData['id'] ?? $memberData['userId'] ?? $memberData['zalo_user_id'] ?? null;
                            if (!$userId) continue;
                            
                            $displayName = $memberData['name'] ?? $memberData['display_name'] ?? $memberData['displayName'] ?? 'Unknown';
                            $avatarUrl = $memberData['avatar'] ?? $memberData['avatar_url'] ?? $memberData['avatarUrl'] ?? null;
                            $isAdmin = $memberData['is_admin'] ?? $memberData['isAdmin'] ?? false;
                            
                            ZaloGroupMember::updateOrCreate(
                                [
                                    'zalo_group_id' => $group->id,
                                    'zalo_user_id' => $userId,
                                ],
                                [
                                    'display_name' => $displayName,
                                    'avatar_url' => $avatarUrl,
                                    'is_admin' => $isAdmin,
                                    'metadata' => $memberData,
                                ]
                            );
                        }
                        
                        // Update members count
                        $group->update([
                            'members_count' => count($membersFromApi),
                            'last_sync_at' => now(),
                        ]);
                        
                        // Reload from database
                        $members = $group->members()->get()->map(function ($member) {
                            return [
                                'id' => $member->zalo_user_id,
                                'display_name' => $member->display_name,
                                'avatar_url' => $member->avatar_url,
                                'avatar_path' => $member->avatar_path,
                                'is_admin' => $member->is_admin,
                            ];
                        });
                        
                        Log::info('[ZaloController] Group members synced successfully', [
                            'group_id' => $groupId,
                            'members_count' => $members->count(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[ZaloController] Failed to fetch group members', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Return cached data if sync fails
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $members->values(),
                'count' => $members->count(),
                'cached' => !$sync,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Get group members error', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get group members: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get message history - với phân quyền
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');
        $perPage = $request->input('per_page', 15);

        // Get accounts mà user có quyền
        $query = ZaloAccount::accessibleBy($user);
        if ($branchId) {
            $query->forBranch($branchId);
        }
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        // Filter connected accounts (check WebSocket session, not just database)
        $connectedAccountIds = $accounts->filter(function ($account) {
            return $this->isAccountConnected($account);
        })->pluck('id');

        // If no connected accounts, return empty
        if ($connectedAccountIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có tài khoản Zalo nào đã kết nối. Vui lòng đăng nhập tài khoản để xem lịch sử tin nhắn.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
                'is_connected' => false,
            ], 403);
        }

        // Get messages từ các accounts mà user có quyền
        $messages = ZaloMessage::whereIn('zalo_account_id', $connectedAccountIds)
            ->with(['account:id,name,zalo_id'])
            ->latest('sent_at')
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Get settings
     */
    public function getSettings()
    {
        return response()->json([
            'serviceUrl' => config('services.zalo.base_url'),
            'apiKey' => '••••••••••',
            'notifyNewHomework' => true,
            'notifyHomeworkReminder' => true,
            'notifyScore' => true,
        ]);
    }

    /**
     * Save settings
     */
    public function saveSettings(Request $request)
    {
        // TODO: Implement settings save
        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get all Zalo accounts - với phân quyền
     * - Super-admin OR zalo.manage_accounts: see ALL accounts
     * - Other users: see ONLY primary account
     */
    public function getAccounts(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');
        $assignedTo = $request->input('assigned_to');

        $query = ZaloAccount::with([
            'branch:id,code,name',
            'assignedUser:id,name,email',
        ])
        ->basedOnManagePermission($user, $branchId); // ← Filter based on manage permission

        // Additional filters (only if user can manage accounts)
        $canManage = $user->hasRole('super-admin') || $user->hasPermission('zalo.manage_accounts');

        if ($assignedTo && $canManage) {
            $query->assignedTo($assignedTo);
        }

        // MULTI-SESSION: No longer check "active" account - each account has its own session
        // We'll check each account's individual session status in the map below

        $accounts = $query->get()->map(function ($account) {
            // Get avatar URL using avatar service
            $avatarUrl = $this->avatarService->getAvatarUrl($account);

            // If account has no name but has zalo_id, try to get from account info
            // This is a fallback - normally name should be set during login
            $name = $account->name;
            if (empty($name)) {
                // Try to get from metadata or use zalo_id as fallback
                $name = $account->zalo_id ?? $account->name ?? 'Account ' . $account->id;
            }

            // MULTI-SESSION: Check THIS specific account's session status
            // Each account has its own independent session
            
            // Priority 1: If database says disconnected, trust it (credentials expired)
            if ($account->is_connected === false || $account->is_connected === 0) {
                $isConnected = false;
                
                Log::info('[ZaloController] Account marked as disconnected in database', [
                    'account_id' => $account->id,
                    'reason' => 'Database flag is_connected = false (credentials likely expired)',
                ]);
            } else {
                // Priority 2: Check zalo-service session status
                try {
                    $isConnected = $this->zalo->isReady($account->id);
                    
                    // Only update to connected if zalo-service confirms
                    if ($isConnected && $account->is_connected != true) {
                        $account->update(['is_connected' => true]);
                        
                        Log::info('[ZaloController] Account reconnected', [
                            'account_id' => $account->id,
                        ]);
                    } else if (!$isConnected && $account->is_connected != false) {
                        $account->update(['is_connected' => false]);
                        
                        Log::warning('[ZaloController] Account disconnected', [
                            'account_id' => $account->id,
                            'reason' => 'zalo-service reports not ready',
                        ]);
                    }
                } catch (\Exception $e) {
                    // If error checking session, mark as disconnected
                    $isConnected = false;
                    
                    if ($account->is_connected != false) {
                        $account->update(['is_connected' => false]);
                        
                        Log::warning('[ZaloController] Account marked as disconnected due to error', [
                            'account_id' => $account->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            return [
                'id' => $account->id,
                'name' => $name,
                'phone' => $account->phone,
                'zalo_account_id' => $account->id,
                'branch_id' => $account->branch_id,
                'branch' => $account->branch ? [
                    'id' => $account->branch->id,
                    'code' => $account->branch->code,
                    'name' => $account->branch->name,
                ] : null,
                'assigned_to' => $account->assigned_to,
                'assigned_user' => $account->assignedUser ? [
                    'id' => $account->assignedUser->id,
                    'name' => $account->assignedUser->name,
                    'email' => $account->assignedUser->email,
                ] : null,
                'avatar_url' => $avatarUrl,
                'is_active' => $account->is_active,
                'is_connected' => $isConnected, // Use calculated connection status
                'is_primary' => $account->is_primary,
                'last_sync_at' => $account->last_sync_at ? $account->last_sync_at->toISOString() : null,
                'last_login_at' => $account->last_login_at ? $account->last_login_at->toISOString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get active account - với phân quyền
     */
    public function getActiveAccount(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        $query = ZaloAccount::active()
            ->accessibleBy($user); // ← Áp dụng phân quyền

        if ($branchId) {
            $query->forBranch($branchId);
        }

        $account = $query->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No active account found',
                'data' => null,
            ], 200); // Return 200 instead of 404 to prevent frontend errors
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
                'phone' => $account->phone,
                'zalo_account_id' => $account->id,
                'branch_id' => $account->branch_id,
                'branch' => $account->branch ? [
                    'id' => $account->branch->id,
                    'code' => $account->branch->code,
                    'name' => $account->branch->name,
                ] : null,
                'assigned_to' => $account->assigned_to,
                'assigned_user' => $account->assignedUser ? [
                    'id' => $account->assignedUser->id,
                    'name' => $account->assignedUser->name,
                    'email' => $account->assignedUser->email,
                ] : null,
                'avatar_url' => $this->avatarService->getAvatarUrl($account),
                'is_active' => $account->is_active,
                'is_connected' => $account->is_connected,
                'is_primary' => $account->is_primary,
                'last_sync_at' => $account->last_sync_at,
            ],
        ]);
    }

    /**
     * Set active account - với phân quyền
     */
    public function setActiveAccount(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission to access it.',
            ], 404);
        }

        // Deactivate ALL accounts accessible by this user to ensure only ONE active account
        ZaloAccount::accessibleBy($user)->update(['is_active' => false]);

        // Activate selected account
        $account->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Active account updated',
        ]);
    }

    /**
     * Check connection health for an account
     * Checks if Zalo session is still valid and updates database accordingly
     */
    public function checkConnectionHealth(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required',
            ], 400);
        }

        // Get account with permission check
        $account = ZaloAccount::accessibleBy($user)->find($accountId);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission to access it.',
            ], 404);
        }

        $wasConnected = $account->is_connected;
        $isConnected = false;
        $statusChanged = false;

        // Priority 1: If database says disconnected, trust it (credentials expired)
        if ($account->is_connected === false || $account->is_connected === 0) {
            $isConnected = false;

            Log::info('[ZaloController] Health check: Account marked as disconnected in database', [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'reason' => 'Database flag is_connected = false (credentials likely expired)',
            ]);
        } else {
            // Priority 2: Check zalo-service session status AND validate zpw_sek
            try {
                // Step 1: Quick WebSocket readiness check
                $isReady = $this->zalo->isReady($account->id);

                if (!$isReady) {
                    $isConnected = false;

                    if ($account->is_connected != false) {
                        $account->update(['is_connected' => false]);
                        $statusChanged = true;

                        Log::warning('[ZaloController] Health check: WebSocket not ready', [
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            'reason' => 'zalo-service reports not ready',
                        ]);
                    }
                } else {
                    // Step 2: Validate zpw_sek by making a test API call
                    // This ensures credentials are actually valid, not just WebSocket connected
                    Log::info('[ZaloController] Health check: Testing zpw_sek validity', [
                        'account_id' => $account->id,
                    ]);

                    try {
                        // Make lightweight API call that requires valid zpw_sek
                        $accountInfo = $this->zalo->getAccountInfo($account->id);

                        if ($accountInfo && !empty($accountInfo['zalo_account_id'])) {
                            // zpw_sek is valid
                            $isConnected = true;

                            if ($account->is_connected != true) {
                                $account->update(['is_connected' => true]);
                                $statusChanged = true;

                                Log::info('[ZaloController] Health check: Account reconnected with valid zpw_sek', [
                                    'account_id' => $account->id,
                                    'account_name' => $account->name,
                                    'zalo_account_id' => $accountInfo['zalo_account_id'],
                                ]);
                            }
                        } else {
                            // API call returned but data is invalid
                            $isConnected = false;

                            if ($account->is_connected != false) {
                                $account->update(['is_connected' => false]);
                                $statusChanged = true;

                                Log::warning('[ZaloController] Health check: Invalid zpw_sek (no account data)', [
                                    'account_id' => $account->id,
                                    'account_name' => $account->name,
                                    'reason' => 'getAccountInfo returned empty or invalid data',
                                ]);
                            }
                        }
                    } catch (\Exception $apiError) {
                        // zpw_sek validation failed
                        $isConnected = false;

                        if ($account->is_connected != false) {
                            $account->update(['is_connected' => false]);
                            $statusChanged = true;

                            Log::warning('[ZaloController] Health check: Invalid zpw_sek (API call failed)', [
                                'account_id' => $account->id,
                                'account_name' => $account->name,
                                'reason' => 'zpw_sek validation failed',
                                'error' => $apiError->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // If error checking session, mark as disconnected
                $isConnected = false;

                if ($account->is_connected != false) {
                    $account->update(['is_connected' => false]);
                    $statusChanged = true;

                    Log::warning('[ZaloController] Health check: Account marked as disconnected due to error', [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'is_connected' => $isConnected,
                'was_connected' => $wasConnected,
                'status_changed' => $statusChanged,
                'checked_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Set primary account
     * - Only super-admin or users with zalo.manage_accounts can set primary
     * - Only ONE account can be primary per branch
     */
    public function setPrimaryAccount(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');

        // Check permission: only super-admin or users with zalo.manage_accounts
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thiết lập tài khoản chính.',
            ], 403);
        }

        // Get account
        $account = ZaloAccount::find($accountId);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        // Unset all primary accounts for this branch
        if ($branchId) {
            ZaloAccount::where('branch_id', $branchId)
                ->update(['is_primary' => false]);
        } else {
            // If no branch specified, unset all primary accounts globally
            ZaloAccount::query()->update(['is_primary' => false]);
        }

        // Set selected account as primary
        $account->update(['is_primary' => true]);

        Log::info('[ZaloController] Primary account set', [
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tài khoản chính đã được thiết lập.',
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
                'is_primary' => $account->is_primary,
            ],
        ]);
    }

    /**
     * Delete a Zalo account and all related data
     * This action cannot be undone
     */
    public function deleteAccount(Request $request, $id)
    {
        $user = $request->user();

        // Check permission: only super-admin or users with zalo.manage_accounts
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa tài khoản Zalo.',
            ], 403);
        }

        $account = ZaloAccount::find($id);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản.',
            ], 404);
        }

        try {
            \DB::beginTransaction();

            Log::info('[ZaloController] Deleting Zalo account', [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'user_id' => $user->id,
            ]);

            // Delete all related data
            $friendsCount = $account->friends()->count();
            $account->friends()->delete();

            $groupsCount = $account->groups()->count();
            foreach ($account->groups as $group) {
                // Delete group members first
                $group->members()->delete();
            }
            $account->groups()->delete();

            $messagesCount = $account->messages()->count();
            foreach ($account->messages as $message) {
                // Delete message reactions
                $message->reactions()->delete();
            }
            $account->messages()->delete();

            // Delete conversations where this account is a participant
            \DB::table('zalo_conversation_users')->where('user_id', $account->id)->delete();

            // Delete recent stickers
            \DB::table('zalo_recent_stickers')->where('zalo_account_id', $account->id)->delete();

            // Delete the account itself (soft delete)
            $accountName = $account->name;
            $account->delete();

            \DB::commit();

            Log::info('[ZaloController] Zalo account deleted successfully', [
                'account_id' => $id,
                'account_name' => $accountName,
                'deleted_friends' => $friendsCount,
                'deleted_groups' => $groupsCount,
                'deleted_messages' => $messagesCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản Zalo đã được xóa thành công.',
                'data' => [
                    'deleted_friends' => $friendsCount,
                    'deleted_groups' => $groupsCount,
                    'deleted_messages' => $messagesCount,
                ],
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('[ZaloController] Delete account failed', [
                'account_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản.',
            ], 500);
        }
    }

    /**
     * Save account after successful login
     * Với phân quyền: chỉ admin/super-admin có thể tạo account mới
     */
    public function saveAccount(Request $request)
    {
        $user = $request->user();
        
        // Check permission: chỉ admin/super-admin hoặc user có permission zalo.manage_accounts
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo tài khoản Zalo.',
            ], 403);
        }

        Log::info('[ZaloController] Save account called', [
            'request_data' => $request->all(),
            'user_id' => $user->id,
        ]);

        try {
            // Get account info from zalo-service
            $accountInfo = $this->zalo->getAccountInfo();
            
            Log::info('[ZaloController] Account info received', [
                'has_accountInfo' => !empty($accountInfo),
                'has_zalo_id' => isset($accountInfo['zalo_account_id']),
                'zalo_account_id' => $accountInfo['zalo_account_id'] ?? null,
                'has_cookie' => isset($accountInfo['cookie']),
            ]);
            
            if (!$accountInfo) {
                Log::error('[ZaloController] Account info is null');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get account information from Zalo service.',
                ], 400);
            }
            
            // If zalo_id is missing, try to create account with temporary ID or use cookie hash
            if (!isset($accountInfo['zalo_account_id']) || empty($accountInfo['zalo_account_id'])) {
                Log::warning('[ZaloController] zalo_id missing, attempting to create with temporary ID');
                
                // Try to generate a temporary ID from cookie hash
                $tempZaloId = null;
                if (isset($accountInfo['cookie']) && !empty($accountInfo['cookie'])) {
                    // Use hash of cookie as temporary ID
                    $cookieHash = md5(json_encode($accountInfo['cookie']));
                    $tempZaloId = 'temp_' . substr($cookieHash, 0, 16);
                    Log::info('[ZaloController] Generated temporary zalo_id from cookie hash', [
                        'temp_zalo_id' => $tempZaloId,
                    ]);
                }
                
                if (!$tempZaloId) {
                    Log::error('[ZaloController] Cannot create account without zalo_id', [
                        'accountInfo' => $accountInfo,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Account information is incomplete. zalo_id is required. Please try logging in again.',
                    ], 400);
                }
                
                $accountInfo['zalo_account_id'] = $tempZaloId;
                Log::warning('[ZaloController] Using temporary zalo_id', ['zalo_account_id' => $tempZaloId]);
            }

            $zaloId = $accountInfo['zalo_account_id'];
            $branchId = $request->input('branch_id');

            // ===== IMPROVED DUPLICATE DETECTION =====
            // Check if account already exists using multiple criteria to prevent duplicates
            // Priority: 1) zalo_id, 2) phone, 3) cookie hash
            $account = null;

            // Try by zalo_id first (if available and not temporary)
            if (!empty($zaloId) && !str_starts_with($zaloId, 'temp_')) {
                $account = ZaloAccount::where('zalo_id', $zaloId)->first();
                if ($account) {
                    Log::info('[ZaloController] Found existing account by zalo_id', [
                        'account_id' => $account->id,
                        'zalo_id' => $zaloId,
                    ]);
                }
            }

            // If not found and phone available, try by phone number
            if (!$account && !empty($accountInfo['phone'])) {
                $account = ZaloAccount::where('phone', $accountInfo['phone'])
                    ->whereNotNull('phone')
                    ->first();
                if ($account) {
                    Log::info('[ZaloController] Found existing account by phone', [
                        'account_id' => $account->id,
                        'phone' => $accountInfo['phone'],
                    ]);
                    // Update zalo_id if it was missing
                    if (empty($account->zalo_id) && !empty($zaloId)) {
                        $account->update(['zalo_id' => $zaloId]);
                        Log::info('[ZaloController] Updated missing zalo_id', ['zalo_id' => $zaloId]);
                    }
                }
            }

            // If still not found and name matches, check by name (last resort)
            if (!$account && !empty($accountInfo['name'])) {
                $possibleAccounts = ZaloAccount::where('name', $accountInfo['name'])
                    ->whereNotNull('name')
                    ->get();

                if ($possibleAccounts->count() === 1) {
                    // Only use name matching if there's exactly one match to avoid false positives
                    $account = $possibleAccounts->first();
                    Log::info('[ZaloController] Found existing account by name (single match)', [
                        'account_id' => $account->id,
                        'name' => $accountInfo['name'],
                    ]);
                    // Update zalo_id if it was missing
                    if (empty($account->zalo_id) && !empty($zaloId)) {
                        $account->update(['zalo_id' => $zaloId]);
                        Log::info('[ZaloController] Updated missing zalo_id', ['zalo_id' => $zaloId]);
                    }
                }
            }

            if (!$account) {
                Log::info('[ZaloController] No existing account found, will create new one');
            }

            if ($account) {
                // Check permission: user chỉ có thể update account được gán cho mình, trừ admin/super-admin
                if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && $account->assigned_to !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền cập nhật tài khoản Zalo này.',
                    ], 403);
                }

                // Update existing account
                Log::info('[ZaloController] Updating existing account', [
                    'zalo_account_id' => $zaloId,
                    'branch_id' => $branchId,
                ]);
                
                // Update account with new info, prioritizing non-null values from accountInfo
                $updateData = [
                    'cookie' => $accountInfo['cookie'] ?? $account->cookie,
                    'imei' => $accountInfo['imei'] ?? $account->imei,
                    'user_agent' => $accountInfo['user_agent'] ?? $account->user_agent,
                    'last_login_at' => now(),
                    'metadata' => array_merge($account->metadata ?? [], [
                        'updated_at' => now()->toISOString(),
                        'updated_by' => $user->id,
                    ]),
                ];
                
                // Admin/super-admin có thể update branch và assigned_to
                if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
                    if ($request->has('branch_id')) {
                        $updateData['branch_id'] = $request->input('branch_id');
                    }
                    if ($request->has('assigned_to')) {
                        $updateData['assigned_to'] = $request->input('assigned_to');
                    }
                }
                
                // Always update name/phone/avatar if we have new values
                // This ensures account info is refreshed on re-login
                if (!empty($accountInfo['name'])) {
                    $updateData['name'] = $accountInfo['name'];
                } elseif (empty($account->name)) {
                    // If no name in accountInfo and account has no name, use zalo_id as fallback
                    $updateData['name'] = $account->zalo_id ? ('Zalo ' . substr($account->zalo_id, -6)) : ('Account ' . $account->id);
                }
                
                if (!empty($accountInfo['phone'])) {
                    $updateData['phone'] = $accountInfo['phone'];
                }
                
                if (!empty($accountInfo['avatar_url'])) {
                    $updateData['avatar_url'] = $accountInfo['avatar_url'];
                }
                
                $account->update($updateData);
                
                // Set as active if no other active account
                if (!ZaloAccount::where('id', '!=', $account->id)->where('is_active', true)->exists()) {
                    $account->update(['is_active' => true]);
                }
            } else {
                // Create new account
                Log::info('[ZaloController] Creating new account', [
                    'zalo_account_id' => $zaloId,
                    'branch_id' => $branchId,
                ]);

                // Deactivate all other accounts in same branch (if branch_id provided)
                if ($branchId) {
                    ZaloAccount::where('branch_id', $branchId)->update(['is_active' => false]);
                } else {
                    // If no branch, deactivate all accounts without branch
                    ZaloAccount::whereNull('branch_id')->update(['is_active' => false]);
                }
                
                $account = ZaloAccount::create([
                    'zalo_account_id' => $zaloId,
                    'name' => $accountInfo['name'] ?? 'Zalo Account ' . substr($zaloId, -6), // Fallback to last 6 digits of zalo_id
                    'phone' => $accountInfo['phone'] ?? null,
                    'branch_id' => $branchId, // Lấy từ request (từ header branch selector)
                    'assigned_to' => $request->input('assigned_to', $user->id), // Default to current user
                    'cookie' => $accountInfo['cookie'] ?? null,
                    'imei' => $accountInfo['imei'] ?? null,
                    'user_agent' => $accountInfo['user_agent'] ?? null,
                    'avatar_url' => $accountInfo['avatar_url'] ?? null,
                    'is_active' => true,
                    'is_connected' => true,
                    'last_login_at' => now(),
                    'metadata' => [
                        'created_at' => now()->toISOString(),
                        'source' => 'qr_login',
                        'created_by' => $user->id,
                        'name_missing' => empty($accountInfo['name']), // Flag if name was not available
                    ],
                ]);
                
                Log::info('[ZaloController] Account created', [
                    'account_id' => $account->id,
                    'zalo_account_id' => $account->id,
                    'name' => $account->name,
                    'has_avatar' => !empty($account->avatar_url),
                ]);
            }

            Log::info('[ZaloController] Account saved successfully', [
                'account_id' => $account->id,
                'zalo_account_id' => $account->id,
            ]);

            // ===== MULTI-BRANCH AUTO-DETECTION =====
            // Check if this branch should have access to this account
            if ($branchId) {
                $existingBranchAccess = \App\Models\ZaloAccountBranch::where('zalo_account_id', $account->id)
                    ->where('branch_id', $branchId)
                    ->first();

                if (!$existingBranchAccess) {
                    // Check if this is the owner branch (account's original branch)
                    $isOwner = ($account->branch_id == $branchId);

                    \App\Models\ZaloAccountBranch::create([
                        'zalo_account_id' => $account->id,
                        'branch_id' => $branchId,
                        'role' => $isOwner ? 'owner' : 'shared',
                        'can_send_to_customers' => $isOwner,
                        'can_send_to_teachers' => $isOwner,
                        'can_send_to_class_groups' => $isOwner,
                        'can_send_to_friends' => $isOwner,
                        'can_send_to_groups' => $isOwner,
                        'view_all_friends' => $isOwner,
                        'view_all_groups' => $isOwner,
                        'view_all_conversations' => $isOwner,
                    ]);

                    Log::info('[ZaloController] Multi-branch access created', [
                        'account_id' => $account->id,
                        'branch_id' => $branchId,
                        'role' => $isOwner ? 'owner' : 'shared',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Account saved successfully',
                'data' => [
                    'id' => $account->id,
                    'zalo_account_id' => $account->id,
                    'name' => $account->name,
                    'is_active' => $account->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Save account exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Re-login existing account (update with new QR login) - với phân quyền
     */
    public function reloginAccount(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $update = $request->boolean('update', false);
        
        Log::info('[ZaloController] Re-login account called', [
            'account_id' => $accountId,
            'update' => $update,
            'user_id' => $user->id,
        ]);
        
        // If update flag is set, this means login was successful, update the account
        if ($update) {
            try {
                // Get account với phân quyền
                $account = ZaloAccount::accessibleBy($user)->find($accountId);
                
                if (!$account) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account not found or you do not have permission to access it.',
                    ], 404);
                }
                
                // Check permission: user chỉ có thể re-login account được gán cho mình, trừ admin/super-admin
                if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && $account->assigned_to !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền đăng nhập lại tài khoản Zalo này.',
                    ], 403);
                }
                
                // Get account info from zalo-service
                // CRITICAL: Pass account->id to ensure we get info for the CORRECT account (multi-session support)
                $accountInfo = $this->zalo->getAccountInfo($account->id);
                
                if (!$accountInfo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to get account information from Zalo service.',
                    ], 400);
                }
                
                // Update account with new credentials
                $updateData = [
                    'cookie' => $accountInfo['cookie'] ?? $account->cookie,
                    'imei' => $accountInfo['imei'] ?? $account->imei,
                    'user_agent' => $accountInfo['user_agent'] ?? $account->user_agent,
                    'is_connected' => true,
                    'last_login_at' => now(),
                ];
                
                // Always update name/phone/avatar if available
                // This ensures account info is refreshed on re-login
                if (!empty($accountInfo['name'])) {
                    $updateData['name'] = $accountInfo['name'];
                } elseif (empty($account->name)) {
                    // If no name in accountInfo and account has no name, use zalo_id as fallback
                    $updateData['name'] = $account->zalo_id ? ('Zalo ' . substr($account->zalo_id, -6)) : ('Account ' . $account->id);
                }
                
                if (!empty($accountInfo['phone'])) {
                    $updateData['phone'] = $accountInfo['phone'];
                }
                
                if (!empty($accountInfo['avatar_url'])) {
                    $updateData['avatar_url'] = $accountInfo['avatar_url'];
                }

                // VALIDATION: Check if zalo_id matches (prevent login with different account)
                // Get new zalo_id from account info - try multiple field names
                $newZaloId = $accountInfo['zalo_account_id'] ?? $accountInfo['zalo_id'] ?? $accountInfo['userId'] ?? null;

                if (!empty($newZaloId)) {
                    // If account already has zalo_id, STRICTLY verify it matches (security check)
                    if (!empty($account->zalo_id) && $newZaloId !== $account->zalo_id) {
                        Log::error('[ZaloController] SECURITY: Account mismatch during re-login', [
                            'expected_zalo_id' => $account->zalo_id,
                            'received_zalo_id' => $newZaloId,
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            'received_name' => $accountInfo['name'] ?? 'Unknown',
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Tài khoản Zalo không khớp! Bạn đang đăng nhập bằng tài khoản Zalo khác. Vui lòng đăng nhập bằng đúng tài khoản: ' . $account->name,
                            'error_code' => 'ACCOUNT_MISMATCH',
                            'expected_account' => $account->name . ' (ID: ...' . substr($account->zalo_id, -6) . ')',
                            'received_account' => ($accountInfo['name'] ?? 'Unknown') . ' (ID: ...' . substr($newZaloId, -6) . ')',
                        ], 400);
                    }

                    Log::info('[ZaloController] zalo_id validation passed', [
                        'zalo_id' => $newZaloId,
                    ]);

                    // Update zalo_id if not set yet
                    if (empty($account->zalo_id)) {
                        $updateData['zalo_id'] = $newZaloId;
                        Log::info('[ZaloController] Setting zalo_id for account', [
                            'account_id' => $account->id,
                            'zalo_id' => $newZaloId,
                        ]);
                    }
                } else if (!empty($account->zalo_id)) {
                    // Account has zalo_id but new login didn't return one - this is suspicious
                    Log::warning('[ZaloController] Re-login did not return zalo_id for account that has one', [
                        'account_id' => $account->id,
                        'existing_zalo_id' => $account->zalo_id,
                        'account_info_keys' => array_keys($accountInfo),
                    ]);
                } else {
                    // Neither has zalo_id - first time login, this is OK
                    Log::info('[ZaloController] zalo_id not available (first time login)', [
                        'account_id' => $account->id,
                    ]);
                }

                // 🔥 FIXED: Update THIS account only (no multi-branch sharing)
                $account->update($updateData);

                Log::info('[ZaloController] Account re-login updated successfully', [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'branch_id' => $account->branch_id,
                    'updated_fields' => array_keys($updateData),
                ]);

                // ⚡ OPTIMIZATION: Prefetch removed to avoid 60s+ timeout
                // Frontend will call /api/zalo/sync-progress endpoint separately
                Log::info('[ZaloController] ⚡ Re-login update successful, sync will be polled separately');

                // 🚀 OPTION C HYBRID: Prefetch all groups and friends after successful QR login
                // DISABLED: This was causing 60s+ timeout during relogin update
                // Log::info('[ZaloController] 🚀 PREFETCH: Starting aggressive sync after QR login...');
                // try {
                //     // Prefetch friends (non-blocking, best effort)
                //     try {
                //         $this->syncFriends($account);
                //         Log::info('[ZaloController] ✅ PREFETCH: Friends synced successfully');
                //     } catch (\Exception $e) {
                //         Log::error('[ZaloController] ⚠️  PREFETCH: Failed to sync friends', [
                //             'error' => $e->getMessage(),
                //         ]);
                //     }
                //
                //     // Prefetch groups (non-blocking, best effort)
                //     try {
                //         $this->syncGroups($account);
                //         Log::info('[ZaloController] ✅ PREFETCH: Groups synced successfully');
                //     } catch (\Exception $e) {
                //         Log::error('[ZaloController] ⚠️  PREFETCH: Failed to sync groups', [
                //             'error' => $e->getMessage(),
                //         ]);
                //     }
                //
                //     Log::info('[ZaloController] 🎉 PREFETCH: Completed after QR login!');
                // } catch (\Exception $e) {
                //     // Don't fail login if prefetch fails
                //     Log::error('[ZaloController] ⚠️  PREFETCH: Exception during prefetch', [
                //         'error' => $e->getMessage(),
                //     ]);
                // }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Account re-login successful',
                    'data' => [
                        'id' => $account->id,
                        'zalo_account_id' => $account->id,
                        'name' => $account->name,
                        'is_connected' => $account->is_connected,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('[ZaloController] Re-login update exception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update account: ' . $e->getMessage(),
                ], 500);
            }
        }
        
        // Otherwise, initialize QR login for this account
        try {
            $account = ZaloAccount::find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }
            
            // Initialize with forceNew=true to create new session
            $forceNew = true;
            // MULTI-SESSION: Pass account ID to initialize the correct session
            $result = $this->zalo->initialize($account->id, $forceNew);
            
            Log::info('[ZaloController] Re-login initialize result', [
                'account_id' => $accountId,
                'success' => $result['success'] ?? false,
                'hasQrCode' => isset($result['qrCode']),
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Re-login initialize exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize re-login: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign Zalo account to user (employee)
     * Chỉ admin/super-admin hoặc user có permission zalo.manage_accounts mới có thể assign
     */
    public function assignAccount(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $assignedTo = $request->input('assigned_to');
        $branchId = $request->input('branch_id');

        // Check permission
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gán tài khoản Zalo.',
            ], 403);
        }

        // Validate
        $request->validate([
            'account_id' => 'required|exists:zalo_accounts,id',
            'assigned_to' => 'required|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission to access it.',
            ], 404);
        }

        // Check if target user belongs to the branch (if branch_id provided)
        if ($branchId) {
            $targetUser = \App\Models\User::find($assignedTo);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Check if user belongs to this branch
            $userBranchIds = $targetUser->branches()->pluck('branches.id')->toArray();
            if (!in_array($branchId, $userBranchIds) && !empty($userBranchIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not belong to the selected branch.',
                ], 422);
            }
        }

        // Update account
        $updateData = [
            'assigned_to' => $assignedTo,
        ];

        // Admin/super-admin có thể update branch_id
        if (($user->hasRole('admin') || $user->hasRole('super-admin')) && $branchId) {
            $updateData['branch_id'] = $branchId;
        }

        $account->update($updateData);

        Log::info('[ZaloController] Account assigned', [
            'account_id' => $accountId,
            'assigned_to' => $assignedTo,
            'branch_id' => $updateData['branch_id'] ?? $account->branch_id,
            'assigned_by' => $user->id,
        ]);

        // Reload account with relationships
        $account->load(['branch:id,code,name', 'assignedUser:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Account assigned successfully',
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
                'zalo_account_id' => $account->id,
                'branch_id' => $account->branch_id,
                'branch' => $account->branch ? [
                    'id' => $account->branch->id,
                    'code' => $account->branch->code,
                    'name' => $account->branch->name,
                ] : null,
                'assigned_to' => $account->assigned_to,
                'assigned_user' => $account->assignedUser ? [
                    'id' => $account->assignedUser->id,
                    'name' => $account->assignedUser->name,
                    'email' => $account->assignedUser->email,
                ] : null,
            ],
        ]);
    }


    /**
     * Get messages for a conversation
     */
    public function getMessages(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientPhone = $request->input('recipient_phone');
        $recipientType = $request->input('recipient_type', 'user');
        $perPage = $request->input('per_page', 50);
        $beforeId = $request->input('before_id'); // For pagination: load messages before this ID

        // If recipient_phone is provided instead of recipient_id, resolve it
        if (!$recipientId && $recipientPhone && $recipientType === 'user') {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if ($account) {
                $friend = ZaloFriend::where('zalo_account_id', $account->id)
                    ->where('phone', $recipientPhone)
                    ->first();

                if ($friend) {
                    $recipientId = $friend->zalo_user_id;
                    Log::info('[ZaloController] getMessages - Phone resolved to Zalo user ID', [
                        'phone' => $recipientPhone,
                        'zalo_user_id' => $recipientId,
                    ]);
                } else {
                    Log::warning('[ZaloController] getMessages - Phone not found in friends list', [
                        'phone' => $recipientPhone,
                        'account_id' => $accountId,
                    ]);

                    // Return empty messages if no conversation exists yet
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'meta' => [
                            'current_page' => 1,
                            'total' => 0,
                        ],
                        'message' => 'No conversation found. Send a message to start chatting.',
                    ]);
                }
            }
        }

        if (!$accountId || !$recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and (recipient_id or recipient_phone) are required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        // Check if account is connected (WebSocket session + database)
        if (!$this->isAccountConnected($account)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại để xem tin nhắn.',
                'is_connected' => false,
                'account_name' => $account->name,
            ], 403);
        }

        // Use single account only (no multi-branch sharing)
        $accountIds = [$account->id];

        // Auto-sync group members if this is a group conversation
        if ($recipientType === 'group') {
            // 🔧 FIX: Use raw SQL to avoid BigInt/String data type issues with Eloquent
            // Query using zalo_account_id
            $groupRecord = \DB::selectOne(
                "SELECT id, members_count FROM zalo_groups
                 WHERE zalo_account_id = ? AND zalo_group_id = ?
                 LIMIT 1",
                [$account->id, $recipientId]
            );

            if ($groupRecord) {
                // ✅ FIX: Use withTrashed() to handle soft-deleted groups
                $group = ZaloGroup::withTrashed()->find($groupRecord->id); // Load full model using DB ID

                // Skip if group not found (shouldn't happen but check anyway)
                if (!$group) {
                    Log::warning('[ZaloController] Group not found', ['id' => $groupRecord->id]);
                } else {
                    $membersCount = ZaloGroupMember::where('zalo_group_id', $group->id)->count();
                
                // If no members, sync them
                if ($membersCount === 0) {
                    Log::info('[ZaloController] Auto-syncing group members', [
                        'group_id' => $group->id,
                        'zalo_group_id' => $recipientId,
                    ]);
                    
                    try {
                        // Call zalo-service API to get group members
                        $response = Http::timeout(30)->withHeaders([
                            'X-API-Key' => config('services.zalo.api_key'),
                        ])->get(config('services.zalo.base_url') . '/api/group/members/' . $recipientId, [
                            'accountId' => $account->id,
                        ]);
                        
                        if ($response->successful() && $response->json('success')) {
                            $members = $response->json('data', []);
                            
                            // Save members to database
                            foreach ($members as $memberData) {
                                $userId = $memberData['id'] ?? $memberData['userId'] ?? $memberData['zalo_user_id'] ?? null;
                                if (!$userId) continue;
                                
                                $displayName = $memberData['name'] ?? $memberData['display_name'] ?? $memberData['displayName'] ?? 'Unknown';
                                $avatarUrl = $memberData['avatar'] ?? $memberData['avatar_url'] ?? $memberData['avatarUrl'] ?? null;
                                $isAdmin = $memberData['is_admin'] ?? $memberData['isAdmin'] ?? false;
                                
                                ZaloGroupMember::updateOrCreate(
                                    [
                                        'zalo_group_id' => $group->id,
                                        'zalo_user_id' => (string) $userId,
                                    ],
                                    [
                                        'display_name' => $displayName,
                                        'avatar_url' => $avatarUrl,
                                        'is_admin' => $isAdmin,
                                        'metadata' => $memberData,
                                    ]
                                );
                            }
                            
                            // Update members count
                            $group->update([
                                'members_count' => count($members),
                                'last_sync_at' => now(),
                            ]);
                            
                            Log::info('[ZaloController] Auto-synced group members', [
                                'group_id' => $group->id,
                                'members_count' => count($members),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('[ZaloController] Failed to auto-sync group members', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                } // Close else block
            }
        }

        // Get messages with relationships
        // Get latest N messages, then reverse to show old->new order
        $query = ZaloMessage::whereIn('zalo_account_id', $accountIds)
            ->where('recipient_id', $recipientId)
            ->with(['replyTo.account', 'reactions']);

        // If before_id is provided, get messages older than that ID (for pagination/lazy loading)
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query
            ->orderByRaw('COALESCE(delivered_at, sent_at) DESC') // ✅ Get newest first
            ->orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get()
            ->reverse() // ✅ Reverse to show old->new
            ->values() // ✅ Reset array keys
            ->map(function ($message) use ($account, $recipientId) {
                // Group reactions by icon
                $reactionsGrouped = $message->reactions->groupBy('reaction_icon')->map(function ($group) {
                    return [
                        'reaction' => $group->first()->reaction_icon,
                        'count' => $group->count(),
                        'users' => $group->pluck('zalo_user_id')->toArray(),
                    ];
                })->values();
                
                // Determine sender name for reply_to message
                $replyToSenderName = null;
                if ($message->replyTo) {
                    if ($message->replyTo->type === 'sent') {
                        // Message was sent by us, so sender is our account name
                        $replyToAccount = $message->replyTo->account;
                        $replyToSenderName = $replyToAccount
                            ? ($replyToAccount->name ?? ($replyToAccount->zalo_id ? 'Account ' . substr($replyToAccount->zalo_id, -6) : 'You'))
                            : 'You';
                    } else {
                        // Message was received from someone, so sender is recipient_name (the person who sent it to us)
                        $replyToSenderName = $message->replyTo->recipient_name ?? 'Unknown';
                    }
                } elseif ($message->quote_data) {
                    // Fallback: If replyTo relationship not found, try to get info from quote_data
                    $quoteData = $message->quote_data;
                    $ownerId = $quoteData['ownerId'] ?? $quoteData['uidFrom'] ?? null;
                    
                    // Log for debugging
                    Log::info('[ZaloController] Determining sender name from quote_data', [
                        'message_id' => $message->id,
                        'message_type' => $message->type,
                        'recipient_id' => $message->recipient_id,
                        'recipient_name' => $message->recipient_name,
                        'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                        'ownerId' => $ownerId,
                        'ownerId_type' => gettype($ownerId),
                        'account_zalo_id_type' => gettype($account->zalo_id),
                        'quote_data_keys' => array_keys($quoteData),
                    ]);
                    
                    if ($ownerId) {
                        // Normalize IDs to strings for comparison
                        $ownerIdStr = (string) $ownerId;
                        $accountZaloIdStr = (string) ($account->zalo_id ?? '');
                        
                        // Check if ownerId is our account's zalo_id
                        if ($ownerIdStr === $accountZaloIdStr) {
                            // Original message was sent by us
                            $replyToSenderName = $account->name ?? ($account->zalo_id ? 'Account ' . substr($account->zalo_id, -6) : 'You');
                            Log::info('[ZaloController] Original message sender is our account', [
                                'sender_name' => $replyToSenderName,
                            ]);
                        } else {
                            // Original message was sent by someone else
                            // Try multiple strategies to find the sender name
                            
                            // Strategy 1: Try to find friend by zalo_user_id
                            $friend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                                ->where('zalo_user_id', $ownerId)
                                ->first();
                            
                            if ($friend) {
                                $replyToSenderName = $friend->name ?? $friend->displayName ?? 'Unknown';
                            } else {
                                // Strategy 2: If message is received, the sender is the recipient_id of this message
                                // (the person we're chatting with)
                                if ($message->type === 'received') {
                                    // This is a received reply, so the original message sender is the person chatting with us
                                    // Try to find friend by recipient_id (the person we're chatting with)
                                    $conversationFriend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                                        ->where('zalo_user_id', $message->recipient_id)
                                        ->first();
                                    
                                    if ($conversationFriend) {
                                        $replyToSenderName = $conversationFriend->name ?? $conversationFriend->displayName ?? 'Unknown';
                                    } else {
                                        // Use recipient_name as fallback
                                        $replyToSenderName = $message->recipient_name ?? 'Unknown';
                                    }
                                } else {
                                    // Strategy 3: Message is sent by us, so original message was from the person we're chatting with
                                    // Try to find friend by recipient_id
                                    $conversationFriend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                                        ->where('zalo_user_id', $message->recipient_id)
                                        ->first();
                                    
                                    if ($conversationFriend) {
                                        $replyToSenderName = $conversationFriend->name ?? $conversationFriend->displayName ?? 'Unknown';
                                    } else {
                                        // Use recipient_name as fallback
                                        $replyToSenderName = $message->recipient_name ?? 'Unknown';
                                    }
                                }
                            }
                        }
                    } else {
                        // No ownerId in quote_data, try to infer from message context
                        if ($message->type === 'received') {
                            // Received reply, original message was from the person we're chatting with
                            $conversationFriend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                                ->where('zalo_user_id', $message->recipient_id)
                                ->first();
                            
                            if ($conversationFriend) {
                                $replyToSenderName = $conversationFriend->name ?? $conversationFriend->displayName ?? 'Unknown';
                            } else {
                                $replyToSenderName = $message->recipient_name ?? 'Unknown';
                            }
                        } else {
                            // Sent reply, original message was from us
                            $replyToSenderName = $account->name ?? ($account->zalo_id ? 'Account ' . substr($account->zalo_id, -6) : 'You');
                        }
                    }
                }
                
                // Build reply_to object - use replyTo relationship if available, otherwise use quote_data
                $replyTo = null;
                if ($message->replyTo) {
                    $replyTo = [
                        'id' => $message->replyTo->id,
                        'message_id' => $message->replyTo->message_id,
                        'content' => $message->replyTo->content,
                        'content_type' => $message->replyTo->content_type,
                        'type' => $message->replyTo->type,
                        'sender_name' => $replyToSenderName,
                    ];
                } elseif ($message->quote_data && $replyToSenderName) {
                    // Fallback: Build reply_to from quote_data if we found sender name
                    $quoteData = $message->quote_data;
                    $replyTo = [
                        'id' => null, // No database ID since relationship not found
                        'message_id' => $quoteData['globalMsgId'] ?? $quoteData['msgId'] ?? null,
                        'content' => $quoteData['content'] ?? $quoteData['msg'] ?? '',
                        'content_type' => $quoteData['msgType'] ?? $quoteData['cliMsgType'] ?? 'text',
                        'type' => null, // Unknown type
                        'sender_name' => $replyToSenderName,
                    ];
                }
                
            // Extract metadata for frontend
            $metadata = $message->metadata ?? [];
            $cliMsgId = $metadata['cliMsgId'] ?? null;
            
            // Debug: Log styles in metadata
            if (isset($metadata['styles'])) {
                Log::info('[ZaloController] Message has styles in metadata', [
                    'message_id' => $message->id,
                    'styles_count' => is_array($metadata['styles']) ? count($metadata['styles']) : 0,
                    'styles_preview' => is_array($metadata['styles']) ? array_slice($metadata['styles'], 0, 2) : null,
                ]);
            } else {
                Log::debug('[ZaloController] Message has NO styles in metadata', [
                    'message_id' => $message->id,
                    'metadata_keys' => array_keys($metadata),
                ]);
            }
            
            // Get sender avatar for group messages
            $senderAvatar = null;
            if ($message->recipient_type === 'group' && $message->sender_id) {
                // 🔧 FIX: Use raw SQL to avoid BigInt/String data type issues with Eloquent
                $groupRecord = \DB::selectOne("SELECT id FROM zalo_groups WHERE zalo_account_id = ? AND zalo_group_id = ?", [$account->id, $recipientId]);

                if ($groupRecord) {
                    // Find member in group using raw SQL
                    $memberRecord = \DB::selectOne("SELECT avatar_url FROM zalo_group_members WHERE zalo_group_id = ? AND zalo_user_id = ?", [$groupRecord->id, $message->sender_id]);

                    if ($memberRecord) {
                        $senderAvatar = $memberRecord->avatar_url;
                    }
                }
            }
            
            // ✅ Get sent_by_user info if available
            $sentByUser = null;
            $sentByUserName = null;
            $sentByAccountUsername = null;
            
            if ($message->sent_by_user_id) {
                $sentByUser = \App\Models\User::find($message->sent_by_user_id);
                if ($sentByUser) {
                    $sentByUserName = $sentByUser->name;
                    $sentByAccountUsername = $this->extractUsername($sentByUser->email);
                }
            }
            
            return [
                'id' => $message->id,
                'message_id' => $message->message_id,
                'type' => $message->type,
                'recipient_type' => $message->recipient_type,
                'recipient_id' => $message->recipient_id,
                'recipient_name' => $message->recipient_name,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'sender_avatar' => $senderAvatar,
                'content' => $message->content,
                'content_type' => $message->content_type,
                'media_url' => $message->media_url,
                'status' => $message->status,
                'sent_at' => $message->sent_at?->toISOString(),
                'created_at' => $message->created_at?->toISOString(),
                'reply_to' => $replyTo,
                'quote_data' => $message->quote_data,
                'reactions' => $reactionsGrouped,
                // ✅ ADD sent_by_user info
                'sent_by_user_id' => $message->sent_by_user_id,
                'sent_by_user_name' => $sentByUserName,
                'sent_by_account_username' => $sentByAccountUsername,
                'metadata' => [
                    'cliMsgId' => $cliMsgId,
                    'msgId' => $metadata['msgId'] ?? null,
                    'globalMsgId' => $metadata['globalMsgId'] ?? null,
                    'realMsgId' => $metadata['realMsgId'] ?? null,
                    'styles' => $metadata['styles'] ?? null, // Rich text styles
                ],
            ];
            });

        // Check if there are more messages (for lazy loading)
        $oldestId = $messages->first()['id'] ?? null;
        $hasMore = false;

        if ($oldestId) {
            $hasMore = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('recipient_id', $recipientId)
                ->where('id', '<', $oldestId)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => $messages,
            'meta' => [
                'has_more' => $hasMore,
                'oldest_id' => $oldestId,
                'count' => $messages->count(),
            ],
        ]);
    }

    /**
     * Send message and save to database
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $message = $request->input('message');
        $mediaUrl = $request->input('media_url');
        $mediaPath = $request->input('media_path'); // Absolute file path (preferred over URL)
        $contentType = $request->input('content_type', 'text'); // text, image, file, link, folder
        $styles = $request->input('styles'); // Rich text styles array
        $mentions = $request->input('mentions'); // Mentions array for group messages

        // Validate required fields
        if (!$accountId) {
            Log::error('[ZaloController] Missing account_id');
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }
        
        if (!$recipientId) {
            Log::error('[ZaloController] Missing recipient_id');
            return response()->json([
                'success' => false,
                'message' => 'recipient_id is required',
            ], 400);
        }
        
        // 🎨 FIX: Allow stickers to pass without message or media_url (they have sticker_data)
        // Also allow media_path for images (uploaded files with absolute path)
        $stickerData = $request->input('sticker_data');
        if (empty($message) && empty($mediaUrl) && empty($mediaPath) && empty($stickerData)) {
            Log::error('[ZaloController] message, media_url, media_path, and sticker_data are all empty', [
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'content_type' => $contentType,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Either message, media_url, media_path, or sticker_data is required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        // Check branch permission: can_send_message
        if (!$this->checkBranchPermission($request, $account, 'can_send_message')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gửi tin nhắn từ tài khoản này.',
                'permission_denied' => true,
            ], 403);
        }

        // Get recipient name
        $recipientName = null;
        if ($recipientType === 'user') {
            $friend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                ->where('zalo_user_id', $recipientId)
                ->first();
            $recipientName = $friend ? ($friend->name ?? 'Unknown') : 'Unknown';
        } else {
            $group = \App\Models\ZaloGroup::where('zalo_account_id', $account->id)
                ->where('zalo_group_id', $recipientId)
                ->first();
            $recipientName = $group ? $group->name : 'Unknown';
        }

        try {
            Log::info('[ZaloController] sendMessage called', [
                'user_id' => $user->id,
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'has_message' => !empty($message),
                'has_media_url' => !empty($mediaUrl),
                'content_type' => $contentType,
            ]);

            // Validate account has required data
            if (!$account->cookie) {  // Only check cookie, zalo_id is optional metadata
                Log::error('[ZaloController] Account missing zalo_id and cookie', [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Account is not properly configured. Missing zalo_id and cookie. Please re-login this account.',
                ], 400);
            }

            // Check if Zalo service is ready (MULTI-SESSION: pass account->id)
            $isReady = $this->zalo->isReady($account->id);
            Log::info('[ZaloController] Zalo service status', [
                'is_ready' => $isReady,
                'account_id' => $account->id,
            ]);

            if (!$isReady) {
                Log::warning('[ZaloController] Zalo service not ready, attempting to initialize with account cookie', [
                    'account_id' => $account->id,
                    'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                    'account_name' => $account->name,
                    'has_cookie' => !empty($account->cookie),
                ]);

                // Try to initialize zalo-service with account cookie
                if ($account->cookie) {
                    try {
                        $cookieData = $account->cookie;
                        $cookieString = is_string($cookieData) ? $cookieData : json_encode($cookieData);

                        Log::info('[ZaloController] Sending credentials to zalo-service', [
                            'account_id' => $account->id,
                            'cookie_length' => strlen($cookieString),
                            'has_imei' => !empty($account->imei),
                            'has_user_agent' => !empty($account->user_agent),
                        ]);

                        // Prepare credentials with defaults
                        $credentials = [
                            'cookie' => $cookieData,
                            'zalo_account_id' => $account->id,
                        ];
                        
                        // Add imei if available, otherwise use default
                        if (!empty($account->imei)) {
                            $credentials['imei'] = $account->imei;
                        } else {
                            // Generate a default IMEI if not available
                            $credentials['imei'] = '357895081234567'; // Default IMEI
                            Log::warning('[ZaloController] Account missing IMEI, using default', [
                                'account_id' => $account->id,
                            ]);
                        }
                        
                        // Add user_agent if available, otherwise use default
                        if (!empty($account->user_agent)) {
                            $credentials['user_agent'] = $account->user_agent;
                        } else {
                            // Use default user agent if not available
                            $credentials['user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0';
                            Log::warning('[ZaloController] Account missing user_agent, using default', [
                                'account_id' => $account->id,
                            ]);
                        }
                        
                        Log::info('[ZaloController] Sending credentials to zalo-service', [
                            'account_id' => $account->id,
                            'cookie_length' => strlen(is_string($cookieData) ? $cookieData : json_encode($cookieData)),
                            'has_imei' => !empty($credentials['imei']),
                            'has_user_agent' => !empty($credentials['user_agent']),
                            'zalo_account_id' => $credentials['zalo_account_id'],
                        ]);
                        
                        $initResponse = Http::timeout(30)->withHeaders([
                            'X-API-Key' => config('services.zalo.api_key'),
                            'Content-Type' => 'application/json',
                        ])->post(config('services.zalo.base_url', 'http://localhost:3001') . '/api/auth/set-credentials', $credentials);
                        
                        if ($initResponse->successful() && $initResponse->json('isReady', false)) {
                            Log::info('[ZaloController] Successfully initialized zalo-service with account cookie');
                            // Re-check isReady (MULTI-SESSION: pass account->id)
                            $isReady = $this->zalo->isReady($account->id);

                            // ⚡ OPTIMIZATION: Prefetch removed from login flow to avoid timeout
                            // Sync will be triggered separately via frontend or relogin endpoint
                            Log::info('[ZaloController] ⚡ Login successful, sync skipped for faster response');

                            // 🚀 OPTION C HYBRID: Prefetch all groups and friends after successful login
                            // DISABLED: This was causing 60s+ timeout during login
                            // Log::info('[ZaloController] 🚀 PREFETCH: Starting aggressive sync of groups and friends...');
                            // try {
                            //     // Prefetch friends (non-blocking, best effort)
                            //     try {
                            //         $this->syncFriends($account);
                            //         Log::info('[ZaloController] ✅ PREFETCH: Friends synced successfully');
                            //     } catch (\Exception $e) {
                            //         Log::error('[ZaloController] ⚠️  PREFETCH: Failed to sync friends', [
                            //             'error' => $e->getMessage(),
                            //         ]);
                            //     }
                            //
                            //     // Prefetch groups (non-blocking, best effort)
                            //     try {
                            //         $this->syncGroups($account);
                            //         Log::info('[ZaloController] ✅ PREFETCH: Groups synced successfully');
                            //     } catch (\Exception $e) {
                            //         Log::error('[ZaloController] ⚠️  PREFETCH: Failed to sync groups', [
                            //             'error' => $e->getMessage(),
                            //         ]);
                            //     }
                            //
                            //     Log::info('[ZaloController] 🎉 PREFETCH: Completed! All recipients should be in DB now.');
                            // } catch (\Exception $e) {
                            //     // Don't fail login if prefetch fails
                            //     Log::error('[ZaloController] ⚠️  PREFETCH: Exception during prefetch', [
                            //         'error' => $e->getMessage(),
                            //     ]);
                            // }
                        } else {
                            $errorResponse = $initResponse->json();
                            $errorMessage = $errorResponse['message'] ?? 'Unknown error';
                            Log::error('[ZaloController] Failed to initialize zalo-service', [
                                'status' => $initResponse->status(),
                                'response' => $errorResponse,
                                'error_message' => $errorMessage,
                            ]);
                            
                            // If login failed, the cookie is likely expired
                            if (str_contains(strtolower($errorMessage), 'đăng nhập thất bại') || 
                                str_contains(strtolower($errorMessage), 'login failed') ||
                                str_contains(strtolower($errorMessage), 'thất bại')) {
                                Log::warning('[ZaloController] Cookie appears to be expired or invalid', [
                                    'account_id' => $account->id,
                                    'error_message' => $errorMessage,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('[ZaloController] Exception while initializing zalo-service', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
                
                // Check again after initialization attempt
                if (!$isReady) {
                    // Get the last error message from initialization attempt
                    $lastError = null;
                    if (isset($initResponse) && !$initResponse->successful()) {
                        $errorResponse = $initResponse->json();
                        $lastError = $errorResponse['message'] ?? null;
                    }
                    
                    $isCookieExpired = $lastError && (
                        str_contains(strtolower($lastError), 'đăng nhập thất bại') ||
                        str_contains(strtolower($lastError), 'login failed') ||
                        str_contains(strtolower($lastError), 'thất bại') ||
                        str_contains(strtolower($lastError), 'expired') ||
                        str_contains(strtolower($lastError), 'invalid')
                    );
                    
                    Log::error('[ZaloController] Zalo service still not ready after initialization attempt', [
                        'account_id' => $account->id,
                        'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                        'account_name' => $account->name,
                        'has_cookie' => !empty($account->cookie),
                        'base_url' => config('services.zalo.base_url', 'http://localhost:3001'),
                        'has_api_key' => !empty(config('services.zalo.api_key')),
                        'last_error' => $lastError,
                        'is_cookie_expired' => $isCookieExpired,
                    ]);
                    
                    $message = $isCookieExpired 
                        ? 'Zalo account cookie has expired or is invalid. Please re-login this account using the "Re-login" button in Account Manager.'
                        : 'Zalo service is not ready. Please ensure zalo-service is running on port 3001 and you have logged in with a Zalo account.';
                    
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'details' => [
                            'base_url' => config('services.zalo.base_url', 'http://localhost:3001'),
                            'status_endpoint' => config('services.zalo.base_url', 'http://localhost:3001') . '/api/auth/status',
                            'hint' => $isCookieExpired 
                                ? 'Please click "Re-login" button in Account Manager to get a fresh QR code and scan it with your Zalo app.'
                                : 'Check if zalo-service is running: npm run dev in zalo-service directory',
                            'cookie_expired' => $isCookieExpired,
                            'last_error' => $lastError,
                        ],
                    ], 503);
                }
            }

            // Log account info for debugging
            Log::info('[ZaloController] Sending message', [
                'account_id' => $account->id,
                'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                'account_name' => $account->name,
                'has_cookie' => !empty($account->cookie),
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'message_length' => strlen($message ?? ''),
                'content_type' => $contentType,
                'has_media_url' => !empty($mediaUrl),
            ]);

            $result = null;
            $messageId = null;
            $finalMediaUrl = $mediaUrl;
            $finalContentType = $contentType;

            // Send message via Zalo service based on content type
            if ($contentType === 'image' && ($mediaUrl || $mediaPath)) {
                // Send image - prefer absolute path over URL to avoid localhost download
                $imageSource = $mediaPath ?: $mediaUrl;
                $isAbsolutePath = $mediaPath && file_exists($mediaPath);
                
                Log::info('[ZaloController] Sending image message', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'image_source' => $isAbsolutePath ? 'absolute_path' : 'url',
                    'image_value' => $imageSource,
                ]);
                
                $result = $this->zalo->sendImage($recipientId, $imageSource, $recipientType);
                
                Log::info('[ZaloController] ZaloNotificationService::sendImage response', [
                    'success' => $result['success'] ?? false,
                    'has_data' => isset($result['data']),
                    'message' => $result['message'] ?? null,
                    'data_keys' => isset($result['data']) ? array_keys($result['data']) : [],
                    'full_result' => $result,
                ]);
                
                // Extract message_id from response - same format as sendMessage
                $messageId = $result['data']['message_id'] ?? null;
                $cliMsgId = $result['data']['cli_msg_id'] ?? null;
                $allMessageIds = $result['data']['all_message_ids'] ?? null;
                
                // IMPORTANT: Extract Zalo CDN URL from response
                // zalo-service now returns the actual Zalo CDN URL where the image was uploaded
                $zaloCdnUrl = $result['data']['zalo_cdn_url'] ?? null;
                $mediaUrlFromZalo = $result['data']['media_url'] ?? null;
                
                // Use Zalo CDN URL if available, otherwise keep local URL
                if ($zaloCdnUrl) {
                    $finalMediaUrl = $zaloCdnUrl;
                    $finalContentType = 'image';
                    Log::info('[ZaloController] Using Zalo CDN URL for image', [
                        'zalo_cdn_url' => $zaloCdnUrl,
                        'local_url' => $mediaUrl,
                    ]);
                } else {
                    Log::warning('[ZaloController] Zalo CDN URL not found in response, using local URL', [
                        'local_url' => $mediaUrl,
                    ]);
                }
                
                Log::info('[ZaloController] Extracted message IDs from sendImage response', [
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                    'all_message_ids' => $allMessageIds,
                    'has_message_id' => !empty($messageId),
                    'zalo_cdn_url' => $zaloCdnUrl,
                    'final_media_url' => $finalMediaUrl,
                ]);
            } elseif ($contentType === 'file' && ($mediaUrl || $mediaPath)) {
                // Send file (document) - similar to image
                $fileSource = $mediaPath ?: $mediaUrl;
                $isAbsolutePath = $mediaPath && file_exists($mediaPath);
                
                Log::info('[ZaloController] Sending file message', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'file_source' => $isAbsolutePath ? 'absolute_path' : 'url',
                    'file_value' => $fileSource,
                    'file_name' => $message ?? 'document',
                ]);
                
                $result = $this->zalo->sendFile($recipientId, $fileSource, $message ?? 'document', $recipientType);
                
                Log::info('[ZaloController] ZaloNotificationService::sendFile response', [
                    'success' => $result['success'] ?? false,
                    'has_data' => isset($result['data']),
                    'message' => $result['message'] ?? null,
                    'full_result' => $result,
                ]);
                
                // Extract message_id from response
                $messageId = $result['data']['message_id'] ?? null;
                $cliMsgId = $result['data']['cli_msg_id'] ?? null;
                
                // Keep original media URL for file
                $finalContentType = 'file';
                
                Log::info('[ZaloController] Extracted message IDs from sendFile response', [
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                    'has_message_id' => !empty($messageId),
                ]);
            } elseif ($contentType === 'video' && ($mediaUrl || $mediaPath)) {
                // Send video using Zalo's sendVideo API
                $videoUrl = $mediaUrl ?: asset('storage/' . $mediaPath);
                $thumbnailUrl = $videoUrl; // Use video URL as thumbnail fallback
                
                Log::info('[ZaloController] Sending video message', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'video_url' => $videoUrl,
                    'thumbnail_url' => $thumbnailUrl,
                ]);
                
                $result = $this->zalo->sendVideo(
                    $recipientId,
                    $videoUrl,
                    $thumbnailUrl,
                    $recipientType,
                    $accountId,
                    $message
                );
                
                Log::info('[ZaloController] ZaloNotificationService::sendVideo response', [
                    'success' => $result['success'] ?? false,
                    'has_data' => isset($result['data']),
                    'message' => $result['message'] ?? null,
                    'full_result' => $result,
                ]);
                
                // Extract message_id from response
                $messageId = $result['data']['msgId'] ?? null;
                
                $finalContentType = 'video';
                
                Log::info('[ZaloController] Extracted message ID from sendVideo response', [
                    'message_id' => $messageId,
                    'has_message_id' => !empty($messageId),
                ]);
            } elseif ($contentType === 'audio' && ($mediaUrl || $mediaPath)) {
                // Send audio/voice using Zalo's sendVoice API
                $voiceUrl = $mediaUrl ?: asset('storage/' . $mediaPath);
                
                Log::info('[ZaloController] Sending audio/voice message', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'voice_url' => $voiceUrl,
                ]);
                
                $result = $this->zalo->sendVoice(
                    $recipientId,
                    $voiceUrl,
                    $recipientType,
                    $accountId
                );
                
                Log::info('[ZaloController] ZaloNotificationService::sendVoice response', [
                    'success' => $result['success'] ?? false,
                    'has_data' => isset($result['data']),
                    'message' => $result['message'] ?? null,
                    'full_result' => $result,
                ]);
                
                // Extract message_id from response
                $messageId = $result['data']['msgId'] ?? null;
                
                $finalContentType = 'audio';
                
                Log::info('[ZaloController] Extracted message ID from sendVoice response', [
                    'message_id' => $messageId,
                    'has_message_id' => !empty($messageId),
                ]);
            } elseif ($contentType === 'sticker' && $stickerData) {
                // Sticker - already sent via zalo-service, just use the provided message_id
                // No need to send again, frontend already sent it directly to zalo-service
                $messageId = $request->input('message_id');
                $result = [
                    'success' => true,
                    'message' => 'Sticker already sent, saving to database',
                    'data' => [
                        'message_id' => $messageId,
                    ]
                ];

                Log::info('[ZaloController] Sticker message (already sent)', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'message_id' => $messageId,
                    'sticker_id' => $stickerData['id'] ?? null,
                ]);
            } else {
                // Send text message (with optional link detection)
                if ($mediaUrl && filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                    // If media_url is a valid URL, treat as link
                    $finalContentType = 'link';
                    $message = $message ?: $mediaUrl;
                }
                
                Log::info('[ZaloController] Sending text message', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'message_length' => strlen($message ?? ''),
                    'content_type' => $finalContentType,
                    'has_media_url' => !empty($finalMediaUrl),
                ]);
                
                // Ensure message is not null or empty (unless we have media_url)
                if (empty($message) && empty($finalMediaUrl)) {
                    Log::error('[ZaloController] Message and media_url are both empty', [
                        'recipient_id' => $recipientId,
                        'recipient_type' => $recipientType,
                    ]);
                    throw new \Exception('Message content or media_url is required');
                }
                
                // If message is empty but we have media_url, use empty string for message
                if (empty($message)) {
                    $message = '';
                }
                
                Log::info('[ZaloController] Calling ZaloNotificationService::sendMessage', [
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'message_length' => strlen($message),
                    'has_styles' => !empty($styles),
                    'styles_count' => $styles ? count($styles) : 0,
                    'account_id' => $accountId,
                    'user_id' => $user->id,
                ]);

                // ✅ PREPARE METADATA with user info (for sent_by_user_id tracking)
                $metadata = [
                    'sent_by_user_id' => $user->id,
                    'sent_by_user_name' => $user->name,
                    'sent_by_user_email' => $user->email,
                    'sent_from' => 'laravel_web',
                ];

                // MULTI-SESSION: Pass account ID to send from the correct account
                $result = $this->zalo->sendMessage(
                    $recipientId,
                    $message,
                    $recipientType,
                    $accountId,
                    $styles, // Pass styles for rich text
                    $mentions, // Pass mentions for group messages
                    $metadata // ← Pass metadata with user_id
                );
                
                // Extract styles from result if available (for self-sent messages)
                $stylesFromResult = $result['data']['styles'] ?? $styles ?? null;
                
                Log::info('[ZaloController] ZaloNotificationService::sendMessage response', [
                    'success' => $result['success'] ?? false,
                    'has_data' => isset($result['data']),
                    'message' => $result['message'] ?? null,
                    'data_keys' => isset($result['data']) ? array_keys($result['data']) : [],
                    'full_result' => $result,
                ]);
                
                // Extract message_id from response - zalo-service returns it in data.message_id
                // Also extract cli_msg_id and all_message_ids for better tracking
                $messageId = $result['data']['message_id'] ?? null;
                $cliMsgId = $result['data']['cli_msg_id'] ?? null;
                $allMessageIds = $result['data']['all_message_ids'] ?? null;
                
                Log::info('[ZaloController] Extracted message IDs from response', [
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                    'all_message_ids' => $allMessageIds,
                    'has_message_id' => !empty($messageId),
                ]);
            }

            // Log result for debugging
            Log::info('[ZaloController] Zalo service response', [
                'success' => $result['success'] ?? false,
                'has_message_id' => !empty($messageId),
                'response_message' => $result['message'] ?? null,
            ]);

            if (!($result['success'] ?? false)) {
                $errorMessage = $result['message'] ?? 'Failed to send message via Zalo service';
                Log::error('[ZaloController] Zalo service returned error', [
                    'error_message' => $errorMessage,
                    'full_response' => $result,
                ]);
                
                // Check if error is related to expired/invalid credentials
                if (str_contains(strtolower($errorMessage), 'zpw_sek') ||
                    str_contains(strtolower($errorMessage), 'cookie') ||
                    str_contains(strtolower($errorMessage), 'expired') ||
                    str_contains(strtolower($errorMessage), 'invalid') ||
                    str_contains(strtolower($errorMessage), 'đăng nhập')) {
                    
                    // Mark account as disconnected
                    $account->update(['is_connected' => false]);
                    
                    Log::warning('[ZaloController] Account credentials expired, marked as disconnected', [
                        'account_id' => $account->id,
                        'error' => $errorMessage,
                    ]);
                    
                    throw new \Exception('Zalo account credentials have expired or are invalid. Please re-login this account in Account Manager.');
                }
                
                throw new \Exception($errorMessage);
            }

            // ✅ Message will be saved by WebSocket when Zalo confirms
            // metadata (sent_by_user_id) will be passed via zalo-service
            Log::info('[ZaloController] Message sent via zalo-service with metadata', [
                'account_id' => $account->id,
                'recipient_id' => $recipientId,
                'recipient_name' => $recipientName,
                'message_length' => strlen($message ?? ''),
                'content_type' => $finalContentType,
                'message_id_from_zalo' => $messageId,
                'has_message_id' => !empty($messageId),
                'sent_by_user_id' => $user->id,
                'will_be_saved_by_websocket' => true,
            ]);

            // Create temporary message object for frontend (will be saved by WebSocket)
            $tempMessage = [
                'id' => null, // Will be set by WebSocket
                'message_id' => $messageId,
                'cli_msg_id' => $cliMsgId,
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'content' => $message ?? '',
                'content_type' => $finalContentType,
                'media_url' => $finalMediaUrl,
                'sent_at' => now()->toISOString(),
                'sent_by_user_id' => $user->id,
                'sent_by_user_name' => $user->name,
                'status' => 'sent',
            ];

            Log::info('[ZaloController] Message sent successfully (will be saved by WebSocket)', [
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'content_type' => $finalContentType,
                'message_id' => $messageId,
                'cli_msg_id' => $cliMsgId,
                'sent_by_user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $tempMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Send message error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a reminder/event in Zalo conversation
     */
    public function createReminder(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $threadId = $request->input('thread_id'); // Group ID or User ID
        $type = $request->input('type', 'group'); // 'user' or 'group'
        $title = $request->input('title');
        $startTime = $request->input('start_time'); // Unix timestamp or ISO date string
        $emoji = $request->input('emoji', '📚');
        $repeat = $request->input('repeat', 0); // 0=None, 1=Daily, 2=Weekly, 3=Monthly

        // Validate required fields
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        if (!$threadId) {
            return response()->json([
                'success' => false,
                'message' => 'thread_id is required',
            ], 400);
        }

        if (!$title) {
            return response()->json([
                'success' => false,
                'message' => 'title is required',
            ], 400);
        }

        if (!$startTime) {
            return response()->json([
                'success' => false,
                'message' => 'start_time is required',
            ], 400);
        }

        // Get account with permissions
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        // Check branch permission: can_send_message (reminders use same permission)
        if (!$this->checkBranchPermission($request, $account, 'can_send_message')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo sự kiện từ tài khoản này.',
                'permission_denied' => true,
            ], 403);
        }

        try {
            Log::info('[ZaloController] createReminder called', [
                'user_id' => $user->id,
                'account_id' => $accountId,
                'thread_id' => $threadId,
                'type' => $type,
                'title' => $title,
                'start_time' => $startTime,
            ]);

            // Check if Zalo service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Call createReminder service method
            $result = $this->zalo->createReminder(
                $threadId,
                $title,
                $startTime,
                $type,
                $emoji,
                $repeat,
                $account->id
            );

            if ($result['success'] ?? false) {
                Log::info('[ZaloController] Reminder created successfully', [
                    'account_id' => $account->id,
                    'thread_id' => $threadId,
                    'title' => $title,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reminder created successfully',
                    'data' => $result['data'] ?? [],
                ]);
            } else {
                Log::error('[ZaloController] Failed to create reminder', [
                    'account_id' => $account->id,
                    'thread_id' => $threadId,
                    'result' => $result,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create reminder',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Create reminder error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId,
                'thread_id' => $threadId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create reminder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread counts for customers (by phone number)
     */
    public function getCustomerUnreadCounts(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');
        $customerIds = $request->input('customer_ids'); // Array of customer IDs
        
        try {
            // Get accounts for this branch
            $accountsQuery = ZaloAccount::where('is_connected', true);

            if ($branchId) {
                $accountsQuery->where('branch_id', $branchId);
            }

            $accounts = $accountsQuery->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            // Get zalo_ids and accountIds for queries (friends use zalo_id, messages use account_id)
            $zaloIds = $accounts->pluck('zalo_account_id')->filter()->unique();
            $accountIds = $accounts->pluck('id');

            // Get customers with their phones
            $customers = \App\Models\Customer::whereIn('id', $customerIds)
                ->select('id', 'phone')
                ->get();

            $unreadByCustomer = [];

            foreach ($customers as $customer) {
                if (!$customer->phone) {
                    continue;
                }

                // Normalize phone number to handle both formats (0xxx and 84xxx)
                $phonesToCheck = [$customer->phone];
                if (substr($customer->phone, 0, 1) === '0') {
                    $phonesToCheck[] = '84' . substr($customer->phone, 1);
                }

                // Find Zalo friend by phone number (friends are now shared via zalo_id)
                $friend = \App\Models\ZaloFriend::whereIn('zalo_account_id', $zaloIds)
                    ->whereIn('phone', $phonesToCheck)
                    ->first();

                if (!$friend) {
                    continue;
                }

                // Count unread messages from this friend (messages still use zalo_account_id)
                $unreadCount = ZaloMessage::whereIn('zalo_account_id', $accountIds)
                    ->where('recipient_id', $friend->zalo_user_id)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->count();
                
                if ($unreadCount > 0) {
                    $unreadByCustomer[$customer->id] = $unreadCount;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $unreadByCustomer,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to get customer unread counts', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread counts',
            ], 500);
        }
    }

    /**
     * Get TOTAL unread count for all accessible customers (for DashboardLayout badge)
     */
    public function getCustomerUnreadTotal(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        try {
            $accountsQuery = ZaloAccount::where('is_connected', true);
            if ($branchId) {
                $accountsQuery->where('branch_id', $branchId);
            }
            $accounts = $accountsQuery->get();

            if ($accounts->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            // Get zalo_ids and accountIds for queries (friends use zalo_id, messages use account_id)
            $zaloIds = $accounts->pluck('zalo_account_id')->filter()->unique();
            $accountIds = $accounts->pluck('id');

            $customersQuery = \App\Models\Customer::query()
                ->accessibleBy($user)
                ->whereNotNull('phone')
                ->select('id', 'phone');
            $customers = $customersQuery->get();

            if ($customers->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $phones = $customers->pluck('phone')->unique()->filter();
            if ($phones->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            // Normalize phone numbers to handle both formats (0xxx and 84xxx)
            $normalizedPhones = [];
            foreach ($phones as $phone) {
                $normalizedPhones[] = $phone; // Original format (0xxx)
                // Convert to international format (84xxx)
                if (substr($phone, 0, 1) === '0') {
                    $normalizedPhones[] = '84' . substr($phone, 1);
                }
            }

            // Friends are now shared via zalo_id
            $friends = \App\Models\ZaloFriend::whereIn('zalo_account_id', $zaloIds)
                ->whereIn('phone', $normalizedPhones)
                ->get();

            if ($friends->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $totalUnread = 0;
            foreach ($friends as $friend) {
                // Messages still use zalo_account_id
                $unreadCount = ZaloMessage::whereIn('zalo_account_id', $accountIds)
                    ->where('recipient_id', $friend->zalo_user_id)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->count();
                $totalUnread += $unreadCount;
            }

            return response()->json(['success' => true, 'data' => ['total_unread' => $totalUnread]]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to get customer unread total', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to get unread total'], 500);
        }
    }

    /**
     * Get conversations (grouped by recipient) - for history view
     */
    /**
     * Sync chat history for an account
     * This will mark the account as synced and ensure all future messages are saved
     * Note: Actual message history is saved via WebSocket listener in real-time
     * This endpoint just marks the sync status and triggers a check
     */
    public function syncHistory(Request $request)
    {
        // Allow API key authentication for zalo-service auto-sync
        $apiKey = $request->header('X-API-Key');
        $expectedKey = config('services.zalo.api_key');
        
        if ($apiKey === $expectedKey) {
            // Called by zalo-service - use zalo_id instead of account_id
            $zaloId = $request->input('zalo_account_id');
            if (!$zaloId) {
                return response()->json([
                    'success' => false,
                    'message' => 'zalo_id is required when using API key',
                ], 400);
            }
            
            $account = ZaloAccount::where('zalo_id', $zaloId)->first();
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }
            
            $accountId = $account->id;
            $user = null; // No user for API key calls
            $branchId = null; // No branch for API key calls
        } else {
            // Called by frontend - try to authenticate with Bearer token
            // Manually authenticate using Sanctum since route is outside auth middleware
            $bearerToken = $request->bearerToken();
            $user = null;
            
            if ($bearerToken) {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
                if ($token) {
                    $user = $token->tokenable;
                }
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }
            
            $accountId = $request->input('account_id');
            $branchId = $request->input('branch_id');

            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'account_id is required',
                ], 400);
            }

            // Get account
            $query = ZaloAccount::accessibleBy($user);
            if ($branchId) {
                $query->forBranch($branchId);
            }
            $account = $query->where('id', $accountId)->first();
        }

        // For API key calls, account is already found above
        if (!$account && !$user) {
            $account = ZaloAccount::find($accountId);
        }

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or access denied',
            ], 404);
        }

        try {
            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Get account info from zalo-service to verify connection (MULTI-SESSION: pass account->id)
            $accountInfo = $this->zalo->getAccountInfo($account->id);
            if (!$accountInfo || empty($accountInfo['zalo_account_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot get account info from Zalo service',
                ], 503);
            }

            // Verify this is the correct account
            if (!empty($account->zalo_id) && (string)$accountInfo['zalo_account_id'] !== (string)$account->zalo_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account mismatch. Please ensure the correct account is active in Zalo service.',
                ], 400);
            }

            // 🚀 CRITICAL: Fetch ALL friends & groups from Zalo API BEFORE fixing Unknown messages
            Log::info('[ZaloController] 🚀 SYNC HISTORY: Fetching friends and groups from Zalo API...');
            $friendsFetchedCount = 0;
            $groupsFetchedCount = 0;
            
            try {
                // Fetch friends from Zalo API
                $this->syncFriends($account);
                $friendsFetchedCount = ZaloFriend::where('zalo_account_id', $account->id)->count();
                Log::info('[ZaloController] ✅ Friends fetched', ['count' => $friendsFetchedCount]);
            } catch (\Exception $e) {
                Log::error('[ZaloController] ⚠️  Failed to fetch friends', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            try {
                // Fetch groups from Zalo API
                $this->syncGroups($account);
                $groupsFetchedCount = ZaloGroup::where('zalo_account_id', $account->id)->count();
                Log::info('[ZaloController] ✅ Groups fetched', ['count' => $groupsFetchedCount]);
            } catch (\Exception $e) {
                Log::error('[ZaloController] ⚠️  Failed to fetch groups', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('[ZaloController] 🎉 SYNC HISTORY: Fetch completed!', [
                'friends' => $friendsFetchedCount,
                'groups' => $groupsFetchedCount,
            ]);

            // Get all friends and groups for this account (NOW includes newly fetched ones)
            $friends = ZaloFriend::where('zalo_account_id', $account->id)->get();
            $groups = ZaloGroup::where('zalo_account_id', $account->id)->get();

            $totalRecipients = $friends->count() + $groups->count();
            $syncedCount = 0;
            $skippedCount = 0;

            // Count existing messages for each recipient
            foreach ($friends as $friend) {
                $existingCount = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('recipient_id', $friend->zalo_user_id)
                    ->where('recipient_type', 'user')
                    ->count();
                
                if ($existingCount > 0) {
                    $syncedCount++;
                } else {
                    $skippedCount++;
                }
            }

            foreach ($groups as $group) {
                $existingCount = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('recipient_id', $group->zalo_group_id)
                    ->where('recipient_type', 'group')
                    ->count();
                
                if ($existingCount > 0) {
                    $syncedCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Update last_sync_at
            $account->last_sync_at = now();
            $account->save();
            
            // Fix Unknown recipient names in existing messages
            $fixedCount = 0;
            $unknownMessages = ZaloMessage::where('zalo_account_id', $account->id)
                ->where(function($query) {
                    $query->where('recipient_name', 'Unknown')
                          ->orWhereNull('recipient_name')
                          ->orWhere('recipient_name', '');
                })
                ->get();
            
            if ($unknownMessages->count() > 0) {
                Log::info('[ZaloController] Fixing Unknown recipient names', [
                    'account_id' => $account->id,
                    'unknown_count' => $unknownMessages->count(),
                ]);
                
                foreach ($unknownMessages as $msg) {
                    $newName = null;
                    
                    if ($msg->recipient_type === 'user') {
                        $friend = ZaloFriend::where('zalo_account_id', $account->id)
                            ->where('zalo_user_id', $msg->recipient_id)
                            ->first();
                        
                        if ($friend && $friend->name && $friend->name !== 'Unknown') {
                            $newName = $friend->name;
                        }
                    } else {
                        $group = ZaloGroup::where('zalo_account_id', $account->id)
                            ->where('zalo_group_id', $msg->recipient_id)
                            ->first();
                        
                        if ($group && $group->name && $group->name !== 'Unknown') {
                            $newName = $group->name;
                        }
                    }
                    
                    if ($newName) {
                        $msg->recipient_name = $newName;
                        $msg->save();
                        $fixedCount++;
                    }
                }
                
                Log::info('[ZaloController] Fixed Unknown recipient names', [
                    'account_id' => $account->id,
                    'fixed_count' => $fixedCount,
                ]);
            }

            Log::info('[ZaloController] History sync completed', [
                'account_id' => $account->id,
                'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                'total_recipients' => $totalRecipients,
                'synced_recipients' => $syncedCount,
                'skipped_recipients' => $skippedCount,
                'fixed_unknown_messages' => $fixedCount,
            ]);

            // 🔔 Broadcast to frontend that sync completed and conversations need refresh
            if ($fixedCount > 0 || $friendsFetchedCount > 0 || $groupsFetchedCount > 0) {
                try {
                    $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)->post(
                        config('services.zalo.base_url') . '/api/socket/broadcast',
                        [
                            'event' => 'conversations_updated',
                            'account_id' => $account->id, // Top-level for room targeting
                            'data' => [
                                'account_id' => $account->id,
                                'fixed_count' => $fixedCount,
                                'friends_fetched' => $friendsFetchedCount,
                                'groups_fetched' => $groupsFetchedCount,
                                'message' => 'Conversations updated. Please refresh.',
                            ],
                        ]
                    );
                    if ($broadcastResponse->successful()) {
                        Log::info('[ZaloController] Conversations update broadcasted via Socket.IO');
                    }
                } catch (\Exception $broadcastError) {
                    Log::warning('[ZaloController] Failed to broadcast conversations update', [
                        'error' => $broadcastError->getMessage(),
                    ]);
                }
            }
            
            // Build success message
            $message = 'History sync completed. All future messages will be saved automatically via WebSocket listener.';
            if ($fixedCount > 0) {
                $message .= " Fixed {$fixedCount} message(s) with Unknown recipient names.";
            }
            if ($friendsFetchedCount > 0 || $groupsFetchedCount > 0) {
                $message .= " Fetched {$friendsFetchedCount} friends and {$groupsFetchedCount} groups from Zalo.";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'account_id' => $account->id,
                    'total_recipients' => $totalRecipients,
                    'synced_recipients' => $syncedCount,
                    'skipped_recipients' => $skippedCount,
                    'fixed_unknown_messages' => $fixedCount,
                    'friends_fetched' => $friendsFetchedCount,
                    'groups_fetched' => $groupsFetchedCount,
                    'last_sync_at' => $account->last_sync_at->toISOString(),
                    'note' => 'Messages are saved in real-time via WebSocket listener. This sync marks the account as synced.',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] History sync failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload image(s) for Zalo message
     * Supports both single image upload (backward compatible) and multiple images (dropzone)
     * 
     * Single image: use field 'image'
     * Multiple images: use field 'images[]' (array)
     */
    public function uploadImage(Request $request)
    {
        $user = $request->user();
        
        // Detect if uploading single or multiple images
        $isSingle = $request->hasFile('image');
        $isMultiple = $request->hasFile('images');
        
        // Log request details for debugging
        Log::info('[ZaloController] uploadImage request received', [
            'user_id' => $user?->id,
            'is_single' => $isSingle,
            'is_multiple' => $isMultiple,
            'images_count' => $isMultiple ? count($request->file('images')) : 0,
            'all_inputs' => $request->all(),
            'file_keys' => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);
        
        // Validate that at least one type of upload is present
        if (!$isSingle && !$isMultiple) {
            Log::error('[ZaloController] uploadImage: No image file provided', [
                'has_single' => $isSingle,
                'has_multiple' => $isMultiple,
                'all_files' => $request->allFiles(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Image file(s) required. Use field "image" for single or "images[]" for multiple.',
                'debug' => [
                    'all_file_keys' => array_keys($request->allFiles()),
                    'content_type' => $request->header('Content-Type'),
                ],
            ], 400);
        }
        
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');

        // Validate required fields
        if (!$accountId) {
            $allInputs = $request->all();
            $accountId = $allInputs['account_id'] ?? null;
            
            if (!$accountId) {
                Log::error('[ZaloController] uploadImage: account_id is missing');
                return response()->json([
                    'success' => false,
                    'message' => 'account_id is required',
                ], 400);
            }
        }
        
        // Convert account_id to integer if it's a string
        $accountId = is_numeric($accountId) ? (int) $accountId : $accountId;

        try {
            // Get account with permissions
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission',
                ], 404);
            }

            // Check branch permission: can_send_message
            if (!$this->checkBranchPermission($request, $account, 'can_send_message')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền gửi tin nhắn từ tài khoản này.',
                    'permission_denied' => true,
                ], 403);
            }

            // Define allowed types and max size
            $allowedMimes = [
                'image/jpeg', 
                'image/jpg', 
                'image/png', 
                'image/gif', 
                'image/webp',
                'image/avif',
            ];
            $maxSize = 5 * 1024 * 1024; // 5MB per image
            $maxCount = 10; // Max 10 images for multiple upload

            // Handle single or multiple upload
            if ($isSingle) {
                // SINGLE IMAGE UPLOAD (backward compatible)
                $file = $request->file('image');
                
                // Validate
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP, AVIF.',
                    ], 400);
                }
                
                if ($file->getSize() > $maxSize) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image size exceeds 5MB limit',
                    ], 400);
                }
                
                // Store image
                $timestamp = now()->format('YmdHis');
                $filename = $timestamp . '_' . $file->getClientOriginalName();
                $path = $file->storeAs("zalo/images/{$account->id}", $filename, 'public');
                $url = asset('storage/' . $path);
                $absolutePath = storage_path('app/public/' . $path);

                Log::info('[ZaloController] Single image uploaded', [
                    'account_id' => $account->id,
                    'file_path' => $path,
                    'size' => $file->getSize(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'data' => [
                        'url' => $url,
                        'absolute_path' => $absolutePath,
                        'path' => $path,
                        'filename' => $filename,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ],
                ]);
                
            } else {
                // MULTIPLE IMAGES UPLOAD (dropzone support)
                $files = $request->file('images');
                
                // Validate count
                if (count($files) > $maxCount) {
                    return response()->json([
                        'success' => false,
                        'message' => "Maximum {$maxCount} images allowed. You uploaded " . count($files),
                    ], 400);
                }
                
                $uploadedImages = [];
                $errors = [];
                
                foreach ($files as $index => $file) {
                    try {
                        // Validate each file
                        if (!in_array($file->getMimeType(), $allowedMimes)) {
                            $errors[] = [
                                'index' => $index,
                                'filename' => $file->getClientOriginalName(),
                                'error' => 'Invalid image type',
                            ];
                            continue;
                        }
                        
                        if ($file->getSize() > $maxSize) {
                            $errors[] = [
                                'index' => $index,
                                'filename' => $file->getClientOriginalName(),
                                'error' => 'File size exceeds 5MB',
                            ];
                            continue;
                        }
                        
                        // Store image
                        $timestamp = now()->format('YmdHis') . '_' . $index;
                        $filename = $timestamp . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs("zalo/images/{$account->id}", $filename, 'public');
                        $url = asset('storage/' . $path);
                        $absolutePath = storage_path('app/public/' . $path);
                        
                        $uploadedImages[] = [
                            'index' => $index,
                            'url' => $url,
                            'absolute_path' => $absolutePath,
                            'path' => $path,
                            'filename' => $filename,
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ];
                        
                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $index,
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage(),
                        ];
                    }
                }
                
                Log::info('[ZaloController] Multiple images uploaded', [
                    'account_id' => $account->id,
                    'total_uploaded' => count($uploadedImages),
                    'total_errors' => count($errors),
                ]);
                
                $successCount = count($uploadedImages);
                $totalCount = count($files);
                
                return response()->json([
                    'success' => $successCount > 0,
                    'message' => "Uploaded {$successCount}/{$totalCount} images successfully",
                    'data' => [
                        'images' => $uploadedImages,
                        'errors' => $errors,
                        'total' => $totalCount,
                        'success_count' => $successCount,
                        'error_count' => count($errors),
                    ],
                ], $successCount > 0 ? 200 : 400);
            }
            
        } catch (\Exception $e) {
            Log::error('[ZaloController] Upload image error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image(s): ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload file (document) for sending via Zalo
     */
    public function uploadFile(Request $request)
    {
        $user = $request->user();
        
        // Log request details for debugging
        Log::info('[ZaloController] uploadFile request received', [
            'user_id' => $user?->id,
            'has_file' => $request->hasFile('file'),
            'all_inputs' => $request->all(),
            'file_keys' => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);
        
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');

        // Validate required fields
        if (!$accountId) {
            $allInputs = $request->all();
            $accountId = $allInputs['account_id'] ?? null;
            
            if (!$accountId) {
                Log::error('[ZaloController] uploadFile: account_id is missing', [
                    'input_account_id' => $request->input('account_id'),
                    'all_inputs' => $allInputs,
                    'all_files' => $request->allFiles(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'account_id is required',
                    'debug' => [
                        'has_file' => $request->hasFile('file'),
                        'all_input_keys' => array_keys($allInputs),
                        'all_file_keys' => array_keys($request->allFiles()),
                    ],
                ], 400);
            }
        }
        
        $accountId = is_numeric($accountId) ? (int) $accountId : $accountId;

        if (!$request->hasFile('file')) {
            Log::error('[ZaloController] uploadFile: file is missing', [
                'has_file' => $request->hasFile('file'),
                'all_files' => $request->allFiles(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'File is required',
            ], 400);
        }

        $file = $request->file('file');
        
        Log::info('[ZaloController] uploadFile: File received', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
        ]);

        // Validate file type - Support common document formats
        $allowedMimes = [
            'application/pdf',
            'application/msword', // .doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.ms-excel', // .xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-powerpoint', // .ppt
            'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
            'text/plain', // .txt
            'application/zip',
            'application/x-rar-compressed',
        ];
        
        $fileMimeType = $file->getMimeType();
        if (!in_array($fileMimeType, $allowedMimes)) {
            Log::error('[ZaloController] uploadFile: Invalid file type', [
                'mime_type' => $fileMimeType,
                'allowed_mimes' => $allowedMimes,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, RAR. Received: ' . $fileMimeType,
            ], 400);
        }

        // Validate file size (max 50MB for documents)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file->getSize() > $maxSize) {
            return response()->json([
                'success' => false,
                'message' => 'File size exceeds 50MB limit',
            ], 400);
        }

        try {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission',
                ], 404);
            }

            // Store file in public storage
            $timestamp = now()->format('YmdHis');
            $filename = $timestamp . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("zalo/files/{$account->id}", $filename, 'public');

            // Generate public URL and absolute path
            $url = asset('storage/' . $path);
            $absolutePath = storage_path('app/public/' . $path);

            Log::info('[ZaloController] File uploaded successfully', [
                'account_id' => $account->id,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'file_path' => $path,
                'absolute_path' => $absolutePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'url' => $url,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => $url,
                    'absolute_path' => $absolutePath,
                    'path' => $path,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Upload file error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload video file for Zalo message
     */
    public function uploadVideo(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        if (!$request->hasFile('video')) {
            return response()->json([
                'success' => false,
                'message' => 'Video file is required',
            ], 400);
        }

        $file = $request->file('video');
        
        // Validate file type - Support video formats
        $allowedMimes = [
            'video/mp4',
            'video/quicktime', // .mov
            'video/x-msvideo', // .avi
            'video/x-ms-wmv', // .wmv
            'video/webm',
            'video/ogg',
        ];
        
        $fileMimeType = $file->getMimeType();
        if (!in_array($fileMimeType, $allowedMimes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid video file type. Allowed: MP4, MOV, AVI, WMV, WEBM, OGG. Received: ' . $fileMimeType,
            ], 400);
        }

        // Validate file size (max 100MB for videos)
        $maxSize = 100 * 1024 * 1024; // 100MB
        if ($file->getSize() > $maxSize) {
            return response()->json([
                'success' => false,
                'message' => 'Video size exceeds 100MB limit',
            ], 400);
        }

        try {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission',
                ], 404);
            }

            // Store video in public storage
            $timestamp = now()->format('YmdHis');
            $filename = $timestamp . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("zalo/videos/{$account->id}", $filename, 'public');

            // Generate public URL and absolute path
            $url = asset('storage/' . $path);
            $absolutePath = storage_path('app/public/' . $path);

            // Try to get video metadata (duration, width, height)
            // Use reasonable default values instead of 0 (which Zalo API rejects)
            // In production, you'd use ffmpeg/ffprobe to get actual values
            $duration = null; // Don't send if unknown
            $width = null; // Don't send if unknown  
            $height = null; // Don't send if unknown
            $thumbnailUrl = $url; // Use video URL as thumbnail fallback
            
            // TODO: Use ffmpeg to extract metadata and thumbnail:
            // $duration = exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '$absolutePath'");
            // $width = exec("ffprobe -v error -select_streams v:0 -show_entries stream=width -of default=noprint_wrappers=1:nokey=1 '$absolutePath'");
            // $height = exec("ffprobe -v error -select_streams v:0 -show_entries stream=height -of default=noprint_wrappers=1:nokey=1 '$absolutePath'");
            // exec("ffmpeg -i '$absolutePath' -ss 00:00:01 -vframes 1 '$thumbnailPath'");

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => [
                    'url' => $url,
                    'absolute_path' => $absolutePath,
                    'path' => $path,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'duration' => $duration,
                    'width' => $width,
                    'height' => $height,
                    'thumbnail_url' => $thumbnailUrl,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Upload video error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload audio file for Zalo message
     */
    public function uploadAudio(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        if (!$request->hasFile('audio')) {
            return response()->json([
                'success' => false,
                'message' => 'Audio file is required',
            ], 400);
        }

        $file = $request->file('audio');
        
        // Validate file type - Support audio formats
        $allowedMimes = [
            'audio/mpeg', // .mp3
            'audio/mp3',
            'audio/mp4', // .m4a
            'audio/x-m4a',
            'audio/wav',
            'audio/x-wav',
            'audio/ogg',
            'audio/webm',
            'video/webm', // MediaRecorder might use video/webm for audio-only recordings
        ];
        
        $fileMimeType = $file->getMimeType();
        if (!in_array($fileMimeType, $allowedMimes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio file type. Allowed: MP3, M4A, WAV, OGG, WEBM. Received: ' . $fileMimeType,
            ], 400);
        }

        // Validate file size (max 50MB for audio)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file->getSize() > $maxSize) {
            return response()->json([
                'success' => false,
                'message' => 'Audio size exceeds 50MB limit',
            ], 400);
        }

        try {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission',
                ], 404);
            }

            // Store audio in public storage
            $timestamp = now()->format('YmdHis');
            $filename = $timestamp . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("zalo/audio/{$account->id}", $filename, 'public');

            // Generate public URL and absolute path
            $url = asset('storage/' . $path);
            $absolutePath = storage_path('app/public/' . $path);

            return response()->json([
                'success' => true,
                'message' => 'Audio uploaded successfully',
                'data' => [
                    'url' => $url,
                    'absolute_path' => $absolutePath,
                    'path' => $path,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Upload audio error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload audio: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload folder (multiple files) and create zip file
     */
    public function uploadFolder(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $folderName = $request->input('folder_name', 'Folder');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        if (!$request->hasFile('files')) {
            return response()->json([
                'success' => false,
                'message' => 'Files are required',
            ], 400);
        }

        $files = $request->file('files');
        if (!is_array($files)) {
            $files = [$files];
        }

        if (count($files) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'At least one file is required',
            ], 400);
        }

        try {
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission',
                ], 404);
            }

            // Create temporary directory for files
            $tempDir = storage_path('app/temp/zalo/folders/' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Store all files in temp directory
            $filePaths = [];
            foreach ($files as $file) {
                $filename = $file->getClientOriginalName();
                $filePath = $tempDir . '/' . $filename;
                $file->move($tempDir, $filename);
                $filePaths[] = [
                    'path' => $filePath,
                    'name' => $filename,
                ];
            }

            // Create zip file
            $zipFileName = $folderName . '_' . now()->format('YmdHis') . '.zip';
            $zipPath = storage_path('app/public/zalo/folders/' . $account->id);
            if (!file_exists($zipPath)) {
                mkdir($zipPath, 0755, true);
            }
            $zipFilePath = $zipPath . '/' . $zipFileName;

            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($filePaths as $fileInfo) {
                    $zip->addFile($fileInfo['path'], $fileInfo['name']);
                }
                $zip->close();

                // Clean up temp directory
                foreach ($filePaths as $fileInfo) {
                    if (file_exists($fileInfo['path'])) {
                        unlink($fileInfo['path']);
                    }
                }
                if (file_exists($tempDir)) {
                    rmdir($tempDir);
                }

                // Generate public URL and absolute path
                $relativePath = 'zalo/folders/' . $account->id . '/' . $zipFileName;
                $url = asset('storage/' . $relativePath);
                $absolutePath = storage_path('app/public/' . $relativePath);

                Log::info('[ZaloController] Folder uploaded and zipped successfully', [
                    'account_id' => $account->id,
                    'folder_name' => $folderName,
                    'file_count' => count($files),
                    'zip_path' => $absolutePath,
                    'zip_size' => filesize($absolutePath),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Folder uploaded and zipped successfully',
                    'data' => [
                        'url' => $url,
                        'absolute_path' => $absolutePath,
                        'path' => $relativePath,
                        'filename' => $zipFileName,
                        'folder_name' => $folderName,
                        'file_count' => count($files),
                        'size' => filesize($absolutePath),
                        'mime_type' => 'application/zip',
                    ],
                ]);
            } else {
                // Clean up on failure
                foreach ($filePaths as $fileInfo) {
                    if (file_exists($fileInfo['path'])) {
                        unlink($fileInfo['path']);
                    }
                }
                if (file_exists($tempDir)) {
                    rmdir($tempDir);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create zip file',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Upload folder error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive message from Zalo service (called by zalo-service when message received)
     * This endpoint is called by zalo-service, not by frontend
     */
    public function receiveMessage(Request $request)
    {
        // Verify API key (from zalo-service)
        $apiKey = $request->header('X-API-Key');
        $expectedKey = config('services.zalo.api_key');
        
        if ($apiKey !== $expectedKey) {
            Log::warning('[ZaloController] Invalid API key for receiveMessage');
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }
        
        // MULTI-SESSION: Support both account_id and zalo_id for finding account
        $accountId = $request->input('account_id'); // Preferred for multi-session
        $zaloId = $request->input('zalo_account_id'); // Backward compatibility
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $messageId = $request->input('message_id');
        $isSelf = $request->input('is_self', false); // CRITICAL: Indicates if message is from same account (other device)
        $cliMsgId = $request->input('cli_msg_id');
        $globalMsgId = $request->input('global_msg_id');
        $realMsgId = $request->input('real_msg_id');
        $allMessageIds = $request->input('all_message_ids', []);
        $content = $request->input('content', '') ?? '';
        $contentType = $request->input('content_type', 'text');
        $styles = $request->input('styles'); // Rich text styles array

        // Ensure content is always a string (never null)
        if ($content === null) {
            $content = '';
        }
        $mediaUrl = $request->input('media_url');
        $stickerData = $request->input('sticker_data'); // Sticker metadata (id, stickerUrl, etc.)
        $fileData = $request->input('file_data'); // File metadata (title, href, params, etc.)
        $quote = $request->input('quote');
        $sentAt = $request->input('sent_at');
        $senderId = $request->input('sender_id'); // Sender Zalo user ID (for group messages)
        $senderName = $request->input('sender_name'); // Sender display name (for group messages)
        $actualSenderId = $request->input('actual_sender_id') ?? $senderId; // Actual sender ID (for group messages)
        $actualSenderName = $request->input('actual_sender_name') ?? $senderName; // Actual sender name (for group messages)
        $recipientAvatar = $request->input('recipient_avatar'); // Recipient avatar URL (from zalo-service)
        
        // ✅ RECEIVE sent_by_user_id FROM ZALO-SERVICE (metadata passed through)
        $sentByUserId = $request->input('sent_by_user_id'); // User ID who sent this message (from Laravel)
        $sentByUserName = $request->input('sent_by_user_name'); // User name (from Laravel)
        $sentFrom = $request->input('sent_from'); // Source: 'laravel_web', etc.

        // MULTI-SESSION: Require either account_id or zalo_id
        if (!$accountId && !$zaloId) {
            return response()->json([
                'success' => false,
                'message' => 'Either account_id or zalo_id is required',
            ], 400);
        }

        if (!$recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'recipient_id is required',
            ], 400);
        }

        try {
            // MULTI-BRANCH: Find account(s) by account_id (preferred) or zalo_id (multi-branch support)
            $account = null;
            $accounts = collect();
            $isMultiBranch = false;

            if ($accountId) {
                // Specific account_id provided: Use that account only (backward compatibility)
                $account = ZaloAccount::find($accountId);
                if (!$account) {
                    Log::warning('[ZaloController] Account not found for account_id', ['account_id' => $accountId]);
                }
                $accounts = $account ? collect([$account]) : collect();
            }

            // Fallback to zalo_id: Get ALL accounts with this zalo_id (multi-branch support)
            if ($accounts->isEmpty() && $zaloId) {
                $accounts = \App\Services\ZaloMultiBranchService::getAccountsForZaloId($zaloId);
                if ($accounts->isEmpty()) {
                    Log::warning('[ZaloController] No accounts found for zalo_id', ['zalo_account_id' => $zaloId]);
                } else {
                    $account = $accounts->first(); // Primary account for processing
                    $isMultiBranch = $accounts->count() > 1;
                    Log::info('[ZaloController] Multi-branch mode activated', [
                        'zalo_account_id' => $zaloId,
                        'account_count' => $accounts->count(),
                        'branch_ids' => $accounts->pluck('branch_id')->toArray(),
                    ]);
                }
            }

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

        Log::info('[ZaloController] Webhook received message', [
            'account_id' => $account->id,
            'zalo_account_id' => $account->id,
            'recipient_id' => $recipientId,
            'message_id' => $messageId,
            'is_self' => $isSelf,
            'sent_by_user_id' => $sentByUserId, // ← FROM METADATA
            'sent_from' => $sentFrom,
            'has_account_id_param' => !!$accountId,
            'has_zalo_id_param' => !!$zaloId,
            'is_multi_branch' => $isMultiBranch,
            'total_accounts' => $accounts->count(),
        ]);
            
            // 🔥 FIX: Check if this message already exists with a DIFFERENT recipient_id
            // This prevents duplicate messages when Zalo sends same message to multiple groups
            $existingMessage = \App\Models\ZaloMessage::where('zalo_account_id', $account->id)
                ->where('message_id', $messageId)
                ->first();
            
            if ($existingMessage && $existingMessage->recipient_id !== $recipientId) {
                Log::warning('[ZaloController] ⚠️ DUPLICATE MESSAGE DETECTED - Same message_id but different recipient_id', [
                    'message_id' => $messageId,
                    'existing_recipient_id' => $existingMessage->recipient_id,
                    'new_recipient_id' => $recipientId,
                    'existing_recipient_name' => $existingMessage->recipient_name,
                    'existing_conversation_id' => $existingMessage->zalo_conversation_id,
                ]);
                
                // Skip this duplicate message - keep the first one
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate message skipped',
                    'skipped' => true,
                ]);
            }
            
            // Get recipient name - AGGRESSIVE SYNC to ensure recipient always exists
            $recipientName = null;
            if ($recipientType === 'user') {
                $friend = ZaloFriend::where('zalo_account_id', $account->id)
                    ->where('zalo_user_id', $recipientId)
                    ->first();
                
                // If friend not found or name is Unknown, fetch from Zalo
                if (!$friend || !$friend->name || $friend->name === 'Unknown') {
                    $cacheKey = "zalo_sync_friend_{$account->id}_{$recipientId}";
                    $syncAttempted = false;
                    
                    // Try sync friends list first (with rate limit)
                    if (!cache()->has($cacheKey)) {
                        Log::info('[ZaloController] Friend not found/incomplete, syncing friends list', [
                            'recipient_id' => $recipientId,
                            'account_id' => $account->id,
                            'has_friend' => !!$friend,
                            'friend_name' => $friend->name ?? 'null',
                        ]);
                        
                        try {
                            $this->syncFriends($account);
                            $syncAttempted = true;
                            
                            // Try to find again after sync
                            $friend = ZaloFriend::where('zalo_account_id', $account->id)
                                ->where('zalo_user_id', $recipientId)
                                ->first();
                        } catch (\Exception $e) {
                            Log::error('[ZaloController] Failed to sync friends list', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // If still not found, fetch specific user info from Zalo API
                    if (!$friend || !$friend->name || $friend->name === 'Unknown') {
                        Log::info('[ZaloController] Friend still not found, fetching specific user info', [
                            'recipient_id' => $recipientId,
                        ]);
                        
                        try {
                            // Call zalo-service to get specific user info
                            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                                'X-API-Key' => config('services.zalo.api_key'),
                            ])->get(config('services.zalo.base_url') . '/api/user/info/' . $recipientId, [
                                'accountId' => $account->id,
                            ]);
                            
                            if ($response->successful() && $response->json('success')) {
                                $userData = $response->json('data');

                                // Save to database - use find-then-update pattern to avoid duplicate key errors
                                $recipientIdStr = (string) $recipientId;
                                $friend = ZaloFriend::where('zalo_account_id', $account->id)
                                    ->where('zalo_user_id', $recipientIdStr)
                                    ->first();

                                $friendData = [
                                    'name' => $userData['display_name'] ?? $userData['name'] ?? 'Unknown',
                                    'display_name' => $userData['display_name'] ?? $userData['name'] ?? null,
                                    'avatar' => $userData['avatar'] ?? $userData['avatar_url'] ?? null,
                                ];

                                if ($friend) {
                                    $friend->update($friendData);
                                } else {
                                    try {
                                        $friend = ZaloFriend::create(array_merge($friendData, [
                                            'zalo_account_id' => $account->id,
                                            'zalo_user_id' => $recipientIdStr,
                                        ]));
                                    } catch (\Illuminate\Database\QueryException $e) {
                                        if ($e->errorInfo[1] == 1062) {
                                            // Race condition - fetch existing
                                            $friend = ZaloFriend::where('zalo_account_id', $account->id)
                                                ->where('zalo_user_id', $recipientIdStr)
                                                ->first();
                                        } else {
                                            throw $e;
                                        }
                                    }
                                }
                                
                                Log::info('[ZaloController] Friend info fetched and saved', [
                                    'recipient_id' => $recipientId,
                                    'name' => $friend->name,
                                ]);
                                
                                // 🔔 OPTION C HYBRID: Broadcast to frontend that friend was updated
                                try {
                                    $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)->post(
                                        config('services.zalo.base_url') . '/api/socket/broadcast',
                                        [
                                            'event' => 'friend_updated',
                                            'account_id' => $account->id, // Top-level for room targeting
                                            'data' => [
                                                'account_id' => $account->id,
                                                'friend_id' => $recipientId,
                                                'name' => $friend->name,
                                                'avatar' => $friend->avatar,
                                            ],
                                        ]
                                    );
                                    if ($broadcastResponse->successful()) {
                                        Log::info('[ZaloController] Friend update broadcasted via Socket.IO');
                                    }
                                } catch (\Exception $broadcastError) {
                                    Log::warning('[ZaloController] Failed to broadcast friend update', [
                                        'error' => $broadcastError->getMessage(),
                                    ]);
                                }
                                
                                $syncAttempted = true;
                            }
                        } catch (\Exception $e) {
                            Log::error('[ZaloController] Failed to fetch specific user info', [
                                'recipient_id' => $recipientId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // 🔥 REMOVED BUGGY FALLBACK: Never use sender_name for recipient's friend record!
                    // The sender_name is the name of the person who SENT the message (YOU),
                    // NOT the recipient (the person you're messaging).
                    // Using sender_name for recipient_id would save YOUR name as THEIR name!

                    // Only cache if we successfully found the friend
                    // OPTION C HYBRID: Cache for 7 days (604800 seconds) since recipients rarely change
                    if ($syncAttempted && $friend && $friend->name && $friend->name !== 'Unknown') {
                        cache()->put($cacheKey, true, 604800); // 7 days
                    }
                }

                $recipientName = $friend ? ($friend->name ?? 'Unknown') : 'Unknown';
            } else {
                $group = ZaloGroup::where('zalo_account_id', $account->id)
                    ->where('zalo_group_id', $recipientId)
                    ->first();
                
                // If group not found or name is Unknown, fetch from Zalo
                if (!$group || !$group->name || $group->name === 'Unknown') {
                    $cacheKey = "zalo_sync_group_{$account->id}_{$recipientId}";
                    $syncAttempted = false;
                    
                    // Try sync groups list first (with rate limit)
                    if (!cache()->has($cacheKey)) {
                        Log::info('[ZaloController] Group not found/incomplete, syncing groups list', [
                            'recipient_id' => $recipientId,
                            'account_id' => $account->id,
                            'has_group' => !!$group,
                            'group_name' => $group->name ?? 'null',
                        ]);
                        
                        try {
                            $this->syncGroups($account);
                            $syncAttempted = true;
                            
                            // Try to find again after sync
                            $group = ZaloGroup::where('zalo_account_id', $account->id)
                                ->where('zalo_group_id', $recipientId)
                                ->first();
                        } catch (\Exception $e) {
                            Log::error('[ZaloController] Failed to sync groups list', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // If still not found, fetch specific group info from Zalo API
                    if (!$group || !$group->name || $group->name === 'Unknown') {
                        Log::info('[ZaloController] Group still not found, fetching specific group info', [
                            'recipient_id' => $recipientId,
                        ]);
                        
                        try {
                            // Call zalo-service to get specific group info
                            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                                'X-API-Key' => config('services.zalo.api_key'),
                            ])->get(config('services.zalo.base_url') . '/api/group/info/' . $recipientId, [
                                'accountId' => $account->id,
                            ]);
                            
                            if ($response->successful() && $response->json('success')) {
                                $groupData = $response->json('data');
                                
                                // Save to database
                                $group = ZaloGroup::updateOrCreate(
                                    [
                                        'zalo_account_id' => $account->id,
                                        'zalo_group_id' => $recipientId,
                                    ],
                                    [
                                        'name' => $groupData['name'] ?? 'Unknown',
                                        'avatar' => $groupData['avatar'] ?? $groupData['avatar_url'] ?? null,
                                        'type' => $groupData['type'] ?? 0,
                                        'creator_id' => $groupData['creator_id'] ?? null,
                                        'description' => $groupData['description'] ?? null,
                                    ]
                                );
                                
                                Log::info('[ZaloController] Group info fetched and saved', [
                                    'recipient_id' => $recipientId,
                                    'name' => $group->name,
                                ]);
                                
                                // 🔔 OPTION C HYBRID: Broadcast to frontend that group was updated
                                try {
                                    $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)->post(
                                        config('services.zalo.base_url') . '/api/socket/broadcast',
                                        [
                                            'event' => 'group_updated',
                                            'account_id' => $account->id, // Top-level for room targeting
                                            'data' => [
                                                'account_id' => $account->id,
                                                'group_id' => $recipientId,
                                                'name' => $group->name,
                                                'avatar' => $group->avatar,
                                            ],
                                        ]
                                    );
                                    if ($broadcastResponse->successful()) {
                                        Log::info('[ZaloController] Group update broadcasted via Socket.IO');
                                    }
                                } catch (\Exception $broadcastError) {
                                    Log::warning('[ZaloController] Failed to broadcast group update', [
                                        'error' => $broadcastError->getMessage(),
                                    ]);
                                }
                                
                                $syncAttempted = true;
                            }
                        } catch (\Exception $e) {
                            Log::error('[ZaloController] Failed to fetch specific group info', [
                                'recipient_id' => $recipientId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // Only cache if we successfully found the group
                    // OPTION C HYBRID: Cache for 7 days (604800 seconds) since recipients rarely change
                    if ($syncAttempted && $group && $group->name && $group->name !== 'Unknown') {
                        cache()->put($cacheKey, true, 604800); // 7 days
                    }
                }
                
                $recipientName = $group ? $group->name : 'Unknown';
                
                // CRITICAL: Sync group members if message is from a group
                // This ensures we have sender avatar info
                if ($group && $recipientType === 'group' && $senderId) {
                    $memberCacheKey = "zalo_sync_group_member_{$group->id}_{$senderId}";
                    
                    // Check if sender exists in group members
                    $member = \App\Models\ZaloGroupMember::where('zalo_group_id', $group->id)
                        ->where('zalo_user_id', $senderId)
                        ->first();
                    
                    // If sender not found, sync group members (with rate limit)
                    if (!$member && !cache()->has($memberCacheKey)) {
                        Log::info('[ZaloController] Group member not found, syncing members', [
                            'group_id' => $recipientId,
                            'sender_id' => $senderId,
                            'group_db_id' => $group->id,
                        ]);
                        
                        try {
                            // Call getGroupMembers with sync=true
                            $response = \Illuminate\Support\Facades\Http::timeout(60)->withHeaders([
                                'X-API-Key' => config('services.zalo.api_key'),
                            ])->get(config('services.zalo.base_url') . '/api/group/members/' . $recipientId, [
                                'accountId' => $account->id,
                            ]);
                            
                            if ($response->successful() && $response->json('success')) {
                                $membersFromApi = $response->json('data', []);
                                
                                // Save members to database
                                foreach ($membersFromApi as $memberData) {
                                    $userId = $memberData['id'] ?? null;
                                    if (!$userId) continue;
                                    
                                    \App\Models\ZaloGroupMember::updateOrCreate(
                                        [
                                            'zalo_group_id' => $group->id,
                                            'zalo_user_id' => $userId,
                                        ],
                                        [
                                            'display_name' => $memberData['display_name'] ?? 'Unknown',
                                            'avatar_url' => $memberData['avatar_url'] ?? null,
                                            'is_admin' => $memberData['is_admin'] ?? false,
                                        ]
                                    );
                                }
                                
                                Log::info('[ZaloController] Group members synced', [
                                    'count' => count($membersFromApi),
                                ]);
                                
                                // Broadcast to frontend via Socket.IO that members were updated
                                try {
                                    $socketResponse = \Illuminate\Support\Facades\Http::timeout(5)->post(
                                        config('services.zalo.base_url') . '/api/socket/broadcast',
                                        [
                                            'event' => 'group_members_updated',
                                            'account_id' => $account->id, // Top-level for room targeting
                                            'data' => [
                                                'account_id' => $account->id,
                                                'group_id' => $recipientId,
                                                'group_db_id' => $group->id,
                                                'members_count' => count($membersFromApi),
                                            ],
                                        ]
                                    );
                                    
                                    if ($socketResponse->successful()) {
                                        Log::info('[ZaloController] Group members update broadcasted via Socket.IO');
                                    }
                                } catch (\Exception $broadcastError) {
                                    Log::warning('[ZaloController] Failed to broadcast group members update', [
                                        'error' => $broadcastError->getMessage(),
                                    ]);
                                }
                            }
                            
                            // OPTION C HYBRID: Cache for 7 days since group members rarely change
                            cache()->put($memberCacheKey, true, 604800); // 7 days
                        } catch (\Exception $e) {
                            Log::error('[ZaloController] Failed to sync group members', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // IMPROVEMENT: If member still not found but we have senderName from message data, save it
                    if ($senderId && $senderName) {
                        $member = \App\Models\ZaloGroupMember::where('zalo_group_id', $group->id)
                            ->where('zalo_user_id', $senderId)
                            ->first();

                        if (!$member || !$member->display_name || $member->display_name === 'Unknown') {
                            Log::info('[ZaloController] Using senderName from message data for group member', [
                                'group_id' => $recipientId,
                                'sender_id' => $senderId,
                                'sender_name' => $senderName,
                            ]);

                            // Save member info from message data
                            \App\Models\ZaloGroupMember::updateOrCreate(
                                [
                                    'zalo_group_id' => $group->id,
                                    'zalo_user_id' => $senderId,
                                ],
                                [
                                    'display_name' => $senderName,
                                ]
                            );

                            Log::info('[ZaloController] Group member info saved from message sender_name');
                        }
                    }
                }
            }
            
            // Determine message type: if isSelf = true, this is a message from same account (other device)
            // So it should be saved as 'sent', not 'received'
            $messageType = $isSelf ? 'sent' : 'received';
            
            Log::info('[ZaloController] Saving incoming message', [
                'is_self' => $isSelf,
                'message_type' => $messageType,
                'recipient_id' => $recipientId,
                'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
            ]);
            
            // 🔥 NEW: Handle UNDO/RECALL messages (type: 3, actionType: 1)
            // When a message is recalled, Zalo sends a JSON array with recall info
            if ($content && is_string($content) && str_starts_with(trim($content), '[{')) {
                try {
                    $parsedContent = json_decode($content, true);
                    if (is_array($parsedContent) && isset($parsedContent[0])) {
                        $firstItem = $parsedContent[0];
                        
                        // Check if this is a recall/undo message
                        if (isset($firstItem['type']) && $firstItem['type'] === 3 &&
                            isset($firstItem['actionType']) && $firstItem['actionType'] === 1) {
                            
                            $globalDelMsgId = $firstItem['globalDelMsgId'] ?? null;
                            $clientDelMsgId = $firstItem['clientDelMsgId'] ?? null;
                            
                            Log::info('[ZaloController] 🔥 UNDO/RECALL message detected', [
                                'globalDelMsgId' => $globalDelMsgId,
                                'clientDelMsgId' => $clientDelMsgId,
                                'uidFrom' => $firstItem['uidFrom'] ?? null,
                            ]);
                            
                            // Find the original message to recall
                            if ($globalDelMsgId || $clientDelMsgId) {
                                $originalMessage = \App\Models\ZaloMessage::where('zalo_account_id', $account->id)
                                    ->where(function($query) use ($globalDelMsgId, $clientDelMsgId) {
                                        if ($globalDelMsgId) {
                                            $query->where('message_id', $globalDelMsgId)
                                                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.globalMsgId')) = ?", [$globalDelMsgId]);
                                        }
                                        if ($clientDelMsgId) {
                                            $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.cliMsgId')) = ?", [$clientDelMsgId]);
                                        }
                                    })
                                    ->first();
                                
                                if ($originalMessage) {
                                    Log::info('[ZaloController] ✅ Found original message to recall', [
                                        'message_id' => $originalMessage->id,
                                        'zalo_message_id' => $originalMessage->message_id,
                                        'original_content' => substr($originalMessage->content ?? '', 0, 50),
                                    ]);
                                    
                                    // Update original message
                                    $originalMessage->content = 'Tin nhắn đã được thu hồi';
                                    $originalMessage->is_recalled = true;
                                    $originalMessage->recalled_at = now();
                                    $originalMessage->save();
                                    
                                    // Broadcast update via WebSocket
                                    try {
                                        $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)->post(
                                            config('services.zalo.base_url') . '/api/socket/broadcast',
                                            [
                                                'event' => 'zalo:message:recalled',
                                                'account_id' => $account->id,
                                                'data' => [
                                                    'account_id' => $account->id,
                                                    'message_id' => $originalMessage->id,
                                                    'recipient_id' => $originalMessage->recipient_id,
                                                    'zalo_message_id' => $originalMessage->message_id,
                                                    'content' => 'Tin nhắn đã được thu hồi',
                                                ],
                                            ]
                                        );
                                        
                                        if ($broadcastResponse->successful()) {
                                            Log::info('[ZaloController] Message recall broadcasted via Socket.IO');
                                        }
                                    } catch (\Exception $broadcastError) {
                                        Log::warning('[ZaloController] Failed to broadcast message recall', [
                                            'error' => $broadcastError->getMessage(),
                                        ]);
                                    }
                                    
                                    // Return success WITHOUT saving the undo message itself
                                    return response()->json([
                                        'success' => true,
                                        'message' => 'Message recalled successfully',
                                        'recalled_message_id' => $originalMessage->id,
                                    ]);
                                } else {
                                    Log::warning('[ZaloController] ⚠️ Original message not found for recall', [
                                        'globalDelMsgId' => $globalDelMsgId,
                                        'clientDelMsgId' => $clientDelMsgId,
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $parseError) {
                    Log::debug('[ZaloController] Content is not recall JSON, proceeding normally', [
                        'error' => $parseError->getMessage(),
                    ]);
                }
            }
            
            // Save message to database
            // If isSelf = true, use saveSentMessage instead of saveReceivedMessage
            $messageService = new \App\Services\ZaloMessageService();
            if ($isSelf) {
                // Message from same account (other device) - save as 'sent'
                $savedMessage = $messageService->saveSentMessage(
                    $account,
                    $recipientId,
                    $recipientName,
                    $content,
                    $recipientType,
                    $messageId,
                    $mediaUrl,
                    null, // media_path
                    $contentType,
                    $cliMsgId,
                    $allMessageIds,
                    $stickerData, // FIX: Pass sticker metadata for sticker messages
                    $styles // Pass styles for rich text
                );
                
                // ✅ UPDATE sent_by_user_id from metadata (passed via zalo-service)
                if ($sentByUserId) {
                    $savedMessage->update([
                        'sent_by_user_id' => $sentByUserId
                    ]);
                    
                    Log::info('[ZaloController] Updated sent_by_user_id from zalo-service metadata', [
                        'message_id' => $savedMessage->id,
                        'zalo_message_id' => $messageId,
                        'sent_by_user_id' => $sentByUserId,
                        'sent_by_user_name' => $sentByUserName,
                        'sent_from' => $sentFrom,
                    ]);
                } else {
                    Log::warning('[ZaloController] No sent_by_user_id from zalo-service (message from mobile/other device?)', [
                        'message_id' => $savedMessage->id,
                        'zalo_message_id' => $messageId,
                        'cliMsgId' => $cliMsgId,
                    ]);
                }
                
                // If it's a reply, update reply_to fields
                if ($quote) {
                    // Find original message being replied to
                    $finder = new \App\Services\ZaloMessageFinderService();
                    $originalMessage = $finder->findMessage(
                        $account,
                        $quote['globalMsgId'] ?? $quote['msgId'] ?? $quote['cliMsgId'] ?? null,
                        $quote['cliMsgId'] ?? null,
                        $recipientId
                    );

                    if ($originalMessage) {
                        $savedMessage->reply_to_message_id = $originalMessage->id;
                        $savedMessage->reply_to_zalo_message_id = $originalMessage->message_id;
                        $savedMessage->reply_to_cli_msg_id = $quote['cliMsgId'] ?? null;
                        $savedMessage->quote_data = $quote;
                        $savedMessage->save();
                    }
                }
            } else {
                // Message from someone else - save as 'received'
                $savedMessage = $messageService->saveReceivedMessage(
                    $account,
                    $senderId, // senderId
                    $senderName, // senderName
                    $content,
                    $recipientType,
                    $messageId,
                    $mediaUrl,
                    null, // media_path
                    $contentType,
                    $cliMsgId,
                    $quote,
                    $allMessageIds, // All message IDs from Zalo
                    $globalMsgId,
                    $realMsgId,
                    $sentAt, // Pass sent_at to service
                    $actualSenderId,
                    $actualSenderName,
                    $stickerData,
                    $fileData,
                    $styles, // Pass styles for rich text
                    $recipientId, // 🔥 FIX: Pass recipientId (group ID for groups)
                    $isSelf // 🔥 PRAGMATIC FIX: Pass isSelf flag for cache lookup
                );
            }
            
            // Update sent_at if provided and not already set
            if ($sentAt && !$savedMessage->sent_at) {
                try {
                    $savedMessage->sent_at = \Carbon\Carbon::parse($sentAt);
                    $savedMessage->save();
                } catch (\Exception $e) {
                    Log::warning('[ZaloController] Failed to parse sent_at', [
                        'sent_at' => $sentAt,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // AUTO-CREATE/UPDATE CONVERSATION
            try {
                $conversationService = app(\App\Services\ZaloConversationService::class);

                // Build recipient info from request data
                $recipientInfo = null;
                if ($recipientName || $recipientAvatar) {
                    $recipientInfo = [
                        'name' => $recipientName,
                        'displayName' => $recipientName,
                        'avatar' => $recipientAvatar,
                        'avatar_url' => $recipientAvatar,
                    ];
                }

                $conversation = $conversationService->handleNewMessage($savedMessage, null, $recipientInfo);

                Log::info('[ZaloController] Conversation auto-updated', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $savedMessage->id,
                    'has_recipient_info' => !!$recipientInfo,
                ]);
            } catch (\Exception $e) {
                // If conversation update fails (e.g., duplicate key), try to load existing conversation
                Log::error('[ZaloController] Failed to update conversation', [
                    'message_id' => $savedMessage->id,
                    'error' => $e->getMessage(),
                ]);
                
                // 🔥 FIX: Load existing conversation (including soft deleted) so we can still broadcast updates
                Log::warning('[ZaloController] Attempting to load existing conversation after duplicate error', [
                    'account_id' => $account->id,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                ]);
                
                try {
                    // Check for soft deleted conversation first
                    $conversation = \App\Models\ZaloConversation::withTrashed()
                        ->where('zalo_account_id', $account->id)
                        ->where('recipient_id', $recipientId)
                        ->where('recipient_type', $recipientType)
                        ->first();
                    
                    if ($conversation) {
                        // If conversation was soft deleted, restore it
                        if ($conversation->trashed()) {
                            $conversation->restore();
                            Log::info('[ZaloController] 🔄 Restored soft-deleted conversation', [
                                'conversation_id' => $conversation->id,
                            ]);
                        }
                        
                        // Update last_message_at manually since handleNewMessage failed
                        $conversation->update(['last_message_at' => $savedMessage->created_at]);
                        
                        Log::info('[ZaloController] ✅ Loaded existing conversation after duplicate error', [
                            'conversation_id' => $conversation->id,
                            'message_id' => $savedMessage->id,
                            'was_trashed' => $conversation->trashed(),
                        ]);
                    } else {
                        Log::error('[ZaloController] ❌ Conversation not found in database (even with trashed)', [
                            'account_id' => $account->id,
                            'recipient_id' => $recipientId,
                            'recipient_type' => $recipientType,
                        ]);
                    }
                } catch (\Exception $loadError) {
                    Log::error('[ZaloController] Failed to load existing conversation', [
                        'error' => $loadError->getMessage(),
                    ]);
                }
            }

            Log::info('[ZaloController] Message received and saved', [
                'account_id' => $account->id,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'db_message_id' => $savedMessage->id,
                'zalo_message_id' => $savedMessage->message_id,
                'cliMsgId' => $savedMessage->metadata['cliMsgId'] ?? null,
                'content_preview' => substr($savedMessage->content ?? '', 0, 50),
            ]);

            // Get sender info for toast notification
            $senderAvatar = null;
            $senderName = null;

            if ($recipientType === 'group' && $senderId) {
                // Group message - get sender from group members
                $member = \App\Models\ZaloGroupMember::where('zalo_group_id', $group->id ?? null)
                    ->where('zalo_user_id', $senderId)
                    ->first();

                if ($member) {
                    $senderAvatar = $member->avatar_url;
                    $senderName = $member->display_name;
                }
            } else if ($recipientType === 'user' && !$isSelf && isset($conversation)) {
                // 1-on-1 incoming message - sender is the contact (recipient in conversation)
                $senderAvatar = $conversation->recipient_avatar_url;
                $senderName = $conversation->recipient_name;
            }

            // Load reply_to relationship for frontend display
            if ($savedMessage->reply_to_message_id) {
                $savedMessage->load('replyTo');
            }

            // MULTI-BRANCH: Broadcast message using ZaloMultiBranchService
            // This will intelligently route to:
            // - All branches if conversation is unassigned (branch_id = NULL)
            // - Specific branch if conversation is assigned (branch_id != NULL)
            try {
                if ($isMultiBranch && isset($conversation)) {
                    // Multi-branch mode: Use service to handle intelligent routing
                    \App\Services\ZaloMultiBranchService::broadcastMessage($savedMessage, $conversation);

                    Log::info('[ZaloController] Message broadcasted via ZaloMultiBranchService', [
                        'message_id' => $savedMessage->id,
                        'conversation_id' => $conversation->id,
                        'conversation_branch_id' => $conversation->branch_id,
                        'is_global' => is_null($conversation->branch_id),
                    ]);
                } else {
                    // Single account mode: Broadcast to specific account only (backward compatibility)
                    
                    // 🔥 NEW: Prepare sent_by_user_name and sent_by_account_username for broadcast
                    $sentByUserName = null;
                    $sentByAccountUsername = null;
                    if ($savedMessage->sent_by_user_id) {
                        $systemUser = \App\Models\User::find($savedMessage->sent_by_user_id);
                        if ($systemUser) {
                            $sentByUserName = $systemUser->name;
                            $sentByAccountUsername = $this->extractUsername($systemUser->email);
                        }
                    }
                    
                    $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)
                        ->withHeaders([
                            'X-API-Key' => config('services.zalo.api_key'),
                            'Content-Type' => 'application/json',
                        ])
                        ->post(
                            config('services.zalo.base_url') . '/api/socket/broadcast',
                            [
                                'event' => 'zalo:message:new',
                                'account_id' => $account->id, // Top-level for room targeting
                                'data' => [
                                    'account_id' => $account->id,
                                    'recipient_id' => $recipientId,
                                    'message' => array_merge($savedMessage->toArray(), [
                                        'sender_avatar' => $senderAvatar,
                                        'sender_name' => $senderName,
                                        'sent_by_user_name' => $sentByUserName, // 🔥 NEW
                                        'sent_by_account_username' => $sentByAccountUsername, // 🔥 NEW
                                    ]),
                                ],
                            ]
                        );

                    if ($broadcastResponse->successful()) {
                        Log::info('[ZaloController] Message broadcasted via Socket.IO', [
                            'account_id' => $account->id,
                            'message_id' => $savedMessage->id,
                        ]);
                    } else {
                        Log::warning('[ZaloController] Failed to broadcast message', [
                            'status' => $broadcastResponse->status(),
                            'body' => $broadcastResponse->body(),
                        ]);
                    }
                }
            } catch (\Exception $broadcastError) {
                Log::warning('[ZaloController] Failed to broadcast message', [
                    'error' => $broadcastError->getMessage(),
                ]);
            }

            // Broadcast conversation update (for conversation list)
            try {
                if (isset($conversation)) {
                    // Reload conversation with full data
                    $conversation->load(['assignedUsers', 'department']);

                    $conversationData = [
                        'account_id' => $account->id,
                        'recipient_id' => $conversation->recipient_id,
                        'recipient_type' => $conversation->recipient_type,
                        'last_message' => $conversation->last_message_preview,
                        'last_message_at' => $conversation->last_message_at?->toISOString(),
                        'unread_count' => $conversation->unread_count,
                        'recipient_name' => $conversation->recipient_name,
                        'recipient_avatar_url' => $conversation->recipient_avatar_url,
                    ];

                    if ($isMultiBranch) {
                        // Multi-branch mode: Broadcast conversation update to all relevant accounts
                        foreach ($accounts as $acc) {
                            // Only broadcast to assigned branch OR all branches if unassigned
                            if ($conversation->branch_id && $conversation->branch_id !== $acc->branch_id) {
                                continue; // Skip non-assigned branches
                            }

                            $accConversationData = array_merge($conversationData, ['account_id' => $acc->id]);

                            $conversationUpdateResponse = \Illuminate\Support\Facades\Http::timeout(5)
                                ->withHeaders([
                                    'X-API-Key' => config('services.zalo.api_key'),
                                    'Content-Type' => 'application/json',
                                ])
                                ->post(
                                    config('services.zalo.base_url') . '/api/socket/broadcast',
                                    [
                                        'event' => 'zalo:conversation:updated',
                                        'account_id' => $acc->id,
                                        'data' => $accConversationData,
                                    ]
                                );

                            if ($conversationUpdateResponse->successful()) {
                                Log::info('[ZaloController] Conversation update broadcasted via Socket.IO', [
                                    'account_id' => $acc->id,
                                    'conversation_id' => $conversation->id,
                                ]);
                            }
                        }
                    } else {
                        // Single account mode: Broadcast to specific account only
                        $conversationUpdateResponse = \Illuminate\Support\Facades\Http::timeout(5)
                            ->withHeaders([
                                'X-API-Key' => config('services.zalo.api_key'),
                                'Content-Type' => 'application/json',
                            ])
                            ->post(
                                config('services.zalo.base_url') . '/api/socket/broadcast',
                                [
                                    'event' => 'zalo:conversation:updated',
                                    'account_id' => $account->id,
                                    'data' => $conversationData,
                                ]
                            );

                        if ($conversationUpdateResponse->successful()) {
                            Log::info('[ZaloController] Conversation update broadcasted via Socket.IO', [
                                'account_id' => $account->id,
                                'conversation_id' => $conversation->id,
                            ]);
                        } else {
                            Log::warning('[ZaloController] Failed to broadcast conversation update', [
                                'status' => $conversationUpdateResponse->status(),
                                'body' => $conversationUpdateResponse->body(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $broadcastError) {
                Log::warning('[ZaloController] Failed to broadcast conversation update', [
                    'error' => $broadcastError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message saved successfully',
                'data' => array_merge($savedMessage->toArray(), [
                    'sender_avatar' => $senderAvatar,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Receive message error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'zalo_account_id' => $zaloId,
                'recipient_id' => $recipientId,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reply to a message with quote
     */
    public function replyMessage(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $message = $request->input('message');
        $replyToMessageId = $request->input('reply_to_message_id'); // Database ID
        $replyToZaloMessageId = $request->input('reply_to_zalo_message_id'); // Zalo message ID
        $replyToCliMsgId = $request->input('reply_to_cli_msg_id'); // Zalo CLI message ID

        // Validate required fields
        if (!$accountId || !$recipientId || !$message) {
            return response()->json([
                'success' => false,
                'message' => 'account_id, recipient_id, and message are required',
            ], 400);
        }

        if (!$replyToMessageId && !$replyToZaloMessageId) {
            return response()->json([
                'success' => false,
                'message' => 'reply_to_message_id or reply_to_zalo_message_id is required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        try {
            // Get the original message to quote
            $originalMessage = null;
            if ($replyToMessageId) {
                $originalMessage = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('id', $replyToMessageId)
                    ->first();
            } elseif ($replyToZaloMessageId) {
                $originalMessage = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('message_id', $replyToZaloMessageId)
                    ->first();
            }

            if (!$originalMessage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original message not found',
                ], 404);
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass accountId)
            if (!$this->zalo->isReady($accountId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready',
                ], 503);
            }

            // Prepare quote data for zalo-api-final
            // Format: { cliMsgId, globalMsgId, msg, cliMsgType, ts, ownerId, ttl }
            $metadata = $originalMessage->metadata ?? [];
            $cliMsgIdForQuote = $metadata['cliMsgId'] ?? $replyToCliMsgId ?? $originalMessage->message_id ?? '';
            $globalMsgIdForQuote = $originalMessage->message_id ?? '';
            
            // Extract msgType/cliMsgType from metadata or content_type
            $msgType = $metadata['msgType'] ?? $metadata['cliMsgType'] ?? $originalMessage->content_type ?? 'text';
            
            // Determine ownerId - the user who sent the original message
            // For received messages, ownerId is the sender (recipient_id in our DB)
            // For sent messages, ownerId is our account's zalo_id
            $ownerId = '';
            if ($originalMessage->type === 'received') {
                // Original message was received from someone, so ownerId is that person
                $ownerId = $originalMessage->recipient_id;
            } else {
                // Original message was sent by us, so ownerId is our zalo_id
                $ownerId = $account->zalo_id ?? '';
            }
            
            // If ownerId is still empty, try to get from metadata or use recipient_id as fallback
            if (empty($ownerId)) {
                $ownerId = $metadata['uidFrom'] ?? $metadata['ownerId'] ?? $originalMessage->recipient_id ?? '';
            }
            
            $quoteData = [
                'cliMsgId' => $cliMsgIdForQuote,
                'globalMsgId' => $globalMsgIdForQuote,
                'msg' => $originalMessage->content ?? '',
                'cliMsgType' => $msgType, // Use extracted msgType
                'msgType' => $msgType, // Also include msgType for compatibility
                'ts' => $originalMessage->sent_at ? $originalMessage->sent_at->timestamp * 1000 : (time() * 1000),
                'ownerId' => $ownerId, // Must be a valid user ID string
                'ttl' => $metadata['ttl'] ?? 0,
            ];
            
            // Validate required fields
            if (empty($quoteData['ownerId'])) {
                Log::warning('[ZaloController] Quote data missing ownerId, using fallback', [
                    'original_message_type' => $originalMessage->type,
                    'recipient_id' => $originalMessage->recipient_id,
                    'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                ]);
                // Use recipient_id as last resort
                $quoteData['ownerId'] = $originalMessage->recipient_id ?? '';
            }

            Log::info('[ZaloController] Replying to message', [
                'account_id' => $account->id,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'original_message_id' => $originalMessage->id,
                'quote_data' => $quoteData,
            ]);

            // Send reply via Zalo service
            $result = $this->zalo->sendReply(
                $recipientId,
                $message,
                $recipientType,
                $quoteData
            );

            $messageId = $result['data']['message_id'] ?? null;

            // IMPORTANT: Do NOT save reply message here!
            // WebSocket listener will receive it and save with correct data
            // This prevents duplicate messages
            
            Log::info('[ZaloController] Reply sent successfully, WebSocket will save it', [
                'account_id' => $account->id,
                'message_id_from_zalo' => $messageId,
                'will_be_saved_by_websocket' => true,
            ]);
            
            // Create temporary object for response (not saved to DB yet)
            $replyMessage = (object)[
                'id' => null, // Will be set by WebSocket
                'message_id' => $messageId,
                'type' => 'sent',
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'content' => $message,
                'content_type' => 'text',
                'reply_to_message_id' => $originalMessage->id,
                'sent_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Reply sent successfully',
                'data' => $replyMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Reply message error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add reaction to a message
     */
    public function addReaction(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $messageId = $request->input('message_id'); // Database ID
        $zaloMessageId = $request->input('zalo_message_id'); // Zalo message ID (msgId)
        $cliMsgId = $request->input('cli_msg_id'); // Zalo CLI message ID
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $reaction = $request->input('reaction'); // Reaction icon (e.g., 'HEART', 'LIKE')

        // Validate required fields
        if (!$accountId || !$reaction) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and reaction are required',
            ], 400);
        }

        if (!$messageId && !$zaloMessageId) {
            return response()->json([
                'success' => false,
                'message' => 'message_id or zalo_message_id is required',
            ], 400);
        }

        if (!$recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'recipient_id is required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        try {
            // Get the message
            $message = null;
            if ($messageId) {
                $message = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('id', $messageId)
                    ->first();
            } elseif ($zaloMessageId) {
                $message = ZaloMessage::where('zalo_account_id', $account->id)
                    ->where('message_id', $zaloMessageId)
                    ->first();
            }

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                ], 404);
            }

            // Use message's recipient info if not provided
            if (!$recipientId) {
                $recipientId = $message->recipient_id;
                $recipientType = $message->recipient_type;
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready',
                ], 503);
            }

            // Get current user's Zalo ID
            $zaloUserId = $account->zalo_id;
            if (!$zaloUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account Zalo ID not found',
                ], 400);
            }

            // Use message IDs from database or request
            $finalZaloMessageId = $message->message_id ?? $zaloMessageId;
            $finalCliMsgId = $cliMsgId ?? $message->metadata['cliMsgId'] ?? $finalZaloMessageId;

            Log::info('[ZaloController] Adding reaction', [
                'account_id' => $account->id,
                'message_id' => $message->id,
                'zalo_message_id' => $finalZaloMessageId,
                'cli_msg_id' => $finalCliMsgId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'reaction' => $reaction,
            ]);

            // Send reaction via Zalo service
            $result = $this->zalo->addReaction(
                $reaction,
                $finalZaloMessageId,
                $finalCliMsgId,
                $recipientId,
                $recipientType
            );

            // Save reaction to database
            $reactionModel = ZaloMessageReaction::updateOrCreate(
                [
                    'zalo_message_id' => $message->id,
                    'zalo_user_id' => $zaloUserId,
                ],
                [
                    'zalo_message_id_external' => $finalZaloMessageId,
                    'reaction_icon' => $reaction,
                    'reaction_type' => 0, // Default, can be updated from API response
                    'reaction_source' => 0, // Default, can be updated from API response
                    'reaction_data' => $result['data'] ?? null,
                    'reacted_at' => now(),
                ]
            );

            Log::info('[ZaloController] Reaction saved', [
                'reaction_id' => $reactionModel->id,
                'message_id' => $message->id,
            ]);

            // Broadcast reaction via zalo-service Socket.IO
            try {
                $wsUrl = config('services.zalo.base_url', 'http://localhost:3001');
                $apiKey = config('services.zalo.api_key');

                // Call zalo-service to broadcast reaction (zalo-service has Socket.IO server)
                Http::timeout(5)->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$wsUrl}/api/realtime/broadcast-reaction", [
                    'account_id' => $account->id,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'message_id' => $message->id, // Database message ID
                    'zalo_message_id' => $finalZaloMessageId, // Zalo's message ID
                    'reaction' => [
                        'id' => $reactionModel->id,
                        'reaction_icon' => $reaction,
                        'zalo_user_id' => $zaloUserId,
                    ],
                ]);

                Log::info('[ZaloController] Reaction broadcasted via zalo-service');
            } catch (\Exception $broadcastError) {
                // Don't fail the request if broadcast fails
                Log::warning('[ZaloController] Failed to broadcast reaction', [
                    'error' => $broadcastError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reaction added successfully',
                'data' => $reactionModel,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Add reaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reactions for a message
     */
    public function getReactions(Request $request, $messageId)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        try {
            // Get message
            $message = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('id', $messageId)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                ], 404);
            }

            // Get reactions with user details
            $reactionsRaw = ZaloMessageReaction::where('zalo_message_id', $message->id)
                ->orderBy('reacted_at', 'desc')
                ->get();

            // Build user info cache (zalo_user_id => {name, avatar})
            $userInfoCache = [];
            
            // Get group info if this is a group conversation
            $zaloGroup = null;
            if ($message->recipient_type === 'group') {
                $zaloGroup = ZaloGroup::where('zalo_account_id', $account->id)
                    ->where('zalo_group_id', $message->recipient_id)
                    ->first();
            }
            
            foreach ($reactionsRaw as $reaction) {
                $zaloUserId = $reaction->zalo_user_id;
                
                if (!isset($userInfoCache[$zaloUserId])) {
                    $userName = "User {$zaloUserId}";
                    $userAvatar = null;
                    
                    // PRIORITY 1: Get from zalo_group_members if available
                    if ($zaloGroup) {
                        $groupMember = ZaloGroupMember::where('zalo_group_id', $zaloGroup->id)
                            ->where('zalo_user_id', $zaloUserId)
                            ->first();
                        
                        if ($groupMember) {
                            $userName = $groupMember->display_name ?: $userName;
                            $userAvatar = $groupMember->avatar_url;
                        }
                    }
                    
                    // PRIORITY 2: Get from user messages if not found in group members
                    if (!$userAvatar) {
                        $userMessage = ZaloMessage::where('zalo_account_id', $account->id)
                            ->where('sender_id', $zaloUserId)
                            ->whereNotNull('sender_name')
                            ->orderBy('created_at', 'desc')
                            ->first();
                        
                        if ($userMessage) {
                            $userName = $userMessage->sender_name;
                            
                            // Try to get avatar from message metadata
                            if ($userMessage->metadata) {
                                $metadata = is_string($userMessage->metadata) ? json_decode($userMessage->metadata, true) : $userMessage->metadata;
                                if (isset($metadata['sender_avatar'])) {
                                    $userAvatar = $metadata['sender_avatar'];
                                } elseif (isset($metadata['avatar'])) {
                                    $userAvatar = $metadata['avatar'];
                                }
                            }
                        }
                    }
                    
                    $userInfoCache[$zaloUserId] = [
                        'zalo_user_id' => $zaloUserId,
                        'name' => $userName,
                        'avatar' => $userAvatar,
                    ];
                }
            }

            // Group by reaction icon and build detailed user list
            $reactions = $reactionsRaw->groupBy('reaction_icon')
                ->map(function ($group) use ($userInfoCache) {
                    $usersDetailed = $group->map(function ($reaction) use ($userInfoCache) {
                        $userInfo = $userInfoCache[$reaction->zalo_user_id] ?? [
                            'zalo_user_id' => $reaction->zalo_user_id,
                            'name' => "User {$reaction->zalo_user_id}",
                            'avatar' => null,
                        ];
                        
                        return [
                            'zalo_user_id' => $reaction->zalo_user_id,
                            'name' => $userInfo['name'],
                            'avatar' => $userInfo['avatar'],
                            'reacted_at' => $reaction->reacted_at,
                        ];
                    })->toArray();

                    return [
                        'reaction' => $group->first()->reaction_icon,
                        'count' => $group->count(),
                        'users' => $usersDetailed,
                        'latest_reacted_at' => $group->max('reacted_at'),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $reactions,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Get reactions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get reactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get media (files, links, images, videos) for a conversation
     */
    public function getMedia(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');

        if (!$accountId || !$recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and recipient_id are required',
            ], 400);
        }

        // Get account với phân quyền
        $account = ZaloAccount::accessibleBy($user)->find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission',
            ], 404);
        }

        // Check if account is connected (WebSocket session + database)
        if (!$this->isAccountConnected($account)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản Zalo chưa được kết nối. Vui lòng đăng nhập lại để xem media.',
                'is_connected' => false,
                'account_name' => $account->name,
            ], 403);
        }

        // Get messages with media
        $messages = ZaloMessage::where('zalo_account_id', $account->id)
            ->where('recipient_id', $recipientId)
            ->whereNotNull('media_url')
            ->orderBy('sent_at', 'desc')
            ->get();

        // Group by content type
        $images = [];
        $videos = [];
        $files = [];
        $links = [];

        foreach ($messages as $msg) {
            $mediaItem = [
                'id' => $msg->id,
                'media_url' => $msg->media_url,
                'content' => $msg->content,
                'sent_at' => $msg->sent_at,
            ];

            switch ($msg->content_type) {
                case 'image':
                    $images[] = $mediaItem;
                    break;
                case 'file':
                    $files[] = $mediaItem;
                    break;
                case 'link':
                    $links[] = $mediaItem;
                    break;
                default:
                    if (str_contains($msg->media_url, '.jpg') || 
                        str_contains($msg->media_url, '.jpeg') || 
                        str_contains($msg->media_url, '.png') || 
                        str_contains($msg->media_url, '.gif')) {
                        $images[] = $mediaItem;
                    } elseif (str_contains($msg->media_url, '.mp4') || 
                              str_contains($msg->media_url, '.mov') || 
                              str_contains($msg->media_url, '.avi')) {
                        $videos[] = $mediaItem;
                    } else {
                        $files[] = $mediaItem;
                    }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'images' => $images,
                'videos' => $videos,
                'files' => $files,
                'links' => $links,
            ],
        ]);
    }

    /**
     * Receive reaction from Zalo service (called by zalo-service when reaction received)
     */
    public function receiveReaction(Request $request)
    {
        // Verify API key (from zalo-service)
        $apiKey = $request->header('X-API-Key');
        $expectedKey = config('services.zalo.api_key');
        
        if ($apiKey !== $expectedKey) {
            Log::warning('[ZaloController] Invalid API key for receiveReaction');
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }
        
        // MULTI-SESSION: Support both account_id and zalo_id for finding account
        $accountId = $request->input('account_id'); // Preferred for multi-session
        $zaloId = $request->input('zalo_account_id'); // Backward compatibility
        $messageId = $request->input('message_id');
        $cliMsgId = $request->input('cli_msg_id');
        $recipientId = $request->input('recipient_id');
        $recipientType = $request->input('recipient_type', 'user');
        $userId = $request->input('user_id');
        $reactionIcon = $request->input('reaction_icon');
        $reactionType = $request->input('reaction_type', 0);
        $reactionSource = $request->input('reaction_source', 0);
        $reactionData = $request->input('reaction_data');
        $reactedAt = $request->input('reacted_at');

        // MULTI-SESSION: Accept either account_id or zalo_id
        if ((!$accountId && !$zaloId) || !$messageId || !$userId || !$reactionIcon) {
            return response()->json([
                'success' => false,
                'message' => 'account_id (or zalo_id), message_id, user_id, and reaction_icon are required',
            ], 400);
        }

        try {
            // MULTI-SESSION: Find account by account_id (preferred) or zalo_id (backward compatibility)
            $account = null;
            if ($accountId) {
                $account = ZaloAccount::find($accountId);
                if (!$account) {
                    Log::warning('[ZaloController] Account not found for account_id', ['account_id' => $accountId]);
                }
            }

            // Fallback to zalo_id if account_id not found
            if (!$account && $zaloId) {
                $account = ZaloAccount::where('zalo_id', $zaloId)->first();
            }

            if (!$account) {
                Log::error('[ZaloController] No Zalo account found', [
                    'account_id' => $accountId,
                    'zalo_account_id' => $zaloId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            Log::info('[ZaloController] Webhook received reaction', [
                'account_id' => $account->id,
                'zalo_account_id' => $account->id,
                'message_id' => $messageId,
                'user_id' => $userId,
                'reaction_icon' => $reactionIcon,
                'has_account_id_param' => !!$accountId,
                'has_zalo_id_param' => !!$zaloId,
            ]);
            
            // Use MessageFinderService for optimized message finding
            $finder = new \App\Services\ZaloMessageFinderService();
            $message = $finder->findMessage(
                $account,
                $messageId,
                $cliMsgId,
                $recipientId
            );
            
            // If not found, get debug info and return error
            if (!$message) {
                $debugResult = $finder->findMessageWithDebug(
                    $account,
                    $messageId,
                    $cliMsgId,
                    $recipientId
                );
                
                Log::warning('[ZaloController] ❌ Message not found for reaction', [
                    'zalo_account_id' => $zaloId,
                    'account_id' => $account->id,
                    'account_id' => $account->id, 'account_zalo_id' => $account->zalo_id,
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'user_id' => $userId,
                    'debug_info' => $debugResult,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found. The message may not have been saved to the database yet.',
                    'debug' => [
                        'search_message_id' => $messageId,
                        'search_cli_msg_id' => $cliMsgId,
                        'recipient_id' => $recipientId,
                        'account_id' => $account->id,
                        'sample_messages' => $debugResult['sample_messages'] ?? [],
                    ],
                ], 404);
            }
            
            // Save or update reaction
            $reaction = ZaloMessageReaction::updateOrCreate(
                [
                    'zalo_message_id' => $message->id,
                    'zalo_user_id' => $userId,
                ],
                [
                    'zalo_message_id_external' => $messageId,
                    'reaction_icon' => $reactionIcon,
                    'reaction_type' => $reactionType,
                    'reaction_source' => $reactionSource,
                    'reaction_data' => $reactionData,
                    'reacted_at' => $reactedAt ? new \DateTime($reactedAt) : now(),
                ]
            );
            
            Log::info('[ZaloController] Reaction saved', [
                'reaction_id' => $reaction->id,
                'message_id' => $message->id,
                'user_id' => $userId,
                'reaction' => $reactionIcon,
            ]);
            
            // Broadcast reaction via zalo-service Socket.IO
            try {
                $wsUrl = config('services.zalo.base_url', 'http://localhost:3001');
                $apiKey = config('services.zalo.api_key');
                
                // Call zalo-service to broadcast reaction (zalo-service has Socket.IO server)
                Http::timeout(5)->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$wsUrl}/api/realtime/broadcast-reaction", [
                    'account_id' => $account->id,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'message_id' => $message->id,
                    'zalo_message_id' => $messageId,
                    'reaction' => [
                        'id' => $reaction->id,
                        'reaction_icon' => $reactionIcon,
                        'zalo_user_id' => $userId,
                    ],
                ]);
                
                Log::info('[ZaloController] Reaction broadcasted via zalo-service');
            } catch (\Exception $broadcastError) {
                // Don't fail the request if broadcast fails
                Log::warning('[ZaloController] Failed to broadcast reaction', [
                    'error' => $broadcastError->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Reaction saved successfully',
                'data' => [
                    'id' => $reaction->id,
                    'zalo_account_id' => $account->id,
                    'zalo_message_id' => $message->id,
                    'reaction' => $reaction,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Receive reaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save reaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync friends for an account (private helper method)
     */
    private function syncFriends(ZaloAccount $account): void
    {
        $cacheKey = "zalo_sync_progress_{$account->id}";

        try {
            Log::info('[ZaloController] Syncing friends for account', ['account_id' => $account->id]);

            // 🔥 Update progress: Starting
            $currentProgress = Cache::get($cacheKey, [
                'friends' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
                'groups' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
            ]);
            $currentProgress['friends'] = [
                'current' => 0,
                'total' => 0,
                'percent' => 0,
                'message' => 'Đang lấy danh sách bạn bè từ Zalo...',
                'completed' => false,
            ];
            Cache::put($cacheKey, $currentProgress, 300);

            // MULTI-SESSION: Pass account ID to get friends for the correct account
            $friendsFromApi = $this->zalo->getFriends($account->id);

            if (!empty($friendsFromApi)) {
                $this->cacheService->syncFriends($account, $friendsFromApi);
                Log::info('[ZaloController] Friends synced successfully', ['count' => count($friendsFromApi)]);
            } else {
                // 🔥 NEW: Update progress when no friends returned (possible rate limit)
                Log::warning('[ZaloController] No friends returned (possible rate limit)', ['account_id' => $account->id]);
                $currentProgress['friends'] = [
                    'current' => 0,
                    'total' => 0,
                    'percent' => 0,
                    'message' => '⚠️ Không thể lấy danh sách bạn bè. Có thể do giới hạn tần suất (rate limit). Vui lòng thử lại sau vài phút.',
                    'completed' => false,
                    'error' => true,
                ];
                Cache::put($cacheKey, $currentProgress, 300);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to sync friends', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            // 🔥 NEW: Update progress on error
            $currentProgress = Cache::get($cacheKey, [
                'friends' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
                'groups' => ['current' => 0, 'total' => 0, 'percent' => 0, 'message' => '', 'completed' => false],
            ]);
            $currentProgress['friends'] = [
                'current' => 0,
                'total' => 0,
                'percent' => 0,
                'message' => '❌ Lỗi: ' . $e->getMessage(),
                'completed' => false,
                'error' => true,
            ];
            Cache::put($cacheKey, $currentProgress, 300);

            throw $e;
        }
    }

    /**
     * Sync groups for an account (private helper method)
     */
    private function syncGroups(ZaloAccount $account): void
    {
        try {
            Log::info('[ZaloController] Syncing groups for account', ['account_id' => $account->id]);

            // MULTI-SESSION: Pass account ID to get groups for the correct account
            $groupsFromApi = $this->zalo->getGroups($account->id);
            
            if (!empty($groupsFromApi)) {
                $this->cacheService->syncGroups($account, $groupsFromApi);
                Log::info('[ZaloController] Groups synced successfully', ['count' => count($groupsFromApi)]);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to sync groups', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Search for a Zalo user by phone number
     */
    public function searchUser(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $phoneNumber = $request->input('phone_number');
        
        if (!$accountId || !$phoneNumber) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and phone_number are required',
            ], 400);
        }
        
        try {
            // Get account với phân quyền
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Call zalo-service to search user
            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/user/search', [
                'accountId' => $account->id,
                'phoneNumber' => $phoneNumber,
            ]);
            
            if (!$response->successful()) {
                $statusCode = $response->status();
                $errorMessage = $response->json('message') ?? 'Unknown error';
                
                // If user not found (404)
                if ($statusCode === 404) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found',
                    ], 404);
                }
                
                throw new \Exception('Failed to search user: ' . $errorMessage);
            }
            
            $userData = $response->json('data');
            
            return response()->json([
                'success' => true,
                'message' => 'User found successfully',
                'data' => $userData,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[ZaloController] Search user error', [
                'account_id' => $accountId,
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Send friend request to a user
     */
    public function sendFriendRequest(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $userId = $request->input('user_id');
        $message = $request->input('message');
        
        if (!$accountId || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id and user_id are required',
            ], 400);
        }
        
        try {
            // Get account với phân quyền
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            Log::info('[ZaloController] Sending friend request', [
                'account_id' => $account->id,
                'user_id' => $userId,
                'has_message' => !empty($message),
            ]);
            
            // Call zalo-service to send friend request
            $response = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
                'X-Account-Id' => $account->id,
            ])->post(config('services.zalo.base_url') . '/api/friend/send-request', [
                'userId' => $userId,
                'message' => $message,
            ]);
            
            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Failed to send friend request'));
            }
            
            $result = $response->json();
            
            Log::info('[ZaloController] Friend request sent successfully', [
                'account_id' => $account->id,
                'user_id' => $userId,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Friend request sent successfully',
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to send friend request', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send friend request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new group
     */
    public function createGroup(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $name = $request->input('name');
        $members = $request->input('members');
        $avatarPath = $request->input('avatar_path');
        
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }
        
        if (!$members || !is_array($members) || count($members) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'members array is required and must not be empty',
            ], 400);
        }
        
        try {
            // Get account với phân quyền
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            Log::info('[ZaloController] Creating group', [
                'account_id' => $account->id,
                'name' => $name,
                'members_count' => count($members),
                'has_avatar' => !empty($avatarPath),
            ]);
            
            // Call zalo-service to create group
            $response = Http::timeout(60)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/group/create', [
                'name' => $name,
                'members' => $members,
                'avatarPath' => $avatarPath,
                'accountId' => $account->id,
            ]);
            
            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Failed to create group'));
            }
            
            $result = $response->json();
            
            $groupId = $result['data']['groupId'] ?? null;
            $groupName = $name ?: 'nhóm mới';
            
            Log::info('[ZaloController] Group created successfully', [
                'account_id' => $account->id,
                'group_id' => $groupId,
                'group_name' => $groupName,
                'success_members' => count($result['data']['successMembers'] ?? []),
                'error_members' => count($result['data']['errorMembers'] ?? []),
            ]);
            
            // Sync groups to update database
            try {
                $this->syncGroups($account);
            } catch (\Exception $e) {
                Log::warning('[ZaloController] Failed to sync groups after creation', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Send welcome message to the new group
            if ($groupId) {
                try {
                    $welcomeMessage = "Cảm ơn bạn đã tham gia nhóm {$groupName} 🎉";
                    
                    $msgResponse = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                        'X-API-Key' => config('services.zalo.api_key'),
                    ])->post(config('services.zalo.base_url') . '/api/message/send', [
                        'to' => $groupId,
                        'message' => $welcomeMessage,
                        'type' => 'group',
                        'accountId' => $account->id,
                    ]);
                    
                    if ($msgResponse->successful()) {
                        Log::info('[ZaloController] Welcome message sent to new group', [
                            'group_id' => $groupId,
                            'message' => $welcomeMessage,
                        ]);
                    } else {
                        Log::warning('[ZaloController] Failed to send welcome message', [
                            'group_id' => $groupId,
                            'error' => $msgResponse->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('[ZaloController] Exception sending welcome message', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the whole request if welcome message fails
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to create group', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new group with automatic friend requests for non-friends
     * POST /api/zalo/groups/create-with-auto-friend
     */
    public function createGroupWithAutoFriend(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $name = $request->input('name');
        $members = $request->input('members'); // Array of objects with phone, name, zalo_user_id

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        if (!$members || !is_array($members) || count($members) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'members array is required and must not be empty',
            ], 400);
        }

        try {
            // Get account with permission check
            $account = ZaloAccount::accessibleBy($user)->find($accountId);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            Log::info('[ZaloController] Creating group with auto friend requests', [
                'account_id' => $account->id,
                'name' => $name,
                'members_count' => count($members),
            ]);

            $friendRequestsSent = [];
            $friendRequestsFailed = [];
            $finalMemberIds = [];
            $pendingFriendRequests = []; // Users who need friend request AFTER group creation
            $zaloServiceUrl = config('services.zalo.base_url');
            $apiKey = config('services.zalo.api_key');

            // Process each member
            foreach ($members as $member) {
                // Validate member has either phone or zalo_user_id
                if (empty($member['phone']) && empty($member['zalo_user_id'])) {
                    Log::warning('[ZaloController] Member has no phone and no zalo_user_id', [
                        'name' => $member['name'],
                    ]);
                    continue;
                }

                try {
                    // Step 1: Get Zalo user info and check friend status
                    // Always check via API to get current friend status
                    Log::info('[ZaloController] Checking user friend status', [
                        'phone' => $member['phone'] ?? null,
                        'zalo_user_id' => $member['zalo_user_id'] ?? null,
                        'name' => $member['name'],
                    ]);

                    $searchResponse = Http::timeout(30)->withHeaders([
                        'X-API-Key' => $apiKey,
                        'X-Account-Id' => $account->id,
                    ])->post($zaloServiceUrl . '/api/user/search', [
                        'phoneNumber' => $member['phone'],
                    ]);

                    if (!$searchResponse->successful()) {
                        Log::warning('[ZaloController] User search failed', [
                            'phone' => $member['phone'],
                            'error' => $searchResponse->body(),
                        ]);
                        $friendRequestsFailed[] = [
                            'name' => $member['name'],
                            'phone' => $member['phone'],
                            'reason' => 'User not found on Zalo',
                        ];
                        continue;
                    }

                    $searchResult = $searchResponse->json();

                    if (!$searchResult['success'] || empty($searchResult['data'])) {
                        Log::warning('[ZaloController] User not found in search', [
                            'phone' => $member['phone'],
                        ]);
                        $friendRequestsFailed[] = [
                            'name' => $member['name'],
                            'phone' => $member['phone'],
                            'reason' => 'User not found on Zalo',
                        ];
                        continue;
                    }

                    $userData = $searchResult['data'];
                    $zaloUserId = $userData['id'];
                    $isFriend = $userData['isFriend'] ?? false;

                    Log::info('[ZaloController] User found with friend status', [
                        'phone' => $member['phone'],
                        'zalo_user_id' => $zaloUserId,
                        'display_name' => $userData['display_name'] ?? null,
                        'isFriend' => $isFriend,
                    ]);

                    // Step 2: Add user to group (regardless of friend status)
                    $finalMemberIds[] = $zaloUserId;

                    // Step 3: If not friend, mark for friend request AFTER group creation
                    if (!$isFriend) {
                        $pendingFriendRequests[] = [
                            'name' => $member['name'],
                            'phone' => $member['phone'],
                            'zalo_user_id' => $zaloUserId,
                            'display_name' => $userData['display_name'] ?? null,
                        ];
                        Log::info('[ZaloController] Member needs friend request', [
                            'name' => $member['name'],
                            'zalo_user_id' => $zaloUserId,
                        ]);
                    } else {
                        Log::info('[ZaloController] Member is already friend', [
                            'name' => $member['name'],
                            'zalo_user_id' => $zaloUserId,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('[ZaloController] Error processing member', [
                        'name' => $member['name'],
                        'phone' => $member['phone'],
                        'error' => $e->getMessage(),
                    ]);
                    $friendRequestsFailed[] = [
                        'name' => $member['name'],
                        'phone' => $member['phone'],
                        'reason' => 'Error: ' . $e->getMessage(),
                    ];
                }
            }

            // If we have no valid members, return error
            if (empty($finalMemberIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid members found to add to group',
                    'friendRequestsSent' => $friendRequestsSent,
                    'friendRequestsFailed' => $friendRequestsFailed,
                ], 400);
            }

            Log::info('[ZaloController] Creating group with processed members', [
                'name' => $name,
                'final_member_count' => count($finalMemberIds),
                'friend_requests_sent' => count($friendRequestsSent),
                'friend_requests_failed' => count($friendRequestsFailed),
            ]);

            // Step 3: Create the group with all valid member IDs
            $response = Http::timeout(60)->withHeaders([
                'X-API-Key' => $apiKey,
            ])->post($zaloServiceUrl . '/api/group/create', [
                'name' => $name,
                'members' => $finalMemberIds,
                'accountId' => $account->id,
            ]);

            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Failed to create group'));
            }

            $result = $response->json();

            $groupId = $result['data']['groupId'] ?? null;
            $groupName = $name ?: 'nhóm mới';

            Log::info('[ZaloController] Group created successfully', [
                'account_id' => $account->id,
                'group_id' => $groupId,
                'group_name' => $groupName,
                'success_members' => count($result['data']['successMembers'] ?? []),
                'error_members' => count($result['data']['errorMembers'] ?? []),
                'pending_friend_requests' => count($pendingFriendRequests),
            ]);

            // NOW send friend requests to users who are not friends (AFTER group creation)
            foreach ($pendingFriendRequests as $pendingRequest) {
                try {
                    Log::info('[ZaloController] Sending friend request after group creation', [
                        'zalo_user_id' => $pendingRequest['zalo_user_id'],
                        'name' => $pendingRequest['name'],
                    ]);

                    $friendRequestResponse = Http::timeout(30)->withHeaders([
                        'X-API-Key' => $apiKey,
                        'X-Account-Id' => $account->id,
                    ])->post($zaloServiceUrl . '/api/friend/send-request', [
                        'userId' => $pendingRequest['zalo_user_id'],
                        'message' => 'Xin chào! Chúc mừng bạn đã tham gia nhóm ' . $groupName,
                    ]);

                    if ($friendRequestResponse->successful()) {
                        $friendRequestsSent[] = $pendingRequest;
                        Log::info('[ZaloController] Friend request sent successfully', [
                            'zalo_user_id' => $pendingRequest['zalo_user_id'],
                        ]);
                    } else {
                        $errorResponse = $friendRequestResponse->json();
                        $errorMessage = $errorResponse['message'] ?? 'Failed to send friend request';

                        Log::warning('[ZaloController] Friend request failed', [
                            'zalo_user_id' => $pendingRequest['zalo_user_id'],
                            'error' => $friendRequestResponse->body(),
                        ]);
                        $friendRequestsFailed[] = [
                            'name' => $pendingRequest['name'],
                            'phone' => $pendingRequest['phone'],
                            'reason' => $errorMessage,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('[ZaloController] Error sending friend request', [
                        'zalo_user_id' => $pendingRequest['zalo_user_id'],
                        'error' => $e->getMessage(),
                    ]);
                    $friendRequestsFailed[] = [
                        'name' => $pendingRequest['name'],
                        'phone' => $pendingRequest['phone'],
                        'reason' => 'Error: ' . $e->getMessage(),
                    ];
                }
            }

            Log::info('[ZaloController] Friend requests processing completed', [
                'sent_count' => count($friendRequestsSent),
                'failed_count' => count($friendRequestsFailed),
            ]);

            // Sync groups to update database
            try {
                $this->syncGroups($account);
            } catch (\Exception $e) {
                Log::warning('[ZaloController] Failed to sync groups after creation', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => $result['data'] ?? null,
                'friendRequests' => [
                    'sent' => $friendRequestsSent,
                    'failed' => $friendRequestsFailed,
                    'sentCount' => count($friendRequestsSent),
                    'failedCount' => count($friendRequestsFailed),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to create group with auto friend requests', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add members to an existing group
     */
    public function addMembersToGroup(Request $request, $groupId)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        // Accept both 'members' and 'member_ids' for compatibility
        $memberIds = $request->input('members') ?? $request->input('member_ids');
        
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }
        
        if (!$memberIds || (is_array($memberIds) && count($memberIds) === 0)) {
            return response()->json([
                'success' => false,
                'message' => 'members array is required and must not be empty',
            ], 400);
        }
        
        try {
            // Get account với phân quyền
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready (MULTI-SESSION: pass account->id)
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            Log::info('[ZaloController] Adding members to group', [
                'account_id' => $account->id,
                'group_id' => $groupId,
                'members_count' => is_array($memberIds) ? count($memberIds) : 1,
            ]);
            
            // Call zalo-service to add members
            $response = Http::timeout(60)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/group/add-members/' . $groupId, [
                'memberIds' => $memberIds,
            ]);
            
            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Failed to add members to group'));
            }
            
            $result = $response->json();
            
            Log::info('[ZaloController] Members added to group successfully', [
                'account_id' => $account->id,
                'group_id' => $groupId,
                'success_count' => $result['data']['successCount'] ?? 0,
                'error_members' => count($result['data']['errorMembers'] ?? []),
            ]);
            
            // Sync group members to update database
            try {
                $response = Http::timeout(60)->withHeaders([
                    'X-API-Key' => config('services.zalo.api_key'),
                ])->get(config('services.zalo.base_url') . '/api/group/members/' . $groupId, [
                    'accountId' => $account->id,
                ]);
                
                if ($response->successful() && $response->json('success')) {
                    $membersFromApi = $response->json('data', []);
                    $group = ZaloGroup::where('zalo_account_id', $account->id)
                        ->where('zalo_group_id', $groupId)
                        ->first();
                    
                    if ($group) {
                        foreach ($membersFromApi as $memberData) {
                            $userId = $memberData['id'] ?? null;
                            if (!$userId) continue;
                            
                            \App\Models\ZaloGroupMember::updateOrCreate(
                                ['zalo_group_id' => $group->id, 'zalo_user_id' => $userId],
                                [
                                    'display_name' => $memberData['display_name'] ?? 'Unknown',
                                    'avatar_url' => $memberData['avatar_url'] ?? null,
                                    'is_admin' => $memberData['is_admin'] ?? false,
                                ]
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[ZaloController] Failed to sync group members after adding', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Members added to group successfully',
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to add members to group', [
                'account_id' => $accountId,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add members to group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add member to group by phone number (search, add to group, send friend request if needed)
     */
    public function addMemberByPhone(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => 'required|exists:zalo_accounts,id',
            'group_id' => 'required|string',
            'phone' => 'required|string',
        ]);

        $accountId = $validated['account_id'];
        $groupId = $validated['group_id'];
        $phone = $validated['phone'];

        try {
            // Get account with permission check
            $account = ZaloAccount::accessibleBy($user)->find($accountId);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }

            // Check if zalo-service is ready
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            $zaloServiceUrl = config('services.zalo.base_url');
            $apiKey = config('services.zalo.api_key');

            Log::info('[ZaloController] Adding member to group by phone', [
                'account_id' => $account->id,
                'group_id' => $groupId,
                'phone' => $phone,
            ]);

            // Step 1: Search for user by phone via Zalo API
            $searchResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => $apiKey,
                'X-Account-Id' => $account->id,
            ])->post($zaloServiceUrl . '/api/user/search', [
                'phoneNumber' => $phone,
            ]);

            if (!$searchResponse->successful() || $searchResponse->status() === 404) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng Zalo với số điện thoại này',
                ], 404);
            }

            $searchResult = $searchResponse->json();

            if (!$searchResult['success'] || empty($searchResult['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng Zalo với số điện thoại này',
                ], 404);
            }

            $userData = $searchResult['data'];
            $zaloUserId = $userData['id'];
            $isFriend = $userData['isFriend'] ?? false;
            $displayName = $userData['display_name'] ?? $phone;

            Log::info('[ZaloController] User found', [
                'phone' => $phone,
                'zalo_user_id' => $zaloUserId,
                'display_name' => $displayName,
                'isFriend' => $isFriend,
            ]);

            // Step 2: Add to group FIRST (regardless of friend status)
            $addMemberResponse = Http::timeout(60)->withHeaders([
                'X-API-Key' => $apiKey,
            ])->post($zaloServiceUrl . '/api/group/add-members/' . $groupId, [
                'memberIds' => [$zaloUserId],
                'accountId' => $account->id,
            ]);

            if (!$addMemberResponse->successful()) {
                throw new \Exception('Không thể thêm thành viên vào nhóm: ' . $addMemberResponse->json('message', 'Unknown error'));
            }

            $addResult = $addMemberResponse->json();

            Log::info('[ZaloController] Member added to group', [
                'zalo_user_id' => $zaloUserId,
                'group_id' => $groupId,
            ]);

            // Step 3: Send friend request if not friend (AFTER adding to group)
            $friendRequestSent = false;
            if (!$isFriend) {
                try {
                    Log::info('[ZaloController] Sending friend request after adding to group', [
                        'zalo_user_id' => $zaloUserId,
                    ]);

                    $friendRequestResponse = Http::timeout(30)->withHeaders([
                        'X-API-Key' => $apiKey,
                        'X-Account-Id' => $account->id,
                    ])->post($zaloServiceUrl . '/api/friend/send-request', [
                        'userId' => $zaloUserId,
                        'message' => 'Xin chào! Mời bạn kết bạn để tiện trao đổi trong nhóm.',
                    ]);

                    if ($friendRequestResponse->successful()) {
                        $friendRequestSent = true;
                        Log::info('[ZaloController] Friend request sent successfully', [
                            'zalo_user_id' => $zaloUserId,
                        ]);
                    } else {
                        Log::warning('[ZaloController] Friend request failed', [
                            'zalo_user_id' => $zaloUserId,
                            'error' => $friendRequestResponse->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[ZaloController] Error sending friend request', [
                        'zalo_user_id' => $zaloUserId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sync group members to update database
            try {
                $membersResponse = Http::timeout(60)->withHeaders([
                    'X-API-Key' => $apiKey,
                ])->get($zaloServiceUrl . '/api/group/members/' . $groupId, [
                    'accountId' => $account->id,
                ]);

                if ($membersResponse->successful() && $membersResponse->json('success')) {
                    $membersFromApi = $membersResponse->json('data', []);
                    $group = ZaloGroup::where('zalo_account_id', $account->id)
                        ->where('zalo_group_id', $groupId)
                        ->first();

                    if ($group) {
                        foreach ($membersFromApi as $memberData) {
                            $userId = $memberData['id'] ?? null;
                            if (!$userId) continue;

                            \App\Models\ZaloGroupMember::updateOrCreate(
                                ['zalo_group_id' => $group->id, 'zalo_user_id' => $userId],
                                [
                                    'display_name' => $memberData['display_name'] ?? 'Unknown',
                                    'avatar_url' => $memberData['avatar_url'] ?? null,
                                    'is_admin' => $memberData['is_admin'] ?? false,
                                ]
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[ZaloController] Failed to sync group members after adding', [
                    'error' => $e->getMessage(),
                ]);
            }

            $message = "Đã thêm {$displayName} vào nhóm thành công";
            if ($friendRequestSent) {
                $message .= " và đã gửi lời mời kết bạn";
            } elseif (!$isFriend) {
                // Friend request failed
                $message .= ".<br><br>⚠️ Lưu ý: Không thể gửi lời mời kết bạn do giới hạn của Zalo (giới hạn số lượng/ngày, cài đặt riêng tư, v.v.). Thành viên đã được thêm vào group và có thể chat ngay.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'zalo_user_id' => $zaloUserId,
                    'display_name' => $displayName,
                    'friend_request_sent' => $friendRequestSent,
                    'add_result' => $addResult['data'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to add member by phone', [
                'account_id' => $accountId,
                'group_id' => $groupId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm thành viên: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate if a specific group exists (optimized - only checks one group)
     */
    public function validateGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:zalo_accounts,id',
            'group_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $account = ZaloAccount::accessibleBy($user)->find($request->account_id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or you do not have permission to access it.'
            ], 404);
        }

        try {
            $zaloServiceUrl = config('services.zalo.base_url');
            $apiKey = config('services.zalo.api_key');

            Log::info('[ZaloController] Validating group', [
                'account_id' => $account->id,
                'group_id' => $request->group_id,
            ]);

            // First, try to get from database (fast)
            $groupInDb = ZaloGroup::where('zalo_account_id', $account->id)
                ->where('zalo_group_id', $request->group_id)
                ->first();

            // If found in DB and recently synced (within 5 minutes), use that
            if ($groupInDb && $groupInDb->updated_at && $groupInDb->updated_at->diffInMinutes(now()) < 5) {
                Log::info('[ZaloController] Using cached group data', [
                    'group_id' => $request->group_id,
                    'cached_age_minutes' => $groupInDb->updated_at->diffInMinutes(now()),
                ]);

                return response()->json([
                    'success' => true,
                    'exists' => true,
                    'cached' => true,
                    'data' => [
                        'id' => $groupInDb->zalo_group_id,
                        'name' => $groupInDb->name,
                        'displayName' => $groupInDb->name,
                        'avatar_url' => $groupInDb->avatar_url,
                        'members_count' => $groupInDb->members_count ?? 0,
                    ]
                ]);
            }

            // Fetch from Zalo API to validate
            $response = Http::timeout(30)->withHeaders([
                'X-API-Key' => $apiKey,
                'X-Account-Id' => $account->id,
            ])->get($zaloServiceUrl . '/api/group/info/' . $request->group_id);

            if (!$response->successful()) {
                $statusCode = $response->status();

                Log::warning('[ZaloController] Failed to validate group', [
                    'account_id' => $account->id,
                    'group_id' => $request->group_id,
                    'status' => $statusCode,
                    'response' => $response->body(),
                ]);

                // ⚠️ CHỈ XÓA khi CHẮC CHẮN group không tồn tại (404)
                if ($statusCode === 404) {
                    Log::warning('[ZaloController] Group not found (404), deleting from database', [
                        'group_id' => $request->group_id,
                    ]);

                    // Delete from database if exists
                    if ($groupInDb) {
                        $groupInDb->delete();
                    }

                    return response()->json([
                        'success' => true,
                        'exists' => false,
                        'reason' => 'not_found',
                        'message' => 'Group không tồn tại trên Zalo'
                    ]);
                }

                // ⚠️ Với các lỗi khác (401, 500, timeout) → KHÔNG XÓA, chỉ báo lỗi
                if ($statusCode === 401 || $statusCode === 403) {
                    // Account bị logout hoặc không có quyền
                    return response()->json([
                        'success' => false,
                        'exists' => true, // ← Giữ nguyên group
                        'reason' => 'unauthorized',
                        'message' => 'Tài khoản Zalo cần đăng nhập lại. Vui lòng kiểm tra tài khoản Zalo trong cài đặt.',
                        'status_code' => $statusCode
                    ], 401);
                }

                // Lỗi server hoặc network
                return response()->json([
                    'success' => false,
                    'exists' => true, // ← Giữ nguyên group
                    'reason' => 'service_error',
                    'message' => 'Không thể kết nối với Zalo. Vui lòng thử lại sau.',
                    'status_code' => $statusCode
                ], 503);
            }

            $groupData = $response->json('data');

            // Update or create in database
            ZaloGroup::updateOrCreate(
                [
                    'zalo_account_id' => $account->id,
                    'zalo_group_id' => $request->group_id,
                ],
                [
                    'name' => $groupData['name'] ?? $groupData['displayName'],
                    'avatar_url' => $groupData['avatar_url'] ?? null,
                    'members_count' => $groupData['members_count'] ?? 0,
                ]
            );

            Log::info('[ZaloController] Group validated and updated', [
                'group_id' => $request->group_id,
                'members_count' => $groupData['members_count'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'exists' => true,
                'cached' => false,
                'data' => [
                    'id' => $groupData['id'],
                    'name' => $groupData['name'] ?? $groupData['displayName'],
                    'displayName' => $groupData['displayName'] ?? $groupData['name'],
                    'avatar_url' => $groupData['avatar_url'] ?? null,
                    'members_count' => $groupData['members_count'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[ZaloController] Error validating group', [
                'error' => $e->getMessage(),
                'group_id' => $request->group_id,
            ]);

            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Lỗi khi kiểm tra group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change group avatar
     */
    public function changeGroupAvatar(Request $request, $groupId)
    {
        try {
            $accountId = $request->input('account_id');
            $user = $request->user();
            
            // Get account với phân quyền
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission to access it.',
                ], 404);
            }
            
            // Validate request
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,jpg,png,gif|max:5120', // Max 5MB
            ]);
            
            Log::info('[ZaloController] Changing group avatar', [
                'account_id' => $account->id,
                'group_id' => $groupId,
            ]);
            
            // Upload the image file to temporary location
            $file = $request->file('avatar');
            $tempPath = storage_path('app/temp');
            
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }
            
            $fileName = uniqid('group_avatar_') . '.' . $file->getClientOriginalExtension();
            $fullPath = $tempPath . '/' . $fileName;
            $file->move($tempPath, $fileName);
            
            Log::info('[ZaloController] Avatar uploaded to temp path', [
                'path' => $fullPath,
            ]);
            
            // Call zalo-service to change group avatar
            $response = Http::timeout(60)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/group/change-avatar/' . $groupId, [
                'accountId' => $account->id,
                'avatarPath' => $fullPath,
            ]);
            
            // Delete temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            if (!$response->successful()) {
                throw new \Exception($response->json('message', 'Failed to change group avatar'));
            }
            
            $result = $response->json();
            
            Log::info('[ZaloController] Group avatar changed successfully', [
                'account_id' => $account->id,
                'group_id' => $groupId,
            ]);
            
            // Sync group info to update avatar_url in database
            $newAvatarUrl = null;
            try {
                $this->syncGroups($account);
                
                // Fetch updated group info from database
                $group = ZaloGroup::where('zalo_account_id', $account->id)
                    ->where('zalo_group_id', $groupId)
                    ->first();
                
                if ($group) {
                    $newAvatarUrl = $group->avatar_url;
                    Log::info('[ZaloController] New avatar URL after sync', [
                        'group_id' => $groupId,
                        'avatar_url' => $newAvatarUrl,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('[ZaloController] Failed to sync groups after changing avatar', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Group avatar changed successfully',
                'data' => [
                    'group_id' => $groupId,
                    'avatar_url' => $newAvatarUrl,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to change group avatar', [
                'account_id' => $accountId ?? null,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to change group avatar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========== CONVERSATION MANAGEMENT ==========

    /**
     * Get conversations with permission-based filtering
     */
    public function getConversations(Request $request)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');
        $branchId = $request->input('branch_id');
        $unreadOnly = $request->input('unread_only', false);

        Log::info('[ZaloController] getConversations called', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'unread_only' => $unreadOnly,
            'user_branches' => $user->branches()->pluck('branches.id')->toArray(),
            'user_has_view_all' => $user->hasPermission('zalo.view_all_conversations'),
            'is_superadmin' => $user->hasRole('super-admin'),
        ]);

        $query = \App\Models\ZaloConversation::with([
            'zaloAccount:id,name,phone,is_connected',
            'branch:id,code,name',
            'assigned_branch:id,code,name', // Include assigned branch for access control
            'department:id,name',
            'creator:id,name,email',
            'assignedUsers:id,name,email', // Include assigned users
        ])
        ->accessibleBy($user)
        ->recentFirst();

        // Handle shared Zalo accounts across branches
        if ($accountId) {
            $account = \App\Models\ZaloAccount::find($accountId);

            if ($account) {
                // Check branch permission: view_all_conversations
                if (!$this->checkBranchPermission($request, $account, 'view_all_conversations')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền xem danh sách hội thoại của tài khoản này.',
                        'data' => [],
                        'permission_denied' => true,
                    ], 403);
                }

                // No multi-branch sharing - use single account only
                $sharedAccountIds = [$account->id];

                Log::info('[ZaloController] Shared account detection', [
                    'requested_account_id' => $accountId,
                    'zalo_account_id' => $account->id,
                    'shared_account_ids' => $sharedAccountIds,
                ]);

                // Load conversations from all accounts with same zalo_id
                if (count($sharedAccountIds) > 1) {
                    $query->whereIn('zalo_account_id', $sharedAccountIds);
                } else {
                    $query->forAccount($accountId);
                }
            } else {
                $query->forAccount($accountId);
            }
        }

        // Check if user should bypass branch filtering
        $bypassBranchFilter = $user->hasRole('super-admin')
            || $user->can('zalo.view_all_branches_conversations')
            || $user->can('zalo.all_conversation_management');

        if ($branchId && !$bypassBranchFilter) {
            // Include both branch-specific AND global conversations (assigned_branch_id=NULL)
            $query->where(function($q) use ($branchId) {
                $q->where('assigned_branch_id', $branchId)
                  ->orWhereNull('assigned_branch_id');
            });
        }

        if ($unreadOnly) {
            $query->unread();
        }

        // Only show conversations from connected accounts
        $query->whereHas('zaloAccount', function ($q) {
            $q->where('is_connected', true);
        });

        Log::info('[ZaloController] Query SQL before execute', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $conversations = $query->paginate(50);

        Log::info('[ZaloController] Query result', [
            'total_conversations' => $conversations->total(),
            'current_page_count' => $conversations->count(),
        ]);

        // Enrich conversations with recipient (friend/group) data
        if ($conversations->count() > 0 && $accountId) {
            // Get the account's zalo_id for querying shared friends/groups
            $account = \App\Models\ZaloAccount::find($accountId);

            if ($account) {
                // Get all friend and group IDs from conversations
                $friendIds = [];
                $groupIds = [];

                foreach ($conversations as $conv) {
                    if ($conv->recipient_type === 'user') {
                        $friendIds[] = $conv->recipient_id;
                    } else {
                        $groupIds[] = $conv->recipient_id;
                    }
                }

                // Bulk load friends and groups (now using zalo_id for shared data)
                $friends = [];
                $groups = [];

                if (!empty($friendIds)) {
                    $friends = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                        ->whereIn('zalo_user_id', $friendIds)
                        ->get()
                        ->keyBy('zalo_user_id');
                }

                if (!empty($groupIds)) {
                    $groups = \App\Models\ZaloGroup::where('zalo_account_id', $account->id)
                        ->whereIn('zalo_group_id', $groupIds)
                        ->get()
                        ->keyBy('zalo_group_id');
                }
            } else {
                $friends = [];
                $groups = [];
            }

            // Attach recipient data to each conversation
            foreach ($conversations as $conv) {
                if ($conv->recipient_type === 'user') {
                    $friend = $friends[$conv->recipient_id] ?? null;
                    if ($friend) {
                        $conv->recipient_name = $friend->name ?? $friend->display_name ?? $conv->recipient_name;
                        $conv->recipient_avatar_url = $friend->avatar_url ?? $friend->avatar ?? $conv->recipient_avatar_url;
                    }
                } else {
                    $group = $groups[$conv->recipient_id] ?? null;
                    if ($group) {
                        $conv->recipient_name = $group->name ?? $conv->recipient_name;
                        $conv->recipient_avatar_url = $group->avatar_url ?? $group->avatar ?? $conv->recipient_avatar_url;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Assign user to conversation
     */
    public function assignUserToConversation(Request $request, $id)
    {
        $user = $request->user();
        $userId = $request->input('user_id');
        $canView = $request->input('can_view', true);
        $canReply = $request->input('can_reply', true);
        $note = $request->input('note');

        $conversation = \App\Models\ZaloConversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Check permission
        $hasFullPermission = $user->hasRole('super-admin') || $user->hasPermission('zalo.all_conversation_management');

        if (!$hasFullPermission) {
            // Check if user is head or deputy of the conversation's department
            $isDepartmentLeader = false;

            if ($conversation->department_id) {
                $isDepartmentLeader = $user->departments()
                    ->where('departments.id', $conversation->department_id)
                    ->where(function ($q) {
                        $q->where('department_user.is_head', true)
                          ->orWhere('department_user.is_deputy', true);
                    })
                    ->exists();
            }

            if (!$isDepartmentLeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền gán người dùng cho cuộc trò chuyện này. Chỉ trưởng phòng/phó phòng mới có thể gán nhân viên vào conversation thuộc phòng ban của mình.',
                ], 403);
            }
        }

        $assignUser = \App\Models\User::find($userId);
        if (!$assignUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Check if user belongs to branch
        $branchIds = $assignUser->branches()->pluck('branches.id')->toArray();
        if (!in_array($conversation->branch_id, $branchIds)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to this branch.',
            ], 400);
        }

        $conversationService = app(\App\Services\ZaloConversationService::class);
        $conversationService->assignUser($conversation, $assignUser, $user, $canView, $canReply, $note);

        return response()->json([
            'success' => true,
            'message' => 'User assigned to conversation successfully.',
        ]);
    }

    /**
     * Assign department to conversation
     */
    public function assignDepartmentToConversation(Request $request, $id)
    {
        $user = $request->user();
        $departmentId = $request->input('department_id');

        // Check permission
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.all_conversation_management')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gán phòng ban cho cuộc trò chuyện.',
            ], 403);
        }

        $conversation = \App\Models\ZaloConversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        if ($departmentId) {
            $department = \App\Models\Department::find($departmentId);
            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found.',
                ], 404);
            }
        }

        // 🔥 FIX: Update ALL conversation records with same recipient_id across shared accounts
        // Get account and find shared accounts
        $account = ZaloAccount::find($conversation->zalo_account_id);
        $accountIds = [$account->id];

        if ($account) {
            // No multi-branch sharing - use single account only
            $sharedAccountIds = [$account->id];

            if (count($sharedAccountIds) > 1) {
                $accountIds = $sharedAccountIds;
                Log::info('[ZaloController] assignDepartment - Updating all shared conversations', [
                    'recipient_id' => $conversation->recipient_id,
                    'shared_account_ids' => $accountIds,
                ]);
            }
        }

        // Update all conversations with same recipient_id across shared accounts
        $affectedRows = \App\Models\ZaloConversation::whereIn('zalo_account_id', $accountIds)
            ->where('recipient_id', $conversation->recipient_id)
            ->where('recipient_type', $conversation->recipient_type)
            ->update(['department_id' => $departmentId]);

        Log::info('[ZaloController] Department assigned to conversations', [
            'affected_rows' => $affectedRows,
            'department_id' => $departmentId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Department assigned to conversation successfully.',
            'data' => [
                'department_id' => $departmentId,
                'department' => $departmentId ? \App\Models\Department::find($departmentId) : null,
            ],
        ]);
    }

    /**
     * Get available branches for conversation
     * Used when assigning branch to a conversation
     */
    public function getAvailableBranchesForConversation(Request $request, $id)
    {
        $user = $request->user();
        $accountId = $request->input('account_id');

        // Check permission - require view_all_branches_conversations to assign branches
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.view_all_branches_conversations')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem branches.',
            ], 403);
        }

        $conversation = \App\Models\ZaloConversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Get account
        $account = ZaloAccount::find($accountId ?: $conversation->zalo_account_id);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Zalo account not found.',
            ], 404);
        }

        // Get all branches that have access to this account
        $branches = \App\Models\ZaloAccountBranch::where('zalo_account_id', $account->id)
            ->with('branch')
            ->get()
            ->map(function ($accountBranch) use ($account) {
                return [
                    'id' => $accountBranch->branch_id,
                    'name' => $accountBranch->branch->name ?? 'Unknown',
                    'zalo_account_name' => $account->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /**
     * Assign branch to conversation
     * Updates assigned_branch_id for multi-branch data isolation
     * Frontend sends branch_id, we store in assigned_branch_id
     */
    public function assignBranchToConversation(Request $request, $id)
    {
        $user = $request->user();
        // Accept both branch_id (from frontend) and assigned_branch_id (legacy)
        $branchId = $request->input('branch_id', $request->input('assigned_branch_id'));
        $accountId = $request->input('account_id');

        // Check permission - allow superadmin or users with view_all_branches_conversations
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.view_all_branches_conversations') && !$user->hasPermission('zalo.all_conversation_management')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gán branch cho cuộc trò chuyện.',
            ], 403);
        }

        $conversation = \App\Models\ZaloConversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Validate branch exists if provided
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found.',
                ], 404);
            }

            // Verify branch has access to this account
            $account = ZaloAccount::find($accountId ?: $conversation->zalo_account_id);
            if ($account) {
                $hasAccess = \App\Models\ZaloAccountBranch::where('zalo_account_id', $account->id)
                    ->where('branch_id', $branchId)
                    ->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Branch does not have access to this Zalo account.',
                    ], 403);
                }
            }
        }

        // Update conversation - store in assigned_branch_id
        $conversation->update(['assigned_branch_id' => $branchId]);

        Log::info('[ZaloController] Branch assigned to conversation', [
            'conversation_id' => $id,
            'assigned_branch_id' => $branchId,
            'user_id' => $user->id,
        ]);

        // Load updated conversation with relationships
        $conversation->load('assigned_branch');

        // Return both formats for frontend compatibility
        return response()->json([
            'success' => true,
            'message' => 'Branch assigned to conversation successfully.',
            'data' => [
                'branch_id' => $conversation->assigned_branch_id,
                'branch' => $conversation->assigned_branch,
                'assigned_branch_id' => $conversation->assigned_branch_id,
                'assigned_branch' => $conversation->assigned_branch,
            ],
        ]);
    }

    /**
     * Remove user assignment from conversation
     */
    public function removeUserFromConversation(Request $request, $conversationId, $userId)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.all_conversation_management')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa phân công người dùng.',
            ], 403);
        }

        $conversation = \App\Models\ZaloConversation::find($conversationId);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Remove user from conversation
        $conversation->assignedUsers()->detach($userId);

        \Illuminate\Support\Facades\Log::info('[ZaloController] User removed from conversation', [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'removed_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User removed from conversation successfully.',
        ]);
    }

    /**
     * Mark conversation as read
     */
    public function markConversationAsRead(Request $request, $id)
    {
        $user = $request->user();

        $conversation = \App\Models\ZaloConversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.',
            ], 404);
        }

        // Check if user can view this conversation
        if (!$conversation->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this conversation.',
            ], 403);
        }

        $conversation->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read.',
        ]);
    }

    /**
     * Get recent stickers for the account
     */
    public function getRecentStickers(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch ID not found',
                ], 400);
            }

            // Get the Zalo account for this branch
            $account = ZaloAccount::where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active Zalo account found for this branch',
                ]);
            }

            // Get recent stickers
            $recentStickers = \App\Models\ZaloRecentSticker::getRecent($account->id, 30);

            return response()->json([
                'success' => true,
                'data' => $recentStickers,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] getRecentStickers error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent stickers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a sticker usage in recent stickers
     */
    public function recordRecentSticker(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_id' => 'required|integer',
                'sticker_data' => 'required|array',
                'sticker_data.id' => 'required',
            ]);

            $accountId = $validated['account_id'];
            $stickerData = $validated['sticker_data'];

            // Record the sticker usage
            \App\Models\ZaloRecentSticker::recordUsage($accountId, $stickerData);

            return response()->json([
                'success' => true,
                'message' => 'Sticker usage recorded successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] recordRecentSticker error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record sticker usage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a phone number has a Zalo account and is a friend
     * Uses Zalo API service to search for user by phone
     */
    public function checkPhone(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'account_id' => 'required|exists:zalo_accounts,id',
                'phone' => 'required|string',
            ]);

            $accountId = $validated['account_id'];
            $phone = $validated['phone'];

            // Check permission
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or access denied',
                ], 403);
            }

            // Check if zalo-service is ready
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Call zalo-service to search user by phone
            $response = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/user/search', [
                'accountId' => $account->id,
                'phoneNumber' => $phone,
            ]);

            // If user not found (404), phone doesn't have Zalo account
            if ($response->status() === 404) {
                Log::info('[ZaloController] checkPhone - User not found', [
                    'phone' => $phone,
                    'account_id' => $accountId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'hasAccount' => false,
                        'isFriend' => false,
                        'zalo_user_id' => null,
                    ],
                ]);
            }

            if (!$response->successful()) {
                $errorMessage = $response->json('message', 'Unknown error');

                // Check for zpw_sek expired error
                if (str_contains($errorMessage, 'zpw_sek')) {
                    Log::warning('[ZaloController] checkPhone - Session expired (zpw_sek)', [
                        'account_id' => $accountId,
                        'error' => $errorMessage,
                    ]);

                    // Update account status to indicate session expired
                    $account->update([
                        'is_connected' => false,
                        'status' => 'session_expired',
                    ]);

                    return response()->json([
                        'success' => false,
                        'error_code' => 'SESSION_EXPIRED',
                        'message' => 'Phiên đăng nhập Zalo đã hết hạn. Vui lòng đăng nhập lại tài khoản Zalo trong phần Quản lý Zalo.',
                        'account_id' => $accountId,
                        'account_name' => $account->name,
                    ], 401);
                }

                throw new \Exception('Failed to search user: ' . $errorMessage);
            }

            $userData = $response->json('data');
            $hasAccount = !empty($userData);
            $isFriend = $hasAccount && ($userData['isFriend'] ?? false);

            Log::info('[ZaloController] checkPhone - User found', [
                'phone' => $phone,
                'has_account' => $hasAccount,
                'is_friend' => $isFriend,
                'zalo_user_id' => $userData['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'hasAccount' => $hasAccount,
                    'isFriend' => $isFriend,
                    'zalo_user_id' => $userData['id'] ?? null,  // Match zalo-service /api/user/search response
                    'display_name' => $userData['display_name'] ?? null,  // Match zalo-service response (snake_case)
                    'avatar' => $userData['avatar'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] checkPhone error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check for zpw_sek error in exception message
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'zpw_sek')) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'SESSION_EXPIRED',
                    'message' => 'Phiên đăng nhập Zalo đã hết hạn. Vui lòng đăng nhập lại tài khoản Zalo trong phần Quản lý Zalo.',
                ], 401);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to check phone number',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send message to customer by phone number
     * Uses Zalo API service - automatically sends friend request if not friends yet
     */
    public function sendMessageToCustomer(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'account_id' => 'required|exists:zalo_accounts,id',
                'customer_phone' => 'required|string',
                'customer_id' => 'nullable|exists:customers,id',
                'message' => 'required|string',
                'auto_assign' => 'boolean',
            ]);

            $accountId = $validated['account_id'];
            $customerPhone = $validated['customer_phone'];
            $customerId = $validated['customer_id'] ?? null;
            $messageText = $validated['message'];
            $autoAssign = $validated['auto_assign'] ?? false;

            // Check permission
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or access denied',
                ], 403);
            }

            // Check if zalo-service is ready
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Step 1: Search for user by phone number via Zalo service
            Log::info('[ZaloController] sendMessageToCustomer - Searching user by phone', [
                'phone' => $customerPhone,
                'account_id' => $accountId,
            ]);

            $searchResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/user/search', [
                'accountId' => $account->id,
                'phoneNumber' => $customerPhone,
            ]);

            // If user not found
            if ($searchResponse->status() === 404) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer does not have a Zalo account with this phone number',
                ], 404);
            }

            if (!$searchResponse->successful()) {
                throw new \Exception('Failed to search user: ' . $searchResponse->json('message', 'Unknown error'));
            }

            $userData = $searchResponse->json('data');
            $zaloUserId = $userData['id'];  // Match zalo-service /api/user/search response
            $isFriend = $userData['isFriend'] ?? false;

            // Step 2: If not friends yet, send friend request
            if (!$isFriend) {
                Log::info('[ZaloController] sendMessageToCustomer - Not friends, sending friend request', [
                    'phone' => $customerPhone,
                    'zalo_user_id' => $zaloUserId,
                ]);

                $friendRequestResponse = Http::timeout(30)->withHeaders([
                    'X-API-Key' => config('services.zalo.api_key'),
                ])->post(config('services.zalo.base_url') . '/api/friend/send-request', [
                    'accountId' => $account->id,
                    'userId' => $zaloUserId,
                    'message' => 'Xin chào! Tôi muốn kết bạn với bạn.',
                ]);

                if (!$friendRequestResponse->successful()) {
                    Log::warning('[ZaloController] sendMessageToCustomer - Friend request failed, but continuing', [
                        'error' => $friendRequestResponse->json('message', 'Unknown error'),
                    ]);
                    // Continue even if friend request fails - we'll try to send message anyway
                }
            }

            // Step 3: Send message via Zalo service
            Log::info('[ZaloController] sendMessageToCustomer - Sending message', [
                'zalo_user_id' => $zaloUserId,
            ]);

            $sendMessageResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/message/send', [
                'accountId' => $account->id,
                'recipientId' => $zaloUserId,
                'recipientType' => 'user',
                'message' => $messageText,
            ]);

            if (!$sendMessageResponse->successful()) {
                $errorData = $sendMessageResponse->json();
                Log::error('[ZaloController] sendMessageToCustomer - Send message failed', [
                    'error' => $errorData,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send message',
                    'error' => $errorData['message'] ?? 'Unknown error',
                ], 500);
            }

            $sendResult = $sendMessageResponse->json('data');

            // Step 4: Find or create conversation in database
            $conversation = ZaloConversation::where('zalo_account_id', $account->id)
                ->where('recipient_id', $zaloUserId)
                ->where('recipient_type', 'user')
                ->first();

            if (!$conversation) {
                // Create new conversation
                $conversation = ZaloConversation::create([
                    'zalo_account_id' => $account->id,
                    'branch_id' => $account->branch_id,
                    'recipient_id' => $zaloUserId,
                    'recipient_type' => 'user',
                    'recipient_name' => $userData['display_name'] ?? 'Unknown',
                    'recipient_avatar' => $userData['avatar'] ?? null,
                    'last_message' => $messageText,
                    'last_message_time' => now(),
                    'unread_count' => 0,
                ]);

                // Auto-assign if requested
                if ($autoAssign) {
                    $conversation->assigned_user_id = $user->id;

                    // Auto-assign to user's primary department if available
                    if ($user->primaryDepartment) {
                        $conversation->department_id = $user->primaryDepartment->id;
                    }

                    $conversation->save();
                }

                // Link to customer if customer_id provided
                if ($customerId) {
                    $conversation->customer_id = $customerId;
                    $conversation->save();
                }
            } else {
                // Update existing conversation
                $conversation->update([
                    'last_message' => $messageText,
                    'last_message_time' => now(),
                ]);
            }

            // Step 5: Save message to database
            $message = ZaloMessage::create([
                'zalo_account_id' => $account->id,
                'zalo_conversation_id' => $conversation->id,
                'recipient_id' => $zaloUserId,
                'recipient_type' => 'user',
                'message_id' => $sendResult['messageId'] ?? null,
                'type' => 'sent',
                'content' => $messageText,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            Log::info('[ZaloController] sendMessageToCustomer - Success', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully' . (!$isFriend ? ' (friend request sent)' : ''),
                'data' => [
                    'message' => $message,
                    'conversation' => $conversation,
                    'friend_request_sent' => !$isFriend,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] sendMessageToCustomer error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message to customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user info from Zalo API
     */
    public function getUserInfo(Request $request, $userId)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'account_id' => 'required|exists:zalo_accounts,id',
            ]);

            $accountId = $validated['account_id'];

            // Check permission
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or access denied',
                ], 403);
            }

            // Check if zalo-service is ready
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo service is not ready. Please ensure the account is connected.',
                ], 503);
            }

            // Call ZaloNotificationService to get user info
            $result = $this->zalo->getUserInfo($userId, $account->id);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('[ZaloController] getUserInfo error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get user info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all groups for assignment (with current assignments)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listGroupsForAssignment(Request $request)
    {
        try {
            $user = $request->user();

            // Get groups accessible by user
            $query = ZaloGroup::with(['zaloAccount', 'branch', 'department']);

            // Apply access control
            if (!$user->hasPermission('zalo.view_groups')) {
                // Filter by user's branches/departments
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                $userDepartmentIds = $user->departments()->pluck('departments.id')->toArray();

                $query->where(function($q) use ($userBranchIds, $userDepartmentIds) {
                    $q->whereNull('branch_id')
                      ->orWhereIn('branch_id', $userBranchIds)
                      ->orWhereIn('department_id', $userDepartmentIds);
                });
            }

            $groups = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] listGroupsForAssignment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list groups for assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available branches for group assignment
     * Returns branches that share the same Zalo account
     *
     * @param Request $request
     * @param string $groupId - zalo_group_id (BigInt string)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableBranches(Request $request, $groupId)
    {
        try {
            $user = $request->user();
            $accountId = $request->input('account_id');

            // Get account
            if ($accountId) {
                $account = ZaloAccount::accessibleBy($user)->find($accountId);
            } else {
                $account = ZaloAccount::active()->accessibleBy($user)->first();
            }

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active Zalo account found',
                ], 404);
            }

            // Find group by zalo_group_id
            $group = ZaloGroup::where('zalo_account_id', $account->id)
                ->where('zalo_group_id', $groupId)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found',
                ], 404);
            }

            // Get all active branches
            // Groups can be assigned to any branch
            $branches = \App\Models\Branch::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($branch) use ($account) {
                    return [
                        'id' => $branch->id,
                        'code' => $branch->code,
                        'name' => $branch->name,
                        'zalo_account_name' => $account->name,
                    ];
                });

            Log::info('[ZaloController] Available branches loaded', [
                'group_id' => $group->id,
                'zalo_group_id' => $groupId,
                'zalo_account_id' => $account->id,
                'branches_count' => $branches->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $branches,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] getAvailableBranches error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $groupId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load available branches',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign group to branch/department
     *
     * @param Request $request
     * @param string $groupId - zalo_group_id (BigInt string)
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignGroup(Request $request, $groupId)
    {
        try {
            $user = $request->user();

            // Find group by zalo_group_id only (groups are now shared across accounts)
            $group = ZaloGroup::where('zalo_group_id', $groupId)->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found',
                ], 404);
            }

            // Check permission: Only superadmin or users with view_all_branches_groups permission can assign branches
            $canAssignBranch = $user->hasRole('super-admin') || $user->hasPermissionTo('zalo.view_all_branches_groups');

            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'nullable|exists:departments,id',
            ]);

            // Only update branch_id if user has permission
            if (!$canAssignBranch) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to assign branches. Only superadmin or users with view_all_branches_groups permission can assign branches.',
                ], 403);
            }

            $branchId = $validated['branch_id'];
            $departmentId = $validated['department_id'] ?? null;

            // Check if this assignment already exists
            $existingAssignment = $group->branches()->where('branch_id', $branchId)->first();

            if ($existingAssignment) {
                // Update existing assignment
                $group->branches()->updateExistingPivot($branchId, [
                    'department_id' => $departmentId,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);

                Log::info('[ZaloController] Updated existing group assignment', [
                    'group_id' => $group->id,
                    'zalo_group_id' => $groupId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'user_id' => $user->id,
                ]);
            } else {
                // Create new assignment
                $group->branches()->attach($branchId, [
                    'department_id' => $departmentId,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);

                Log::info('[ZaloController] Created new group assignment', [
                    'group_id' => $group->id,
                    'zalo_group_id' => $groupId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'user_id' => $user->id,
                ]);
            }

            // Reload relationships after update
            $group->load(['branches']);

            // Get the updated assignment
            $assignment = $group->branches()->where('branch_id', $branchId)->first();
            $branch = $assignment;
            $department = $assignment && $assignment->pivot->department_id
                ? Department::find($assignment->pivot->department_id)
                : null;

            return response()->json([
                'success' => true,
                'message' => 'Group assigned successfully',
                'data' => [
                    'branch_id' => $branch ? $branch->id : null,
                    'branch' => $branch ? [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                    ] : null,
                    'department_id' => $department ? $department->id : null,
                    'department' => $department ? [
                        'id' => $department->id,
                        'name' => $department->name,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] assignGroup error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $groupId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all Zalo accounts with their branch access information
     * For display in Branch Access Management settings
     */
    public function getAccountsBranchAccess(Request $request)
    {
        try {
            $user = $request->user();

            // Get accounts accessible by user with branch relationships
            $accounts = ZaloAccount::accessibleBy($user)
                ->with(['branch:id,code,name', 'branches:id,code,name', 'assignedUser:id,name,email'])
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->orderBy('name')
                ->get()
                ->map(function ($account) {
                    // Collect all branches (primary + shared)
                    $allBranches = collect();

                    // Add primary branch
                    if ($account->branch) {
                        $allBranches->push([
                            'id' => $account->branch->id,
                            'code' => $account->branch->code,
                            'name' => $account->branch->name,
                            'is_primary' => true,
                        ]);
                    }

                    // Add shared branches from zalo_account_branches
                    foreach ($account->branches as $branch) {
                        if (!$account->branch || $branch->id !== $account->branch->id) {
                            $allBranches->push([
                                'id' => $branch->id,
                                'code' => $branch->code,
                                'name' => $branch->name,
                                'is_primary' => false,
                            ]);
                        }
                    }

                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'phone' => $account->phone,
                        'avatar_url' => $account->avatar_url,
                        'is_active' => $account->is_active,
                        'is_connected' => $account->is_connected,
                        'primary_branch' => $account->branch ? [
                            'id' => $account->branch->id,
                            'code' => $account->branch->code,
                            'name' => $account->branch->name,
                        ] : null,
                        'branches' => $allBranches->values()->toArray(),
                        'branches_count' => $allBranches->count(),
                        'assigned_user' => $account->assignedUser ? [
                            'id' => $account->assignedUser->id,
                            'name' => $account->assignedUser->name,
                        ] : null,
                    ];
                });

            // Get all available branches for adding
            $availableBranches = \App\Models\Branch::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'accounts' => $accounts,
                    'available_branches' => $availableBranches,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] getAccountsBranchAccess error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load account branch access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update branch access for a Zalo account
     * Can add or remove branch access
     */
    public function updateAccountBranchAccess(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Validate request
            $validated = $request->validate([
                'action' => 'required|in:add,remove',
                'branch_id' => 'required|exists:branches,id',
            ]);

            $action = $validated['action'];
            $branchId = $validated['branch_id'];

            // Get account
            $account = ZaloAccount::accessibleBy($user)->find($id);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission.',
                ], 404);
            }

            // Cannot remove primary branch access
            if ($action === 'remove' && $account->branch_id == $branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove access from the primary branch. Change primary branch first.',
                ], 422);
            }

            if ($action === 'add') {
                // Check if already exists
                $exists = \DB::table('zalo_account_branches')
                    ->where('zalo_account_id', $account->id)
                    ->where('branch_id', $branchId)
                    ->exists();

                if (!$exists) {
                    \DB::table('zalo_account_branches')->insert([
                        'zalo_account_id' => $account->id,
                        'branch_id' => $branchId,
                        'role' => 'shared',
                        'can_send_message' => true,
                        'view_all_friends' => true,
                        'view_all_groups' => true,
                        'view_all_conversations' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('[ZaloController] Branch access added with full permissions', [
                        'account_id' => $account->id,
                        'branch_id' => $branchId,
                        'added_by' => $user->id,
                    ]);
                }
            } else {
                // Remove branch access
                \DB::table('zalo_account_branches')
                    ->where('zalo_account_id', $account->id)
                    ->where('branch_id', $branchId)
                    ->delete();

                Log::info('[ZaloController] Branch access removed', [
                    'account_id' => $account->id,
                    'branch_id' => $branchId,
                    'removed_by' => $user->id,
                ]);
            }

            // Reload and return updated account
            $account->load(['branch:id,code,name', 'branches:id,code,name']);

            $allBranches = collect();
            if ($account->branch) {
                $allBranches->push([
                    'id' => $account->branch->id,
                    'code' => $account->branch->code,
                    'name' => $account->branch->name,
                    'is_primary' => true,
                ]);
            }
            foreach ($account->branches as $branch) {
                if (!$account->branch || $branch->id !== $account->branch->id) {
                    $allBranches->push([
                        'id' => $branch->id,
                        'code' => $branch->code,
                        'name' => $branch->name,
                        'is_primary' => false,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $action === 'add' ? 'Branch access added' : 'Branch access removed',
                'data' => [
                    'id' => $account->id,
                    'branches' => $allBranches->values()->toArray(),
                    'branches_count' => $allBranches->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] updateAccountBranchAccess error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get permissions for a specific branch access
     */
    public function getBranchPermissions(Request $request, $accountId, $branchId)
    {
        try {
            $user = $request->user();

            // Get account
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission.',
                ], 404);
            }

            // Get branch access record
            $branchAccess = \DB::table('zalo_account_branches')
                ->where('zalo_account_id', $accountId)
                ->where('branch_id', $branchId)
                ->first();

            if (!$branchAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch access not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'permissions' => [
                        'can_send_message' => (bool) $branchAccess->can_send_message,
                        'view_all_friends' => (bool) $branchAccess->view_all_friends,
                        'view_all_groups' => (bool) $branchAccess->view_all_groups,
                        'view_all_conversations' => (bool) $branchAccess->view_all_conversations,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] getBranchPermissions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId ?? null,
                'branch_id' => $branchId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load branch permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a single permission for branch access
     */
    public function updateBranchPermission(Request $request, $accountId, $branchId)
    {
        try {
            $user = $request->user();

            // Validate request
            $validated = $request->validate([
                'permission' => 'required|in:can_send_message,view_all_friends,view_all_groups,view_all_conversations',
                'value' => 'required|boolean',
            ]);

            // Get account
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission.',
                ], 404);
            }

            // Check if branch access exists
            $exists = \DB::table('zalo_account_branches')
                ->where('zalo_account_id', $accountId)
                ->where('branch_id', $branchId)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch access not found.',
                ], 404);
            }

            // Update the specific permission
            \DB::table('zalo_account_branches')
                ->where('zalo_account_id', $accountId)
                ->where('branch_id', $branchId)
                ->update([
                    $validated['permission'] => $validated['value'],
                    'updated_at' => now(),
                ]);

            Log::info('[ZaloController] Branch permission updated', [
                'account_id' => $accountId,
                'branch_id' => $branchId,
                'permission' => $validated['permission'],
                'value' => $validated['value'],
                'updated_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] updateBranchPermission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId ?? null,
                'branch_id' => $branchId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove branch access (delete from zalo_account_branches)
     * Only allowed for shared branches, not the primary branch
     */
    public function removeBranchAccess(Request $request, $accountId, $branchId)
    {
        try {
            $user = $request->user();

            // Get account
            $account = ZaloAccount::accessibleBy($user)->find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found or you do not have permission.',
                ], 404);
            }

            // Cannot remove primary branch access
            if ($account->branch_id == $branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove access for the primary branch. Please delete the account instead.',
                ], 400);
            }

            // Check if branch access exists
            $exists = \DB::table('zalo_account_branches')
                ->where('zalo_account_id', $accountId)
                ->where('branch_id', $branchId)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch access not found.',
                ], 404);
            }

            // Delete the branch access
            \DB::table('zalo_account_branches')
                ->where('zalo_account_id', $accountId)
                ->where('branch_id', $branchId)
                ->delete();

            Log::info('[ZaloController] Branch access removed', [
                'account_id' => $accountId,
                'branch_id' => $branchId,
                'removed_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch access removed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] removeBranchAccess error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'account_id' => $accountId ?? null,
                'branch_id' => $branchId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove branch access',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate session status for a Zalo account
     * Checks if zpw_sek is valid by making a test API call
     */
    public function validateSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_id' => 'required|exists:zalo_accounts,id',
            ]);

            $account = ZaloAccount::find($validated['account_id']);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            // Check if zalo-service is running
            if (!$this->zalo->isReady($account->id)) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'SERVICE_NOT_READY',
                    'message' => 'Zalo service is not ready',
                    'is_valid' => false,
                ]);
            }

            // Try to get profile or make a simple API call to validate session
            $response = Http::timeout(15)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->get(config('services.zalo.base_url') . '/api/auth/status', [
                'accountId' => $account->id,
            ]);

            if (!$response->successful()) {
                $errorMessage = $response->json('message', 'Unknown error');

                // Check for zpw_sek expired error
                if (str_contains($errorMessage, 'zpw_sek')) {
                    $account->update([
                        'is_connected' => false,
                        'status' => 'session_expired',
                    ]);

                    return response()->json([
                        'success' => true,
                        'is_valid' => false,
                        'error_code' => 'SESSION_EXPIRED',
                        'message' => 'Phiên đăng nhập Zalo đã hết hạn',
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'is_valid' => false,
                    'message' => $errorMessage,
                ]);
            }

            // Session is valid
            $account->update([
                'is_connected' => true,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'is_valid' => true,
                'message' => 'Session is valid',
                'account_id' => $account->id,
                'account_name' => $account->name,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] validateSession error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'is_valid' => false,
                'message' => 'Failed to validate session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate sessions for all accessible Zalo accounts
     * Updates status for both owner and shared accounts
     */
    public function validateAllSessions(Request $request)
    {
        try {
            $user = $request->user();
            $branchId = $this->getBranchId($request);

            // Get all accessible accounts
            $accounts = ZaloAccount::accessibleBy($user)
                ->when($branchId, fn($q) => $q->forBranch($branchId))
                ->where('is_active', true)
                ->get();

            $results = [];
            $expiredCount = 0;
            $validCount = 0;

            foreach ($accounts as $account) {
                // Check if zalo-service is running for this account
                $isReady = $this->zalo->isReady($account->id);

                if (!$isReady) {
                    $results[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'is_valid' => false,
                        'error_code' => 'SERVICE_NOT_READY',
                        'message' => 'Zalo service is not ready',
                    ];
                    continue;
                }

                // Try to validate session
                try {
                    $response = Http::timeout(10)->withHeaders([
                        'X-API-Key' => config('services.zalo.api_key'),
                    ])->get(config('services.zalo.base_url') . '/api/auth/status', [
                        'accountId' => $account->id,
                    ]);

                    if (!$response->successful()) {
                        $errorMessage = $response->json('message', 'Unknown error');

                        if (str_contains($errorMessage, 'zpw_sek')) {
                            $account->update([
                                'is_connected' => false,
                                'status' => 'session_expired',
                            ]);

                            $results[] = [
                                'account_id' => $account->id,
                                'account_name' => $account->name,
                                'is_valid' => false,
                                'error_code' => 'SESSION_EXPIRED',
                                'message' => 'Phiên đăng nhập đã hết hạn',
                            ];
                            $expiredCount++;
                        } else {
                            $results[] = [
                                'account_id' => $account->id,
                                'account_name' => $account->name,
                                'is_valid' => false,
                                'error_code' => 'ERROR',
                                'message' => $errorMessage,
                            ];
                        }
                    } else {
                        $account->update([
                            'is_connected' => true,
                            'status' => 'active',
                        ]);

                        $results[] = [
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            'is_valid' => true,
                            'message' => 'Session is valid',
                        ];
                        $validCount++;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'is_valid' => false,
                        'error_code' => 'ERROR',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'summary' => [
                    'total' => count($accounts),
                    'valid' => $validCount,
                    'expired' => $expiredCount,
                    'other_errors' => count($accounts) - $validCount - $expiredCount,
                ],
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] validateAllSessions error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate sessions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete conversation from database (soft delete - keeps messages but marks conversation as deleted)
     * This only affects local database, not Zalo server
     */
    public function deleteConversation(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Find conversation by ID or recipient_id (Zalo user ID)
            $conversation = ZaloConversation::find($id);

            if (!$conversation) {
                // Try finding by recipient_id (Zalo user ID)
                $conversation = ZaloConversation::where('recipient_id', $id)->first();
            }

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            // Check permission - user must have zalo.view or be assigned to this conversation
            $hasPermission = $user->is_super_admin ||
                             $user->hasPermission('zalo.all_conversation_management') ||
                             $conversation->assigned_user_id === $user->id;

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this conversation',
                ], 403);
            }

            $conversationId = $conversation->id;
            $recipientId = $conversation->recipient_id;
            $zaloAccountId = $conversation->zalo_account_id;

            // Delete all messages in this conversation (use zalo_conversation_id or recipient_id)
            $deletedMessagesCount = ZaloMessage::where('zalo_conversation_id', $conversationId)
                ->orWhere(function ($query) use ($zaloAccountId, $recipientId) {
                    $query->where('zalo_account_id', $zaloAccountId)
                          ->where('recipient_id', $recipientId);
                })
                ->delete();

            // Delete the conversation record
            $conversation->delete();

            Log::info('[ZaloController] deleteConversation success', [
                'user_id' => $user->id,
                'conversation_id' => $conversationId,
                'recipient_id' => $recipientId,
                'zalo_account_id' => $zaloAccountId,
                'deleted_messages' => $deletedMessagesCount,
            ]);

            // 🔥 Broadcast conversation deleted event for realtime UI update
            try {
                $broadcastResponse = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders([
                        'X-API-Key' => config('services.zalo.api_key'),
                        'Content-Type' => 'application/json',
                    ])
                    ->post(
                        config('services.zalo.base_url') . '/api/socket/broadcast',
                        [
                            'event' => 'zalo:conversation:deleted',
                            'account_id' => $zaloAccountId,
                            'data' => [
                                'conversation_id' => $conversationId,
                                'recipient_id' => $recipientId,
                                'account_id' => $zaloAccountId,
                            ],
                        ]
                    );

                if ($broadcastResponse->successful()) {
                    Log::info('[ZaloController] Conversation deleted event broadcasted', [
                        'conversation_id' => $conversationId,
                    ]);
                }
            } catch (\Exception $broadcastError) {
                Log::warning('[ZaloController] Failed to broadcast conversation deleted event', [
                    'error' => $broadcastError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
                'data' => [
                    'deleted_messages' => $deletedMessagesCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] deleteConversation error', [
                'error' => $e->getMessage(),
                'conversation_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recall (undo) a sent message via Zalo API
     * This calls zalo-service to recall the message on Zalo server
     */
    public function recallMessage(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Find the message
            $message = ZaloMessage::find($id);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                ], 404);
            }

            // Can only recall sent messages (type = 'sent')
            if ($message->type !== 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only sent messages can be recalled',
                ], 400);
            }

            // Check if message was sent within time limit (Zalo allows ~2 minutes)
            $sentAt = $message->sent_at ?? $message->created_at;
            $minutesSinceSent = now()->diffInMinutes($sentAt);

            if ($minutesSinceSent > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message is too old to recall (limit: 5 minutes)',
                ], 400);
            }

            // Get message IDs needed for recall
            $msgId = $message->message_id;
            $cliMsgId = $message->metadata['cliMsgId'] ?? $message->metadata['cli_msg_id'] ?? null;

            if (!$msgId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message ID not found, cannot recall',
                ], 400);
            }

            // Call zalo-service to recall the message
            $zaloServiceUrl = config('services.zalo.base_url');
            $apiKey = config('services.zalo.api_key');

            $response = Http::timeout(30)->withHeaders([
                'X-API-Key' => $apiKey,
                'X-Account-Id' => $message->zalo_account_id,
            ])->post($zaloServiceUrl . '/api/message/recall', [
                'to' => $message->recipient_id,
                'msgId' => $msgId,
                'cliMsgId' => $cliMsgId ?? $msgId,
                'type' => $message->recipient_type === 'group' ? 'group' : 'user',
            ]);

            if (!$response->successful()) {
                $errorMessage = $response->json('message', 'Failed to recall message');

                Log::error('[ZaloController] recallMessage failed', [
                    'message_id' => $id,
                    'error' => $errorMessage,
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }

            // Update message in database to mark as recalled
            $message->update([
                'content' => '[Tin nhắn đã thu hồi]',
                'metadata' => array_merge($message->metadata ?? [], [
                    'recalled' => true,
                    'recalled_at' => now()->toIso8601String(),
                    'recalled_by' => $user->id,
                ]),
            ]);

            Log::info('[ZaloController] recallMessage success', [
                'user_id' => $user->id,
                'message_id' => $id,
                'zalo_message_id' => $msgId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message recalled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] recallMessage error', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to recall message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recall message by Zalo message ID (called from zalo-service undo listener)
     * This is triggered when an undo event is received from Zalo WebSocket
     */
    public function recallMessageByMsgId(Request $request)
    {
        try {
            // Validate API key from zalo-service
            $apiKey = $request->header('X-API-Key');
            $expectedKey = config('services.zalo.api_key');

            if (!$apiKey || $apiKey !== $expectedKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $zaloAccountId = $request->input('zalo_account_id');
            $messageId = $request->input('message_id');
            $cliMsgId = $request->input('cli_msg_id');

            // Require zalo_account_id and at least one of message_id or cli_msg_id
            if (!$zaloAccountId || (!$messageId && !$cliMsgId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields (need zalo_account_id and at least one of message_id or cli_msg_id)',
                ], 400);
            }

            // Find message by message_id or cli_msg_id
            $query = ZaloMessage::where('zalo_account_id', $zaloAccountId)
                ->where(function ($q) use ($messageId, $cliMsgId) {
                    // Only search by message_id if it's valid (not null, empty, or "0")
                    if ($messageId && $messageId != '0' && $messageId != 0) {
                        $q->where('message_id', $messageId);
                    }

                    // Search by cli_msg_id in metadata
                    if ($cliMsgId) {
                        if ($messageId && $messageId != '0' && $messageId != 0) {
                            $q->orWhere('metadata->cliMsgId', $cliMsgId)
                              ->orWhere('metadata->cli_msg_id', $cliMsgId);
                        } else {
                            // If no valid message_id, use WHERE instead of OR WHERE
                            $q->where(function ($subQ) use ($cliMsgId) {
                                $subQ->where('metadata->cliMsgId', $cliMsgId)
                                     ->orWhere('metadata->cli_msg_id', $cliMsgId);
                            });
                        }
                    }
                });

            $message = $query->first();

            if (!$message) {
                Log::warning('[ZaloController] Message not found for recall', [
                    'zalo_account_id' => $zaloAccountId,
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                ], 404);
            }

            // Update message to mark as recalled
            $message->update([
                'content' => '[Tin nhắn đã thu hồi]',
                'metadata' => array_merge($message->metadata ?? [], [
                    'recalled' => true,
                    'recalled_at' => now()->toIso8601String(),
                    'recalled_by_event' => true, // Marked by undo event listener
                ]),
            ]);

            Log::info('[ZaloController] Message recalled by undo event', [
                'message_id' => $message->id,
                'zalo_message_id' => $messageId,
                'zalo_account_id' => $zaloAccountId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message recalled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] recallMessageByMsgId error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to recall message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================================
    // TELEGRAM NOTIFICATION SETTINGS
    // ============================================================================

    /**
     * Get Telegram settings for a Zalo account
     * Settings are stored in the metadata column
     */
    public function getTelegramSettings(Request $request, $accountId)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $account = ZaloAccount::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        $metadata = $account->metadata ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'telegram_bot_token' => $metadata['telegram_bot_token'] ?? null,
                'telegram_chat_id' => $metadata['telegram_chat_id'] ?? null,
                'telegram_enabled' => !empty($metadata['telegram_bot_token']) && !empty($metadata['telegram_chat_id']),
            ],
        ]);
    }

    /**
     * Save Telegram settings for a Zalo account
     */
    public function saveTelegramSettings(Request $request, $accountId)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $account = ZaloAccount::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        $validated = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        // Update metadata
        $metadata = $account->metadata ?? [];
        $metadata['telegram_bot_token'] = $validated['telegram_bot_token'] ?? null;
        $metadata['telegram_chat_id'] = $validated['telegram_chat_id'] ?? null;
        $account->metadata = $metadata;
        $account->save();

        Log::info('[ZaloController] Telegram settings saved', [
            'account_id' => $accountId,
            'user_id' => $user->id,
            'has_token' => !empty($validated['telegram_bot_token']),
            'has_chat_id' => !empty($validated['telegram_chat_id']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Telegram settings saved successfully',
        ]);
    }

    /**
     * Get all Telegram settings for Node.js zalo-service
     * This endpoint is called by the zalo-service to fetch all active Telegram configs
     * Protected by API key (X-API-Key header)
     */
    public function getAllTelegramSettings(Request $request)
    {
        // Verify API key (from zalo-service)
        $apiKey = $request->header('X-API-Key');
        $expectedKey = config('services.zalo.api_key');

        if ($apiKey !== $expectedKey) {
            Log::warning('[ZaloController] Invalid API key for getAllTelegramSettings');
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        $accounts = ZaloAccount::where('is_active', true)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($account) {
                $metadata = $account->metadata ?? [];
                return !empty($metadata['telegram_bot_token']) && !empty($metadata['telegram_chat_id']);
            })
            ->map(function ($account) {
                $metadata = $account->metadata ?? [];
                return [
                    'account_id' => $account->id,
                    'telegram_bot_token' => $metadata['telegram_bot_token'],
                    'telegram_chat_id' => $metadata['telegram_chat_id'],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Test Telegram notification for a specific account
     */
    public function testTelegramNotification(Request $request, $accountId)
    {
        $user = $request->user();

        // Check permission
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.manage_accounts')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $account = ZaloAccount::find($accountId);
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        $metadata = $account->metadata ?? [];
        $botToken = $metadata['telegram_bot_token'] ?? null;
        $chatId = $metadata['telegram_chat_id'] ?? null;

        if (!$botToken || !$chatId) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram settings not configured for this account',
            ], 400);
        }

        // Send test message via Telegram API
        try {
            $timestamp = now()->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s');
            $message = "🧪 <b>TELEGRAM BOT TEST</b>\n\n";
            $message .= "✅ Bot đã được cấu hình thành công!\n";
            $message .= "📱 Account: <code>{$account->name}</code>\n";
            $message .= "⏰ Thời gian: {$timestamp}\n";
            $message .= "🔔 Bạn sẽ nhận thông báo khi Zalo bị ngắt kết nối.";

            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );

            if ($response->successful()) {
                Log::info('[ZaloController] Telegram test message sent', [
                    'account_id' => $accountId,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully',
                ]);
            } else {
                Log::error('[ZaloController] Telegram test failed', [
                    'account_id' => $accountId,
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test message',
                    'error' => $response->json()['description'] ?? 'Unknown error',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloController] Telegram test exception', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proxy media files from Zalo CDN to avoid CORS issues
     * This endpoint fetches media from Zalo CDN and streams it to the browser
     */
    public function proxyMedia(Request $request)
    {
        try {
            $url = $request->query('url');

            if (!$url) {
                return response()->json([
                    'error' => 'URL parameter is required'
                ], 400);
            }

            // Validate that the URL is from Zalo CDN
            if (!str_contains($url, 'dlfl.vn')) {
                return response()->json([
                    'error' => 'Invalid media URL'
                ], 400);
            }

            // Fetch the media from Zalo CDN
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning('[ZaloController] Failed to fetch media from Zalo CDN', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return response()->json([
                    'error' => 'Failed to fetch media',
                    'status' => $response->status(),
                ], $response->status());
            }

            // Get content type from response or default to application/octet-stream
            $contentType = $response->header('Content-Type') ?? 'application/octet-stream';
            $contentLength = $response->header('Content-Length');

            // Stream the media back to the browser with proper headers
            return response($response->body())
                ->header('Content-Type', $contentType)
                ->header('Content-Length', $contentLength ?? strlen($response->body()))
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET')
                ->header('Access-Control-Allow-Headers', 'Content-Type');

        } catch (\Exception $e) {
            Log::error('[ZaloController] Media proxy exception', [
                'url' => $request->query('url'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to proxy media',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract username from email (part before @)
     * Example: "user1212342@songthuy.edu.vn" => "user1212342"
     */
    private function extractUsername(?string $email): ?string
    {
        if (!$email) {
            return null;
        }
        
        $parts = explode('@', $email);
        return $parts[0] ?? null;
    }
}
