<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;
use App\Models\ZaloConversation;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing orphaned messages\n\n";

DB::beginTransaction();
try {
    // Fix message 1404 - from Tuấn Lệ to Test phần mềm
    $msg1 = ZaloMessage::find(1404);
    if ($msg1) {
        echo "Fixing message 1404: {$msg1->content}\n";
        echo "  Wrong recipient_id: {$msg1->recipient_id}\n";
        
        $msg1->recipient_id = '1356578101035802739'; // Correct group ID
        $msg1->recipient_name = 'Test phần mềm';
        $msg1->zalo_conversation_id = 26; // Test phần mềm conversation
        $msg1->save();
        echo "  ✅ FIXED! Conv ID: 26\n\n";
    }
    
    // Fix message 1405 - from Mike Hoàng Anh to Test phần mềm  
    $msg2 = ZaloMessage::find(1405);
    if ($msg2) {
        echo "Fixing message 1405: {$msg2->content}\n";
        echo "  Wrong recipient_id: {$msg2->recipient_id}\n";
        
        $msg2->recipient_id = '1356578101035802739'; // Correct group ID
        $msg2->recipient_name = 'Test phần mềm';
        $msg2->zalo_conversation_id = 26; // Test phần mềm conversation
        $msg2->save();
        echo "  ✅ FIXED! Conv ID: 26\n\n";
    }
    
    // Update conversation last_message
    $conv = ZaloConversation::find(26);
    if ($conv) {
        $lastMsg = $conv->messages()->latest('sent_at')->first();
        $conv->update([
            'last_message_at' => $lastMsg->sent_at,
            'last_message_preview' => substr($lastMsg->content ?? '', 0, 100),
            'unread_count' => $conv->messages()->where('type', 'received')->count(),
        ]);
        echo "✅ Updated conversation metadata\n";
    }
    
    DB::commit();
    echo "\n✅ All orphaned messages fixed!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

