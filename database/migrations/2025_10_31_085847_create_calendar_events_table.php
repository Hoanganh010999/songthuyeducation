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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship - Event có thể thuộc về bất kỳ model nào
            $table->morphs('eventable'); // eventable_type, eventable_id
            
            // Thông tin cơ bản
            $table->string('title'); // Tiêu đề event
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->string('category')->default('general'); // Loại: customer_follow_up, task, meeting, placement_test, etc.
            
            // Thời gian
            $table->datetime('start_date'); // Ngày giờ bắt đầu
            $table->datetime('end_date'); // Ngày giờ kết thúc
            $table->boolean('is_all_day')->default(false); // Sự kiện cả ngày
            
            // Trạng thái
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            
            // Người tham gia & Phân quyền
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Người tạo/chịu trách nhiệm
            $table->unsignedBigInteger('branch_id')->nullable(); // Chi nhánh (1=Kinh doanh, 2=Học thuật)
            $table->unsignedBigInteger('created_by')->nullable(); // Người tạo event
            $table->unsignedBigInteger('manager_id')->nullable(); // Quản lý trực tiếp (sẽ dùng sau khi có HR module)
            $table->json('attendees')->nullable(); // Danh sách người tham gia (array of user_ids)
            
            // Hiển thị & Styling
            $table->string('color')->default('#3B82F6'); // Màu hiển thị trên calendar
            $table->string('icon')->nullable(); // Icon đại diện
            $table->string('location')->nullable(); // Địa điểm
            
            // Nhắc nhở
            $table->boolean('has_reminder')->default(false);
            $table->integer('reminder_minutes_before')->nullable(); // Nhắc trước bao nhiêu phút
            
            // Test Result (for placement_test category)
            $table->json('test_result')->nullable(); // Kết quả test: {score, level, notes, evaluated_by, evaluated_at}
            
            // Metadata
            $table->json('metadata')->nullable(); // Thông tin bổ sung tùy module
            
            $table->timestamps();
            
            // Indexes (morphs() đã tạo index cho eventable_type và eventable_id)
            $table->index('category');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('user_id');
            $table->index('branch_id');
            $table->index('created_by');
            $table->index('manager_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
