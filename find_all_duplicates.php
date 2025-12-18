<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloGroup;
use App\Models\ZaloConversation;
use Illuminate\Support\Facades\DB;

echo "🔍 Finding ALL duplicates in system...\n\n";

// Find duplicate groups
echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 DUPLICATE GROUPS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$duplicateGroups = DB::table('zalo_groups')
    ->select('name', 'zalo_account_id')
    ->selectRaw('COUNT(*) as count, GROUP_CONCAT(id ORDER BY created_at) as ids, GROUP_CONCAT(zalo_group_id ORDER BY created_at) as group_ids, MIN(created_at) as first_created, MAX(created_at) as last_created')
    ->whereNull('deleted_at')
    ->groupBy('name', 'zalo_account_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicateGroups->isEmpty()) {
    echo "✅ No duplicate groups found!\n\n";
} else {
    foreach ($duplicateGroups as $dup) {
        echo "⚠️  DUPLICATE GROUP:\n";
        echo "   Name: {$dup->name}\n";
        echo "   Account ID: {$dup->zalo_account_id}\n";
        echo "   Count: {$dup->count}\n";
        echo "   IDs: {$dup->ids}\n";
        echo "   Group IDs: {$dup->group_ids}\n";
        echo "   First: {$dup->first_created}\n";
        echo "   Last: {$dup->last_created}\n\n";
    }
}

// Find duplicate conversations (same recipient_id)
echo "═══════════════════════════════════════════════════════════════\n";
echo "💬 DUPLICATE CONVERSATIONS (same recipient_id):\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$duplicateConvs = DB::table('zalo_conversations')
    ->select('zalo_account_id', 'recipient_id', 'recipient_type')
    ->selectRaw('COUNT(*) as count, GROUP_CONCAT(id ORDER BY created_at) as ids, GROUP_CONCAT(recipient_name) as names, MIN(created_at) as first_created')
    ->whereNull('deleted_at')
    ->groupBy('zalo_account_id', 'recipient_id', 'recipient_type')
    ->having('count', '>', 1)
    ->get();

if ($duplicateConvs->isEmpty()) {
    echo "✅ No duplicate conversations by recipient_id!\n\n";
} else {
    foreach ($duplicateConvs as $dup) {
        echo "⚠️  DUPLICATE CONVERSATION:\n";
        echo "   Recipient ID: {$dup->recipient_id}\n";
        echo "   Type: {$dup->recipient_type}\n";
        echo "   Count: {$dup->count}\n";
        echo "   Conv IDs: {$dup->ids}\n";
        echo "   Names: {$dup->names}\n\n";
    }
}

// Find conversations with same name but different recipient_id
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 SAME NAME but DIFFERENT recipient_id:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$sameNameDiffId = DB::table('zalo_conversations')
    ->select('recipient_name', 'zalo_account_id')
    ->selectRaw('COUNT(DISTINCT recipient_id) as unique_ids, GROUP_CONCAT(DISTINCT recipient_id) as ids')
    ->whereNull('deleted_at')
    ->whereNotNull('recipient_name')
    ->groupBy('recipient_name', 'zalo_account_id')
    ->having('unique_ids', '>', 1)
    ->get();

if ($sameNameDiffId->isEmpty()) {
    echo "✅ No issues - each name has only one recipient_id!\n\n";
} else {
    foreach ($sameNameDiffId as $group) {
        echo "⚠️  PROBLEM:\n";
        echo "   Name: {$group->recipient_name}\n";
        echo "   Account: {$group->zalo_account_id}\n";
        echo "   Different IDs: {$group->unique_ids}\n";
        echo "   IDs: {$group->ids}\n\n";
    }
}

