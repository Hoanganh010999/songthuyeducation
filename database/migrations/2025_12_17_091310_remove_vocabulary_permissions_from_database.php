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
        // Remove vocabulary permissions from database
        DB::table('permissions')->whereIn('name', [
            'vocabulary.view',
            'vocabulary.create',
            'vocabulary.edit',
            'vocabulary.delete',
            'vocabulary.record_pronunciation'
        ])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore vocabulary permissions
        $permissions = [
            ['name' => 'vocabulary.view', 'display_name' => 'View Vocabulary Book', 'description' => 'Can view personal vocabulary book'],
            ['name' => 'vocabulary.create', 'display_name' => 'Create Vocabulary Entry', 'description' => 'Can create new vocabulary entries'],
            ['name' => 'vocabulary.edit', 'display_name' => 'Edit Vocabulary Entry', 'description' => 'Can edit vocabulary entries'],
            ['name' => 'vocabulary.delete', 'display_name' => 'Delete Vocabulary Entry', 'description' => 'Can delete vocabulary entries'],
            ['name' => 'vocabulary.record_pronunciation', 'display_name' => 'Record Pronunciation', 'description' => 'Can record and check pronunciation'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
};
