<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get a recent graded submission
$submission = \App\Models\Examination\Submission::with(['answers'])
    ->where('status', 'graded')
    ->orderBy('updated_at', 'desc')
    ->first();

if (!$submission) {
    echo "No graded submissions found\n";
    exit(0);
}

echo "Submission ID: {$submission->id}\n";
echo "Status: {$submission->status}\n";
echo "Band Score: {$submission->band_score}\n";
echo "Overall Feedback: " . ($submission->feedback ?: '(empty)') . "\n\n";

echo "Answers with grading:\n";
echo str_repeat("=", 100) . "\n";

foreach ($submission->answers as $answer) {
    echo "Answer ID: {$answer->id}\n";
    echo "  Band Score: " . ($answer->band_score ?? '(null)') . "\n";
    echo "  Feedback: " . ($answer->feedback ? mb_substr($answer->feedback, 0, 100) : '(empty)') . "\n";
    echo "  Grading Criteria: " . ($answer->grading_criteria ? 'YES' : 'NO') . "\n";
    
    if ($answer->grading_criteria) {
        if (is_string($answer->grading_criteria)) {
            $criteria = json_decode($answer->grading_criteria, true);
        } else {
            $criteria = $answer->grading_criteria;
        }
        
        if ($criteria) {
            echo "  Criteria keys: " . implode(', ', array_keys($criteria)) . "\n";
            foreach ($criteria as $key => $value) {
                if (is_array($value)) {
                    echo "    {$key}: score={$value['score']}, feedback=" . (isset($value['feedback']) ? 'YES' : 'NO') . "\n";
                }
            }
        }
    }
    echo str_repeat("-", 100) . "\n";
}

// Check API response format
echo "\n\nTesting API response format:\n";
echo str_repeat("=", 100) . "\n";

$apiController = app(\App\Http\Controllers\Api\SubmissionController::class);
$request = new \Illuminate\Http\Request(['id' => $submission->id]);
$request->setRouteResolver(function() use ($submission) {
    $route = new \Illuminate\Routing\Route('GET', '/api/examination/submissions/{id}', []);
    $route->bind(new \Illuminate\Http\Request(['id' => $submission->id]));
    $route->setParameter('id', $submission->id);
    return $route;
});

try {
    $response = $apiController->show($submission->id);
    $data = $response->getData(true);
    
    if ($data['success']) {
        $submissionData = $data['data'];
        echo "API Response:\n";
        echo "  Has answers: " . (isset($submissionData['answers']) ? 'YES' : 'NO') . "\n";
        
        if (isset($submissionData['answers']) && count($submissionData['answers']) > 0) {
            $firstAnswer = $submissionData['answers'][0];
            echo "  First answer keys: " . implode(', ', array_keys($firstAnswer)) . "\n";
            echo "  Has feedback: " . (isset($firstAnswer['feedback']) ? 'YES' : 'NO') . "\n";
            echo "  Has grading_criteria: " . (isset($firstAnswer['grading_criteria']) ? 'YES' : 'NO') . "\n";
            
            if (isset($firstAnswer['grading_criteria'])) {
                echo "  Grading criteria keys: " . implode(', ', array_keys($firstAnswer['grading_criteria'])) . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

