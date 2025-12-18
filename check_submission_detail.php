<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$submissionId = 1918; // Latest submission with band_score 5.5

echo "=== CHECKING SUBMISSION ID: {$submissionId} ===\n\n";

$submission = App\Models\Examination\Submission::find($submissionId);

if (!$submission) {
    echo "Submission not found\n";
    exit;
}

echo "Submission Details:\n";
echo "  - ID: {$submission->id}\n";
echo "  - User ID: {$submission->user_id}\n";
echo "  - Status: {$submission->status}\n";
echo "  - Band Score: {$submission->band_score}\n";
echo "  - Feedback: " . ($submission->feedback ?? "NULL") . "\n";
echo "  - Created: {$submission->created_at}\n\n";

// Get answers
$answers = App\Models\Examination\SubmissionAnswer::where("submission_id", $submission->id)
    ->get();

echo "Total Answers: " . $answers->count() . "\n\n";

foreach ($answers as $index => $answer) {
    echo "--- Answer #" . ($index + 1) . " ---\n";
    echo "Answer ID: {$answer->id}\n";
    echo "Question ID: {$answer->question_id}\n";
    echo "Band Score: " . ($answer->band_score ?? "NULL") . "\n";
    
    // Grading criteria
    $criteria = $answer->grading_criteria;
    echo "Grading Criteria:\n";
    if ($criteria && is_array($criteria)) {
        foreach ($criteria as $key => $value) {
            if (is_array($value) || is_object($value)) {
                echo "  - {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  - {$key}: {$value}\n";
            }
        }
    } else {
        echo "  NULL or not array: " . json_encode($criteria) . "\n";
    }
    
    // AI Feedback
    echo "\n";
    echo "AI Feedback:\n";
    if ($answer->ai_feedback) {
        echo "  Length: " . strlen($answer->ai_feedback) . " chars\n";
        echo "  Content: " . $answer->ai_feedback . "\n";
    } else {
        echo "  NULL or EMPTY\n";
    }
    
    // Teacher Feedback
    echo "\n";
    echo "Teacher Feedback: " . ($answer->feedback ?? "NULL") . "\n";
    
    // Student Answer (first 200 chars)
    echo "\n";
    echo "Student Answer (preview): " . substr($answer->student_answer ?? "NULL", 0, 200) . "...\n";
    
    echo "\n";
}

