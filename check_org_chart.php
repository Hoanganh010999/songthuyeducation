<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "═══════════════════════════════════════════\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "═══════════════════════════════════════════\n\n";

// Check branches
echo "Branches (via branch_user):\n";
$branches = $user->branches;
foreach ($branches as $branch) {
    $pivot = $branch->pivot;
    echo "  - Branch {$branch->id}: {$branch->name}\n";
    if ($pivot) {
        echo "    Position: " . ($pivot->position ?? 'N/A') . "\n";
        echo "    Is Admin: " . ($pivot->is_admin ?? 'N/A') . "\n";
    }
}

echo "\nPrimary Branch:\n";
$primaryBranch = $user->getPrimaryBranch();
if ($primaryBranch) {
    echo "  ✅ Branch {$primaryBranch->id}: {$primaryBranch->name}\n";
} else {
    echo "  ❌ No primary branch\n";
}

// Check departments
echo "\nDepartments (via department_user):\n";
$departments = $user->departments;
if ($departments->isEmpty()) {
    echo "  ❌ No departments\n";
} else {
    foreach ($departments as $dept) {
        $pivot = $dept->pivot;
        echo "  - Dept {$dept->id}: {$dept->name} (Branch {$dept->branch_id})\n";
        if ($pivot) {
            echo "    Position: " . ($pivot->position ?? 'N/A') . "\n";
            echo "    Is Head: " . ($pivot->is_head ? 'YES' : 'NO') . "\n";
            echo "    Is Deputy: " . ($pivot->is_deputy ? 'YES' : 'NO') . "\n";
        }
    }
}

// Check subordinates
echo "\nSubordinates:\n";
try {
    $subordinates = $user->getAllSubordinates();
    if ($subordinates && $subordinates->count() > 0) {
        foreach ($subordinates as $sub) {
            echo "  - {$sub->name} (ID: {$sub->id})\n";
        }
    } else {
        echo "  (none)\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: {$e->getMessage()}\n";
}

// Test accessibleBy for branch 2
echo "\n\nTesting Customer::accessibleBy() for branch_id=2:\n";
$query = \App\Models\Customer::query()->accessibleBy($user, 2);
$sql = $query->toSql();
$bindings = $query->getBindings();

echo "SQL: {$sql}\n";
echo "Bindings: " . json_encode($bindings) . "\n";
echo "Count: " . $query->count() . "\n";

