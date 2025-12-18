<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_lesson_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_plan_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_schedule_id')->nullable()->constrained()->nullOnDelete(); // Lịch học cố định
            $table->date('scheduled_date'); // Ngày dự kiến học
            $table->date('actual_date')->nullable(); // Ngày thực tế học (nếu thay đổi)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled', 'holiday'])->default('scheduled');
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('rescheduled_to')->nullable()->constrained('class_lesson_sessions')->nullOnDelete(); // Link to new session if rescheduled
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['class_id', 'scheduled_date']);
            $table->index(['lesson_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_lesson_sessions');
    }
};
