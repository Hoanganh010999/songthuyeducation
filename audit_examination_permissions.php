<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;

echo "=== EXAMINATION MODULE PERMISSIONS AUDIT ===\n\n";

// Get all examination permissions from DB
$permissions = Permission::where('module', 'examination')
    ->where('is_active', true)
    ->orderBy('name')
    ->get();

echo "📋 Total Permissions: " . $permissions->count() . "\n\n";

// Group by action/category
$grouped = [];
foreach ($permissions as $perm) {
    $parts = explode('.', $perm->name);
    $category = $parts[1] ?? 'other';
    $action = $parts[2] ?? 'general';
    
    if (!isset($grouped[$category])) {
        $grouped[$category] = [];
    }
    $grouped[$category][] = [
        'name' => $perm->name,
        'display' => $perm->display_name,
        'action' => $action
    ];
}

// Display grouped permissions
foreach ($grouped as $category => $perms) {
    echo "📁 Category: examination.{$category}\n";
    echo str_repeat('-', 60) . "\n";
    foreach ($perms as $perm) {
        echo "   ✓ {$perm['name']}\n";
        echo "     → {$perm['display']}\n";
    }
    echo "\n";
}

// Check which permissions are assigned to roles
echo "\n🔐 PERMISSION ASSIGNMENTS TO ROLES:\n";
echo str_repeat('=', 60) . "\n\n";

$roles = \App\Models\Role::whereIn('name', ['admin', 'teacher', 'student'])->get();

foreach ($roles as $role) {
    echo "👥 Role: {$role->name}\n";
    $rolePerms = $role->permissions()
        ->where('module', 'examination')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    
    if ($rolePerms->count() > 0) {
        foreach ($rolePerms as $perm) {
            echo "   ✓ {$perm->name}\n";
        }
    } else {
        echo "   (no examination permissions)\n";
    }
    echo "\n";
}

