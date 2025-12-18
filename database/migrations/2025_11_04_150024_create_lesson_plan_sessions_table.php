<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained()->onDelete('cascade');
            $table->integer('session_number'); // Buổi học thứ mấy (1, 2, 3, ...)
            $table->string('lesson_title'); // Tên bài học: Phương trình bậc nhất
            $table->text('lesson_objectives')->nullable(); // Mục tiêu bài học
            $table->text('lesson_content')->nullable(); // Nội dung chính
            $table->string('lesson_plan_url')->nullable(); // Link Google Drive giáo án
            $table->string('materials_url')->nullable(); // Link Google Drive tài liệu
            $table->string('homework_url')->nullable(); // Link Google Drive bài tập về nhà
            $table->json('additional_resources')->nullable(); // Videos, PDFs, etc.
            $table->integer('duration_minutes')->default(45); // Thời lượng dự kiến
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['lesson_plan_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_sessions');
    }
};
