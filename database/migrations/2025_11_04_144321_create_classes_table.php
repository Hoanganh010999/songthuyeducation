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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Lớp 10A1, 11B2, etc.
            $table->string('code')->unique(); // CLASS_10A1
            $table->text('description')->nullable();
            $table->string('academic_year'); // 2024-2025
            $table->enum('level', ['elementary', 'middle', 'high', 'university'])->default('high');
            $table->integer('capacity')->default(40); // Sĩ số tối đa
            $table->integer('current_students')->default(0); // Sĩ số hiện tại
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete(); // Giáo viên chủ nhiệm
            $table->string('room_number')->nullable(); // Phòng học
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_id', 'academic_year']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
