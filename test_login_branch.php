<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SIMULATE LOGIN FOR user00129@songthuy.edu.vn ===\n\n";

$user = DB::table('users')->where('email', 'user00129@songthuy.edu.vn')->first();

echo "Step 1: Get user from database\n";
echo "  User ID: {$user->id}\n";
echo "  Name: {$user->name}\n\n";

echo "Step 2: Determine branch_id (using new logic)\n";

// 1. Check if user is a student
$studentRecord = DB::table('students')
    ->where('user_id', $user->id)
    ->where('is_active', true)
    ->first();

if ($studentRecord && $studentRecord->branch_id) {
    $branchId = $studentRecord->branch_id;
    echo "  ✓ Found student record\n";
    echo "    Student ID: {$studentRecord->id}\n";
    echo "    Branch ID: {$studentRecord->branch_id}\n";
} else {
    echo "  ✗ No student record found\n";
    $branchId = null;
}

// 2. If not student, check if user is a parent
if (!$branchId) {
    $parentRecord = DB::table('parents')
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->first();

    if ($parentRecord && $parentRecord->branch_id) {
        $branchId = $parentRecord->branch_id;
        echo "  ✓ Found parent record\n";
        echo "    Parent ID: {$parentRecord->id}\n";
        echo "    Branch ID: {$parentRecord->branch_id}\n";
    }
}

// 3. If neither student nor parent, check employee branch assignment
if (!$branchId) {
    $firstBranch = DB::table('branch_user')->where('user_id', $user->id)->first();
    if ($firstBranch) {
        $branchId = $firstBranch->branch_id;
        echo "  ✓ Found employee branch assignment\n";
        echo "    Branch ID: {$firstBranch->branch_id}\n";
    }
}

echo "\nStep 3: Final branch assignment\n";
if ($branchId) {
    $branch = DB::table('branches')->where('id', $branchId)->first();
    echo "  Branch ID: $branchId\n";
    echo "  Branch Name: {$branch->name}\n";
    echo "\n✓ SUCCESS! User will login to: {$branch->name}\n";
} else {
    echo "  Branch ID: NULL\n";
    echo "\n⚠️  No branch assigned\n";
}

echo "\nStep 4: Verify class enrollment\n";
$enrollments = DB::table('class_students')
    ->where('student_id', $user->id)
    ->where('status', 'active')
    ->get();

foreach ($enrollments as $e) {
    $class = DB::table('classes')->where('id', $e->class_id)->first();
    $classBranch = DB::table('branches')->where('id', $class->branch_id)->first();
    echo "  Class: {$class->name}\n";
    echo "  Class Branch: {$classBranch->name} (ID: {$class->branch_id})\n";
}

if (count($enrollments) > 0 && $branchId) {
    $firstClass = DB::table('classes')->where('id', $enrollments[0]->class_id)->first();
    if ($firstClass->branch_id == $branchId) {
        echo "\n✓✓ PERFECT! Login branch matches class branch!\n";
    } else {
        echo "\n✗✗ ERROR! Login branch ($branchId) does NOT match class branch ({$firstClass->branch_id})\n";
    }
}
