<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloGroup;
use App\Models\ZaloMessage;

echo "🔍 Analyzing 'Test phần mềm' group...\n\n";

// Find all conversations with this name
$conversations = ZaloConversation::withTrashed()
    ->where('recipient_name', 'like', '%Test%phần mềm%')
    ->orWhere('recipient_name', 'like', '%Test phần mềm%')
    ->orderBy('created_at', 'DESC')
    ->get();

echo "Found {$conversations->count()} conversations:\n\n";

foreach ($conversations as $conv) {
    $status = $conv->deleted_at ? '🗑️' : '✅';
    $messageCount = ZaloMessage::where('zalo_conversation_id', $conv->id)->count();
    
    // Get last message info
    $lastMsg = ZaloMessage::where('zalo_conversation_id', $conv->id)
        ->orderBy('sent_at', 'DESC')
        ->first();
    
    echo "{$status} Conv ID: {$conv->id}\n";
    echo "   Name: {$conv->recipient_name}\n";
    echo "   Recipient ID: {$conv->recipient_id}\n";
    echo "   Account ID: {$conv->zalo_account_id}\n";
    echo "   Messages: {$messageCount}\n";
    echo "   Created: {$conv->created_at}\n";
    if ($lastMsg) {
        echo "   Last message: {$lastMsg->sent_at}\n";
        echo "   Last sender: {$lastMsg->sender_name}\n";
    }
    echo "\n";
}

// Find groups
echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 GROUPS in zalo_groups:\n\n";

$groups = ZaloGroup::where('name', 'like', '%Test%phần mềm%')
    ->orWhere('name', 'like', '%Test phần mềm%')
    ->get();

echo "Found {$groups->count()} groups:\n\n";

foreach ($groups as $group) {
    echo "Group ID (DB): {$group->id}\n";
    echo "Zalo Group ID: {$group->zalo_group_id}\n";
    echo "Name: {$group->name}\n";
    echo "Account ID: {$group->zalo_account_id}\n";
    echo "Members: {$group->members_count}\n";
    echo "Created: {$group->created_at}\n\n";
}

// Check if recipient_ids match
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 ANALYSIS:\n\n";

$recipientIds = $conversations->pluck('recipient_id')->unique();
echo "Unique recipient_ids from conversations: " . $recipientIds->count() . "\n";
foreach ($recipientIds as $id) {
    $group = ZaloGroup::where('zalo_group_id', $id)->first();
    if ($group) {
        echo "   ✅ {$id} → Found in zalo_groups: {$group->name}\n";
    } else {
        echo "   ❌ {$id} → NOT in zalo_groups (WRONG ID!)\n";
    }
}

echo "\n";

if ($groups->count() > 0) {
    $correctGroupId = $groups->first()->zalo_group_id;
    echo "✅ CORRECT GROUP ID (from zalo_groups): {$correctGroupId}\n";
    echo "   Use THIS ID for all conversations with 'Test phần mềm'\n";
}

