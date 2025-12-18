<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructure zalo_groups to use shared data model:
     * - Add zalo_id (which physical Zalo account owns this data)
     * - Remove zalo_account_id (no longer needed)
     * - Remove branch_id, department_id (moved to pivot table)
     * - Make zalo_group_id unique (not composite anymore)
     */
    public function up(): void
    {
        Log::info('[Migration] Starting zalo_groups table restructure');

        Schema::table('zalo_groups', function (Blueprint $table) {
            // Add zalo_id column (will store the physical Zalo account ID)
            $table->string('zalo_id')->nullable()->after('zalo_group_id')->comment('Physical Zalo account that owns this group');
        });

        // Populate zalo_id from the zalo_account
        DB::statement('
            UPDATE zalo_groups g
            JOIN zalo_accounts a ON g.zalo_account_id = a.id
            SET g.zalo_id = a.zalo_id
            WHERE g.deleted_at IS NULL
        ');

        Log::info('[Migration] Populated zalo_id from zalo_accounts');

        Schema::table('zalo_groups', function (Blueprint $table) {
            // Drop old composite unique constraint
            $table->dropUnique('zalo_groups_zalo_account_id_zalo_group_id_unique');

            // Drop foreign key and indexes
            $table->dropForeign(['zalo_account_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);

            // Drop old columns
            $table->dropColumn(['zalo_account_id', 'branch_id', 'department_id']);

            // Add new unique constraint on zalo_group_id only
            $table->unique('zalo_group_id');

            // Add index on zalo_id for queries
            $table->index('zalo_id');
        });

        Log::info('[Migration] Completed zalo_groups table restructure');
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This cannot fully restore the original structure
     * as we've lost the zalo_account_id associations.
     */
    public function down(): void
    {
        Log::warning('[Migration] Reversing zalo_groups restructure');

        Schema::table('zalo_groups', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique(['zalo_group_id']);
            $table->dropIndex(['zalo_id']);

            // Re-add old columns
            $table->foreignId('zalo_account_id')->nullable()->after('id')->constrained('zalo_accounts')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->after('zalo_account_id')->constrained('branches')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->onDelete('set null');

            // Remove zalo_id
            $table->dropColumn('zalo_id');

            // Re-add composite unique constraint
            $table->unique(['zalo_account_id', 'zalo_group_id']);
        });

        Log::info('[Migration] Reversed zalo_groups restructure (data may be incomplete)');
    }
};
