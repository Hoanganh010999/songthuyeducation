<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Examination\Submission;
use App\Models\User;

// Find user by email
$user = User::where('email', 'user10064@student.songthuy.edu.vn')->first();
if (!$user) {
    echo 'User not found' . PHP_EOL;
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})" . PHP_EOL . PHP_EOL;

// Get latest IELTS Speaking submission
$submission = Submission::where('user_id', $user->id)
    ->whereHas('test', function($q) {
        $q->where('title', 'like', '%IELTS%')
          ->where('type', 'ielts');
    })
    ->orderBy('submitted_at', 'DESC')
    ->first();

if (!$submission) {
    echo 'No IELTS submission found' . PHP_EOL;
    exit(1);
}

echo 'Submission ID: ' . $submission->id . PHP_EOL;
echo 'Test: ' . $submission->test->title . PHP_EOL;
echo 'Status: ' . $submission->status . PHP_EOL;
echo 'Submitted at: ' . $submission->submitted_at . PHP_EOL;
echo 'Duration: ' . ($submission->time_spent ?? 0) . ' seconds' . PHP_EOL;
echo PHP_EOL;

// Check answers
$answers = $submission->answers;
echo 'Total answers: ' . $answers->count() . PHP_EOL . PHP_EOL;

foreach ($answers as $answer) {
    echo '===== Answer ' . $answer->id . ' =====' . PHP_EOL;
    echo 'Question ID: ' . $answer->question_id . PHP_EOL;
    echo 'Question type: ' . $answer->question->question_type . PHP_EOL;
    echo 'Has audio_path: ' . (!empty($answer->audio_path) ? 'YES' : 'NO') . PHP_EOL;
    
    if ($answer->audio_path) {
        echo 'Audio path: ' . $answer->audio_path . PHP_EOL;
        $fullPath = storage_path('app/' . $answer->audio_path);
        echo 'Full path: ' . $fullPath . PHP_EOL;
        echo 'File exists: ' . (file_exists($fullPath) ? 'YES' : 'NO') . PHP_EOL;
        if (file_exists($fullPath)) {
            echo 'File size: ' . round(filesize($fullPath) / 1024, 2) . ' KB' . PHP_EOL;
        }
    }
    
    echo 'Answer content length: ' . strlen($answer->answer_content ?? '') . PHP_EOL;
    echo 'Answer content (first 200 chars): ' . substr($answer->answer_content ?? '', 0, 200) . PHP_EOL;
    echo PHP_EOL;
}

