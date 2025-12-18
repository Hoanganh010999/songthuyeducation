<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ca sáng, Ca chiều, Ca tối
            $table->string('code'); // MORNING, AFTERNOON, EVENING
            $table->time('start_time'); // 07:00
            $table->time('end_time'); // 11:00
            $table->integer('duration_minutes'); // 240 phút
            $table->integer('lesson_duration')->default(45); // Thời lượng 1 tiết (phút)
            $table->integer('break_duration')->default(10); // Thời gian nghỉ giữa các tiết
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_periods');
    }
};
