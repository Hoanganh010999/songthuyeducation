<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloMessage;

echo "Recent messages (last 10):\n";
echo str_repeat("=", 100) . "\n";

$messages = ZaloMessage::where('created_at', '>=', '2025-12-10 10:45:00')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'message_id', 'type', 'content_type', 'content', 'sent_at', 'created_at']);

foreach ($messages as $msg) {
    echo sprintf(
        "ID: %d | MsgID: %s | Type: %s | ContentType: %s | Time: %s\n",
        $msg->id,
        $msg->message_id,
        $msg->type,
        $msg->content_type,
        $msg->created_at
    );
    
    $contentPreview = mb_substr($msg->content, 0, 100);
    echo "Content: {$contentPreview}\n";
    echo str_repeat("-", 100) . "\n";
}

// Check for "abc" reminder messages
echo "\n\nMessages containing 'abc':\n";
echo str_repeat("=", 100) . "\n";

$abcMessages = ZaloMessage::where('created_at', '>=', '2025-12-10 10:45:00')
    ->where('content', 'like', '%abc%')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'message_id', 'type', 'content_type', 'content', 'created_at']);

foreach ($abcMessages as $msg) {
    echo sprintf(
        "ID: %d | MsgID: %s | Type: %s | ContentType: %s | Time: %s\n",
        $msg->id,
        $msg->message_id,
        $msg->type,
        $msg->content_type,
        $msg->created_at
    );
    echo "Content: " . mb_substr($msg->content, 0, 200) . "\n";
    echo str_repeat("-", 100) . "\n";
}

// Check for "Def" reminder messages
echo "\n\nMessages containing 'Def':\n";
echo str_repeat("=", 100) . "\n";

$defMessages = ZaloMessage::where('created_at', '>=', '2025-12-10 10:45:00')
    ->where('content', 'like', '%Def%')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'message_id', 'type', 'content_type', 'content', 'created_at']);

foreach ($defMessages as $msg) {
    echo sprintf(
        "ID: %d | MsgID: %s | Type: %s | ContentType: %s | Time: %s\n",
        $msg->id,
        $msg->message_id,
        $msg->type,
        $msg->content_type,
        $msg->created_at
    );
    echo "Content: " . mb_substr($msg->content, 0, 200) . "\n";
    echo str_repeat("-", 100) . "\n";
}

