<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIX branch_user FOR STUDENTS BASED ON CLASS ENROLLMENTS ===\n\n";

// Get all active students
$students = \DB::table('students')
    ->where('is_active', true)
    ->get();

echo "Total active students: " . count($students) . "\n\n";

$processed = 0;
$added = 0;
$updated = 0;
$unchanged = 0;
$noEnrollments = 0;

foreach ($students as $student) {
    $user = \DB::table('users')->where('id', $student->user_id)->first();
    
    if (!$user) {
        echo "⚠️ Student ID {$student->id} has no user, skipping\n";
        continue;
    }
    
    // Get active class enrollments (class_students.student_id stores user_id, not student_id)
    $enrollments = \DB::table('class_students')
        ->where('student_id', $user->id)
        ->where('status', 'active')
        ->get();
    
    if (count($enrollments) == 0) {
        $noEnrollments++;
        continue;
    }
    
    // Get unique branch_ids from their classes
    $branchIds = [];
    foreach ($enrollments as $enrollment) {
        $class = \DB::table('classes')->where('id', $enrollment->class_id)->first();
        if ($class && $class->branch_id) {
            $branchIds[] = $class->branch_id;
        }
    }
    $branchIds = array_unique($branchIds);
    
    if (count($branchIds) == 0) {
        echo "⚠️ Student {$user->email} has enrollments but no valid branch_id\n";
        continue;
    }
    
    // For each branch_id, ensure student is in branch_user
    foreach ($branchIds as $branchId) {
        $exists = \DB::table('branch_user')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->exists();
        
        if (!$exists) {
            // Add to branch_user
            \DB::table('branch_user')->insert([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $branch = \DB::table('branches')->where('id', $branchId)->first();
            echo "✓ Added: {$user->email} → {$branch->name} (ID: $branchId)\n";
            $added++;
        } else {
            $unchanged++;
        }
    }
    
    // Remove branch_user records that are NOT in their class branches
    $currentBranchIds = \DB::table('branch_user')
        ->where('user_id', $user->id)
        ->pluck('branch_id')
        ->toArray();
    
    $toRemove = array_diff($currentBranchIds, $branchIds);
    
    if (count($toRemove) > 0) {
        foreach ($toRemove as $wrongBranchId) {
            $wrongBranch = \DB::table('branches')->where('id', $wrongBranchId)->first();
            
            // Check if this student has NO classes in this branch
            $hasClassInBranch = false;
            foreach ($enrollments as $e) {
                $c = \DB::table('classes')->where('id', $e->class_id)->first();
                if ($c && $c->branch_id == $wrongBranchId) {
                    $hasClassInBranch = true;
                    break;
                }
            }
            
            if (!$hasClassInBranch) {
                \DB::table('branch_user')
                    ->where('user_id', $user->id)
                    ->where('branch_id', $wrongBranchId)
                    ->delete();
                
                echo "✗ Removed: {$user->email} from {$wrongBranch->name} (no classes there)\n";
                $updated++;
            }
        }
    }
    
    $processed++;
}

echo "\n=== SUMMARY ===\n";
echo "Students processed: $processed\n";
echo "branch_user records added: $added\n";
echo "branch_user records removed (wrong): $updated\n";
echo "branch_user records unchanged (correct): $unchanged\n";
echo "Students with no enrollments: $noEnrollments\n";

echo "\n=== VERIFY user00129@songthuy.edu.vn ===\n";
$user = \DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();
$student = \DB::table('students')->where('user_id', $user->id)->first();
$branchUser = \DB::table('branch_user')->where('user_id', $user->id)->get();

echo "User ID: {$user->id}\n";
echo "Student branch_id: {$student->branch_id}\n";
echo "branch_user records:\n";
foreach ($branchUser as $bu) {
    $branch = \DB::table('branches')->where('id', $bu->branch_id)->first();
    echo "  - {$branch->name} (ID: {$bu->branch_id})\n";
}

echo "\n✓ Done! Students now have correct branch_user records.\n";
