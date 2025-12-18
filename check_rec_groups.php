<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloGroup;

echo "🔍 Checking for R.E.C. TN PRE STARTER in zalo_groups table...\n\n";

$groups = ZaloGroup::where('name', 'like', '%R.E.C.%PRE STARTER%')
    ->orWhere('name', 'like', '%R.E.C. TN PRE STARTER%')
    ->orderBy('created_at', 'DESC')
    ->get();

echo "Found {$groups->count()} groups:\n\n";

foreach ($groups as $group) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "ID: {$group->id}\n";
    echo "Name: {$group->name}\n";
    echo "Zalo Group ID: {$group->zalo_group_id}\n";
    echo "Account ID: {$group->zalo_account_id}\n";
    echo "Members: {$group->members_count}\n";
    echo "Created: {$group->created_at}\n";
    echo "\n";
}

// Check if the 3 different recipient_ids from conversations match any groups
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔎 Checking if conversation recipient_ids match group IDs:\n\n";

$recipientIds = [
    '5449444974793656832', // Conv 97 - oldest
    '2035688885890530784', // Conv 105
    '5441050538148177954', // Conv 106 - newest
];

foreach ($recipientIds as $id) {
    $group = ZaloGroup::where('zalo_group_id', $id)->first();
    if ($group) {
        echo "✅ Found: {$id}\n";
        echo "   Group Name: {$group->name}\n";
        echo "   Members: {$group->members_count}\n";
        echo "   Created: {$group->created_at}\n\n";
    } else {
        echo "❌ NOT FOUND in zalo_groups: {$id}\n\n";
    }
}

