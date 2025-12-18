<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

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
    $permission = Permission::create(['name' => 'customers.view_all', 'guard_name' => 'web']);
    echo "✅ Permission created!\n\n";
}

// Grant permission
if ($user->hasPermissionTo('customers.view_all')) {
    echo "✅ User already has 'customers.view_all' permission\n";
} else {
    echo "Granting 'customers.view_all' permission...\n";
    $user->givePermissionTo('customers.view_all');
    echo "✅ Permission granted!\n\n";
    
    echo "User now has all customer permissions:\n";
    foreach ($user->getAllPermissions()->filter(fn($p) => str_contains($p->name, 'customer')) as $perm) {
        echo "  ✅ {$perm->name}\n";
    }
}

