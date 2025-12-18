<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class IELTSTestBankPermissionsSeeder extends Seeder
{
    /**
     * Create granular permissions for IELTS Test Bank
     */
    public function run(): void
    {
        echo "🎓 Creating IELTS Test Bank Permissions...\n\n";

        $permissions = [
            [
                'name' => 'examination.ielts.tests.view',
                'display_name' => 'Xem ngân hàng đề IELTS',
                'description' => 'Xem danh sách và chi tiết đề thi IELTS trong Test Bank',
                'module' => 'examination',
                'action' => 'view',
            ],
            [
                'name' => 'examination.ielts.tests.create',
                'display_name' => 'Tạo đề IELTS',
                'description' => 'Tạo đề thi IELTS mới trong Test Bank',
                'module' => 'examination',
                'action' => 'create',
            ],
            [
                'name' => 'examination.ielts.tests.edit',
                'display_name' => 'Sửa đề IELTS',
                'description' => 'Chỉnh sửa đề thi IELTS trong Test Bank',
                'module' => 'examination',
                'action' => 'edit',
            ],
            [
                'name' => 'examination.ielts.tests.delete',
                'display_name' => 'Xóa đề IELTS',
                'description' => 'Xóa đề thi IELTS khỏi Test Bank',
                'module' => 'examination',
                'action' => 'delete',
            ],
        ];

        foreach ($permissions as $permData) {
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

        echo "\n📋 Assigning to Roles...\n\n";

        // Admin: Full IELTS access
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPerms = [
                'examination.ielts.view',          // Practice tests
                'examination.ielts.tests.view',    // Test bank
                'examination.ielts.tests.create',
                'examination.ielts.tests.edit',
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
            echo "✅ Admin role: " . count($adminPerms) . " permissions\n";
        }

        // Teacher: Full IELTS access
        $teacherRole = Role::where('name', 'teacher')->first();
        if ($teacherRole) {
            $teacherPerms = [
                'examination.ielts.view',
                'examination.ielts.tests.view',
                'examination.ielts.tests.create',
                'examination.ielts.tests.edit',
                'examination.ielts.tests.delete',
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
            echo "✅ Teacher role: " . count($teacherPerms) . " permissions\n";
        }

        // Student: View practice tests only
        $studentRole = Role::where('name', 'user')->first();
        if ($studentRole) {
            $studentPerms = [
                'examination.ielts.view',  // Practice tests only
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
            echo "✅ Student role: " . count($studentPerms) . " permission\n";
        }

        echo "\n✨ IELTS Test Bank permissions created successfully!\n";
    }
}

