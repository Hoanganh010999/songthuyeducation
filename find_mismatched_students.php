<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINDING ALL STUDENTS WITH BRANCH MISMATCHES ===\n\n";

// Get all students
$students = DB::table('students')
    ->where('is_active', true)
    ->get();

$mismatches = [];
$noEnrollments = [];

foreach ($students as $student) {
    // Get user info
    $user = DB::table('users')->where('id', $student->user_id)->first();
    
    // Check enrollments using user_id (because class_students.student_id stores user_id)
    $enrollments = DB::table('class_students')
        ->where('student_id', $user->id)
        ->where('status', 'active')
        ->get();
    
    if (count($enrollments) > 0) {
        // Get branch_id from first class enrollment
        $firstClass = DB::table('classes')->where('id', $enrollments[0]->class_id)->first();
        $classBranchId = $firstClass->branch_id;
        
        // Check if student's branch_id matches class branch_id
        if ($student->branch_id != $classBranchId) {
            $mismatches[] = [
                'student_id' => $student->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'current_branch_id' => $student->branch_id,
                'correct_branch_id' => $classBranchId,
                'enrollment_count' => count($enrollments)
            ];
        }
    } else {
        $noEnrollments[] = [
            'student_id' => $student->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'branch_id' => $student->branch_id
        ];
    }
}

echo "=== SUMMARY ===\n";
echo "Total active students: " . count($students) . "\n";
echo "Students with MISMATCHED branch_id: " . count($mismatches) . "\n";
echo "Students with NO enrollments: " . count($noEnrollments) . "\n\n";

if (count($mismatches) > 0) {
    echo "=== STUDENTS WITH BRANCH MISMATCHES ===\n";
    foreach ($mismatches as $m) {
        $currentBranchName = DB::table('branches')->where('id', $m['current_branch_id'])->value('name');
        $correctBranchName = DB::table('branches')->where('id', $m['correct_branch_id'])->value('name');
        
        echo "Student ID: {$m['student_id']} | User: {$m['name']} ({$m['email']})\n";
        echo "  Current: {$currentBranchName} (ID: {$m['current_branch_id']})\n";
        echo "  Should be: {$correctBranchName} (ID: {$m['correct_branch_id']})\n";
        echo "  Enrollments: {$m['enrollment_count']}\n\n";
    }
}

// Save to JSON for fix script
file_put_contents('/var/www/school/mismatched_students.json', json_encode($mismatches, JSON_PRETTY_PRINT));
echo "✓ Mismatch data saved to mismatched_students.json\n";
