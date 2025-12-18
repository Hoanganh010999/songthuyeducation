<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('email', 'lethuy@songthuy.edu.vn')->first();

echo "User: {$user->name} (ID: {$user->id})\n\n";

// Check permission
echo "Checking hasPermission('customers.view')...\n";
try {
    $hasView = $user->hasPermission('customers.view');
    echo "Result: " . ($hasView ? '✅ YES' : '❌ NO') . "\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Check roles and their permissions
echo "User's Roles:\n";
$roles = $user->roles;
if ($roles->isEmpty()) {
    echo "  ❌ No roles\n";
} else {
    foreach ($roles as $role) {
        echo "  - {$role->name} (ID: {$role->id})\n";
        
        $permissions = \DB::table('permission_role')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('permission_role.role_id', $role->id)
            ->where('permissions.name', 'like', 'customers%')
            ->select('permissions.name', 'permissions.is_active')
            ->get();
        
        if ($permissions->isEmpty()) {
            echo "    ❌ No customer permissions\n";
        } else {
            foreach ($permissions as $perm) {
                $status = $perm->is_active ? '✅' : '❌';
                echo "    {$status} {$perm->name}\n";
            }
        }
    }
}

echo "\n\nUser's Department Positions:\n";
$deptUsers = \DB::table('department_user')
    ->join('departments', 'department_user.department_id', '=', 'departments.id')
    ->where('department_user.user_id', $user->id)
    ->select('departments.name', 'departments.branch_id', 'department_user.position', 'department_user.is_head')
    ->get();

foreach ($deptUsers as $du) {
    echo "  - {$du->name} (Branch {$du->branch_id})\n";
    echo "    Position: " . ($du->position ?? 'N/A') . "\n";
    echo "    Is Head: " . ($du->is_head ? 'YES' : 'NO') . "\n";
    
    if ($du->position) {
        $posPermissions = \DB::table('position_role')
            ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('position_role.position_id', $du->position)
            ->where('permissions.name', 'like', 'customers%')
            ->select('permissions.name')
            ->get();
        
        if ($posPermissions->isNotEmpty()) {
            echo "    Permissions via position:\n";
            foreach ($posPermissions as $perm) {
                echo "      - {$perm->name}\n";
            }
        }
    }
}

