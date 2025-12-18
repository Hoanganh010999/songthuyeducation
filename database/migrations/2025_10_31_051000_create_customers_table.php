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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('code')->unique()->comment('Mã khách hàng (auto-generated)');
            $table->string('name')->comment('Họ tên khách hàng');
            $table->string('phone')->nullable()->comment('Số điện thoại');
            $table->string('email')->nullable()->comment('Email');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Giới tính');
            
            // Address
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->string('city')->nullable()->comment('Thành phố');
            $table->string('district')->nullable()->comment('Quận/Huyện');
            $table->string('ward')->nullable()->comment('Phường/Xã');
            
            // Sales Pipeline (Kanban Stages)
            $table->enum('stage', [
                'lead',          // Khách hàng tiềm năng
                'contacted',     // Đã liên hệ
                'qualified',     // Đủ điều kiện
                'proposal',      // Đã gửi đề xuất
                'negotiation',   // Đang đàm phán
                'closed_won',    // Chốt đơn thành công
                'closed_lost'    // Mất khách
            ])->default('lead')->comment('Giai đoạn trong pipeline');
            
            $table->integer('stage_order')->default(0)->comment('Thứ tự trong stage (for drag-drop)');
            
            // Source & Assignment
            $table->string('source')->nullable()->comment('Nguồn khách hàng (Facebook, Google, Referral, etc.)');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete()->comment('Chi nhánh');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->comment('Người phụ trách');
            
            // Additional Info
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->decimal('estimated_value', 15, 2)->nullable()->comment('Giá trị dự kiến');
            $table->date('expected_close_date')->nullable()->comment('Ngày dự kiến chốt');
            $table->date('closed_at')->nullable()->comment('Ngày chốt thực tế');
            
            // Status
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('metadata')->nullable()->comment('Thông tin bổ sung (JSON)');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('phone');
            $table->index('email');
            $table->index('stage');
            $table->index('branch_id');
            $table->index('assigned_to');
            $table->index('is_active');
            $table->index(['stage', 'stage_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
