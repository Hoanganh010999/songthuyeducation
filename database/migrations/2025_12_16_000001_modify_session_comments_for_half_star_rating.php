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
        Schema::table('session_comments', function (Blueprint $table) {
            // Change rating from INT to DECIMAL to support half stars (0.5, 1.5, 2.5, etc.)
            $table->decimal('rating', 3, 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_comments', function (Blueprint $table) {
            // Revert back to INT
            $table->integer('rating')->nullable()->change();
        });
    }
};

