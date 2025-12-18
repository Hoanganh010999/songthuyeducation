<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\ZaloMessage;
use App\Models\ZaloConversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloMultiBranchService
{
    /**
     * Get all accounts for a zalo_id across all branches
     *
     * @param string $zaloId
     * @return Collection
     */
    public static function getAccountsForZaloId(string $zaloId): Collection
    {
        return ZaloAccount::where('zalo_id', $zaloId)->get();
    }

    /**
     * Broadcast message to relevant accounts based on conversation assignment
     *
     * Logic:
     * - If conversation assigned to branch (branch_id != NULL): Only broadcast to that branch's account
     * - If conversation is global (branch_id = NULL): Broadcast to ALL branches with same zalo_id
     *
     * @param ZaloMessage $message
     * @param ZaloConversation $conversation
     * @return void
     */
    public static function broadcastMessage(
        ZaloMessage $message,
        ZaloConversation $conversation
    ): void {
        // If conversation assigned to branch: Only broadcast to that branch's account
        if ($conversation->branch_id) {
            $account = $conversation->zaloAccount;
            self::broadcastToAccount($account->id, $message, $conversation);

            Log::info('[ZaloMultiBranch] Message broadcast to assigned branch', [
                'account_id' => $account->id,
                'branch_id' => $conversation->branch_id,
                'message_id' => $message->id,
            ]);

            return;
        }

        // If conversation is global: Broadcast to ALL branches with same zalo_id
        $zaloId = $conversation->zaloAccount->zalo_id;
        $accounts = self::getAccountsForZaloId($zaloId);

        Log::info('[ZaloMultiBranch] Broadcasting message to all branches', [
            'zalo_id' => $zaloId,
            'account_count' => $accounts->count(),
            'message_id' => $message->id,
        ]);

        foreach ($accounts as $account) {
            self::broadcastToAccount($account->id, $message, $conversation);
        }
    }

    /**
     * Broadcast to a specific account's WebSocket room
     *
     * @param int $accountId
     * @param ZaloMessage $message
     * @param ZaloConversation $conversation
     * @return void
     */
    private static function broadcastToAccount(
        int $accountId,
        ZaloMessage $message,
        ZaloConversation $conversation
    ): void {
        try {
            // Get sender info
            $senderAvatar = null;
            $senderName = null;

            if ($conversation->recipient_type === 'group' && isset($message->metadata['sender_id'])) {
                // Group message - get sender from group members
                $senderId = $message->metadata['sender_id'];
                $group = \App\Models\ZaloGroup::where('zalo_account_id', $accountId)
                    ->where('zalo_group_id', $conversation->recipient_id)
                    ->first();

                if ($group) {
                    $member = \App\Models\ZaloGroupMember::where('zalo_group_id', $group->id)
                        ->where('zalo_user_id', $senderId)
                        ->first();

                    if ($member) {
                        $senderAvatar = $member->avatar_url;
                        $senderName = $member->display_name;
                    }
                }
            } else {
                // 1-on-1 message - sender is the conversation recipient (contact)
                // For incoming messages, use recipient's avatar and name
                if ($message->direction === 'incoming') {
                    $senderAvatar = $conversation->recipient_avatar_url;
                    $senderName = $conversation->recipient_name;
                }
            }

            // 🔥 NEW: Prepare sent_by_user_name and sent_by_account_username for broadcast
            $sentByUserName = null;
            $sentByAccountUsername = null;
            if ($message->sent_by_user_id) {
                $systemUser = \App\Models\User::find($message->sent_by_user_id);
                if ($systemUser) {
                    $sentByUserName = $systemUser->name;
                    // Extract username from email (part before @)
                    $email = $systemUser->email;
                    $atIndex = strpos($email, '@');
                    $sentByAccountUsername = $atIndex !== false ? substr($email, 0, $atIndex) : $email;
                }
            }
            
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-Key' => config('services.zalo.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post(
                    config('services.zalo.base_url') . '/api/socket/broadcast',
                    [
                        'event' => 'zalo:message:new',
                        'account_id' => $accountId, // Target this account's room
                        'data' => [
                            'account_id' => $accountId,
                            'recipient_id' => $conversation->recipient_id,
                            'message' => array_merge($message->toArray(), [
                                'sender_avatar' => $senderAvatar,
                                'sender_name' => $senderName,
                                'sent_by_user_name' => $sentByUserName, // 🔥 NEW
                                'sent_by_account_username' => $sentByAccountUsername, // 🔥 NEW
                            ]),
                        ],
                    ]
                );

            if ($response->successful()) {
                Log::info('[ZaloMultiBranch] Message broadcasted', [
                    'account_id' => $accountId,
                    'message_id' => $message->id,
                ]);
            } else {
                Log::warning('[ZaloMultiBranch] Broadcast returned non-success', [
                    'account_id' => $accountId,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloMultiBranch] Broadcast failed', [
                'account_id' => $accountId,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create or find conversations for all accounts with the same zalo_id
     *
     * Used when receiving a new message without account_id (only zalo_id)
     *
     * @param string $zaloId
     * @param string $recipientId
     * @param string $recipientType
     * @param string|null $recipientName
     * @param string|null $recipientAvatarUrl
     * @return Collection Collection of ZaloConversation models
     */
    public static function getOrCreateConversationsForZaloId(
        string $zaloId,
        string $recipientId,
        string $recipientType,
        ?string $recipientName = null,
        ?string $recipientAvatarUrl = null
    ): Collection {
        $accounts = self::getAccountsForZaloId($zaloId);
        $conversations = collect();

        foreach ($accounts as $account) {
            $conversation = ZaloConversation::firstOrCreate(
                [
                    'zalo_account_id' => $account->id,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                ],
                [
                    'recipient_name' => $recipientName,
                    'recipient_avatar_url' => $recipientAvatarUrl,
                    'branch_id' => null, // NULL = global/unassigned
                    'department_id' => null,
                ]
            );

            $conversations->push($conversation);
        }

        return $conversations;
    }
}
