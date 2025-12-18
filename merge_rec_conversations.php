<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;

echo "🔧 Merging R.E.C. TN PRE STARTER conversations...\n\n";

// Keep conversation 97 (has correct group ID: 5449444974793656832)
$keepConvId = 97;
$mergeConvIds = [105, 106]; // Wrong recipient_ids

DB::beginTransaction();
try {
    $keepConv = ZaloConversation::find($keepConvId);
    if (!$keepConv) throw new Exception("Keep conversation not found!");
    
    echo "📌 Keeping Conv ID {$keepConv->id}: {$keepConv->recipient_name}\n";
    echo "   Recipient ID (CORRECT): {$keepConv->recipient_id}\n";
    echo "   Messages: " . $keepConv->messages()->count() . "\n\n";
    
    $totalMerged = 0;
    foreach ($mergeConvIds as $mergeId) {
        $mergeConv = ZaloConversation::find($mergeId);
        if (!$mergeConv) continue;
        
        $count = ZaloMessage::where('zalo_conversation_id', $mergeConv->id)->count();
        echo "🔀 Merging Conv ID {$mergeId}:\n";
        echo "   Recipient ID (WRONG): {$mergeConv->recipient_id}\n";
        echo "   Messages: {$count}\n";
        
        if ($count > 0) {
            ZaloMessage::where('zalo_conversation_id', $mergeConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
            $totalMerged += $count;
            echo "   ✅ Moved {$count} messages\n";
        }
        $mergeConv->delete();
        echo "   🗑️  Deleted\n\n";
    }
    
    $lastMessage = $keepConv->messages()->latest('sent_at')->first();
    if ($lastMessage) {
        $keepConv->update([
            'last_message_at' => $lastMessage->sent_at,
            'last_message_preview' => substr($lastMessage->content ?? '', 0, 100),
        ]);
    }
    $keepConv->update(['unread_count' => $keepConv->messages()->where('type', 'received')->count()]);
    
    DB::commit();
    echo "✅ Success! Total messages in Conv 97: " . $keepConv->messages()->count() . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

