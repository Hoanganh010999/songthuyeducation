<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds branch_id and department_id to zalo_groups table
     * to support multi-branch group management and department assignment.
     */
    public function up(): void
    {
        Schema::table('zalo_groups', function (Blueprint $table) {
            // Add branch_id and department_id after zalo_account_id
            $table->foreignId('branch_id')
                ->nullable()
                ->after('zalo_account_id')
                ->constrained('branches')
                ->onDelete('set null')
                ->comment('Branch assignment for this group');

            $table->foreignId('department_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('departments')
                ->onDelete('set null')
                ->comment('Department assignment for this group');

            // Add indexes for better query performance
            $table->index('branch_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_groups', function (Blueprint $table) {
            // Drop foreign keys and indexes first
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['department_id']);

            // Drop columns
            $table->dropColumn(['branch_id', 'department_id']);
        });
    }
};
