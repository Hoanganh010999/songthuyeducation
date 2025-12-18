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
        Schema::table('income_reports', function (Blueprint $table) {
            // Add cash account (selected by accountant during approval)
            $table->foreignId('cash_account_id')->nullable()->after('account_item_id')->constrained('cash_accounts')->onDelete('set null')->comment('Tài khoản nhận tiền');
            
            // Add verification fields (cashier verification)
            $table->foreignId('verified_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null')->comment('Người xác minh (Thủ quỹ)');
            $table->timestamp('verified_at')->nullable()->after('verified_by')->comment('Ngày xác minh');
            
            // Add index
            $table->index('cash_account_id');
        });

        // Update status enum to add 'verified'
        DB::statement("ALTER TABLE `income_reports` MODIFY COLUMN `status` ENUM('draft', 'pending', 'approved', 'rejected', 'verified') DEFAULT 'draft' COMMENT 'Trạng thái'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('income_reports', function (Blueprint $table) {
            $table->dropForeign(['cash_account_id']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['cash_account_id', 'verified_by', 'verified_at']);
        });

        // Revert status enum
        DB::statement("ALTER TABLE `income_reports` MODIFY COLUMN `status` ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft' COMMENT 'Trạng thái'");
    }
};
