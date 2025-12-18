<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo Permissions theo từng Module
        $permissions = [
            // Module: Users (Quản lý người dùng)
            [
                'module' => 'users',
                'actions' => [
                    ['action' => 'view', 'display_name' => 'Xem danh sách người dùng', 'sort_order' => 1],
                    ['action' => 'create', 'display_name' => 'Tạo người dùng mới', 'sort_order' => 2],
                    ['action' => 'edit', 'display_name' => 'Chỉnh sửa người dùng', 'sort_order' => 3],
                    ['action' => 'delete', 'display_name' => 'Xóa người dùng', 'sort_order' => 4],
                    ['action' => 'assign-role', 'display_name' => 'Gán vai trò cho người dùng', 'sort_order' => 5],
                ],
            ],
            // Module: Roles (Quản lý vai trò)
            [
                'module' => 'roles',
                'actions' => [
                    ['action' => 'view', 'display_name' => 'Xem danh sách vai trò', 'sort_order' => 1],
                    ['action' => 'create', 'display_name' => 'Tạo vai trò mới', 'sort_order' => 2],
                    ['action' => 'edit', 'display_name' => 'Chỉnh sửa vai trò', 'sort_order' => 3],
                    ['action' => 'delete', 'display_name' => 'Xóa vai trò', 'sort_order' => 4],
                    ['action' => 'assign-permission', 'display_name' => 'Gán quyền cho vai trò', 'sort_order' => 5],
                ],
            ],
            // Module: Products (Quản lý sản phẩm)
            [
                'module' => 'products',
                'actions' => [
                    ['action' => 'view', 'display_name' => 'Xem danh sách sản phẩm', 'sort_order' => 1],
                    ['action' => 'create', 'display_name' => 'Tạo sản phẩm mới', 'sort_order' => 2],
                    ['action' => 'edit', 'display_name' => 'Chỉnh sửa sản phẩm', 'sort_order' => 3],
                    ['action' => 'delete', 'display_name' => 'Xóa sản phẩm', 'sort_order' => 4],
                ],
            ],
            // Module: Orders (Quản lý đơn hàng)
            [
                'module' => 'orders',
                'actions' => [
                    ['action' => 'view', 'display_name' => 'Xem danh sách đơn hàng', 'sort_order' => 1],
                    ['action' => 'create', 'display_name' => 'Tạo đơn hàng mới', 'sort_order' => 2],
                    ['action' => 'edit', 'display_name' => 'Chỉnh sửa đơn hàng', 'sort_order' => 3],
                    ['action' => 'delete', 'display_name' => 'Xóa đơn hàng', 'sort_order' => 4],
                    ['action' => 'approve', 'display_name' => 'Duyệt đơn hàng', 'sort_order' => 5],
                ],
            ],
            // Module: Reports (Báo cáo)
            [
                'module' => 'reports',
                'actions' => [
                    ['action' => 'view', 'display_name' => 'Xem báo cáo', 'sort_order' => 1],
                    ['action' => 'export', 'display_name' => 'Xuất báo cáo', 'sort_order' => 2],
                ],
            ],
        ];

        // Tạo permissions
        $createdPermissions = [];
        foreach ($permissions as $moduleData) {
            $module = $moduleData['module'];
            foreach ($moduleData['actions'] as $actionData) {
                $permissionName = Permission::makeName($module, $actionData['action']);
                
                $permission = Permission::create([
                    'module' => $module,
                    'action' => $actionData['action'],
                    'name' => $permissionName,
                    'display_name' => $actionData['display_name'],
                    'description' => "Quyền {$actionData['display_name']} trong module {$module}",
                    'sort_order' => $actionData['sort_order'],
                    'is_active' => true,
                ]);

                $createdPermissions[$permissionName] = $permission;
            }
        }

        // Tạo Roles
        $superAdmin = Role::create([
            'name' => 'super-admin',
            'display_name' => 'Super Admin',
            'description' => 'Quản trị viên cấp cao - có toàn quyền truy cập',
            'is_active' => true,
        ]);

        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Quản trị viên - quản lý hệ thống',
            'is_active' => true,
        ]);

        $manager = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Quản lý - quản lý sản phẩm và đơn hàng',
            'is_active' => true,
        ]);

        $staff = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Nhân viên - xử lý đơn hàng',
            'is_active' => true,
        ]);

        $user = Role::create([
            'name' => 'user',
            'display_name' => 'User',
            'description' => 'Người dùng - quyền cơ bản',
            'is_active' => true,
        ]);

        $parent = Role::create([
            'name' => 'parent',
            'display_name' => 'Parent',
            'description' => 'Phụ huynh - xem kết quả học tập của con',
            'is_active' => true,
        ]);

        $student = Role::create([
            'name' => 'student',
            'display_name' => 'Student',
            'description' => 'Học viên - truy cập lớp học và nội dung học tập',
            'is_active' => true,
        ]);

        // Gán quyền cho Super Admin (tất cả quyền)
        $superAdmin->permissions()->attach(Permission::pluck('id'));

        // Gán quyền cho Admin (tất cả trừ quản lý roles)
        $adminPermissions = Permission::where('module', '!=', 'roles')->pluck('id');
        $admin->permissions()->attach($adminPermissions);

        // Gán quyền cho Manager (products, orders, reports)
        $managerPermissions = Permission::whereIn('module', ['products', 'orders', 'reports'])
            ->pluck('id');
        $manager->permissions()->attach($managerPermissions);

        // Gán quyền cho Staff (chỉ xem và xử lý orders)
        $staffPermissions = Permission::where('module', 'orders')
            ->whereIn('action', ['view', 'edit'])
            ->pluck('id');
        $staff->permissions()->attach($staffPermissions);

        // Gán quyền cho User (chỉ xem products)
        $userPermissions = Permission::where('module', 'products')
            ->where('action', 'view')
            ->pluck('id');
        $user->permissions()->attach($userPermissions);

        $this->command->info('✅ Đã tạo ' . Permission::count() . ' permissions');
        $this->command->info('✅ Đã tạo ' . Role::count() . ' roles');
        $this->command->info('✅ Đã gán quyền cho các roles');
    }
}
