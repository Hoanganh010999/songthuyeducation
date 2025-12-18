<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CoursePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'module' => 'course',
                'action' => 'view',
                'name' => 'course.view',
                'display_name' => 'Xem Course Management',
                'description' => 'Xem module Course (Classroom Board, Learning History)',
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'post',
                'name' => 'course.post',
                'display_name' => 'Đăng bài trong Classroom',
                'description' => 'Tạo và đăng bài thường trong Classroom Board',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'create_event',
                'name' => 'course.create_event',
                'display_name' => 'Tạo Event',
                'description' => 'Tạo sự kiện trong Classroom Board',
                'sort_order' => 3,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'create_homework',
                'name' => 'course.create_homework',
                'display_name' => 'Tạo Homework',
                'description' => 'Tạo bài tập về nhà trong Classroom Board',
                'sort_order' => 4,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'manage_assignments',
                'name' => 'course.manage_assignments',
                'display_name' => 'Quản lý Bài tập',
                'description' => 'Tạo, sửa, xóa bài tập và chấm điểm',
                'sort_order' => 5,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'view_zalo_chat',
                'name' => 'course.view_zalo_chat',
                'display_name' => 'Xem Zalo Chat lớp học',
                'description' => 'Xem và tương tác với Zalo Group Chat của lớp học trong Classroom Board',
                'sort_order' => 6,
                'is_active' => true
            ],
            [
                'module' => 'course',
                'action' => 'manage',
                'name' => 'course.manage',
                'display_name' => 'Quản lý Course',
                'description' => 'Quản lý toàn bộ module Course',
                'sort_order' => 7,
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

        $this->command->info('✅ Course permissions seeded successfully!');
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin: All permissions
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($allPermissions);
        }

        // Admin: All course permissions
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $coursePermissions = Permission::where('module', 'course')->pluck('id');
            $admin->permissions()->syncWithoutDetaching($coursePermissions);
        }

        // Teachers: View, post, create events, create homework, manage assignments, and view zalo chat
        $teacher = Role::where('name', 'teacher')->first();
        if ($teacher) {
            $teacherPermissions = Permission::whereIn('name', [
                'course.view',
                'course.post',
                'course.create_event',
                'course.create_homework',
                'course.manage_assignments',
                'course.view_zalo_chat',
            ])->pluck('id');
            $teacher->permissions()->syncWithoutDetaching($teacherPermissions);
        }

        // Students: View only
        $student = Role::where('name', 'student')->first();
        if ($student) {
            $studentPermissions = Permission::where('name', 'course.view')->pluck('id');
            $student->permissions()->syncWithoutDetaching($studentPermissions);
        }

        // Parents: View only
        $parent = Role::where('name', 'parent')->first();
        if ($parent) {
            $parentPermissions = Permission::where('name', 'course.view')->pluck('id');
            $parent->permissions()->syncWithoutDetaching($parentPermissions);
        }
    }
}
