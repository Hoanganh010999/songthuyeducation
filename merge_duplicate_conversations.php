<?php

/**
 * Script to merge duplicate Zalo conversations
 * 
 * This script:
 * 1. Finds duplicate conversations (same account + recipient + type)
 * 2. Keeps the oldest conversation
 * 3. Moves all messages to the oldest conversation
 * 4. Deletes duplicate conversations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🔍 Searching for duplicate conversations...\n\n";

// Find duplicates by grouping on (account_id, recipient_id, recipient_type)
$duplicates = DB::select("
    SELECT zalo_account_id, recipient_id, recipient_type, COUNT(*) as count
    FROM zalo_conversations
    WHERE deleted_at IS NULL
    GROUP BY zalo_account_id, recipient_id, recipient_type
    HAVING COUNT(*) > 1
");

if (empty($duplicates)) {
    echo "✅ No duplicate conversations found!\n";
    exit(0);
}

echo "⚠️  Found " . count($duplicates) . " sets of duplicate conversations:\n\n";

foreach ($duplicates as $dup) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Account ID: {$dup->zalo_account_id}\n";
    echo "Recipient ID: {$dup->recipient_id}\n";
    echo "Type: {$dup->recipient_type}\n";
    echo "Duplicate count: {$dup->count}\n\n";
    
    // Get all conversations for this group
    $conversations = ZaloConversation::where('zalo_account_id', $dup->zalo_account_id)
        ->where('recipient_id', $dup->recipient_id)
        ->where('recipient_type', $dup->recipient_type)
        ->whereNull('deleted_at')
        ->orderBy('id', 'asc') // Keep the oldest one
        ->get();
    
    if ($conversations->count() <= 1) {
        echo "⚠️  Skipping (only 1 conversation found)\n\n";
        continue;
    }
    
    $keepConversation = $conversations->first(); // Keep the oldest
    $deleteConversations = $conversations->skip(1); // Delete the rest
    
    echo "📌 Keeping conversation ID: {$keepConversation->id}\n";
    echo "   Name: {$keepConversation->recipient_name}\n";
    echo "   Created: {$keepConversation->created_at}\n";
    echo "   Messages: " . $keepConversation->messages()->count() . "\n\n";
    
    echo "🗑️  Will delete " . $deleteConversations->count() . " duplicate(s):\n";
    
    DB::beginTransaction();
    try {
        $totalMessagesMoved = 0;
        
        foreach ($deleteConversations as $dupConv) {
            echo "   - ID {$dupConv->id}: {$dupConv->recipient_name} (Created: {$dupConv->created_at})\n";
            
            // Move all messages from duplicate to keep conversation
            $messageCount = ZaloMessage::where('zalo_conversation_id', $dupConv->id)->count();
            
            if ($messageCount > 0) {
                ZaloMessage::where('zalo_conversation_id', $dupConv->id)
                    ->update(['zalo_conversation_id' => $keepConversation->id]);
                
                echo "     → Moved {$messageCount} messages\n";
                $totalMessagesMoved += $messageCount;
            }
            
            // Soft delete the duplicate conversation
            $dupConv->delete();
            echo "     → Deleted conversation\n";
        }
        
        // Update the keep conversation's last_message_at and unread_count
        $lastMessage = $keepConversation->messages()->latest('sent_at')->first();
        if ($lastMessage) {
            $keepConversation->update([
                'last_message_at' => $lastMessage->sent_at,
                'last_message_preview' => $lastMessage->content ? substr($lastMessage->content, 0, 100) : null,
            ]);
        }
        
        // Recalculate unread count
        $unreadCount = $keepConversation->messages()->where('type', 'received')->count();
        $keepConversation->update(['unread_count' => $unreadCount]);
        
        DB::commit();
        
        echo "\n✅ Successfully merged! Total messages moved: {$totalMessagesMoved}\n";
        echo "   Updated conversation ID {$keepConversation->id} with {$keepConversation->messages()->count()} total messages\n\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "\n❌ Error merging conversations: " . $e->getMessage() . "\n\n";
        Log::error('[MergeConversations] Error', [
            'error' => $e->getMessage(),
            'account_id' => $dup->zalo_account_id,
            'recipient_id' => $dup->recipient_id,
        ]);
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 Merge process completed!\n";

