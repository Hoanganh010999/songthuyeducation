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
        Schema::create('expense_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã đề xuất: DC202501001');
            $table->string('title')->comment('Tiêu đề');
            $table->foreignId('financial_plan_id')->constrained('financial_plans')->onDelete('cascade')->comment('Kế hoạch (BẮT BUỘC)');
            $table->foreignId('financial_plan_item_id')->constrained('financial_plan_items')->onDelete('cascade')->comment('Khoản mục trong KH');
            $table->foreignId('account_item_id')->constrained('account_items')->onDelete('cascade')->comment('Khoản chi');
            $table->decimal('amount', 15, 2)->comment('Số tiền');
            $table->date('requested_date')->comment('Ngày yêu cầu chi');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid'])->default('draft')->comment('Trạng thái');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade')->comment('Người đề xuất');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Ngày duyệt');
            $table->text('rejected_reason')->nullable()->comment('Lý do từ chối');
            $table->date('payment_date')->nullable()->comment('Ngày thanh toán');
            $table->string('payment_method')->nullable()->comment('Phương thức TT');
            $table->string('payment_ref')->nullable()->comment('Mã tham chiếu TT');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null')->comment('Chi nhánh');
            $table->json('attachments')->nullable()->comment('File đính kèm');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('financial_plan_id');
            $table->index('financial_plan_item_id');
            $table->index('account_item_id');
            $table->index('status');
            $table->index('requested_by');
            $table->index('requested_date');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_proposals');
    }
};
