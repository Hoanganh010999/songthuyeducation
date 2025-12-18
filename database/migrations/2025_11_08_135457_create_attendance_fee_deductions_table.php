<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_fee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_lesson_sessions')->onDelete('cascade');
            $table->foreignId('policy_id')->nullable()->constrained('attendance_fee_policies')->nullOnDelete();
            
            $table->enum('deduction_type', [
                'unexcused_absence',       // Vắng không lý do
                'excused_over_limit',      // Vắng có lý do vượt giới hạn
                'late'                     // Đi trễ
            ]);
            
            $table->decimal('hourly_rate', 10, 2)->comment('Giá gốc/giờ của lớp');
            $table->decimal('deduction_percent', 5, 2)->comment('% trừ');
            $table->decimal('deduction_amount', 10, 2)->comment('Số tiền thực trừ');
            
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete()
                ->comment('Liên kết đến giao dịch ví');
            
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['student_id', 'class_id']);
            $table->index('session_id');
            $table->index('applied_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_fee_deductions');
    }
};
