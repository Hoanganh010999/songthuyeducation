<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Enrollment;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

echo "Testing Enrollment access for: {$user->name}\n\n";

// Test current query (no branch filter)
echo "Current query (NO branch filter):\n";
$allEnrollments = Enrollment::query()->count();
echo "  Total enrollments: {$allEnrollments}\n\n";

// Check enrollments by branch
echo "Enrollments by branch:\n";
$branches = \App\Models\Branch::all();
foreach ($branches as $branch) {
    $count = Enrollment::where('branch_id', $branch->id)->count();
    echo "  - Branch {$branch->id} ({$branch->name}): {$count} enrollments\n";
}

echo "\n\nUser's branches:\n";
$userBranches = $user->branches;
foreach ($userBranches as $branch) {
    echo "  - Branch {$branch->id}: {$branch->name}\n";
}

echo "\n⚠️  Issue: EnrollmentController does NOT filter by branch!\n";
echo "   User can see ALL enrollments from ALL branches.\n";
echo "\n✅ Solution: Add accessibleBy() scope to Enrollment model\n";
echo "   and apply it in EnrollmentController::index()\n";

