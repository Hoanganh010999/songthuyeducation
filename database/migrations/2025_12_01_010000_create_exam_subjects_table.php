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
        // Bảng môn học cho module Examination
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Tên môn học: Tiếng Anh, Toán, Vật lý...
            $table->string('code', 50)->unique();            // Mã môn học: english, math, physics...
            $table->text('description')->nullable();
            $table->string('icon')->nullable();              // Icon class hoặc URL
            $table->string('color', 20)->nullable();         // Màu đại diện: #3B82F6
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Bảng phân loại/kỹ năng trong mỗi môn học
        Schema::create('exam_subject_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('exam_subjects')->onDelete('cascade');
            $table->string('name');                          // Tên: Listening, Reading, Writing, Speaking
            $table->string('code', 50);                      // Mã: listening, reading, writing, speaking
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['subject_id', 'code']);
        });

        // Cập nhật bảng questions để liên kết với subject và category
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('category_id')->constrained('exam_subjects')->onDelete('set null');
            $table->foreignId('subject_category_id')->nullable()->after('subject_id')->constrained('exam_subject_categories')->onDelete('set null');
        });

        // Cập nhật bảng tests để liên kết với subject
        Schema::table('tests', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('type')->constrained('exam_subjects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['subject_category_id']);
            $table->dropColumn(['subject_id', 'subject_category_id']);
        });

        Schema::dropIfExists('exam_subject_categories');
        Schema::dropIfExists('exam_subjects');
    }
};
