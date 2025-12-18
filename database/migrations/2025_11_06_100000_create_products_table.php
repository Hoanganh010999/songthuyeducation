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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã sản phẩm: PRD001
            $table->string('name'); // Tên sản phẩm
            $table->string('slug')->unique(); // URL-friendly name
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->string('type'); // course, package, material, service
            
            // Giá tiền
            $table->decimal('price', 15, 2); // Giá gốc
            $table->decimal('sale_price', 15, 2)->nullable(); // Giá khuyến mãi
            
            // Thông tin khóa học (nếu type = course)
            $table->integer('duration_months')->nullable(); // Thời hạn (tháng)
            $table->integer('total_sessions')->nullable(); // Tổng số buổi học
            $table->decimal('price_per_session', 15, 2)->nullable(); // Giá/buổi (tự động tính)
            
            // Phân loại
            $table->string('category')->nullable(); // english, math, science...
            $table->string('level')->nullable(); // beginner, intermediate, advanced
            $table->json('target_ages')->nullable(); // [5, 6, 7, 8] - Độ tuổi phù hợp
            
            // Trạng thái
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Sản phẩm nổi bật
            $table->boolean('allow_trial')->default(true); // Cho phép học thử
            
            // Media
            $table->string('image')->nullable(); // Ảnh đại diện
            $table->json('gallery')->nullable(); // Album ảnh
            
            // SEO & Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('metadata')->nullable(); // Thông tin bổ sung
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('type');
            $table->index('category');
            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

