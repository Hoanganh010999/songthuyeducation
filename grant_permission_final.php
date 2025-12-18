<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "User: {$user->name}\n\n";

// Check if permission exists
$permission = Permission::where('name', 'customers.view_all')->first();

if (!$permission) {
    echo "Creating permission 'customers.view_all'...\n";
    $permission = Permission::create([
        'name' => 'customers.view_all',
        'module' => 'customers',
        'action' => 'view_all',
        'display_name' => 'Xem tất cả khách hàng',
        'description' => 'View all customers across all branches',
        'sort_order' => 100,
    ]);
    echo "✅ Permission created! (ID: {$permission->id})\n\n";
} else {
    echo "✅ Permission exists (ID: {$permission->id})\n\n";
}

// Get user's roles
$userRoles = $user->roles;
echo "User roles:\n";
if ($userRoles->isEmpty()) {
    echo "  ❌ NO roles assigned!\n\n";
    
    // Assign admin role
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        \DB::table('role_user')->insert([
            'role_id' => $adminRole->id,
            'user_id' => $user->id,
        ]);
        echo "  ✅ Assigned 'admin' role\n\n";
        $userRoles = collect([$adminRole]);
    }
} else {
    foreach ($userRoles as $role) {
        echo "  - {$role->name} (ID: {$role->id})\n";
    }
    echo "\n";
}

// Grant permission to user's roles
foreach ($userRoles as $role) {
    $exists = \DB::table('permission_role')
        ->where('permission_id', $permission->id)
        ->where('role_id', $role->id)
        ->exists();
    
    if (!$exists) {
        \DB::table('permission_role')->insert([
            'permission_id' => $permission->id,
            'role_id' => $role->id,
        ]);
        echo "✅ Added permission to role '{$role->name}'\n";
    } else {
        echo "✓ Role '{$role->name}' already has this permission\n";
    }
}

// Clear cache
\Artisan::call('cache:clear');
echo "\n✅ Cache cleared!\n";
echo "\n🎉 Done! User can now access customers in ALL branches!\n";

