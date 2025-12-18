<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UpdateQualityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Update lesson_plans permissions to use syllabus naming
        $lessonPlanPermissions = Permission::where('module', 'lesson_plans')->get();
        
        foreach ($lessonPlanPermissions as $permission) {
            // Create new syllabus permission
            $newName = str_replace('lesson_plans', 'syllabus', $permission->name);
            
            Permission::updateOrCreate(
                ['name' => $newName],
                [
                    'module' => 'syllabus',
                    'action' => $permission->action,
                    'display_name' => str_replace('Lesson Plan', 'Syllabus', $permission->display_name),
                    'description' => str_replace('lesson plan', 'syllabus', $permission->description),
                    'sort_order' => $permission->sort_order,
                    'is_active' => true
                ]
            );
        }
        
        // Add missing permissions
        $missingPermissions = [
            // Subjects - already has view and manage, add individual CRUD
            [
                'module' => 'subjects',
                'action' => 'create',
                'name' => 'subjects.create',
                'display_name' => 'Tạo Môn học',
                'description' => 'Tạo môn học mới',
                'sort_order' => 22,
                'is_active' => true
            ],
            [
                'module' => 'subjects',
                'action' => 'edit',
                'name' => 'subjects.edit',
                'display_name' => 'Sửa Môn học',
                'description' => 'Chỉnh sửa môn học',
                'sort_order' => 23,
                'is_active' => true
            ],
            [
                'module' => 'subjects',
                'action' => 'delete',
                'name' => 'subjects.delete',
                'display_name' => 'Xóa Môn học',
                'description' => 'Xóa môn học',
                'sort_order' => 24,
                'is_active' => true
            ],
            [
                'module' => 'subjects',
                'action' => 'assign_teachers',
                'name' => 'subjects.assign_teachers',
                'display_name' => 'Gán Giáo viên cho Môn học',
                'description' => 'Gán giáo viên và giáo viên chủ nhiệm môn học',
                'sort_order' => 25,
                'is_active' => true
            ],
            
            // Classes - add individual actions
            [
                'module' => 'quality',
                'action' => 'create_class',
                'name' => 'classes.create',
                'display_name' => 'Tạo Lớp học',
                'description' => 'Tạo lớp học mới',
                'sort_order' => 26,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'edit_class',
                'name' => 'classes.edit',
                'display_name' => 'Sửa Lớp học',
                'description' => 'Chỉnh sửa thông tin lớp học',
                'sort_order' => 27,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'delete_class',
                'name' => 'classes.delete',
                'display_name' => 'Xóa Lớp học',
                'description' => 'Xóa lớp học',
                'sort_order' => 28,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'manage_class_students',
                'name' => 'classes.manage_students',
                'display_name' => 'Quản lý Học viên trong Lớp',
                'description' => 'Thêm, sửa, xóa học viên trong lớp',
                'sort_order' => 29,
                'is_active' => true
            ],
            
            // Students CRUD
            [
                'module' => 'quality',
                'action' => 'create_student',
                'name' => 'students.create',
                'display_name' => 'Tạo Học viên',
                'description' => 'Tạo học viên mới',
                'sort_order' => 30,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'edit_student',
                'name' => 'students.edit',
                'display_name' => 'Sửa Học viên',
                'description' => 'Chỉnh sửa thông tin học viên',
                'sort_order' => 31,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'delete_student',
                'name' => 'students.delete',
                'display_name' => 'Xóa Học viên',
                'description' => 'Xóa học viên',
                'sort_order' => 32,
                'is_active' => true
            ],
            
            // Parents CRUD
            [
                'module' => 'quality',
                'action' => 'create_parent',
                'name' => 'parents.create',
                'display_name' => 'Tạo Phụ huynh',
                'description' => 'Tạo phụ huynh mới',
                'sort_order' => 33,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'edit_parent',
                'name' => 'parents.edit',
                'display_name' => 'Sửa Phụ huynh',
                'description' => 'Chỉnh sửa thông tin phụ huynh',
                'sort_order' => 34,
                'is_active' => true
            ],
            [
                'module' => 'quality',
                'action' => 'delete_parent',
                'name' => 'parents.delete',
                'display_name' => 'Xóa Phụ huynh',
                'description' => 'Xóa phụ huynh',
                'sort_order' => 35,
                'is_active' => true
            ],
        ];
        
        foreach ($missingPermissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
        
        $this->command->info('✅ Quality Management permissions updated successfully!');
    }
    
    private function assignPermissionsToRoles(): void
    {
        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $teacher = Role::where('name', 'teacher')->first();
        
        // Super Admin: All permissions
        if ($superAdmin) {
            $allPermissions = Permission::pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
        }
        
        // Admin: All quality, syllabus, subjects permissions
        if ($admin) {
            $adminPermissions = Permission::whereIn('module', ['quality', 'syllabus', 'subjects', 'lesson_plans'])
                ->pluck('id');
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
        }
        
        // Teacher: View permissions only
        if ($teacher) {
            $teacherPermissions = Permission::whereIn('name', [
                // Quality
                'quality.view',
                'students.view_all',
                'parents.view_all',
                'teachers.view',
                'classes.view',
                
                // Syllabus - view and manage materials
                'syllabus.view',
                'syllabus.manage_materials',
                'lesson_plans.view', // Keep old permission for backwards compatibility
                
                // Subjects
                'subjects.view',
            ])->pluck('id');
            $teacher->permissions()->syncWithoutDetaching($teacherPermissions);
        }
    }
}

