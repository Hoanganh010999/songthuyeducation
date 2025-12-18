<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class ExaminationSpecialViewPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the special view permission
        $permission = Permission::firstOrCreate(
            ['name' => 'examination.submissions.special_view'],
            [
                'display_name' => 'Xem bài thi được chia sẻ đặc biệt',
                'description' => 'Có thể xem các bài thi mà người dùng cho phép chia sẻ với người có quyền đặc biệt',
                'module' => 'examination',
                'action' => 'special_view',
                'is_active' => true,
            ]
        );

        echo "✓ Created permission: {$permission->name}\n";
        
        // Optional: Assign this permission to specific roles
        // For example, assign to 'Teacher' or 'Admin' roles
        $teacherRole = DB::table('roles')->where('name', 'Teacher')->first();
        $adminRole = DB::table('roles')->where('name', 'Admin')->first();
        
        if ($teacherRole) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permission->id,
                'role_id' => $teacherRole->id,
            ]);
            echo "✓ Assigned to Teacher role\n";
        }
        
        if ($adminRole) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permission->id,
                'role_id' => $adminRole->id,
            ]);
            echo "✓ Assigned to Admin role\n";
        }
    }
}

