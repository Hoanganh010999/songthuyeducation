<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$email = 'lethuy@songthuy.edu.vn';

$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found: {$email}\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n";
echo "Branch ID: {$user->branch_id}\n";
echo "\nRoles:\n";
foreach ($user->roles as $role) {
    echo "  - {$role->name}\n";
}

echo "\nPermissions:\n";
$permissions = $user->getAllPermissions();
foreach ($permissions as $permission) {
    echo "  - {$permission->name}\n";
}

echo "\n\nChecking customer-related permissions:\n";
$customerPermissions = $permissions->filter(function($p) {
    return str_contains($p->name, 'customer') || str_contains($p->name, 'sale');
});

if ($customerPermissions->isEmpty()) {
    echo "❌ NO customer/sale permissions found!\n";
} else {
    foreach ($customerPermissions as $perm) {
        echo "  ✅ {$perm->name}\n";
    }
}

