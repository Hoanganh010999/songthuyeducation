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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã đơn: ENR20251106001
            
            // Khách hàng
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            // Người học (Polymorphic - Customer hoặc CustomerChild)
            $table->morphs('student'); // student_id, student_type
            
            // Sản phẩm/Khóa học
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Giá & Thanh toán
            $table->decimal('original_price', 15, 2); // Giá gốc
            $table->decimal('discount_amount', 15, 2)->default(0); // Giảm giá
            $table->decimal('final_price', 15, 2); // Giá cuối = original - discount
            $table->decimal('paid_amount', 15, 2)->default(0); // Đã thanh toán
            $table->decimal('remaining_amount', 15, 2); // Còn lại
            
            // Voucher & Campaign
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voucher_code')->nullable(); // Lưu lại code đã dùng
            
            // Thông tin khóa học
            $table->integer('total_sessions')->nullable(); // Tổng số buổi
            $table->integer('attended_sessions')->default(0); // Đã học
            $table->integer('remaining_sessions')->nullable(); // Còn lại
            $table->decimal('price_per_session', 15, 2)->nullable(); // Giá/buổi
            
            // Ngày tháng
            $table->date('start_date')->nullable(); // Ngày bắt đầu
            $table->date('end_date')->nullable(); // Ngày kết thúc dự kiến
            $table->date('completed_at')->nullable(); // Ngày hoàn thành thực tế
            
            // Trạng thái
            $table->enum('status', [
                'pending', // Chờ thanh toán
                'paid', // Đã thanh toán
                'active', // Đang học
                'completed', // Hoàn thành
                'cancelled', // Hủy
                'refunded' // Hoàn tiền
            ])->default('pending');
            
            // Chi nhánh & Người xử lý
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Giáo viên/Tư vấn viên
            
            // Ghi chú
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes (morphs already creates index)
            $table->index('customer_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

