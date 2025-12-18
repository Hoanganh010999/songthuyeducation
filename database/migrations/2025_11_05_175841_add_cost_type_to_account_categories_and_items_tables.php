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
        // Thêm cost_type vào account_categories
        Schema::table('account_categories', function (Blueprint $table) {
            $table->enum('cost_type', ['fixed', 'variable', 'infrastructure'])->nullable()->after('type');
        });

        // Thêm cost_type vào account_items
        Schema::table('account_items', function (Blueprint $table) {
            $table->enum('cost_type', ['fixed', 'variable', 'infrastructure'])->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropColumn('cost_type');
        });

        Schema::table('account_items', function (Blueprint $table) {
            $table->dropColumn('cost_type');
        });
    }
};
