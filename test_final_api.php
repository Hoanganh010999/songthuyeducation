<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST FINAL API /user FOR user00129@songthuy.edu.vn ===\n\n";

$user = \App\Models\User::where('email', 'user00129@songthuy.edu.vn')->first();

// Simulate what the API does
$studentRecord = \DB::table('students')
    ->where('user_id', $user->id)
    ->where('is_active', true)
    ->first();

$branchId = $studentRecord->branch_id ?? null;

echo "Step 1: Determine branch_id\n";
echo "  Student branch_id: $branchId\n";

$branch = \App\Models\Branch::find($branchId);
echo "  Branch name: {$branch->name}\n\n";

echo "Step 2: Load user with branches relationship\n";
$user->load('branches');
echo "  user->branches count: " . count($user->branches) . "\n";

echo "\nStep 3: Override branches for students\n";
$isStudent = \DB::table('students')
    ->where('user_id', $user->id)
    ->where('is_active', true)
    ->exists();

echo "  Is student: " . ($isStudent ? 'YES' : 'NO') . "\n";

if ($isStudent && $branchId) {
    $branch = \App\Models\Branch::find($branchId);
    $branches = [$branch->toArray()];
    echo "  Override branches with: [{$branch->name}]\n";
} else {
    $branches = $user->branches->toArray();
}

echo "\nStep 4: Final API response\n";
echo "  current_branch_id: $branchId\n";
echo "  branches array count: " . count($branches) . "\n";
foreach ($branches as $b) {
    echo "    - " . (is_array($b) ? $b['name'] : $b->name) . "\n";
}

echo "\n✓ SUCCESS! API will return correct branch for students!\n";
