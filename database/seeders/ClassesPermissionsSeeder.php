<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class ClassesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Class Settings Module
            [
                'module' => 'classes',
                'action' => 'manage_settings',
                'name' => 'classes.manage_settings',
                'display_name' => 'Quản lý Thiết lập Lớp học',
                'description' => 'Quản lý năm học, học kỳ, ca học, phòng học, lịch nghỉ',
                'sort_order' => 1,
                'is_active' => true
            ],
            
            // Classes Module
            [
                'module' => 'classes',
                'action' => 'view',
                'name' => 'classes.view',
                'display_name' => 'Xem Lớp học',
                'description' => 'Xem danh sách và thông tin lớp học',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'create',
                'name' => 'classes.create',
                'display_name' => 'Tạo Lớp học',
                'description' => 'Tạo lớp học mới',
                'sort_order' => 3,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'edit',
                'name' => 'classes.edit',
                'display_name' => 'Sửa Lớp học',
                'description' => 'Cập nhật thông tin lớp học',
                'sort_order' => 4,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'delete',
                'name' => 'classes.delete',
                'display_name' => 'Xóa Lớp học',
                'description' => 'Xóa lớp học',
                'sort_order' => 5,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'manage',
                'name' => 'classes.manage',
                'display_name' => 'Quản lý Lớp học',
                'description' => 'Quản lý toàn bộ thông tin lớp học (Admin)',
                'sort_order' => 6,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'manage_schedule',
                'name' => 'classes.manage_schedule',
                'display_name' => 'Quản lý Lịch học',
                'description' => 'Tạo và quản lý lịch học cho lớp',
                'sort_order' => 7,
                'is_active' => true
            ],
            [
                'module' => 'classes',
                'action' => 'update_session',
                'name' => 'classes.update_session',
                'display_name' => 'Cập nhật Buổi học',
                'description' => 'Cập nhật trạng thái và thông tin buổi học',
                'sort_order' => 8,
                'is_active' => true
            ],
            
            // Lesson Plans Module
            [
                'module' => 'lesson_plans',
                'action' => 'view',
                'name' => 'lesson_plans.view',
                'display_name' => 'Xem Giáo án',
                'description' => 'Xem danh sách và nội dung giáo án',
                'sort_order' => 9,
                'is_active' => true
            ],
            [
                'module' => 'lesson_plans',
                'action' => 'create',
                'name' => 'lesson_plans.create',
                'display_name' => 'Tạo Giáo án',
                'description' => 'Tạo giáo án mới',
                'sort_order' => 10,
                'is_active' => true
            ],
            [
                'module' => 'lesson_plans',
                'action' => 'edit',
                'name' => 'lesson_plans.edit',
                'display_name' => 'Sửa Giáo án',
                'description' => 'Cập nhật và chỉnh sửa giáo án',
                'sort_order' => 11,
                'is_active' => true
            ],
            [
                'module' => 'lesson_plans',
                'action' => 'delete',
                'name' => 'lesson_plans.delete',
                'display_name' => 'Xóa Giáo án',
                'description' => 'Xóa giáo án',
                'sort_order' => 12,
                'is_active' => true
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign permissions to super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $permissionIds = Permission::whereIn('module', ['classes', 'lesson_plans'])->pluck('id');
            $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);
            $this->command->info('Assigned classes permissions to super-admin role');
        }

        $this->command->info('Classes permissions seeded successfully!');
    }
}
