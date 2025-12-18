<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the unique constraint on zalo_id and adds a composite unique key
     * on (zalo_id, branch_id) to allow the same Zalo account to be used across multiple branches.
     */
    public function up(): void
    {
        // First, ensure all existing accounts have a branch_id (set to Yên Tâm branch if null)
        DB::table('zalo_accounts')
            ->whereNull('branch_id')
            ->update(['branch_id' => 1]); // Yên Tâm branch ID

        // Drop the unique constraint on zalo_id only
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropUnique('zalo_accounts_zalo_id_unique');
        });

        // Add composite unique key on (zalo_id, branch_id)
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->unique(['zalo_id', 'branch_id'], 'zalo_accounts_zalo_id_branch_id_unique');
        });

        // Drop and recreate foreign key to change ON DELETE behavior from SET NULL to CASCADE
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        // Make branch_id NOT NULL and recreate foreign key with CASCADE
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        // Make branch_id nullable again
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->change();
        });

        // Recreate foreign key with SET NULL behavior
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });

        // Remove composite unique key
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropUnique('zalo_accounts_zalo_id_branch_id_unique');
        });

        // Restore the unique constraint on zalo_id
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->unique('zalo_id', 'zalo_accounts_zalo_id_unique');
        });
    }
};
