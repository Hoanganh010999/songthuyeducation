<?php

/**
 * Merge duplicate IELTS - K5.TN conversations
 * Keep conversation ID 79 (oldest with most messages: 42)
 * Merge conversations 101 and 104 into 79
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🔧 Merging IELTS - K5.TN conversations...\n\n";

// Keep this conversation (oldest with most messages)
$keepConvId = 79; // 5383303874483592725 - 42 messages

// Merge these into the keep conversation
$mergeConvIds = [101, 104]; // New conversations with wrong group IDs

DB::beginTransaction();
try {
    $keepConv = ZaloConversation::find($keepConvId);
    
    if (!$keepConv) {
        throw new Exception("Keep conversation ID {$keepConvId} not found!");
    }
    
    echo "📌 Keeping conversation:\n";
    echo "   ID: {$keepConv->id}\n";
    echo "   Name: {$keepConv->recipient_name}\n";
    echo "   Recipient ID: {$keepConv->recipient_id}\n";
    echo "   Messages: " . $keepConv->messages()->count() . "\n\n";
    
    $totalMerged = 0;
    
    foreach ($mergeConvIds as $mergeId) {
        $mergeConv = ZaloConversation::find($mergeId);
        
        if (!$mergeConv) {
            echo "⚠️  Conversation ID {$mergeId} not found, skipping...\n\n";
            continue;
        }
        
        echo "🔀 Merging conversation:\n";
        echo "   ID: {$mergeConv->id}\n";
        echo "   Name: {$mergeConv->recipient_name}\n";
        echo "   Recipient ID: {$mergeConv->recipient_id}\n";
        
        $messageCount = ZaloMessage::where('zalo_conversation_id', $mergeConv->id)->count();
        echo "   Messages: {$messageCount}\n";
        
        if ($messageCount > 0) {
            // Move messages
            ZaloMessage::where('zalo_conversation_id', $mergeConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
            
            echo "   ✅ Moved {$messageCount} messages\n";
            $totalMerged += $messageCount;
        }
        
        // Delete the duplicate conversation
        $mergeConv->delete();
        echo "   🗑️  Deleted conversation\n\n";
    }
    
    // Update keep conversation's metadata
    $lastMessage = $keepConv->messages()->latest('sent_at')->first();
    if ($lastMessage) {
        $keepConv->update([
            'last_message_at' => $lastMessage->sent_at,
            'last_message_preview' => $lastMessage->content ? substr($lastMessage->content, 0, 100) : null,
        ]);
    }
    
    $unreadCount = $keepConv->messages()->where('type', 'received')->count();
    $keepConv->update(['unread_count' => $unreadCount]);
    
    DB::commit();
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ Merge completed successfully!\n";
    echo "   Total messages merged: {$totalMerged}\n";
    echo "   Final message count: " . $keepConv->messages()->count() . "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    Log::info('[MergeK5Conversations] Successfully merged', [
        'keep_conv_id' => $keepConvId,
        'merged_conv_ids' => $mergeConvIds,
        'total_merged' => $totalMerged,
    ]);
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    Log::error('[MergeK5Conversations] Error', ['error' => $e->getMessage()]);
    exit(1);
}

