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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã voucher: SUMMER2025
            $table->string('name'); // Tên voucher
            $table->text('description')->nullable();
            
            // Loại giảm giá
            $table->enum('type', ['percentage', 'fixed_amount']); // % hoặc số tiền cố định
            $table->decimal('value', 15, 2); // Giá trị (10 = 10% hoặc 100,000đ)
            $table->decimal('max_discount_amount', 15, 2)->nullable(); // Giảm tối đa (cho %)
            $table->decimal('min_order_amount', 15, 2)->nullable(); // Đơn hàng tối thiểu
            
            // Giới hạn sử dụng
            $table->integer('usage_limit')->nullable(); // Tổng số lần dùng (null = unlimited)
            $table->integer('usage_per_customer')->default(1); // Mỗi khách dùng tối đa
            $table->integer('usage_count')->default(0); // Đã dùng bao nhiêu lần
            
            // Thời gian hiệu lực
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            
            // Áp dụng cho
            $table->json('applicable_product_ids')->nullable(); // [1,2,3] hoặc null = all
            $table->json('applicable_categories')->nullable(); // ['english', 'math']
            $table->json('applicable_customer_ids')->nullable(); // Khách hàng cụ thể
            
            // Trạng thái
            $table->boolean('is_active')->default(true);
            $table->boolean('is_auto_apply')->default(false); // Tự động áp dụng
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

