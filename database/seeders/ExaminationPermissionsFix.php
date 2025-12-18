<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ExaminationPermissionsFix extends Seeder
{
    /**
     * Fix examination permissions structure
     */
    public function run(): void
    {
        echo "🔧 Fixing Examination Module Permissions...\n\n";

        // 1. Create missing permissions
        $missingPermissions = [
            [
                'name' => 'examination.grading.view',
                'display_name' => 'Xem danh sách chấm điểm',
                'description' => 'Xem danh sách bài thi cần chấm và đã chấm',
                'module' => 'examination',
                'action' => 'view',
            ],
        ];

        foreach ($missingPermissions as $permData) {
            $perm = Permission::firstOrCreate(
                ['name' => $permData['name']],
                $permData + ['is_active' => true]
            );
            
            if ($perm->wasRecentlyCreated) {
                echo "✅ Created: {$perm->name}\n";
            } else {
                echo "✓ Exists: {$perm->name}\n";
            }
        }

        echo "\n";

        // 2. Assign permissions to roles properly
        echo "📋 Assigning permissions to roles...\n\n";

        // Admin role permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPerms = [
                'examination.view',
                'examination.submissions.view',
                'examination.submissions.grade',
                'examination.grading.view',
                'examination.ielts.view',
                'examination.submissions.special_view',
                'examination.questions.view', // Admin should see questions
                'examination.tests.view', // Admin should see tests
                'examination.assignments.view', // Admin should see assignments
            ];

            foreach ($adminPerms as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    DB::table('permission_role')->insertOrIgnore([
                        'permission_id' => $perm->id,
                        'role_id' => $adminRole->id,
                    ]);
                }
            }
            echo "✅ Admin role updated\n";
        }

        // Teacher role permissions
        $teacherRole = Role::where('name', 'teacher')->first();
        if ($teacherRole) {
            $teacherPerms = [
                'examination.grading.view',
            ];

            foreach ($teacherPerms as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    DB::table('permission_role')->insertOrIgnore([
                        'permission_id' => $perm->id,
                        'role_id' => $teacherRole->id,
                    ]);
                }
            }
            echo "✅ Teacher role updated\n";
        }

        // Student role - should have basic view access
        $studentRole = Role::where('name', 'student')->orWhere('name', 'user')->first();
        if ($studentRole) {
            $studentPerms = [
                'examination.view',
                'examination.ielts.view', // Students can view IELTS practice tests
            ];

            foreach ($studentPerms as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    DB::table('permission_role')->insertOrIgnore([
                        'permission_id' => $perm->id,
                        'role_id' => $studentRole->id,
                    ]);
                }
            }
            echo "✅ Student/User role updated\n";
        }

        echo "\n✨ Examination permissions fixed successfully!\n";
    }
}

