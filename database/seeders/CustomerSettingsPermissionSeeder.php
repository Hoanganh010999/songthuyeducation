<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class CustomerSettingsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo permission cho Customer Settings
        $permission = Permission::firstOrCreate(
            ['name' => 'customers.settings'],
            [
                'module' => 'customers',
                'action' => 'settings',
                'description' => 'Quản lý cài đặt khách hàng (loại tương tác, kết quả, nguồn)',
            ]
        );

        echo "✅ Customer Settings Permission created: {$permission->name}\n";
    }
}
