<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class GoogleDrivePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'google-drive.view',
                'module' => 'google-drive',
                'action' => 'view',
                'display_name' => 'View Google Drive',
                'description' => 'Can view files and folders in Google Drive',
            ],
            [
                'name' => 'google-drive.view_root_folder',
                'module' => 'google-drive',
                'action' => 'view_root_folder',
                'display_name' => 'View Root Folder',
                'description' => 'Can view and access the root School Drive folder',
            ],
            [
                'name' => 'google-drive.manage',
                'module' => 'google-drive',
                'action' => 'manage',
                'display_name' => 'Manage Google Drive',
                'description' => 'Can upload, delete, rename, and move files in Google Drive',
            ],
            [
                'name' => 'google-drive.settings',
                'module' => 'google-drive',
                'action' => 'settings',
                'display_name' => 'Google Drive Settings',
                'description' => 'Can configure Google Drive API settings',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Gán permissions cho Super Admin và Admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole = Role::where('name', 'admin')->first();

        if ($superAdminRole) {
            $permissionIds = Permission::whereIn('name', [
                'google-drive.view',
                'google-drive.view_root_folder',
                'google-drive.manage',
                'google-drive.settings',
            ])->pluck('id');
            
            $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($adminRole) {
            $permissionIds = Permission::whereIn('name', [
                'google-drive.view',
                'google-drive.view_root_folder',
                'google-drive.manage',
                'google-drive.settings',
            ])->pluck('id');
            
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        $this->command->info('✅ Google Drive permissions created and assigned successfully!');
    }
}
