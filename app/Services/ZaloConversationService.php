<?php

namespace App\Services;

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZaloConversationService
{
    /**
     * Update conversation with recipient information from database
     * (Friend or Group must already exist in database)
     */
    protected function updateRecipientInfo(ZaloConversation $conversation): void
    {
        $accountId = $conversation->zalo_account_id;
        $recipientId = $conversation->recipient_id;
        $recipientType = $conversation->recipient_type;

        if ($recipientType === 'user') {
            // Find existing friend in database using zalo_user_id
            $friend = \App\Models\ZaloFriend::where('zalo_account_id', $accountId)
                ->where('zalo_user_id', $recipientId)
                ->first();

            if ($friend) {
                $conversation->update([
                    'recipient_name' => $friend->name ?? $friend->display_name,
                    'recipient_avatar_url' => $friend->avatar_url ?? $friend->avatar,
                ]);

                Log::info('[ZaloConversation] Updated friend info', [
                    'conversation_id' => $conversation->id,
                    'friend_id' => $recipientId,
                    'name' => $friend->name ?? $friend->display_name,
                ]);
            }
        } else {
            // Find existing group in database using zalo_group_id
            $group = \App\Models\ZaloGroup::where('zalo_account_id', $accountId)
                ->where('zalo_group_id', $recipientId)
                ->first();

            if ($group) {
                $conversation->update([
                    'recipient_name' => $group->name,
                    'recipient_avatar_url' => $group->avatar_url ?? $group->avatar,
                ]);

                Log::info('[ZaloConversation] Updated group info', [
                    'conversation_id' => $conversation->id,
                    'group_id' => $recipientId,
                    'name' => $group->name,
                ]);
            }
        }
    }

    /**
     * Get or create conversation
     */
    public function getOrCreateConversation(
        int $accountId,
        string $recipientId,
        string $recipientType = 'user',
        ?User $creator = null
    ): ZaloConversation {
        // Get account's branch_id
        $account = \App\Models\ZaloAccount::find($accountId);
        $branchId = $account ? $account->branch_id : null;

        // Normalize recipient_id to string to avoid BigInt/type mismatch issues
        $recipientId = (string) $recipientId;

        // First try to find existing conversation
        $conversation = ZaloConversation::where('zalo_account_id', $accountId)
            ->where('recipient_id', $recipientId)
            ->where('recipient_type', $recipientType)
            ->first();

        if (!$conversation) {
            // Try to get recipient name from database before creating conversation
            $recipientName = null;
            if ($recipientType === 'user') {
                $friend = \App\Models\ZaloFriend::where('zalo_account_id', $accountId)
                    ->where('zalo_user_id', $recipientId)
                    ->first();
                $recipientName = $friend ? ($friend->name ?? $friend->display_name ?? 'Unknown') : null;
            } else {
                $group = \App\Models\ZaloGroup::where('zalo_account_id', $accountId)
                    ->where('zalo_group_id', $recipientId)
                    ->first();
                $recipientName = $group ? $group->name : null;
            }
            
            try {
                $conversation = ZaloConversation::create([
                    'zalo_account_id' => $accountId,
                    'recipient_id' => $recipientId,
                    'recipient_type' => $recipientType,
                    'recipient_name' => $recipientName ?? 'Unknown', // Set recipient_name when creating
                    'created_by' => $creator?->id,
                    'branch_id' => $branchId,
                    'last_message_at' => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle race condition - fetch existing if duplicate key error
                if ($e->errorInfo[1] == 1062) {
                    $conversation = ZaloConversation::where('zalo_account_id', $accountId)
                        ->where('recipient_id', $recipientId)
                        ->where('recipient_type', $recipientType)
                        ->first();

                    if (!$conversation) {
                        throw $e; // Re-throw if still not found
                    }
                } else {
                    throw $e;
                }
            }
        }

        // Auto-assign creator
        if ($creator && !$conversation->assignedUsers()->where('user_id', $creator->id)->exists()) {
            $this->assignUser($conversation, $creator, $creator);
        }

        // Update recipient info (name and avatar) from friend/group
        $this->updateRecipientInfo($conversation);

        return $conversation;
    }

    /**
     * Assign user to conversation
     */
    public function assignUser(
        ZaloConversation $conversation,
        User $user,
        ?User $assignedBy = null,
        bool $canView = true,
        bool $canReply = true,
        ?string $note = null
    ): void {
        $conversation->assignedUsers()->syncWithoutDetaching([
            $user->id => [
                'can_view' => $canView,
                'can_reply' => $canReply,
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
                'assignment_note' => $note,
            ]
        ]);

        Log::info('[ZaloConversation] User assigned', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'assigned_by' => $assignedBy?->id,
        ]);
    }

    /**
     * Assign department to conversation
     */
    public function assignDepartment(
        ZaloConversation $conversation,
        int $departmentId,
        ?User $assignedBy = null
    ): void {
        $conversation->update([
            'department_id' => $departmentId,
        ]);

        Log::info('[ZaloConversation] Assigned to department', [
            'conversation_id' => $conversation->id,
            'department_id' => $departmentId,
            'assigned_by' => $assignedBy?->id,
        ]);
    }

    /**
     * Handle new message - update conversation
     */
    public function handleNewMessage(
        ZaloMessage $message,
        ?User $creator = null,
        ?array $recipientInfo = null
    ): ZaloConversation {
        $conversation = $this->getOrCreateConversation(
            $message->zalo_account_id,
            $message->recipient_id,
            $message->recipient_type,
            $creator
        );

        // If recipient info is provided, save/update friend or group
        if ($recipientInfo) {
            $this->saveRecipientInfo(
                $message->zalo_account_id,
                $message->recipient_id,
                $message->recipient_type,
                $recipientInfo
            );

            // Update conversation with the new info
            $this->updateRecipientInfo($conversation);
        } else {
            // Even if recipientInfo is not provided, try to update from database
            // This ensures conversation has correct name even if recipientInfo was missing
            $this->updateRecipientInfo($conversation);
        }

        // Link message to conversation
        $message->update(['zalo_conversation_id' => $conversation->id]);

        // Update conversation metadata
        $conversation->updateLastMessage($message);

        // Increment unread if it's a received message
        if ($message->type === 'received') {
            $conversation->incrementUnread();
        }

        return $conversation;
    }

    /**
     * Save or update recipient (friend or group) info
     */
    protected function saveRecipientInfo(
        int $accountId,
        string $recipientId,
        string $recipientType,
        array $recipientInfo
    ): void {
        // Normalize recipient_id to string
        $recipientId = (string) $recipientId;

        if ($recipientType === 'user') {
            try {
                // First try to find existing friend
                $friend = \App\Models\ZaloFriend::where('zalo_account_id', $accountId)
                    ->where('zalo_user_id', $recipientId)
                    ->first();

                $data = [
                    'name' => $recipientInfo['name'] ?? $recipientInfo['displayName'] ?? 'Unknown',
                    'display_name' => $recipientInfo['displayName'] ?? $recipientInfo['name'] ?? null,
                    'avatar_url' => $recipientInfo['avatar_url'] ?? $recipientInfo['avatar'] ?? null,
                    'avatar' => $recipientInfo['avatar'] ?? $recipientInfo['avatar_url'] ?? null,
                ];

                if ($friend) {
                    $friend->update($data);
                } else {
                    \App\Models\ZaloFriend::create(array_merge($data, [
                        'zalo_account_id' => $accountId,
                        'zalo_user_id' => $recipientId,
                    ]));
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // Silently ignore duplicate key errors
                if ($e->errorInfo[1] != 1062) {
                    throw $e;
                }
            }
        } else {
            try {
                // First try to find existing group
                $group = \App\Models\ZaloGroup::where('zalo_account_id', $accountId)
                    ->where('zalo_group_id', $recipientId)
                    ->first();

                $data = [
                    'name' => $recipientInfo['name'] ?? null,
                    'avatar_url' => $recipientInfo['avatar_url'] ?? $recipientInfo['avatar'] ?? null,
                    'avatar' => $recipientInfo['avatar'] ?? $recipientInfo['avatar_url'] ?? null,
                ];

                if ($group) {
                    $group->update($data);
                } else {
                    \App\Models\ZaloGroup::create(array_merge($data, [
                        'zalo_account_id' => $accountId,
                        'zalo_group_id' => $recipientId,
                    ]));
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // Silently ignore duplicate key errors
                if ($e->errorInfo[1] != 1062) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Migrate existing messages to conversations
     */
    public function migrateExistingMessages(): array
    {
        $migrated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            // Group messages by account + recipient
            $messageGroups = ZaloMessage::select('zalo_account_id', 'recipient_id', 'recipient_type')
                ->whereNull('zalo_conversation_id')
                ->groupBy('zalo_account_id', 'recipient_id', 'recipient_type')
                ->get();

            Log::info('[ZaloConversation] Starting migration', [
                'total_groups' => $messageGroups->count(),
            ]);

            foreach ($messageGroups as $group) {
                try {
                    // Get account's branch_id
                    $account = \App\Models\ZaloAccount::find($group->zalo_account_id);
                    $branchId = $account ? $account->branch_id : null;

                    // Create conversation
                    $conversation = ZaloConversation::create([
                        'zalo_account_id' => $group->zalo_account_id,
                        'branch_id' => $branchId,
                        'recipient_id' => $group->recipient_id,
                        'recipient_type' => $group->recipient_type,
                        'last_message_at' => now(),
                    ]);

                    // Link messages
                    ZaloMessage::where('zalo_account_id', $group->zalo_account_id)
                        ->where('recipient_id', $group->recipient_id)
                        ->where('recipient_type', $group->recipient_type)
                        ->update(['zalo_conversation_id' => $conversation->id]);

                    // Update conversation metadata from last message
                    $lastMessage = $conversation->messages()->latest('sent_at')->first();
                    if ($lastMessage) {
                        $conversation->updateLastMessage($lastMessage);

                        // Set recipient name from message
                        if (!$conversation->recipient_name && $lastMessage->recipient_name) {
                            $conversation->update(['recipient_name' => $lastMessage->recipient_name]);
                        }
                    }

                    // Count unread messages
                    $unreadCount = $conversation->messages()->where('type', 'received')->count();
                    $conversation->update(['unread_count' => $unreadCount]);

                    $migrated++;
                } catch (\Exception $e) {
                    Log::error('[ZaloConversation] Migration error', [
                        'group' => $group,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }

            DB::commit();

            Log::info('[ZaloConversation] Migration completed', [
                'migrated' => $migrated,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ZaloConversation] Migration failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }
}
