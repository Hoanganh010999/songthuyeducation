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
        // MySQL doesn't support ALTER COLUMN for ENUM directly with Blueprint
        // We need to use raw SQL
        DB::statement("ALTER TABLE `financial_plans` MODIFY COLUMN `status` ENUM('draft', 'pending', 'approved', 'active', 'closed') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE `financial_plans` MODIFY COLUMN `status` ENUM('draft', 'approved', 'active', 'closed') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái'");
    }
};
