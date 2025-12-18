<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;
use App\Models\ZaloConversation;

echo "🔍 Debugging message display issue\n\n";

// Get recent messages from last 30 minutes
$recentMessages = ZaloMessage::where('created_at', '>=', now()->subMinutes(30))
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

echo "RECENT MESSAGES (last 30 mins): {$recentMessages->count()}\n\n";

foreach ($recentMessages as $msg) {
    $conv = ZaloConversation::find($msg->zalo_conversation_id);
    echo "─────────────────────────────────────────\n";
    echo "Message ID: {$msg->id}\n";
    echo "Conversation ID: {$msg->zalo_conversation_id}\n";
    echo "Conversation Name: " . ($conv ? $conv->recipient_name : 'NOT FOUND') . "\n";
    echo "Type: {$msg->type}\n";
    echo "Content: " . substr($msg->content ?? '', 0, 50) . "\n";
    echo "Sender: {$msg->sender_name}\n";
    echo "Created: {$msg->created_at}\n";
    echo "\n";
}

// Check Test phần mềm conversation
$testConv = ZaloConversation::where('recipient_name', 'Test phần mềm')
    ->where('zalo_account_id', 23)
    ->whereNull('deleted_at')
    ->first();

if ($testConv) {
    echo "═══════════════════════════════════════════\n";
    echo "TEST PHẦN MỀM CONVERSATION:\n";
    echo "ID: {$testConv->id}\n";
    echo "Recipient ID: {$testConv->recipient_id}\n";
    
    $messages = ZaloMessage::where('zalo_conversation_id', $testConv->id)
        ->orderBy('sent_at', 'DESC')
        ->limit(5)
        ->get();
    
    echo "Total Messages: " . $testConv->messages()->count() . "\n";
    echo "Last 5 messages:\n\n";
    
    foreach ($messages as $msg) {
        echo "  - [{$msg->sent_at}] {$msg->sender_name}: " . substr($msg->content ?? '', 0, 40) . "\n";
    }
}

