<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloGroup;
use App\Models\ZaloConversation;

echo "📊 Current State Check\n\n";

$groupName = "Test phần mềm";
$accountId = 23;

$groups = ZaloGroup::where('name', $groupName)->where('zalo_account_id', $accountId)->get();
echo "GROUPS: {$groups->count()}\n";
foreach ($groups as $g) {
    echo "  ID {$g->id}: {$g->zalo_group_id}\n";
}

$conversations = ZaloConversation::where('recipient_name', $groupName)->where('zalo_account_id', $accountId)->whereNull('deleted_at')->get();
echo "\nCONVERSATIONS: {$conversations->count()}\n";
foreach ($conversations as $c) {
    echo "  ID {$c->id}: {$c->recipient_id}\n";
}

