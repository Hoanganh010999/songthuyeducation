<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new calendar permissions
        $permissions = [
            [
                'module' => 'calendar',
                'action' => 'view_all',
                'name' => 'calendar.view_all',
                'display_name' => 'Xem tất cả Calendar',
                'description' => 'Xem tất cả events trong calendar (super-admin)',
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'module' => 'calendar',
                'action' => 'view_all_classes',
                'name' => 'calendar.view_all_classes',
                'display_name' => 'Xem tất cả lịch lớp học',
                'description' => 'Xem tất cả lịch lớp học trong branch',
                'sort_order' => 101,
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Auto-assign calendar.view_all to super-admin role
        $superAdminRole = DB::table('roles')->where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $viewAllPermission = DB::table('permissions')
                ->where('name', 'calendar.view_all')
                ->first();

            if ($viewAllPermission) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $viewAllPermission->id,
                    'role_id' => $superAdminRole->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        DB::table('permissions')
            ->whereIn('name', ['calendar.view_all', 'calendar.view_all_classes'])
            ->delete();
    }
};
