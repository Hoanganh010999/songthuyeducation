<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZaloMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zalo_account_id',
        'zalo_conversation_id', // Link to conversation
        'message_id',
        'type',
        'recipient_type',
        'recipient_id',
        'recipient_name',
        'sender_id',
        'sender_name',
        'sent_by_user_id', // App user who sent this message
        'content',
        'content_type',
        'media_url',
        'media_path',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'metadata',
        'reply_to_message_id',
        'reply_to_zalo_message_id',
        'reply_to_cli_msg_id',
        'quote_data',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'quote_data' => 'array',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    public function conversation()
    {
        return $this->belongsTo(ZaloConversation::class, 'zalo_conversation_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(ZaloMessage::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(ZaloMessage::class, 'reply_to_message_id');
    }

    public function reactions()
    {
        return $this->hasMany(ZaloMessageReaction::class);
    }

    public function sentByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by_user_id');
    }

    // Scopes
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    public function scopeForRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    public function scopeSent($query)
    {
        return $query->where('type', 'sent');
    }

    public function scopeReceived($query)
    {
        return $query->where('type', 'received');
    }

    /**
     * Find message by Zalo IDs (multiple strategies)
     * 
     * @param int $accountId
     * @param string|null $messageId
     * @param string|null $cliMsgId
     * @param string|null $recipientId
     * @return ZaloMessage|null
     */
    public static function findByZaloIds(
        int $accountId,
        ?string $messageId = null,
        ?string $cliMsgId = null,
        ?string $recipientId = null
    ): ?self {
        // Strategy 1: Find by both messageId and cliMsgId (most accurate)
        if ($messageId && $cliMsgId) {
            $message = static::where('zalo_account_id', $accountId)
                ->where('message_id', $messageId)
                ->whereJsonContains('metadata->cliMsgId', $cliMsgId)
                ->first();
            
            if ($message) {
                return $message;
            }
        }
        
        // Strategy 2: Find by messageId (account-wide)
        if ($messageId) {
            $message = static::where('zalo_account_id', $accountId)
                ->where('message_id', $messageId)
                ->first();
            
            if ($message) {
                return $message;
            }
        }
        
        // Strategy 3: Find by cliMsgId (account-wide)
        if ($cliMsgId) {
            $message = static::where('zalo_account_id', $accountId)
                ->whereJsonContains('metadata->cliMsgId', $cliMsgId)
                ->first();
            
            if ($message) {
                return $message;
            }
        }
        
        // Strategy 4: Find in conversation (if recipientId provided)
        if ($recipientId && ($messageId || $cliMsgId)) {
            $query = static::where('zalo_account_id', $accountId)
                ->where('recipient_id', $recipientId);
            
            if ($messageId) {
                $query->where('message_id', $messageId);
            } elseif ($cliMsgId) {
                $query->whereJsonContains('metadata->cliMsgId', $cliMsgId);
            }
            
            $message = $query->orderBy('sent_at', 'desc')->first();
            
            if ($message) {
                return $message;
            }
        }
        
        return null;
    }

    /**
     * Get composite key for this message
     * Format: {account_id}:{message_id}:{cliMsgId}
     */
    public function getCompositeKeyAttribute(): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->zalo_account_id,
            $this->message_id ?? '',
            $this->metadata['cliMsgId'] ?? ''
        );
    }

    /**
     * Get all Zalo message IDs (msgId, cliMsgId, globalMsgId, realMsgId)
     */
    public function getAllZaloIds(): array
    {
        $metadata = $this->metadata ?? [];
        
        return [
            'message_id' => $this->message_id,
            'cliMsgId' => $metadata['cliMsgId'] ?? null,
            'globalMsgId' => $metadata['globalMsgId'] ?? $this->message_id,
            'realMsgId' => $metadata['realMsgId'] ?? $this->message_id,
            'msgId' => $metadata['msgId'] ?? $this->message_id,
        ];
    }
}
