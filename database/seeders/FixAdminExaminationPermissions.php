<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class FixAdminExaminationPermissions extends Seeder
{
    /**
     * Fix admin role examination permissions
     */
    public function run(): void
    {
        echo "🔧 Fixing Admin Role Examination Permissions...\n\n";

        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            echo "❌ Admin role not found!\n";
            return;
        }

        echo "👤 Admin Role ID: {$adminRole->id}\n\n";

        // Full list of permissions admin should have
        $requiredPermissions = [
            // Core
            'examination.view',
            
            // Questions
            'examination.questions.view',
            'examination.questions.create',
            'examination.questions.edit',
            
            // Tests (General)
            'examination.tests.view',
            'examination.tests.create',
            'examination.tests.edit',
            
            // IELTS
            'examination.ielts.view',
            'examination.ielts.tests.view',
            'examination.ielts.tests.create',
            'examination.ielts.tests.edit',
            
            // Assignments
            'examination.assignments.view',
            'examination.assignments.create',
            'examination.assignments.edit',
            
            // Submissions & Grading
            'examination.submissions.view',
            'examination.submissions.grade',
            'examination.grading.view',
            'examination.submissions.special_view',
            
            // Reports
            'examination.reports.view',
        ];

        $added = 0;
        $existing = 0;

        foreach ($requiredPermissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            
            if (!$perm) {
                echo "⚠️  Permission not found: {$permName}\n";
                continue;
            }

            $exists = DB::table('permission_role')
                ->where('permission_id', $perm->id)
                ->where('role_id', $adminRole->id)
                ->exists();

            if (!$exists) {
                DB::table('permission_role')->insert([
                    'permission_id' => $perm->id,
                    'role_id' => $adminRole->id,
                ]);
                echo "✅ Added: {$permName}\n";
                $added++;
            } else {
                $existing++;
            }
        }

        echo "\n📊 Summary:\n";
        echo "  ✅ Added: {$added}\n";
        echo "  ✓ Already exists: {$existing}\n";
        echo "  📋 Total: " . ($added + $existing) . "\n\n";

        echo "✨ Admin permissions fixed successfully!\n";
    }
}

