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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã chiến dịch: BLACKFRIDAY2025
            $table->string('name'); // Tên chiến dịch
            $table->text('description')->nullable();
            
            // Loại giảm giá
            $table->enum('discount_type', ['percentage', 'fixed_amount']);
            $table->decimal('discount_value', 15, 2);
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            $table->decimal('min_order_amount', 15, 2)->nullable();
            
            // Thời gian chiến dịch
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            
            // Áp dụng cho
            $table->json('applicable_product_ids')->nullable();
            $table->json('applicable_categories')->nullable();
            $table->json('target_customer_segments')->nullable(); // new, vip, loyal...
            
            // Ưu tiên (số càng cao càng ưu tiên)
            $table->integer('priority')->default(0);
            
            // Giới hạn
            $table->integer('total_usage_limit')->nullable();
            $table->integer('total_usage_count')->default(0);
            
            // Trạng thái
            $table->boolean('is_active')->default(true);
            $table->boolean('is_auto_apply')->default(true); // Tự động áp dụng trong thời gian
            
            // Banner & Marketing
            $table->string('banner_image')->nullable();
            $table->string('banner_url')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['start_date', 'end_date']);
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

