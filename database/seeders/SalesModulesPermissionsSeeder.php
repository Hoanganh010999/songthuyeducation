<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesModulesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'products',
                'name' => 'Products',
                'description' => 'Quản lý sản phẩm/khóa học',
                'icon' => 'box',
                'is_active' => true,
                'permissions' => [
                    ['key' => 'view', 'name' => 'Xem Sản Phẩm', 'description' => 'Xem danh sách sản phẩm'],
                    ['key' => 'create', 'name' => 'Tạo Sản Phẩm', 'description' => 'Tạo sản phẩm mới'],
                    ['key' => 'edit', 'name' => 'Sửa Sản Phẩm', 'description' => 'Chỉnh sửa sản phẩm'],
                    ['key' => 'delete', 'name' => 'Xóa Sản Phẩm', 'description' => 'Xóa sản phẩm'],
                ]
            ],
            [
                'key' => 'vouchers',
                'name' => 'Vouchers',
                'description' => 'Quản lý mã giảm giá',
                'icon' => 'ticket',
                'is_active' => true,
                'permissions' => [
                    ['key' => 'view', 'name' => 'Xem Voucher', 'description' => 'Xem danh sách voucher'],
                    ['key' => 'create', 'name' => 'Tạo Voucher', 'description' => 'Tạo voucher mới'],
                    ['key' => 'edit', 'name' => 'Sửa Voucher', 'description' => 'Chỉnh sửa voucher'],
                    ['key' => 'delete', 'name' => 'Xóa Voucher', 'description' => 'Xóa voucher'],
                ]
            ],
            [
                'key' => 'campaigns',
                'name' => 'Campaigns',
                'description' => 'Quản lý chiến dịch khuyến mãi',
                'icon' => 'megaphone',
                'is_active' => true,
                'permissions' => [
                    ['key' => 'view', 'name' => 'Xem Chiến Dịch', 'description' => 'Xem danh sách chiến dịch'],
                    ['key' => 'create', 'name' => 'Tạo Chiến Dịch', 'description' => 'Tạo chiến dịch mới'],
                    ['key' => 'edit', 'name' => 'Sửa Chiến Dịch', 'description' => 'Chỉnh sửa chiến dịch'],
                    ['key' => 'delete', 'name' => 'Xóa Chiến Dịch', 'description' => 'Xóa chiến dịch'],
                ]
            ],
            [
                'key' => 'enrollments',
                'name' => 'Enrollments',
                'description' => 'Quản lý đơn đăng ký khóa học',
                'icon' => 'clipboard-check',
                'is_active' => true,
                'permissions' => [
                    ['key' => 'view', 'name' => 'Xem Đơn Đăng Ký', 'description' => 'Xem danh sách đơn đăng ký'],
                    ['key' => 'create', 'name' => 'Tạo Đơn Đăng Ký', 'description' => 'Tạo đơn đăng ký mới (Chốt đơn)'],
                    ['key' => 'edit', 'name' => 'Sửa Đơn Đăng Ký', 'description' => 'Chỉnh sửa & xác nhận thanh toán'],
                    ['key' => 'delete', 'name' => 'Hủy Đơn Đăng Ký', 'description' => 'Hủy đơn đăng ký'],
                ]
            ],
            [
                'key' => 'wallets',
                'name' => 'Wallets',
                'description' => 'Quản lý ví tiền',
                'icon' => 'wallet',
                'is_active' => true,
                'permissions' => [
                    ['key' => 'view', 'name' => 'Xem Ví', 'description' => 'Xem thông tin ví & giao dịch'],
                    ['key' => 'edit', 'name' => 'Quản Lý Ví', 'description' => 'Khóa/Mở khóa ví'],
                ]
            ],
        ];

        foreach ($modules as $moduleData) {
            // Create or update module
            $module = Module::updateOrCreate(
                ['key' => $moduleData['key']],
                [
                    'name' => $moduleData['name'],
                    'description' => $moduleData['description'],
                    'icon' => $moduleData['icon'],
                    'is_active' => $moduleData['is_active'],
                ]
            );

            // Create permissions
            foreach ($moduleData['permissions'] as $permData) {
                $permission = Permission::firstOrCreate(
                    ['key' => "{$moduleData['key']}.{$permData['key']}"],
                    [
                        'module_id' => $module->id,
                        'name' => $permData['name'],
                        'description' => $permData['description'],
                    ]
                );

                $this->command->info("✓ Permission: {$permission->key}");
            }
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info("\n✅ Sales modules permissions seeded successfully!");
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin: All permissions
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::whereHas('module', function ($q) {
                $q->whereIn('key', ['products', 'vouchers', 'campaigns', 'enrollments', 'wallets']);
            })->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
            $this->command->info("✓ Super Admin: All sales permissions");
        }

        // Admin: All except wallet edit
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = Permission::whereHas('module', function ($q) {
                $q->whereIn('key', ['products', 'vouchers', 'campaigns', 'enrollments', 'wallets']);
            })
            ->where('key', '!=', 'wallets.edit')
            ->pluck('id');
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
            $this->command->info("✓ Admin: Sales permissions (except wallet lock)");
        }

        // Manager: View all, create/edit enrollments
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $managerPermissions = Permission::whereIn('key', [
                'products.view',
                'vouchers.view',
                'campaigns.view',
                'enrollments.view',
                'enrollments.create',
                'enrollments.edit',
                'wallets.view',
            ])->pluck('id');
            $manager->permissions()->syncWithoutDetaching($managerPermissions);
            $this->command->info("✓ Manager: View & create enrollments");
        }
    }
}

