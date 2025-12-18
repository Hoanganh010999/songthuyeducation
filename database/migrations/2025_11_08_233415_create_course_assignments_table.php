<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('class_lesson_sessions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('attachment_path')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('max_score')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->integer('submissions_count')->default(0);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['class_id', 'status']);
            $table->index('session_id');
            $table->index('created_by');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};
