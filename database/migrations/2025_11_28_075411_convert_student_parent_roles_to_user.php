<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get role IDs
        $studentRole = DB::table('roles')->where('name', 'student')->first();
        $parentRole = DB::table('roles')->where('name', 'parent')->first();
        $userRole = DB::table('roles')->where('name', 'user')->first();

        if (!$userRole) {
            // Create user role if it doesn't exist
            $userRoleId = DB::table('roles')->insertGetId([
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Người dùng cơ bản',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userRoleId = $userRole->id;
        }

        // Convert all student role assignments to user role
        if ($studentRole) {
            // Get all users with student role
            $studentUserIds = DB::table('role_user')
                ->where('role_id', $studentRole->id)
                ->pluck('user_id')
                ->toArray();

            // Remove student role from these users
            DB::table('role_user')
                ->where('role_id', $studentRole->id)
                ->delete();

            // Add user role to these users (if they don't have it)
            foreach ($studentUserIds as $userId) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $userRoleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            \Log::info('[RoleConversion] Converted student users to user role', [
                'count' => count($studentUserIds),
                'user_ids' => $studentUserIds,
            ]);
        }

        // Convert all parent role assignments to user role
        if ($parentRole) {
            // Get all users with parent role
            $parentUserIds = DB::table('role_user')
                ->where('role_id', $parentRole->id)
                ->pluck('user_id')
                ->toArray();

            // Remove parent role from these users
            DB::table('role_user')
                ->where('role_id', $parentRole->id)
                ->delete();

            // Add user role to these users (if they don't have it)
            foreach ($parentUserIds as $userId) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $userRoleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            \Log::info('[RoleConversion] Converted parent users to user role', [
                'count' => count($parentUserIds),
                'user_ids' => $parentUserIds,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration automatically as we don't know
        // which users were originally students or parents
        \Log::warning('[RoleConversion] Cannot automatically reverse student/parent role conversion');
    }
};
