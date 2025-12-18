<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;

echo "🔍 Checking for duplicate messages...\n\n";

// Get messages from last 10 minutes
$messages = ZaloMessage::where('created_at', '>=', now()->subMinutes(10))
    ->orderBy('created_at', 'DESC')
    ->get(['id', 'message_id', 'content', 'created_at', 'zalo_conversation_id']);

echo "Messages in last 10 mins: {$messages->count()}\n\n";

// Check for duplicates by message_id
$dupeCount = 0;
$grouped = $messages->groupBy('message_id');
foreach ($grouped as $msgId => $group) {
    if ($group->count() > 1) {
        $dupeCount++;
        echo "⚠️ DUPLICATE message_id: $msgId (count: {$group->count()})\n";
        foreach ($group as $msg) {
            echo "  DB ID: {$msg->id} | Conv: {$msg->zalo_conversation_id} | {$msg->created_at}\n";
            echo "  Content: " . substr($msg->content ?? '', 0, 50) . "\n";
        }
        echo "\n";
    }
}

if ($dupeCount === 0) {
    echo "✅ No duplicate messages found by message_id\n";
}

// Check current listener status
echo "\n" . str_repeat("=", 60) . "\n";
echo "PM2 PROCESS STATUS:\n";
system("pm2 list | grep school-zalo");

echo "\n" . str_repeat("=", 60) . "\n";

