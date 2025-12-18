<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloGroup;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;

echo "🔧 Cleaning up 'Test phần mềm' duplicates...\n\n";

DB::beginTransaction();
try {
    // Keep group 753 (oldest, 4 members)
    $keepGroup = ZaloGroup::find(753);
    if (!$keepGroup) throw new Exception("Group 753 not found!");
    
    echo "📌 Keeping Group: {$keepGroup->zalo_group_id} ({$keepGroup->members_count} members)\n\n";
    
    // Delete group 816 (new, 0 members)
    $deleteGroup = ZaloGroup::find(816);
    if ($deleteGroup) {
        echo "🗑️  Deleting duplicate Group: {$deleteGroup->zalo_group_id}\n";
        $deleteGroup->delete();
    }
    
    // Now fix conversations
    echo "\n📋 Fixing conversations:\n\n";
    
    // Keep conv 26 (correct group ID, 47 messages)
    $keepConv = ZaloConversation::find(26);
    
    // Merge conv 107 into conv 26
    $mergeConv = ZaloConversation::find(107);
    if ($mergeConv) {
        $count = ZaloMessage::where('zalo_conversation_id', $mergeConv->id)->count();
        echo "🔀 Merging Conv 107 → Conv 26 ({$count} messages)\n";
        
        if ($count > 0) {
            ZaloMessage::where('zalo_conversation_id', $mergeConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
        }
        $mergeConv->delete();
    }
    
    // Delete wrong conversations
    $wrongConvs = [99, 21];
    foreach ($wrongConvs as $id) {
        $conv = ZaloConversation::withTrashed()->find($id);
        if ($conv) {
            echo "🗑️  Deleting wrong Conv {$id} (recipient_id: {$conv->recipient_id})\n";
            if (!$conv->deleted_at) $conv->delete();
        }
    }
    
    // Update keep conversation
    $lastMessage = $keepConv->messages()->latest('sent_at')->first();
    if ($lastMessage) {
        $keepConv->update([
            'last_message_at' => $lastMessage->sent_at,
            'last_message_preview' => substr($lastMessage->content ?? '', 0, 100),
        ]);
    }
    
    DB::commit();
    echo "\n✅ Cleanup complete!\n";
    echo "   Final: Conv 26 with " . $keepConv->messages()->count() . " messages\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

