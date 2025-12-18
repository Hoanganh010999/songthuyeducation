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
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã tài khoản');
            $table->string('name')->comment('Tên tài khoản');
            $table->enum('type', ['cash', 'bank'])->default('cash')->comment('Loại: tiền mặt hoặc ngân hàng');
            $table->string('account_number')->nullable()->comment('Số tài khoản ngân hàng');
            $table->string('bank_name')->nullable()->comment('Tên ngân hàng');
            $table->string('bank_branch')->nullable()->comment('Chi nhánh ngân hàng');
            $table->decimal('balance', 15, 2)->default(0)->comment('Số dư hiện tại');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade')->comment('Chi nhánh');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('branch_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};
