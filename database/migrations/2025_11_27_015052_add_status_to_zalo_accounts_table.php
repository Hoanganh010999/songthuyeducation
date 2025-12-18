<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add status field to track pending vs active accounts
     */
    public function up(): void
    {
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active'])->default('active')->after('is_active');
            $table->index('status');
        });

        // Set all existing records to 'active'
        DB::table('zalo_accounts')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_accounts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
