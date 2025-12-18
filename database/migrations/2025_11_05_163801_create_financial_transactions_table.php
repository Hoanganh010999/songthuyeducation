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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã giao dịch: GD202501001');
            $table->enum('transaction_type', ['income', 'expense'])->comment('Thu/Chi');
            $table->string('transactionable_type')->comment('Polymorphic type');
            $table->unsignedBigInteger('transactionable_id')->comment('Polymorphic id');
            $table->foreignId('financial_plan_id')->nullable()->constrained('financial_plans')->onDelete('set null')->comment('Kế hoạch');
            $table->foreignId('account_item_id')->constrained('account_items')->onDelete('cascade')->comment('Khoản thu chi');
            $table->decimal('amount', 15, 2)->comment('Số tiền');
            $table->date('transaction_date')->comment('Ngày giao dịch');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->string('payment_method')->nullable()->comment('Phương thức TT');
            $table->string('payment_ref')->nullable()->comment('Mã tham chiếu TT');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade')->comment('Người ghi nhận');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null')->comment('Chi nhánh');
            $table->json('metadata')->nullable()->comment('Metadata bổ sung');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('transaction_type');
            $table->index(['transactionable_type', 'transactionable_id'], 'transactions_polymorphic_index');
            $table->index('financial_plan_id');
            $table->index('account_item_id');
            $table->index('transaction_date');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
