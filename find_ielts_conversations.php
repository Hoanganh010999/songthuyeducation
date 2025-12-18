<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloGroup;
use App\Models\ZaloMessage;

echo "🔍 Searching for IELTS / K5.TN conversations and groups...\n\n";

// Find all conversations (including soft-deleted)
echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 CONVERSATIONS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$conversations = ZaloConversation::withTrashed()
    ->where(function($query) {
        $query->where('recipient_name', 'like', '%IELTS%')
              ->orWhere('recipient_name', 'like', '%K5%');
    })
    ->orderBy('recipient_name')
    ->orderBy('created_at')
    ->get();

if ($conversations->isEmpty()) {
    echo "⚠️  No conversations found matching IELTS or K5\n\n";
} else {
    foreach ($conversations as $conv) {
        $status = $conv->deleted_at ? '🗑️ DELETED' : '✅ ACTIVE';
        $messageCount = ZaloMessage::where('zalo_conversation_id', $conv->id)->count();
        
        echo "─────────────────────────────────────────────────────────────\n";
        echo "ID: {$conv->id} {$status}\n";
        echo "Account ID: {$conv->zalo_account_id}\n";
        echo "Recipient ID: {$conv->recipient_id}\n";
        echo "Recipient Type: {$conv->recipient_type}\n";
        echo "Name: {$conv->recipient_name}\n";
        echo "Messages: {$messageCount}\n";
        echo "Created: {$conv->created_at}\n";
        if ($conv->deleted_at) {
            echo "Deleted: {$conv->deleted_at}\n";
        }
        echo "\n";
    }
}

// Find all groups
echo "═══════════════════════════════════════════════════════════════\n";
echo "👥 GROUPS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$groups = ZaloGroup::where(function($query) {
        $query->where('name', 'like', '%IELTS%')
              ->orWhere('name', 'like', '%K5%');
    })
    ->orderBy('name')
    ->orderBy('created_at')
    ->get();

if ($groups->isEmpty()) {
    echo "⚠️  No groups found matching IELTS or K5\n\n";
} else {
    foreach ($groups as $group) {
        echo "─────────────────────────────────────────────────────────────\n";
        echo "ID: {$group->id}\n";
        echo "Account ID: {$group->zalo_account_id}\n";
        echo "Group ID (Zalo): {$group->zalo_group_id}\n";
        echo "Name: {$group->name}\n";
        echo "Members: {$group->members_count}\n";
        echo "Created: {$group->created_at}\n";
        echo "\n";
    }
}

// Find duplicates
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 CHECKING FOR DUPLICATES:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$duplicates = ZaloConversation::withTrashed()
    ->select('zalo_account_id', 'recipient_id', 'recipient_type', 'recipient_name')
    ->selectRaw('COUNT(*) as count')
    ->where(function($query) {
        $query->where('recipient_name', 'like', '%IELTS%')
              ->orWhere('recipient_name', 'like', '%K5%');
    })
    ->groupBy('zalo_account_id', 'recipient_id', 'recipient_type', 'recipient_name')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "✅ No duplicates found!\n\n";
} else {
    foreach ($duplicates as $dup) {
        echo "⚠️  DUPLICATE FOUND:\n";
        echo "   Account ID: {$dup->zalo_account_id}\n";
        echo "   Recipient ID: {$dup->recipient_id}\n";
        echo "   Name: {$dup->recipient_name}\n";
        echo "   Type: {$dup->recipient_type}\n";
        echo "   Count: {$dup->count}\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✨ Search completed!\n";

