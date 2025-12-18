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
     * Restructure zalo_friends to use shared data model:
     * - Add zalo_id (which physical Zalo account owns this data)
     * - Remove zalo_account_id (no longer needed)
     * - Make zalo_user_id unique (not composite anymore)
     */
    public function up(): void
    {
        Log::info('[Migration] Starting zalo_friends table restructure');

        Schema::table('zalo_friends', function (Blueprint $table) {
            // Add zalo_id column (will store the physical Zalo account ID)
            $table->string('zalo_id')->nullable()->after('zalo_user_id')->comment('Physical Zalo account that owns this friend');
        });

        // Populate zalo_id from the zalo_account
        DB::statement('
            UPDATE zalo_friends f
            JOIN zalo_accounts a ON f.zalo_account_id = a.id
            SET f.zalo_id = a.zalo_id
            WHERE f.deleted_at IS NULL
        ');

        Log::info('[Migration] Populated zalo_id from zalo_accounts');

        Schema::table('zalo_friends', function (Blueprint $table) {
            // Drop old composite unique constraint
            $table->dropUnique('zalo_friends_zalo_account_id_zalo_user_id_unique');

            // Drop foreign key and index
            $table->dropForeign(['zalo_account_id']);

            // Drop old column
            $table->dropColumn('zalo_account_id');

            // Add new unique constraint on zalo_user_id only
            $table->unique('zalo_user_id');

            // Add index on zalo_id for queries
            $table->index('zalo_id');
        });

        Log::info('[Migration] Completed zalo_friends table restructure');
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This cannot fully restore the original structure
     * as we've lost the zalo_account_id associations.
     */
    public function down(): void
    {
        Log::warning('[Migration] Reversing zalo_friends restructure');

        Schema::table('zalo_friends', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique(['zalo_user_id']);
            $table->dropIndex(['zalo_id']);

            // Re-add old column
            $table->foreignId('zalo_account_id')->nullable()->after('id')->constrained('zalo_accounts')->onDelete('cascade');

            // Remove zalo_id
            $table->dropColumn('zalo_id');

            // Re-add composite unique constraint
            $table->unique(['zalo_account_id', 'zalo_user_id']);
        });

        Log::info('[Migration] Reversed zalo_friends restructure (data may be incomplete)');
    }
};
