<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;
use App\Models\ZaloConversation;

echo "🔍 Checking latest messages\n\n";

// Get messages from last 10 minutes
$messages = ZaloMessage::where('created_at', '>=', now()->subMinutes(10))
    ->orderBy('created_at', 'DESC')
    ->get();

echo "Messages in last 10 mins: {$messages->count()}\n\n";

foreach ($messages as $msg) {
    $conv = ZaloConversation::find($msg->zalo_conversation_id);
    echo "───────────────────────────────────────\n";
    echo "Time: {$msg->created_at}\n";
    echo "Sender: {$msg->sender_name}\n";
    echo "Content: {$msg->content}\n";
    echo "Conv ID: {$msg->zalo_conversation_id}\n";
    echo "Conv Name: " . ($conv ? $conv->recipient_name : 'NOT FOUND') . "\n";
    echo "Conv Recipient ID: " . ($conv ? $conv->recipient_id : 'N/A') . "\n";
    echo "\n";
}

// Check Test phần mềm specifically
$testConv = ZaloConversation::where('recipient_name', 'Test phần mềm')
    ->where('zalo_account_id', 23)
    ->whereNull('deleted_at')
    ->first();

if ($testConv) {
    echo "═══════════════════════════════════════\n";
    echo "TEST PHẦN MỀM CONVERSATION:\n";
    echo "Conv ID: {$testConv->id}\n";
    echo "Recipient ID: {$testConv->recipient_id}\n";
    
    $lastMessages = ZaloMessage::where('zalo_conversation_id', $testConv->id)
        ->orderBy('sent_at', 'DESC')
        ->limit(10)
        ->get();
    
    echo "\nLast 10 messages:\n";
    foreach ($lastMessages as $msg) {
        echo "  [{$msg->sent_at}] {$msg->sender_name}: {$msg->content}\n";
    }
}

// Check for conversations with recent activity
echo "\n═══════════════════════════════════════\n";
echo "ALL ACTIVE CONVERSATIONS (last 10 mins):\n\n";

$recentConvIds = ZaloMessage::where('created_at', '>=', now()->subMinutes(10))
    ->pluck('zalo_conversation_id')
    ->unique();

foreach ($recentConvIds as $convId) {
    $conv = ZaloConversation::find($convId);
    if ($conv) {
        $msgCount = ZaloMessage::where('zalo_conversation_id', $convId)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();
        echo "  Conv {$convId}: {$conv->recipient_name} ({$msgCount} new messages)\n";
    }
}

