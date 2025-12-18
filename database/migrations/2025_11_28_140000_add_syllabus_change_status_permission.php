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
        // Add syllabus.change_status permission
        $permission = [
            'module' => 'syllabus',
            'action' => 'change_status',
            'name' => 'syllabus.change_status',
            'display_name' => 'Thay đổi trạng thái Syllabus',
            'description' => 'Quyền thay đổi trạng thái của syllabus (draft, approved, in_use, archived)',
            'sort_order' => 5,
            'is_active' => true,
        ];

        DB::table('permissions')->updateOrInsert(
            ['name' => $permission['name']],
            array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        // Auto-assign to roles that have syllabus.edit permission
        $editPermission = DB::table('permissions')->where('name', 'syllabus.edit')->first();
        $changeStatusPermission = DB::table('permissions')->where('name', 'syllabus.change_status')->first();

        if ($editPermission && $changeStatusPermission) {
            // Get all roles that have syllabus.edit
            $rolesWithEdit = DB::table('permission_role')
                ->where('permission_id', $editPermission->id)
                ->pluck('role_id');

            // Assign syllabus.change_status to those roles
            foreach ($rolesWithEdit as $roleId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $changeStatusPermission->id,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'syllabus.change_status')
            ->delete();
    }
};
