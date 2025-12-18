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
        // Update all existing Zalo accounts to have branch_id = 1
        // This ensures backward compatibility with existing data
        DB::table('zalo_accounts')
            ->whereNull('branch_id')
            ->update([
                'branch_id' => 1,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert branch_id to null for accounts that were set to 1 by this migration
        // Note: This is a best-effort revert, we can't perfectly distinguish
        // which accounts were updated by this migration vs originally had branch_id = 1
        DB::table('zalo_accounts')
            ->where('branch_id', 1)
            ->update([
                'branch_id' => null,
                'updated_at' => now(),
            ]);
    }
};

