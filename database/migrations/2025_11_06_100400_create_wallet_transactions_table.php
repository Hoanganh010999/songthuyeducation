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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            
            $table->string('transaction_code')->unique(); // Mã giao dịch: WTX001
            $table->enum('type', ['deposit', 'withdraw', 'refund']); // Nạp/Rút/Hoàn
            $table->decimal('amount', 15, 2); // Số tiền
            $table->decimal('balance_before', 15, 2); // Số dư trước
            $table->decimal('balance_after', 15, 2); // Số dư sau
            
            // Liên kết
            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();
            $table->index(['transactionable_type', 'transactionable_id'], 'wallet_trans_able_index');
            
            // Thông tin
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->dateTime('completed_at')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('wallet_id');
            $table->index('type');
            $table->index('status');
            // morphs already creates index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

