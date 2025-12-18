<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_fee_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên chính sách
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(false);
            
            // Vắng không lý do
            $table->decimal('absence_unexcused_percent', 5, 2)->default(100.00)
                ->comment('% trừ khi vắng không lý do (100 = trừ full)');
            $table->integer('absence_consecutive_threshold')->default(1)
                ->comment('Số buổi vắng liên tiếp mới bắt đầu trừ tiền');
            
            // Vắng có lý do
            $table->integer('absence_excused_free_limit')->default(2)
                ->comment('Số buổi vắng có lý do miễn phí mỗi tháng');
            $table->decimal('absence_excused_percent', 5, 2)->default(50.00)
                ->comment('% trừ khi vắng có lý do vượt giới hạn');
            
            // Đi trễ
            $table->decimal('late_deduct_percent', 5, 2)->default(30.00)
                ->comment('% trừ khi đi trễ');
            $table->integer('late_grace_minutes')->default(15)
                ->comment('Số phút cho phép trễ (không trừ tiền)');
            
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_fee_policies');
    }
};
