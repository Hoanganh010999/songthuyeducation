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
        Schema::create('income_reports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã báo thu: BT202501001');
            $table->string('title')->comment('Tiêu đề');
            $table->foreignId('financial_plan_id')->nullable()->constrained('financial_plans')->onDelete('set null')->comment('Kế hoạch (Nullable - có thể ngoài KH)');
            $table->foreignId('financial_plan_item_id')->nullable()->constrained('financial_plan_items')->onDelete('set null')->comment('Khoản mục trong KH');
            $table->foreignId('account_item_id')->constrained('account_items')->onDelete('cascade')->comment('Khoản thu');
            $table->decimal('amount', 15, 2)->comment('Số tiền');
            $table->date('received_date')->comment('Ngày thu');
            $table->string('payer_name')->nullable()->comment('Tên người nộp');
            $table->string('payer_phone')->nullable()->comment('SĐT người nộp');
            $table->json('payer_info')->nullable()->comment('Thông tin người nộp (customer_id, student_id...)');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft')->comment('Trạng thái');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade')->comment('Người báo thu');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Ngày duyệt');
            $table->text('rejected_reason')->nullable()->comment('Lý do từ chối');
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
            $table->index('reported_by');
            $table->index('received_date');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_reports');
    }
};
