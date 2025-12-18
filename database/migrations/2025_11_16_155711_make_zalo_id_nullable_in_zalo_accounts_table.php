<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zalo_accounts', function (Blueprint $table) {
            // Make zalo_id nullable since we create account before login
            $table->string('zalo_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_accounts', function (Blueprint $table) {
            // Restore zalo_id to NOT NULL (with caution - may fail if null values exist)
            $table->string('zalo_id')->nullable(false)->change();
        });
    }
};
