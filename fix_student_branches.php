<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIX STUDENT BRANCH ASSIGNMENTS ===\n\n";

// Load mismatched students
$mismatches = json_decode(file_get_contents('/var/www/school/mismatched_students.json'), true);

echo "Found " . count($mismatches) . " students to fix\n\n";

$fixed = 0;
$errors = 0;

foreach ($mismatches as $m) {
    try {
        // Update student branch_id
        DB::table('students')
            ->where('id', $m['student_id'])
            ->update(['branch_id' => $m['correct_branch_id']]);
        
        $currentBranchName = DB::table('branches')->where('id', $m['current_branch_id'])->value('name');
        $correctBranchName = DB::table('branches')->where('id', $m['correct_branch_id'])->value('name');
        
        echo "✓ Fixed: {$m['name']} ({$m['email']})\n";
        echo "  {$currentBranchName} → {$correctBranchName}\n\n";
        
        $fixed++;
    } catch (Exception $e) {
        echo "✗ Error fixing student ID {$m['student_id']}: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo "=== SUMMARY ===\n";
echo "Total students processed: " . count($mismatches) . "\n";
echo "Successfully fixed: $fixed\n";
echo "Errors: $errors\n";

// Verify the fix for user00129
echo "\n=== VERIFY user00129@songthuy.edu.vn ===\n";
$user = DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();
$student = DB::table('students')->where('user_id', $user->id)->first();
$branch = DB::table('branches')->where('id', $student->branch_id)->first();

echo "Student ID: {$student->id}\n";
echo "User ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Branch ID: {$student->branch_id}\n";
echo "Branch Name: {$branch->name}\n";
echo "\n✓ Fix completed! User should now login to correct branch.\n";
