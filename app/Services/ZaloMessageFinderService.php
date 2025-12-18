<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\Log;

class ZaloMessageFinderService
{
    /**
     * Find message by multiple strategies, prioritizing most accurate
     * 
     * @param ZaloAccount $account
     * @param string|null $messageId
     * @param string|null $cliMsgId
     * @param string|null $recipientId
     * @param string|null $messageType Optional: 'sent' or 'received' to filter, null to search both
     * @return ZaloMessage|null
     */
    public function findMessage(
        ZaloAccount $account,
        ?string $messageId = null,
        ?string $cliMsgId = null,
        ?string $recipientId = null,
        ?string $messageType = null
    ): ?ZaloMessage {
        Log::info('[MessageFinder] Starting message search', [
            'account_id' => $account->id,
            'account_zalo_id' => $account->zalo_id,
            'message_id' => $messageId,
            'cli_msg_id' => $cliMsgId,
            'recipient_id' => $recipientId,
            'message_type_filter' => $messageType ?? 'both',
        ]);

        // Strategy 1: Find by both messageId and cliMsgId (most accurate)
        // Search in both sent and received messages unless type is specified
        if ($messageId && $cliMsgId) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('message_id', $messageId)
                ->whereJsonContains('metadata->cliMsgId', $cliMsgId);
            
            if ($messageType) {
                $query->where('type', $messageType);
            }
            
            $message = $query->first();
            
            if ($message) {
                Log::info('[MessageFinder] ✅ Found by both IDs (most accurate)', [
                    'db_id' => $message->id,
                    'message_id' => $message->message_id,
                    'cliMsgId' => $message->metadata['cliMsgId'] ?? null,
                    'recipient_id' => $message->recipient_id,
                ]);
                return $message;
            } else {
                Log::debug('[MessageFinder] ❌ Not found by both IDs', [
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                ]);
            }
        }
        
        // Strategy 2: Find by messageId (account-wide)
        // Also check if messageId exists in metadata (msgId, globalMsgId, realMsgId)
        // Search in both sent and received messages
        if ($messageId) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->where(function($q) use ($messageId) {
                    $q->where('message_id', $messageId)
                      ->orWhereJsonContains('metadata->msgId', $messageId)
                      ->orWhereJsonContains('metadata->globalMsgId', $messageId)
                      ->orWhereJsonContains('metadata->realMsgId', $messageId);
                });
            
            if ($messageType) {
                $query->where('type', $messageType);
            }
            
            $message = $query->first();
            
            if ($message) {
                Log::info('[MessageFinder] ✅ Found by messageId (account-wide, including metadata)', [
                    'db_id' => $message->id,
                    'message_id' => $message->message_id,
                    'metadata_msgId' => $message->metadata['msgId'] ?? null,
                    'metadata_globalMsgId' => $message->metadata['globalMsgId'] ?? null,
                    'recipient_id' => $message->recipient_id,
                ]);
                return $message;
            } else {
                Log::debug('[MessageFinder] ❌ Not found by messageId (including metadata)', [
                    'message_id' => $messageId,
                ]);
            }
        }
        
        // Strategy 3: Find by cliMsgId (account-wide)
        // Search in both sent and received messages
        if ($cliMsgId) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->whereJsonContains('metadata->cliMsgId', $cliMsgId);
            
            if ($messageType) {
                $query->where('type', $messageType);
            }
            
            $message = $query->first();
            
            if ($message) {
                Log::info('[MessageFinder] ✅ Found by cliMsgId (account-wide)', [
                    'db_id' => $message->id,
                    'message_id' => $message->message_id,
                    'cliMsgId' => $message->metadata['cliMsgId'] ?? null,
                    'recipient_id' => $message->recipient_id,
                ]);
                return $message;
            } else {
                Log::debug('[MessageFinder] ❌ Not found by cliMsgId', [
                    'cli_msg_id' => $cliMsgId,
                ]);
            }
        }
        
        // Strategy 4: Find in conversation (if recipientId provided)
        if ($recipientId && ($messageId || $cliMsgId)) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('recipient_id', $recipientId);
            
            if ($messageId) {
                $query->where('message_id', $messageId);
            } elseif ($cliMsgId) {
                $query->whereJsonContains('metadata->cliMsgId', $cliMsgId);
            }
            
            $message = $query->orderBy('sent_at', 'desc')->first();
            
            if ($message) {
                Log::info('[MessageFinder] ✅ Found in conversation', [
                    'db_id' => $message->id,
                    'message_id' => $message->message_id,
                    'recipient_id' => $message->recipient_id,
                ]);
                return $message;
            } else {
                Log::debug('[MessageFinder] ❌ Not found in conversation', [
                    'recipient_id' => $recipientId,
                    'message_id' => $messageId,
                    'cli_msg_id' => $cliMsgId,
                ]);
            }
        }
        
        // Strategy 5: Try cliMsgId as message_id (fallback - for cases where message was saved with cliMsgId as message_id)
        // Search in both sent and received messages
        if ($cliMsgId) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('message_id', $cliMsgId);
            
            if ($messageType) {
                $query->where('type', $messageType);
            }
            
            $message = $query->first();
            
            if ($message) {
                Log::info('[MessageFinder] ✅ Found by cliMsgId as message_id (fallback)', [
                    'db_id' => $message->id,
                    'message_id' => $message->message_id,
                    'cliMsgId_in_metadata' => $message->metadata['cliMsgId'] ?? null,
                ]);
                return $message;
            }
        }
        
        // Strategy 6: Search in conversation with flexible matching (both messageId and cliMsgId)
        // This handles cases where message might be in conversation but IDs don't match exactly
        // Search in both sent and received messages in the conversation
        if ($recipientId && ($messageId || $cliMsgId)) {
            $query = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('recipient_id', $recipientId);
            
            if ($messageType) {
                $query->where('type', $messageType);
            }
            
            $conversationMessages = $query
                ->orderBy('sent_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(200) // Check recent 200 messages in conversation
                ->get();
            
            Log::debug('[MessageFinder] Checking conversation messages', [
                'total_messages' => $conversationMessages->count(),
                'recipient_id' => $recipientId,
            ]);
            
            foreach ($conversationMessages as $msg) {
                $msgMessageId = $msg->message_id ?? null;
                $msgCliMsgId = $msg->metadata['cliMsgId'] ?? null;
                $msgGlobalMsgId = $msg->metadata['globalMsgId'] ?? null;
                $msgRealMsgId = $msg->metadata['realMsgId'] ?? null;
                $msgMsgId = $msg->metadata['msgId'] ?? null;
                
                // Match by messageId (exact or in metadata)
                if ($messageId && (
                    $msgMessageId == $messageId ||
                    strval($msgMessageId) == strval($messageId) ||
                    $msgCliMsgId == $messageId ||
                    $msgGlobalMsgId == $messageId ||
                    $msgRealMsgId == $messageId ||
                    $msgMsgId == $messageId
                )) {
                    Log::info('[MessageFinder] ✅ Found in conversation (by messageId flexible match)', [
                        'db_id' => $msg->id,
                        'message_id' => $msg->message_id,
                        'matched_field' => 'messageId',
                    ]);
                    return $msg;
                }
                
                // Match by cliMsgId (exact or as message_id)
                if ($cliMsgId && (
                    $msgCliMsgId == $cliMsgId ||
                    strval($msgCliMsgId) == strval($cliMsgId) ||
                    $msgMessageId == $cliMsgId ||
                    strval($msgMessageId) == strval($cliMsgId)
                )) {
                    Log::info('[MessageFinder] ✅ Found in conversation (by cliMsgId flexible match)', [
                        'db_id' => $msg->id,
                        'message_id' => $msg->message_id,
                        'cliMsgId' => $msgCliMsgId,
                        'matched_field' => 'cliMsgId',
                    ]);
                    return $msg;
                }
            }
        }
        
        // Strategy 7: Last resort - find by time proximity (within last 10 minutes)
        // This handles cases where message IDs don't match but we know it's the same conversation
        // and the message was sent/received recently
        if ($recipientId) {
            $recentMessages = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('recipient_id', $recipientId)
                ->where('sent_at', '>=', now()->subMinutes(10)) // Within last 10 minutes
                ->orderBy('sent_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            
            Log::debug('[MessageFinder] Strategy 7: Checking recent messages by time proximity', [
                'total_recent' => $recentMessages->count(),
                'recipient_id' => $recipientId,
                'time_window' => 'last 10 minutes',
            ]);
            
            // Try to match by cliMsgId first (more reliable for reactions)
            if ($cliMsgId) {
                foreach ($recentMessages as $msg) {
                    $msgCliMsgId = $msg->metadata['cliMsgId'] ?? null;
                    $msgMessageId = $msg->message_id ?? null;
                    
                    // Match by cliMsgId in metadata or as message_id
                    if (($msgCliMsgId && ($msgCliMsgId == $cliMsgId || strval($msgCliMsgId) == strval($cliMsgId))) ||
                        ($msgMessageId && ($msgMessageId == $cliMsgId || strval($msgMessageId) == strval($cliMsgId)))) {
                        Log::info('[MessageFinder] ✅ Found in recent messages by cliMsgId (time proximity)', [
                            'db_id' => $msg->id,
                            'message_id' => $msg->message_id,
                            'matched_cliMsgId' => $msgCliMsgId,
                            'matched_message_id' => $msgMessageId,
                        ]);
                        return $msg;
                    }
                }
            }
            
            // Try to match by messageId in metadata or message_id column
            if ($messageId) {
                foreach ($recentMessages as $msg) {
                    $msgMsgId = $msg->metadata['msgId'] ?? null;
                    $msgGlobalMsgId = $msg->metadata['globalMsgId'] ?? null;
                    $msgRealMsgId = $msg->metadata['realMsgId'] ?? null;
                    $msgMessageId = $msg->message_id ?? null;
                    
                    // Match by messageId in metadata or message_id column
                    if (($msgMsgId && ($msgMsgId == $messageId || strval($msgMsgId) == strval($messageId))) ||
                        ($msgGlobalMsgId && ($msgGlobalMsgId == $messageId || strval($msgGlobalMsgId) == strval($messageId))) ||
                        ($msgRealMsgId && ($msgRealMsgId == $messageId || strval($msgRealMsgId) == strval($messageId))) ||
                        ($msgMessageId && ($msgMessageId == $messageId || strval($msgMessageId) == strval($messageId)))) {
                        Log::info('[MessageFinder] ✅ Found in recent messages by messageId (time proximity)', [
                            'db_id' => $msg->id,
                            'message_id' => $msg->message_id,
                            'matched_field' => 'metadata or message_id',
                        ]);
                        return $msg;
                    }
                }
            }
            
            // If still not found and we have both IDs, log all recent messages for debugging
            if ($messageId && $cliMsgId) {
                Log::warning('[MessageFinder] Message not found in recent messages, logging all recent for debugging', [
                    'searching_for' => [
                        'message_id' => $messageId,
                        'cli_msg_id' => $cliMsgId,
                    ],
                    'recent_messages' => $recentMessages->map(function($m) {
                        return [
                            'id' => $m->id,
                            'message_id' => $m->message_id,
                            'metadata' => $m->metadata,
                            'sent_at' => $m->sent_at,
                            'type' => $m->type,
                        ];
                    })->toArray(),
                ]);
            }
        }
        
        Log::warning('[MessageFinder] ❌ Message not found after all strategies', [
            'account_id' => $account->id,
            'message_id' => $messageId,
            'cli_msg_id' => $cliMsgId,
            'recipient_id' => $recipientId,
        ]);
        
        return null;
    }

    /**
     * Find message with detailed logging for debugging
     */
    public function findMessageWithDebug(
        ZaloAccount $account,
        ?string $messageId = null,
        ?string $cliMsgId = null,
        ?string $recipientId = null
    ): array {
        $result = [
            'found' => false,
            'message' => null,
            'strategies_tried' => [],
            'sample_messages' => [],
        ];

        // Try each strategy and log results
        if ($messageId && $cliMsgId) {
            $message = ZaloMessage::where('zalo_account_id', $account->id)
                ->where('message_id', $messageId)
                ->whereJsonContains('metadata->cliMsgId', $cliMsgId)
                ->first();
            
            $result['strategies_tried'][] = [
                'strategy' => 'both_ids',
                'success' => $message !== null,
            ];
            
            if ($message) {
                $result['found'] = true;
                $result['message'] = $message;
                return $result;
            }
        }

        // Get sample messages for debugging
        $result['sample_messages'] = ZaloMessage::where('zalo_account_id', $account->id)
            ->when($recipientId, function($q) use ($recipientId) {
                $q->where('recipient_id', $recipientId);
            })
            ->orderBy('sent_at', 'desc')
            ->limit(10)
            ->get(['id', 'message_id', 'metadata', 'recipient_id', 'sent_at'])
            ->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'message_id' => $msg->message_id,
                    'cliMsgId' => $msg->metadata['cliMsgId'] ?? null,
                    'recipient_id' => $msg->recipient_id,
                    'sent_at' => $msg->sent_at?->toISOString(),
                ];
            })
            ->toArray();

        // Try remaining strategies
        $message = $this->findMessage($account, $messageId, $cliMsgId, $recipientId);
        
        if ($message) {
            $result['found'] = true;
            $result['message'] = $message;
        }

        return $result;
    }
}

