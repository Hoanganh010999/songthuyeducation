<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // GV tạo giáo án
            $table->string('name'); // Giáo án Toán 10 - Học kỳ 1
            $table->string('code')->unique(); // LP_MATH_10_SEM1
            $table->text('description')->nullable();
            $table->integer('total_sessions'); // Tổng số buổi học
            $table->string('level')->nullable(); // Cấp độ: elementary, middle, high
            $table->string('academic_year')->nullable(); // 2024-2025
            $table->enum('status', ['draft', 'approved', 'in_use', 'archived'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_id', 'subject_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
