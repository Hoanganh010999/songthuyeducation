<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find user
$user = App\Models\User::where("email", "st20250001@songthuy.edu.vn")->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User ID: " . $user->id . "\n";
echo "User Name: " . $user->name . "\n\n";

// Find IELTS Writing test (5/12/2025)
$test = App\Models\Examination\Test::where("title", "LIKE", "%IELTS%")
    ->where("title", "LIKE", "%Writing%")
    ->where("created_at", ">=", "2025-12-05")
    ->where("created_at", "<=", "2025-12-06")
    ->first();

if ($test) {
    echo "Test found: " . $test->title . " (ID: {$test->id})\n\n";
    
    // Find submission (check both assignment_id and practice_test_id)
    $submission = App\Models\Examination\Submission::where("user_id", $user->id)
        ->where(function($query) use ($test) {
            $query->where("assignment_id", $test->id)
                  ->orWhere("practice_test_id", $test->id);
        })
        ->first();
    
    if ($submission) {
        echo "Submission ID: " . $submission->id . "\n";
        echo "Status: " . $submission->status . "\n";
        echo "Created: " . $submission->created_at . "\n\n";
        
        // Get answers
        $answers = App\Models\Examination\SubmissionAnswer::where("submission_id", $submission->id)
            ->get();
        
        echo "Answers count: " . $answers->count() . "\n\n";
        
        foreach ($answers as $answer) {
            echo "Answer ID: {$answer->id}\n";
            echo "Question ID: {$answer->question_id}\n";
            echo "Band Score: " . ($answer->band_score ?? "NULL") . "\n";
            echo "Grading Criteria: " . json_encode($answer->grading_criteria) . "\n";
            echo "AI Feedback length: " . strlen($answer->ai_feedback ?? "") . " chars\n";
            echo "AI Feedback preview: " . substr($answer->ai_feedback ?? "NULL", 0, 200) . "...\n";
            echo "Feedback: " . ($answer->feedback ?? "NULL") . "\n";
            echo "---\n\n";
        }
    } else {
        echo "No submission found for this test\n\n";
        
        // List all submissions for this user to find the correct one
        echo "All submissions for user {$user->id}:\n";
        $allSubs = App\Models\Examination\Submission::where("user_id", $user->id)
            ->orderBy("created_at", "desc")
            ->take(20)
            ->get();
        
        foreach ($allSubs as $s) {
            $testId = $s->assignment_id ?? $s->practice_test_id ?? "NULL";
            echo "  - Submission ID: {$s->id}, Test ID: {$testId}, Status: {$s->status}, Band Score: {$s->band_score}, Date: {$s->created_at}\n";
            
            // Get test title if available
            if ($s->assignment_id) {
                $t = App\Models\Examination\Test::find($s->assignment_id);
                if ($t) echo "    Test: {$t->title}\n";
            } elseif ($s->practice_test_id) {
                $t = App\Models\Examination\Test::find($s->practice_test_id);
                if ($t) echo "    Test: {$t->title}\n";
            }
        }
    }
} else {
    echo "No IELTS Writing test found on 5/12/2025\n";
    
    // List all recent tests
    echo "\nRecent tests:\n";
    $recentTests = App\Models\Examination\Test::orderBy("created_at", "desc")
        ->take(10)
        ->get(["id", "title", "created_at"]);
    foreach ($recentTests as $t) {
        echo "  - ID: {$t->id}, Title: {$t->title}, Date: {$t->created_at}\n";
    }
}

