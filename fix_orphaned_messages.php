<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;
use App\Models\ZaloConversation;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing orphaned messages (null conversation_id)\n\n";

// Find messages with null conversation_id
$orphaned = ZaloMessage::whereNull('zalo_conversation_id')
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->get();

echo "Found {$orphaned->count()} orphaned messages\n\n";

$fixed = 0;

foreach ($orphaned as $msg) {
    echo "Message ID {$msg->id}:\n";
    echo "  Content: " . substr($msg->content ?? '', 0, 40) . "\n";
    echo "  Sender: {$msg->sender_name}\n";
    echo "  Recipient ID (wrong): {$msg->recipient_id}\n";
    echo "  Recipient Type: {$msg->recipient_type}\n";
    
    // For group messages, recipient_id is wrong (it's sender ID)
    // Need to find correct conversation by looking at other messages from same time
    if ($msg->recipient_type === 'group') {
        // Try to find conversation by checking nearby messages
        $nearbyMessage = ZaloMessage::where('zalo_account_id', $msg->zalo_account_id)
            ->whereNotNull('zalo_conversation_id')
            ->where('sent_at', '>=', $msg->created_at->subMinutes(30))
            ->where('sent_at', '<=', $msg->created_at->addMinutes(30))
            ->orderBy(DB::raw('ABS(TIMESTAMPDIFF(SECOND, sent_at, ?))'), 'ASC')
            ->setBindings([$msg->created_at])
            ->first();
        
        if ($nearbyMessage) {
            $conv = ZaloConversation::find($nearbyMessage->zalo_conversation_id);
            if ($conv && $conv->recipient_type === 'group') {
                echo "  ✅ Found conversation via nearby message\n";
                echo "     Conv ID: {$conv->id} ({$conv->recipient_name})\n";
                echo "     Correct Recipient ID: {$conv->recipient_id}\n";
                
                $msg->zalo_conversation_id = $conv->id;
                $msg->recipient_id = $conv->recipient_id;
                $msg->recipient_name = $conv->recipient_name;
                $msg->save();
                $fixed++;
                echo "     ✅ FIXED!\n\n";
                continue;
            }
        }
    }
    
    echo "  ⚠️  Could not auto-fix\n\n";
}

echo "═══════════════════════════════════════\n";
echo "✅ Fixed {$fixed} messages\n";

