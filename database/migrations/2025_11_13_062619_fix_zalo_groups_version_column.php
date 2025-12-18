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
        Schema::table('zalo_groups', function (Blueprint $table) {
            // Change version column from integer to string to support long version numbers from Zalo
            // Zalo versions can be very long (e.g., "1750509099803") which exceeds INT_MAX
            $table->string('version', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zalo_groups', function (Blueprint $table) {
            $table->integer('version')->nullable()->change();
        });
    }
};
