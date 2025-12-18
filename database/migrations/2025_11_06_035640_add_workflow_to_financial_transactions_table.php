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
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Add workflow status
            $table->enum('status', ['pending', 'approved', 'verified', 'rejected'])->default('pending')->after('transaction_type')->comment('Trạng thái workflow');
            
            // Add cash account (selected during approval)
            $table->foreignId('cash_account_id')->nullable()->after('account_item_id')->constrained('cash_accounts')->onDelete('set null')->comment('Tài khoản tiền');
            
            // Add approval fields (Accountant)
            $table->foreignId('approved_by')->nullable()->after('recorded_by')->constrained('users')->onDelete('set null')->comment('Người duyệt (Kế toán)');
            $table->timestamp('approved_at')->nullable()->after('approved_by')->comment('Ngày duyệt');
            
            // Add verification fields (Cashier)
            $table->foreignId('verified_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null')->comment('Người xác minh (Thủ quỹ)');
            $table->timestamp('verified_at')->nullable()->after('verified_by')->comment('Ngày xác minh');
            
            // Add rejection fields
            $table->text('rejected_reason')->nullable()->after('verified_at')->comment('Lý do từ chối');
            
            // Add indexes
            $table->index('status');
            $table->index('cash_account_id');
            $table->index('approved_by');
            $table->index('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['cash_account_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['verified_by']);
            
            $table->dropIndex(['status']);
            $table->dropIndex(['cash_account_id']);
            $table->dropIndex(['approved_by']);
            $table->dropIndex(['verified_by']);
            
            $table->dropColumn([
                'status',
                'cash_account_id',
                'approved_by',
                'approved_at',
                'verified_by',
                'verified_at',
                'rejected_reason'
            ]);
        });
    }
};
