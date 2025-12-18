<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;

echo "🔧 Merging IELTS - K4.TN conversations...\n\n";

$keepConvId = 46;
$mergeConvIds = [102];

DB::beginTransaction();
try {
    $keepConv = ZaloConversation::find($keepConvId);
    if (!$keepConv) throw new Exception("Keep conversation not found!");
    
    echo "📌 Keeping ID {$keepConv->id}: {$keepConv->recipient_name} ({$keepConv->messages()->count()} messages)\n\n";
    
    $totalMerged = 0;
    foreach ($mergeConvIds as $mergeId) {
        $mergeConv = ZaloConversation::find($mergeId);
        if (!$mergeConv) continue;
        
        $count = ZaloMessage::where('zalo_conversation_id', $mergeConv->id)->count();
        if ($count > 0) {
            ZaloMessage::where('zalo_conversation_id', $mergeConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
            $totalMerged += $count;
        }
        $mergeConv->delete();
        echo "✅ Merged ID {$mergeId}: {$count} messages moved\n";
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
    echo "\n✅ Success! Total: " . $keepConv->messages()->count() . " messages\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

