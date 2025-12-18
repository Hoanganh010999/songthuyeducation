<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class CalendarFeedbackPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'calendar.submit_feedback',
                'action' => 'submit_feedback',
                'display_name' => 'Trả kết quả',
                'description' => 'Trả kết quả cho lịch test/học thử',
                'module' => 'calendar',
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'calendar.assign_teacher',
                'action' => 'assign_teacher',
                'display_name' => 'Phân công giáo viên',
                'description' => 'Phân công giáo viên cho lịch test',
                'module' => 'calendar',
                'sort_order' => 101,
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Gán permissions cho super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $permissionIds = Permission::whereIn('name', [
                'calendar.submit_feedback',
                'calendar.assign_teacher',
            ])->pluck('id');
            
            foreach ($permissionIds as $permissionId) {
                $superAdminRole->permissions()->syncWithoutDetaching([$permissionId]);
            }
        }

        $this->command->info('✅ Calendar feedback permissions created successfully!');
    }
}
