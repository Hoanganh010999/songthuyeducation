<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('study_period_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->integer('lesson_number'); // Tiết thứ mấy trong ca (1, 2, 3, 4)
            $table->time('start_time'); // Thời gian bắt đầu cụ thể
            $table->time('end_time'); // Thời gian kết thúc cụ thể
            $table->date('effective_from')->nullable(); // Áp dụng từ ngày
            $table->date('effective_to')->nullable(); // Đến ngày
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate schedules
            $table->unique(['class_id', 'day_of_week', 'study_period_id', 'lesson_number'], 'unique_class_schedule');
            $table->index(['teacher_id', 'day_of_week', 'study_period_id']); // Check teacher conflicts
            $table->index(['room_id', 'day_of_week', 'study_period_id']); // Check room conflicts
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
