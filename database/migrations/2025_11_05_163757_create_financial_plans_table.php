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
        Schema::create('financial_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã kế hoạch: KH202501Q1');
            $table->string('name')->comment('Tên kế hoạch');
            $table->enum('plan_type', ['quarterly', 'monthly'])->comment('Theo quý/tháng');
            $table->integer('year')->comment('Năm');
            $table->integer('quarter')->nullable()->comment('Quý (1-4)');
            $table->integer('month')->nullable()->comment('Tháng (1-12)');
            $table->decimal('total_income_planned', 15, 2)->default(0)->comment('Tổng thu dự kiến');
            $table->decimal('total_expense_planned', 15, 2)->default(0)->comment('Tổng chi dự kiến');
            $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft')->comment('Trạng thái');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Người duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Ngày duyệt');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null')->comment('Chi nhánh');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('plan_type');
            $table->index('year');
            $table->index('quarter');
            $table->index('month');
            $table->index('status');
            $table->index('branch_id');
            $table->unique(['branch_id', 'year', 'quarter', 'month', 'plan_type'], 'unique_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_plans');
    }
};
