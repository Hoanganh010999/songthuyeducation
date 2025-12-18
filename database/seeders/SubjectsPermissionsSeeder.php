<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class SubjectsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Subjects Management
            [
                'module' => 'quality',
                'action' => 'view',
                'name' => 'subjects.view',
                'display_name' => 'Xem Danh sách Môn học',
                'description' => 'Xem danh sách môn học',
                'sort_order' => 10,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'create',
                'name' => 'subjects.create',
                'display_name' => 'Thêm Môn học',
                'description' => 'Tạo môn học mới',
                'sort_order' => 11,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'edit',
                'name' => 'subjects.edit',
                'display_name' => 'Sửa Môn học',
                'description' => 'Chỉnh sửa thông tin môn học',
                'sort_order' => 12,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'delete',
                'name' => 'subjects.delete',
                'display_name' => 'Xóa Môn học',
                'description' => 'Xóa môn học',
                'sort_order' => 13,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'assign',
                'name' => 'subjects.assign_teachers',
                'display_name' => 'Gán Giáo viên vào Môn học',
                'description' => 'Gán và quản lý giáo viên cho môn học',
                'sort_order' => 14,
                'is_active' => true
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
        
        $this->command->info('Subjects permissions seeded successfully!');
    }
}
