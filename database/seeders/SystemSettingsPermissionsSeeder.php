<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'module' => 'settings',
                'action' => 'view',
                'name' => 'settings.view',
                'display_name' => 'Xem cài đặt hệ thống',
                'description' => 'Cho phép xem cài đặt hệ thống',
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'module' => 'settings',
                'action' => 'edit',
                'name' => 'settings.edit',
                'display_name' => 'Chỉnh sửa cài đặt hệ thống',
                'description' => 'Cho phép chỉnh sửa cài đặt hệ thống',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'module' => 'settings',
                'action' => 'delete',
                'name' => 'settings.delete',
                'display_name' => 'Xóa cài đặt hệ thống',
                'description' => 'Cho phép xóa cài đặt hệ thống',
                'sort_order' => 3,
                'is_active' => true
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                    'is_active' => $permission['is_active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Assign to super_admin role
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        
        if ($superAdminRole) {
            $permissionIds = DB::table('permissions')
                ->whereIn('name', array_column($permissions, 'name'))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'permission_id' => $permissionId,
                        'role_id' => $superAdminRole->id
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        $this->command->info('System Settings permissions seeded successfully!');
    }
}
