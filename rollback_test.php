<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$session = App\Models\ClassLessonSession::where('class_id', 17)->where('session_number', 34)->first();
if ($session) {
    $session->status = 'scheduled';
    $session->cancellation_reason = null;
    $session->save();
    echo "✅ Restored session 34 to scheduled\n";
} else {
    echo "❌ Session 34 not found\n";
}

