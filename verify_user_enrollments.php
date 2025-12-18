<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFY user00129@songthuy.edu.vn ENROLLMENTS ===\n";
$user = DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();
echo "User ID: {$user->id}\n";
echo "Name: {$user->name}\n\n";

$student = DB::table('students')->where('user_id', $user->id)->first();
echo "Student ID: {$student->id}\n";
echo "Student Branch ID: {$student->branch_id}\n\n";

echo "Checking enrollments using user_id ({$user->id}):\n";
$enrollments = DB::table('class_students')->where('student_id', $user->id)->get();
echo "Found: " . count($enrollments) . " enrollments\n\n";

foreach ($enrollments as $e) {
    $class = DB::table('classes')->where('id', $e->class_id)->first();
    $branch = DB::table('branches')->where('id', $class->branch_id)->first();
    echo "  - Class: {$class->name}\n";
    echo "    Branch: {$branch->name} (ID: {$class->branch_id})\n";
    echo "    Status: {$e->status}\n";
}

echo "\n=== CONCLUSION ===\n";
if (count($enrollments) > 0) {
    $firstClass = DB::table('classes')->where('id', $enrollments[0]->class_id)->first();
    $correctBranchId = $firstClass->branch_id;
    
    echo "✓ User has enrollments!\n";
    echo "✓ All classes are in branch_id: $correctBranchId\n";
    echo "✗ But student record has branch_id: {$student->branch_id}\n\n";
    
    if ($student->branch_id != $correctBranchId) {
        echo "⚠️  MISMATCH DETECTED! Student branch_id should be $correctBranchId, not {$student->branch_id}\n";
    }
} else {
    echo "⚠️ No enrollments found for this user\n";
}
