<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ModuleDepartmentSetting;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();
$branchId = 2;

echo "Testing access for User: {$user->name}\n";
echo "Branch ID: {$branchId}\n\n";

// Check super admin
echo "Is Super Admin: " . ($user->is_super_admin ? 'YES' : 'NO') . "\n";
echo "Has role super-admin: " . ($user->hasRole('super-admin') ? 'YES' : 'NO') . "\n";

// Check permission
echo "\nChecking hasPermission('customers.view_all')...\n";
try {
    $hasPermission = $user->hasPermission('customers.view_all');
    echo "Result: " . ($hasPermission ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check access level
echo "\nChecking ModuleDepartmentSetting::getUserAccessLevel('customers', {$branchId}, user)...\n";
try {
    $accessLevel = ModuleDepartmentSetting::getUserAccessLevel('customers', $branchId, $user);
    echo "Access Level: {$accessLevel}\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Test actual query
echo "\n\nTesting Customer::accessibleBy()...\n";
try {
    $query = \App\Models\Customer::query()->accessibleBy($user, $branchId);
    $sql = $query->toSql();
    $bindings = $query->getBindings();
    echo "SQL: {$sql}\n";
    echo "Bindings: " . json_encode($bindings) . "\n";
    
    $count = $query->count();
    echo "Count: {$count}\n";
    
    if ($count > 0) {
        echo "✅ Query would return {$count} customers\n";
    } else {
        echo "⚠️ Query returns 0 customers - access might be blocked\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

