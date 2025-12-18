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
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system_role')->default(false)->comment('Role hệ thống không thể xóa');
        });

        // Mark system roles as non-deletable
        DB::table('roles')
            ->whereIn('name', ['student', 'parent', 'admin', 'super-admin'])
            ->update(['is_system_role' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system_role');
        });
    }
};
