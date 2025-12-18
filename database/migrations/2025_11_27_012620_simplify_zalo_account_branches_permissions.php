<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplify permissions: combine all send permissions into one can_send_message
     */
    public function up(): void
    {
        // Step 1: Add new simplified column
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->boolean('can_send_message')->default(true)->after('role');
        });

        // Step 2: Migrate data - set can_send_message to true for all existing records (full access)
        DB::statement("
            UPDATE zalo_account_branches
            SET can_send_message = 1
        ");

        // Step 3: Also set view permissions to true for existing records (full access by default)
        DB::statement("
            UPDATE zalo_account_branches
            SET view_all_friends = 1,
                view_all_groups = 1,
                view_all_conversations = 1
        ");

        // Step 4: Remove old columns
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->dropColumn([
                'can_send_to_customers',
                'can_send_to_teachers',
                'can_send_to_class_groups',
                'can_send_to_friends',
                'can_send_to_groups',
            ]);
        });

        // Step 5: Update default values for view columns to true
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->boolean('view_all_friends')->default(true)->change();
            $table->boolean('view_all_groups')->default(true)->change();
            $table->boolean('view_all_conversations')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old columns
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->boolean('can_send_to_customers')->default(false)->after('role');
            $table->boolean('can_send_to_teachers')->default(false)->after('can_send_to_customers');
            $table->boolean('can_send_to_class_groups')->default(false)->after('can_send_to_teachers');
            $table->boolean('can_send_to_friends')->default(false)->after('can_send_to_class_groups');
            $table->boolean('can_send_to_groups')->default(false)->after('can_send_to_friends');
        });

        // Copy can_send_message to all old columns
        DB::statement("
            UPDATE zalo_account_branches
            SET can_send_to_customers = can_send_message,
                can_send_to_teachers = can_send_message,
                can_send_to_class_groups = can_send_message,
                can_send_to_friends = can_send_message,
                can_send_to_groups = can_send_message
        ");

        // Remove new column
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->dropColumn('can_send_message');
        });

        // Restore default values
        Schema::table('zalo_account_branches', function (Blueprint $table) {
            $table->boolean('view_all_friends')->default(false)->change();
            $table->boolean('view_all_groups')->default(false)->change();
            $table->boolean('view_all_conversations')->default(false)->change();
        });
    }
};
