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
        Schema::table('account_categories', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('branches')
                ->onDelete('cascade')
                ->comment('Chi nhánh (null = dùng chung cho tất cả chi nhánh)');
            
            $table->index('branch_id');
        });

        Schema::table('account_items', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('category_id')
                ->constrained('branches')
                ->onDelete('cascade')
                ->comment('Chi nhánh (null = dùng chung cho tất cả chi nhánh)');
            
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('account_items', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
