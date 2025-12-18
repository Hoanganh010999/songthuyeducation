<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;

echo "🔍 Searching for R.E.C. conversations...\n\n";

$conversations = ZaloConversation::withTrashed()
    ->where('recipient_name', 'like', '%R.E.C%')
    ->orWhere('recipient_name', 'like', '%PRE STARTER%')
    ->orderBy('created_at', 'DESC')
    ->limit(30)
    ->get();

if ($conversations->isEmpty()) {
    echo "⚠️  No conversations found!\n";
} else {
    echo "Found {$conversations->count()} conversations:\n\n";
    
    foreach ($conversations as $conv) {
        $status = $conv->deleted_at ? '🗑️' : '✅';
        $messageCount = ZaloMessage::where('zalo_conversation_id', $conv->id)->count();
        
        echo "{$status} ID: {$conv->id} | Name: {$conv->recipient_name}\n";
        echo "   Recipient ID: {$conv->recipient_id}\n";
        echo "   Account ID: {$conv->zalo_account_id}\n";
        echo "   Messages: {$messageCount}\n";
        echo "   Created: {$conv->created_at}\n";
        if ($conv->deleted_at) echo "   Deleted: {$conv->deleted_at}\n";
        echo "\n";
    }
}

// Find duplicates
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 Checking for R.E.C. duplicates:\n\n";

$duplicates = ZaloConversation::withTrashed()
    ->select('zalo_account_id', 'recipient_id', 'recipient_type', 'recipient_name')
    ->selectRaw('COUNT(*) as count')
    ->where('recipient_name', 'like', '%R.E.C%')
    ->groupBy('zalo_account_id', 'recipient_id', 'recipient_type', 'recipient_name')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "✅ No duplicates found based on recipient_id!\n";
} else {
    foreach ($duplicates as $dup) {
        echo "⚠️  DUPLICATE:\n";
        echo "   Name: {$dup->recipient_name}\n";
        echo "   Recipient ID: {$dup->recipient_id}\n";
        echo "   Count: {$dup->count}\n\n";
    }
}

// Check for same name but DIFFERENT recipient_id
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 Checking for same name with DIFFERENT recipient_id:\n\n";

$grouped = ZaloConversation::withTrashed()
    ->select('recipient_name', 'zalo_account_id')
    ->selectRaw('COUNT(DISTINCT recipient_id) as unique_ids, GROUP_CONCAT(DISTINCT recipient_id) as ids')
    ->where('recipient_name', 'like', '%R.E.C%')
    ->groupBy('recipient_name', 'zalo_account_id')
    ->having('unique_ids', '>', 1)
    ->get();

if ($grouped->isEmpty()) {
    echo "✅ No issues - each name has only one recipient_id!\n";
} else {
    foreach ($grouped as $group) {
        echo "⚠️  PROBLEM FOUND:\n";
        echo "   Name: {$group->recipient_name}\n";
        echo "   Account ID: {$group->zalo_account_id}\n";
        echo "   Different recipient_ids: {$group->unique_ids}\n";
        echo "   IDs: {$group->ids}\n\n";
        
        // Show conversations with these IDs
        $ids = explode(',', $group->ids);
        foreach ($ids as $id) {
            $conv = ZaloConversation::withTrashed()
                ->where('zalo_account_id', $group->zalo_account_id)
                ->where('recipient_name', $group->recipient_name)
                ->where('recipient_id', trim($id))
                ->first();
            
            if ($conv) {
                $msgCount = ZaloMessage::where('zalo_conversation_id', $conv->id)->count();
                echo "      → Conv ID {$conv->id}: recipient_id={$conv->recipient_id}, messages={$msgCount}, created={$conv->created_at}\n";
            }
        }
        echo "\n";
    }
}

