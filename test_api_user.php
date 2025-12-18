<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST API /user FOR user00129@songthuy.edu.vn ===\n\n";

$user = \App\Models\User::where('email', 'user00129@songthuy.edu.vn')->first();

echo "User ID: {$user->id}\n";
echo "Name: {$user->name}\n\n";

echo "Step 1: Check user.branches from branch_user relationship\n";
$branches = $user->branches;
echo "Count: " . count($branches) . "\n";
foreach ($branches as $b) {
    echo "  - Branch: {$b->name} (ID: {$b->id})\n";
}

echo "\nStep 2: Check student record\n";
$student = \DB::table('students')->where('user_id', $user->id)->first();
if ($student) {
    echo "Student ID: {$student->id}\n";
    echo "Student branch_id: {$student->branch_id}\n";
    $branch = \DB::table('branches')->where('id', $student->branch_id)->first();
    echo "Branch name: {$branch->name}\n";
}

echo "\n Step 3: Check branch_user table\n";
$branchUser = \DB::table('branch_user')->where('user_id', $user->id)->get();
echo "Records in branch_user: " . count($branchUser) . "\n";
foreach ($branchUser as $bu) {
    $b = \DB::table('branches')->where('id', $bu->branch_id)->first();
    echo "  - Branch: {$b->name} (ID: {$bu->branch_id})\n";
}

echo "\n=== PROBLEM ===\n";
echo "Students do not have branch_user records!\n";
echo "Frontend expects user.branches but that is empty for students.\n";
echo "We need to return a branches array based on student.branch_id\n";
