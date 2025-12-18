<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CLEAN branch_user RECORDS FOR STUDENTS ===\n\n";

// Get all active students
$students = \DB::table('students')
    ->where('is_active', true)
    ->get();

echo "Total active students: " . count($students) . "\n\n";

$studentsWithBranchUser = [];
$deleted = 0;

foreach ($students as $student) {
    $user = \DB::table('users')->where('id', $student->user_id)->first();
    
    // Check if this student has branch_user records
    $branchUserRecords = \DB::table('branch_user')
        ->where('user_id', $student->user_id)
        ->get();
    
    if (count($branchUserRecords) > 0) {
        $studentsWithBranchUser[] = [
            'student_id' => $student->id,
            'user_id' => $student->user_id,
            'email' => $user->email,
            'name' => $user->name,
            'student_branch_id' => $student->branch_id,
            'branch_user_count' => count($branchUserRecords)
        ];
        
        // Delete branch_user records for this student
        foreach ($branchUserRecords as $bu) {
            $branch = \DB::table('branches')->where('id', $bu->branch_id)->first();
            echo "Deleting: {$user->email} from branch_user (branch: {$branch->name})\n";
            
            \DB::table('branch_user')
                ->where('user_id', $student->user_id)
                ->where('branch_id', $bu->branch_id)
                ->delete();
            
            $deleted++;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Students with branch_user records: " . count($studentsWithBranchUser) . "\n";
echo "Total branch_user records deleted: $deleted\n\n";

echo "=== VERIFY user00129@songthuy.edu.vn ===\n";
$user = \DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();
$branchUser = \DB::table('branch_user')->where('user_id', $user->id)->get();
$student = \DB::table('students')->where('user_id', $user->id)->first();
$branch = \DB::table('branches')->where('id', $student->branch_id)->first();

echo "User ID: {$user->id}\n";
echo "Student branch_id: {$student->branch_id} ({$branch->name})\n";
echo "branch_user records: " . count($branchUser) . "\n";
echo "\n✓ Cleaned! Students should NOT have branch_user records.\n";
