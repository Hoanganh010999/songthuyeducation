<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class QualityManagementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Quality Management Module
            [
                'module' => 'quality',
                'action' => 'view',
                'name' => 'quality.view',
                'display_name' => 'Xem Quản lý Chất lượng',
                'description' => 'Xem module Quản lý Chất lượng',
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage',
                'name' => 'quality.manage',
                'display_name' => 'Quản lý Chất lượng',
                'description' => 'Quản lý toàn bộ module Chất lượng',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_settings',
                'name' => 'quality.manage_settings',
                'display_name' => 'Quản lý Cài đặt Chất lượng',
                'description' => 'Quản lý cài đặt chính sách học phí, điểm danh và các thiết lập khác',
                'sort_order' => 3,
                'is_active' => true
            ],
            
            // Students Management
            [
                'module' => 'quality',
                'action' => 'view_all_students',
                'name' => 'students.view_all',
                'display_name' => 'Xem Toàn bộ Danh sách Học viên',
                'description' => 'Xem tất cả học viên trong hệ thống',
                'sort_order' => 4,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_students',
                'name' => 'students.manage',
                'display_name' => 'Quản lý Học viên',
                'description' => 'Tạo, sửa, xóa học viên',
                'sort_order' => 5,
                'is_active' => true
            ],
            
            // Parents Management
            [
                'module' => 'quality',
                'action' => 'view_all_parents',
                'name' => 'parents.view_all',
                'display_name' => 'Xem Toàn bộ Danh sách Phụ huynh',
                'description' => 'Xem tất cả phụ huynh trong hệ thống',
                'sort_order' => 6,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_parents',
                'name' => 'parents.manage',
                'display_name' => 'Quản lý Phụ huynh',
                'description' => 'Tạo, sửa, xóa phụ huynh',
                'sort_order' => 7,
                'is_active' => true
            ],
            
            // Teachers Management
            [
                'module' => 'quality',
                'action' => 'view',
                'name' => 'teachers.view',
                'display_name' => 'Xem Danh sách Giáo viên',
                'description' => 'Xem danh sách giáo viên trong module Chất lượng',
                'sort_order' => 8,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'create',
                'name' => 'teachers.create',
                'display_name' => 'Thêm Giáo viên',
                'description' => 'Thêm giáo viên mới vào danh sách',
                'sort_order' => 9,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'edit',
                'name' => 'teachers.edit',
                'display_name' => 'Sửa Giáo viên',
                'description' => 'Chỉnh sửa thông tin giáo viên',
                'sort_order' => 10,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'delete',
                'name' => 'teachers.delete',
                'display_name' => 'Xóa Giáo viên',
                'description' => 'Xóa giáo viên khỏi danh sách',
                'sort_order' => 11,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'settings',
                'name' => 'teachers.settings',
                'display_name' => 'Thiết lập Mã vị trí Giáo viên',
                'description' => 'Thiết lập mã vị trí để lọc danh sách giáo viên',
                'sort_order' => 12,
                'is_active' => true
            ],
            
            // Classes Management
            [
                'module' => 'quality',
                'action' => 'view_classes',
                'name' => 'classes.view',
                'display_name' => 'Xem Danh sách Lớp học',
                'description' => 'Xem danh sách lớp học',
                'sort_order' => 13,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_classes',
                'name' => 'classes.manage',
                'display_name' => 'Quản lý Lớp học',
                'description' => 'Tạo, sửa, xóa lớp học',
                'sort_order' => 14,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'quick_attendance',
                'name' => 'attendance.quick_mark',
                'display_name' => 'Điểm danh nhanh',
                'description' => 'Sử dụng chức năng điểm danh nhanh cho buổi học',
                'sort_order' => 15,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_comments',
                'name' => 'class_comments.manage',
                'display_name' => 'Nhận xét học viên',
                'description' => 'Thêm/Sửa nhận xét cho học viên sau buổi học',
                'sort_order' => 16,
                'is_active' => true
            ],
            
            // Syllabus Management
            [
                'module' => 'syllabus',
                'action' => 'view',
                'name' => 'syllabus.view',
                'display_name' => 'Xem Giáo án',
                'description' => 'Xem danh sách và chi tiết giáo án',
                'sort_order' => 17,
                'is_active' => true
            ],
            [
                'module' => 'syllabus',
                'action' => 'create',
                'name' => 'syllabus.create',
                'display_name' => 'Tạo Giáo án',
                'description' => 'Tạo giáo án mới',
                'sort_order' => 18,
                'is_active' => true
            ],
            [
                'module' => 'syllabus',
                'action' => 'edit',
                'name' => 'syllabus.edit',
                'display_name' => 'Sửa Giáo án',
                'description' => 'Chỉnh sửa giáo án',
                'sort_order' => 19,
                'is_active' => true
            ],
            [
                'module' => 'syllabus',
                'action' => 'delete',
                'name' => 'syllabus.delete',
                'display_name' => 'Xóa Giáo án',
                'description' => 'Xóa giáo án',
                'sort_order' => 20,
                'is_active' => true
            ],
            [
                'module' => 'syllabus',
                'action' => 'manage_materials',
                'name' => 'syllabus.manage_materials',
                'display_name' => 'Quản lý Tài liệu Giáo án',
                'description' => 'Upload và quản lý tài liệu, bài giảng, homework trong giáo án',
                'sort_order' => 21,
                'is_active' => true
            ],
            [
                'module' => 'syllabus',
                'action' => 'manage',
                'name' => 'syllabus.manage',
                'display_name' => 'Quản lý toàn bộ Syllabus',
                'description' => 'Quản lý toàn bộ module Syllabus Management',
                'sort_order' => 22,
                'is_active' => true
            ],
            
            // Subjects Management
            [
                'module' => 'subjects',
                'action' => 'view',
                'name' => 'subjects.view',
                'display_name' => 'Xem Môn học',
                'description' => 'Xem danh sách môn học',
                'sort_order' => 23,
                'is_active' => true
            ],
            [
                'module' => 'subjects',
                'action' => 'manage',
                'name' => 'subjects.manage',
                'display_name' => 'Quản lý Môn học',
                'description' => 'Tạo, sửa, xóa môn học',
                'sort_order' => 24,
                'is_active' => true
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
        
        // Assign to roles
        $this->assignPermissionsToRoles();
        
        $this->command->info('Quality Management permissions seeded successfully!');
    }
    
    private function assignPermissionsToRoles(): void
    {
        $superAdmin = \App\Models\Role::where('name', 'super-admin')->first();
        $admin = \App\Models\Role::where('name', 'admin')->first();
        $teacher = \App\Models\Role::where('name', 'teacher')->first();
        
        // Super Admin: All permissions
        if ($superAdmin) {
            $allPermissions = Permission::pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
        }
        
        // Admin: All quality and syllabus permissions
        if ($admin) {
            $adminPermissions = Permission::whereIn('module', ['quality', 'syllabus', 'subjects'])
                ->pluck('id');
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
        }
        
        // Teacher: View only permissions + attendance and comments
        if ($teacher) {
            $teacherPermissions = Permission::whereIn('name', [
                'quality.view',
                'students.view_all',
                'parents.view_all',
                'teachers.view',
                'classes.view',
                'syllabus.view',
                'syllabus.manage_materials', // Teachers can upload materials
                'subjects.view',
                'attendance.quick_mark', // Teachers can mark attendance
                'class_comments.manage', // Teachers can add comments
            ])->pluck('id');
            $teacher->permissions()->syncWithoutDetaching($teacherPermissions);
        }
    }
}
