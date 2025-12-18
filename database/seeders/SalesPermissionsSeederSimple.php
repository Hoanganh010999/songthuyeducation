<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesPermissionsSeederSimple extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Products
            ['module' => 'products', 'action' => 'view', 'name' => 'Xem Sản Phẩm'],
            ['module' => 'products', 'action' => 'create', 'name' => 'Tạo Sản Phẩm'],
            ['module' => 'products', 'action' => 'edit', 'name' => 'Sửa Sản Phẩm'],
            ['module' => 'products', 'action' => 'delete', 'name' => 'Xóa Sản Phẩm'],
            
            // Vouchers
            ['module' => 'vouchers', 'action' => 'view', 'name' => 'Xem Voucher'],
            ['module' => 'vouchers', 'action' => 'create', 'name' => 'Tạo Voucher'],
            ['module' => 'vouchers', 'action' => 'edit', 'name' => 'Sửa Voucher'],
            ['module' => 'vouchers', 'action' => 'delete', 'name' => 'Xóa Voucher'],
            
            // Campaigns
            ['module' => 'campaigns', 'action' => 'view', 'name' => 'Xem Chiến Dịch'],
            ['module' => 'campaigns', 'action' => 'create', 'name' => 'Tạo Chiến Dịch'],
            ['module' => 'campaigns', 'action' => 'edit', 'name' => 'Sửa Chiến Dịch'],
            ['module' => 'campaigns', 'action' => 'delete', 'name' => 'Xóa Chiến Dịch'],
            
            // Enrollments
            ['module' => 'enrollments', 'action' => 'view', 'name' => 'Xem Đơn Đăng Ký'],
            ['module' => 'enrollments', 'action' => 'create', 'name' => 'Tạo Đơn Đăng Ký'],
            ['module' => 'enrollments', 'action' => 'edit', 'name' => 'Sửa Đơn Đăng Ký'],
            ['module' => 'enrollments', 'action' => 'delete', 'name' => 'Hủy Đơn Đăng Ký'],
            
            // Wallets
            ['module' => 'wallets', 'action' => 'view', 'name' => 'Xem Ví'],
            ['module' => 'wallets', 'action' => 'edit', 'name' => 'Quản Lý Ví'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['module' => $permData['module'], 'action' => $permData['action']],
                ['name' => $permData['name'], 'display_name' => $permData['name']]
            );
            $this->command->info("✓ Permission: {$permData['module']}.{$permData['action']}");
        }

        // Assign to roles
        $this->assignPermissionsToRoles();
        
        $this->command->info("\n✅ Sales permissions seeded successfully!");
    }

    private function assignPermissionsToRoles(): void
    {
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::whereIn('module', ['products', 'vouchers', 'campaigns', 'enrollments', 'wallets'])
                ->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
            $this->command->info("✓ Super Admin: All sales permissions");
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = Permission::whereIn('module', ['products', 'vouchers', 'campaigns', 'enrollments'])
                ->orWhere(function($q) {
                    $q->where('module', 'wallets')->where('action', 'view');
                })
                ->pluck('id');
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
            $this->command->info("✓ Admin: Sales permissions");
        }

        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $managerPermissions = Permission::where(function($q) {
                    $q->whereIn('module', ['products', 'vouchers', 'campaigns', 'wallets'])->where('action', 'view');
                })
                ->orWhere(function($q) {
                    $q->where('module', 'enrollments')->whereIn('action', ['view', 'create', 'edit']);
                })
                ->pluck('id');
            $manager->permissions()->syncWithoutDetaching($managerPermissions);
            $this->command->info("✓ Manager: View & create enrollments");
        }
    }
}

