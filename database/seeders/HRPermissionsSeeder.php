<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class HRPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // HR Module
            ['module' => 'hr', 'action' => 'view', 'name' => 'hr.view', 'display_name' => 'Xem module HR', 'description' => 'Xem module HR', 'sort_order' => 1],
            ['module' => 'hr', 'action' => 'manage', 'name' => 'hr.manage', 'display_name' => 'Quản lý HR', 'description' => 'Quản lý HR', 'sort_order' => 2],
            
            // Organization Chart
            ['module' => 'org_chart', 'action' => 'view', 'name' => 'org_chart.view', 'display_name' => 'Xem sơ đồ tổ chức', 'description' => 'Xem sơ đồ tổ chức', 'sort_order' => 3],
            ['module' => 'org_chart', 'action' => 'edit', 'name' => 'org_chart.edit', 'display_name' => 'Chỉnh sửa sơ đồ tổ chức', 'description' => 'Chỉnh sửa sơ đồ tổ chức', 'sort_order' => 4],
            
            // Departments
            ['module' => 'departments', 'action' => 'view', 'name' => 'departments.view', 'display_name' => 'Xem phòng ban', 'description' => 'Xem phòng ban', 'sort_order' => 5],
            ['module' => 'departments', 'action' => 'create', 'name' => 'departments.create', 'display_name' => 'Tạo phòng ban', 'description' => 'Tạo phòng ban', 'sort_order' => 6],
            ['module' => 'departments', 'action' => 'edit', 'name' => 'departments.edit', 'display_name' => 'Sửa phòng ban', 'description' => 'Sửa phòng ban', 'sort_order' => 7],
            ['module' => 'departments', 'action' => 'delete', 'name' => 'departments.delete', 'display_name' => 'Xóa phòng ban', 'description' => 'Xóa phòng ban', 'sort_order' => 8],
            
            // Employees
            ['module' => 'employees', 'action' => 'view', 'name' => 'employees.view', 'display_name' => 'Xem danh sách nhân viên', 'description' => 'Xem danh sách nhân viên', 'sort_order' => 9],
            ['module' => 'employees', 'action' => 'invite', 'name' => 'employees.invite', 'display_name' => 'Mời nhân viên', 'description' => 'Mời nhân viên', 'sort_order' => 10],
            ['module' => 'employees', 'action' => 'manage', 'name' => 'employees.manage', 'display_name' => 'Quản lý nhân viên', 'description' => 'Quản lý nhân viên', 'sort_order' => 11],
            ['module' => 'employees', 'action' => 'assign', 'name' => 'employees.assign', 'display_name' => 'Phân công nhân viên', 'description' => 'Phân công nhân viên', 'sort_order' => 12],
            
            // Invitations
            ['module' => 'invitations', 'action' => 'view', 'name' => 'invitations.view', 'display_name' => 'Xem lời mời', 'description' => 'Xem lời mời', 'sort_order' => 13],
            ['module' => 'invitations', 'action' => 'send', 'name' => 'invitations.send', 'display_name' => 'Gửi lời mời', 'description' => 'Gửi lời mời', 'sort_order' => 14],
            ['module' => 'invitations', 'action' => 'cancel', 'name' => 'invitations.cancel', 'display_name' => 'Hủy lời mời', 'description' => 'Hủy lời mời', 'sort_order' => 15],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
