<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

class RestoreEssentialDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🔄 Restoring essential data...');
        
        // 1. Permissions
        $this->createPermissions();
        
        // 2. Languages
        $this->createLanguages();
        
        // 3. Basic Settings
        $this->createSettings();
        
        $this->command->info('✅ Essential data restored!');
    }
    
    private function createPermissions()
    {
        $this->command->info('Creating permissions...');
        
        $permissions = [
            // User Management
            ['module' => 'users', 'action' => 'view', 'name' => 'view-users', 'display_name' => 'Xem người dùng', 'description' => 'Xem danh sách người dùng'],
            ['module' => 'users', 'action' => 'create', 'name' => 'create-users', 'display_name' => 'Tạo người dùng', 'description' => 'Tạo người dùng mới'],
            ['module' => 'users', 'action' => 'edit', 'name' => 'edit-users', 'display_name' => 'Sửa người dùng', 'description' => 'Chỉnh sửa người dùng'],
            ['module' => 'users', 'action' => 'delete', 'name' => 'delete-users', 'display_name' => 'Xóa người dùng', 'description' => 'Xóa người dùng'],
            
            // Student Management
            ['module' => 'students', 'action' => 'view', 'name' => 'view-students', 'display_name' => 'Xem học viên', 'description' => 'Xem danh sách học viên'],
            ['module' => 'students', 'action' => 'create', 'name' => 'create-students', 'display_name' => 'Thêm học viên', 'description' => 'Thêm học viên mới'],
            ['module' => 'students', 'action' => 'edit', 'name' => 'edit-students', 'display_name' => 'Sửa học viên', 'description' => 'Chỉnh sửa học viên'],
            ['module' => 'students', 'action' => 'delete', 'name' => 'delete-students', 'display_name' => 'Xóa học viên', 'description' => 'Xóa học viên'],
            
            // Class Management
            ['module' => 'classes', 'action' => 'view', 'name' => 'view-classes', 'display_name' => 'Xem lớp học', 'description' => 'Xem danh sách lớp học'],
            ['module' => 'classes', 'action' => 'create', 'name' => 'create-classes', 'display_name' => 'Tạo lớp học', 'description' => 'Tạo lớp học mới'],
            ['module' => 'classes', 'action' => 'edit', 'name' => 'edit-classes', 'display_name' => 'Sửa lớp học', 'description' => 'Chỉnh sửa lớp học'],
            ['module' => 'classes', 'action' => 'delete', 'name' => 'delete-classes', 'display_name' => 'Xóa lớp học', 'description' => 'Xóa lớp học'],
            
            // Attendance
            ['module' => 'attendance', 'action' => 'view', 'name' => 'view-attendance', 'display_name' => 'Xem điểm danh', 'description' => 'Xem điểm danh'],
            ['module' => 'attendance', 'action' => 'mark', 'name' => 'mark-attendance', 'display_name' => 'Điểm danh', 'description' => 'Điểm danh học viên'],
            ['module' => 'attendance', 'action' => 'edit', 'name' => 'edit-attendance', 'display_name' => 'Sửa điểm danh', 'description' => 'Sửa điểm danh'],
            
            // Enrollment
            ['module' => 'enrollments', 'action' => 'view', 'name' => 'view-enrollments', 'display_name' => 'Xem đăng ký', 'description' => 'Xem danh sách đăng ký'],
            ['module' => 'enrollments', 'action' => 'create', 'name' => 'create-enrollments', 'display_name' => 'Tạo đăng ký', 'description' => 'Tạo đăng ký mới'],
            ['module' => 'enrollments', 'action' => 'edit', 'name' => 'edit-enrollments', 'display_name' => 'Sửa đăng ký', 'description' => 'Chỉnh sửa đăng ký'],
            ['module' => 'enrollments', 'action' => 'delete', 'name' => 'delete-enrollments', 'display_name' => 'Xóa đăng ký', 'description' => 'Xóa đăng ký'],
            
            // Financial
            ['module' => 'financial', 'action' => 'view', 'name' => 'view-financial', 'display_name' => 'Xem tài chính', 'description' => 'Xem báo cáo tài chính'],
            ['module' => 'financial', 'action' => 'manage', 'name' => 'manage-transactions', 'display_name' => 'Quản lý giao dịch', 'description' => 'Quản lý giao dịch'],
            
            // Settings
            ['module' => 'settings', 'action' => 'manage', 'name' => 'manage-settings', 'display_name' => 'Quản lý cài đặt', 'description' => 'Quản lý cài đặt hệ thống'],
            ['module' => 'settings', 'action' => 'manage-roles', 'name' => 'manage-roles', 'display_name' => 'Quản lý vai trò', 'description' => 'Quản lý vai trò'],
            ['module' => 'settings', 'action' => 'manage-permissions', 'name' => 'manage-permissions', 'display_name' => 'Quản lý quyền', 'description' => 'Quản lý quyền'],
            
            // Reports
            ['module' => 'reports', 'action' => 'view', 'name' => 'view-reports', 'display_name' => 'Xem báo cáo', 'description' => 'Xem báo cáo'],
            ['module' => 'reports', 'action' => 'export', 'name' => 'export-reports', 'display_name' => 'Xuất báo cáo', 'description' => 'Xuất báo cáo'],
            
            // CRM
            ['module' => 'crm', 'action' => 'view', 'name' => 'view-customers', 'display_name' => 'Xem khách hàng', 'description' => 'Xem khách hàng'],
            ['module' => 'crm', 'action' => 'manage', 'name' => 'manage-customers', 'display_name' => 'Quản lý khách hàng', 'description' => 'Quản lý khách hàng'],
            
            // Calendar
            ['module' => 'calendar', 'action' => 'view', 'name' => 'view-calendar', 'display_name' => 'Xem lịch', 'description' => 'Xem lịch'],
            ['module' => 'calendar', 'action' => 'manage', 'name' => 'manage-calendar', 'display_name' => 'Quản lý lịch', 'description' => 'Quản lý lịch'],
        ];
        
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'module' => $perm['module'],
                    'action' => $perm['action'],
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'],
                ]
            );
        }
        
        // Assign all permissions to super-admin role
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::all()->pluck('id');
            $superAdmin->permissions()->sync($allPermissions);
            $this->command->info('✓ Assigned all permissions to super-admin');
        }
        
        // Assign basic permissions to teacher role
        $teacher = Role::where('name', 'teacher')->first();
        if ($teacher) {
            $teacherPermissions = Permission::whereIn('name', [
                'view-students',
                'view-classes',
                'view-attendance',
                'mark-attendance',
                'edit-attendance',
                'view-calendar',
            ])->pluck('id');
            $teacher->permissions()->sync($teacherPermissions);
            $this->command->info('✓ Assigned permissions to teacher');
        }
        
        $this->command->info("✓ Created " . count($permissions) . " permissions");
    }
    
    private function createLanguages()
    {
        $this->command->info('Creating languages...');
        
        $languages = [
            ['code' => 'vi', 'name' => 'Tiếng Việt', 'is_default' => true],
            ['code' => 'en', 'name' => 'English', 'is_default' => false],
        ];
        
        foreach ($languages as $lang) {
            Language::firstOrCreate(
                ['code' => $lang['code']],
                ['name' => $lang['name'], 'is_default' => $lang['is_default']]
            );
        }
        
        $this->command->info('✓ Created languages');
    }
    
    private function createSettings()
    {
        $this->command->info('Creating basic settings...');
        
        $settings = [
            ['key' => 'app_name', 'value' => 'Yên Tâm English Center'],
            ['key' => 'app_logo', 'value' => null],
            ['key' => 'timezone', 'value' => 'Asia/Ho_Chi_Minh'],
            ['key' => 'date_format', 'value' => 'd/m/Y'],
            ['key' => 'time_format', 'value' => 'H:i'],
            ['key' => 'currency', 'value' => 'VND'],
            ['key' => 'default_language', 'value' => 'vi'],
            ['key' => 'per_page', 'value' => '20'],
            ['key' => 'session_timeout', 'value' => '120'],
        ];
        
        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('✓ Created basic settings');
    }
}

