<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Permission;

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
    $permission = Permission::create(['name' => 'customers.view_all']);
    echo "✅ Permission created!\n\n";
}

// Grant permission via model_has_permissions table
$exists = \DB::table('model_has_permissions')
    ->where('permission_id', $permission->id)
    ->where('model_id', $user->id)
    ->where('model_type', 'App\\Models\\User')
    ->exists();

if ($exists) {
    echo "✅ User already has this permission\n";
} else {
    \DB::table('model_has_permissions')->insert([
        'permission_id' => $permission->id,
        'model_id' => $user->id,
        'model_type' => 'App\\Models\\User',
    ]);
    
    echo "✅ Permission 'customers.view_all' granted to {$user->name}!\n";
}

// Clear cache
\Artisan::call('cache:clear');
echo "\n✅ Cache cleared!\n";

