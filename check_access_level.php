<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ModuleDepartmentSetting;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "Checking access level for module 'customers':\n\n";

// Check for both branches
foreach ([1, 2] as $branchId) {
    $accessLevel = ModuleDepartmentSetting::getUserAccessLevel('customers', $branchId, $user);
    
    echo "Branch {$branchId}:\n";
    echo "  Access Level: {$accessLevel}\n";
    
    // Get department settings
    $settings = ModuleDepartmentSetting::where('module_name', 'customers')
        ->where('branch_id', $branchId)
        ->get();
    
    echo "  Department settings count: {$settings->count()}\n";
    foreach ($settings as $setting) {
        $dept = $setting->department;
        echo "    - Dept {$setting->department_id} ({$dept->name}): responsible={$setting->is_responsible}\n";
    }
    
    echo "\n";
}

echo "User departments:\n";
$userDepts = $user->departments;
if ($userDepts->isEmpty()) {
    echo "  ❌ NO departments assigned!\n";
} else {
    foreach ($userDepts as $dept) {
        echo "  - Dept {$dept->id}: {$dept->name} (Branch {$dept->branch_id})\n";
        
        // Check position
        $pivot = $dept->pivot;
        if ($pivot) {
            echo "    Position: {$pivot->position}, Is Head: " . ($pivot->is_head ? 'YES' : 'NO') . "\n";
        }
    }
}

