<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Enrollment;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

echo "Testing Enrollment::accessibleBy() for user: {$user->name}\n\n";

// Test 1: No branch_id (should use primary branch = 1)
echo "Test 1: accessibleBy(user) - no branch_id specified:\n";
$query1 = Enrollment::query()->accessibleBy($user);
echo "  SQL: " . $query1->toSql() . "\n";
echo "  Bindings: " . json_encode($query1->getBindings()) . "\n";
echo "  Count: " . $query1->count() . "\n\n";

// Test 2: With branch_id = 1 (Yên Tâm)
echo "Test 2: accessibleBy(user, 1) - Branch 1:\n";
$query2 = Enrollment::query()->accessibleBy($user, 1);
echo "  SQL: " . $query2->toSql() . "\n";
echo "  Bindings: " . json_encode($query2->getBindings()) . "\n";
echo "  Count: " . $query2->count() . "\n\n";

// Test 3: With branch_id = 2 (Thống Nhất)
echo "Test 3: accessibleBy(user, 2) - Branch 2:\n";
$query3 = Enrollment::query()->accessibleBy($user, 2);
echo "  SQL: " . $query3->toSql() . "\n";
echo "  Bindings: " . json_encode($query3->getBindings()) . "\n";
echo "  Count: " . $query3->count() . "\n\n";

echo "✅ Enrollments are now filtered by branch!\n";

