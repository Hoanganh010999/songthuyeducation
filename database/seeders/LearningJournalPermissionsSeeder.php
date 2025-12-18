<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearningJournalPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Create Learning Journal permissions
        $permissions = [
            [
                'module' => 'course',
                'action' => 'view_learning_journal',
                'name' => 'course.view_learning_journal',
                'display_name' => 'Xem Learning Journal',
                'description' => 'Xem và truy cập Learning Journal trong Classroom Board',
                'sort_order' => 8,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module' => 'course',
                'action' => 'create_learning_journal',
                'name' => 'course.create_learning_journal',
                'display_name' => 'Tạo Learning Journal',
                'description' => 'Tạo và chỉnh sửa Learning Journal của chính mình',
                'sort_order' => 9,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module' => 'course',
                'action' => 'grade_learning_journal',
                'name' => 'course.grade_learning_journal',
                'display_name' => 'Chấm Learning Journal',
                'description' => 'Chấm điểm Learning Journal của học viên bằng AI',
                'sort_order' => 10,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $permission) {
            // Check if permission already exists
            $exists = DB::table('permissions')
                ->where('name', $permission['name'])
                ->exists();

            if (!$exists) {
                $permissionId = DB::table('permissions')->insertGetId($permission);

                // Assign to superadmin role (id = 1)
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => 1, // superadmin
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Assign to admin role (id = 2)
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => 2, // admin
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->command->info("Created permission: {$permission['name']}");
            } else {
                $this->command->info("Permission already exists: {$permission['name']}");
            }
        }

        // Also assign 'course.view' to students so they can access Classroom Board
        $viewPermission = DB::table('permissions')->where('name', 'course.view')->first();
        $studentRole = DB::table('roles')->where('name', 'student')->first();

        if ($viewPermission && $studentRole) {
            $hasPermission = DB::table('permission_role')
                ->where('permission_id', $viewPermission->id)
                ->where('role_id', $studentRole->id)
                ->exists();

            if (!$hasPermission) {
                DB::table('permission_role')->insert([
                    'permission_id' => $viewPermission->id,
                    'role_id' => $studentRole->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->command->info("Assigned course.view to student role");
            }
        }

        $this->command->info('Learning Journal permissions seeded successfully!');
    }
}
