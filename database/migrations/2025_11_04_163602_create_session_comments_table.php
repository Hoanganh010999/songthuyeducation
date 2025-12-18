<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('class_lesson_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->integer('rating')->nullable()->comment('1-5 stars');
            $table->enum('behavior', ['excellent', 'good', 'average', 'needs_improvement'])->nullable();
            $table->enum('participation', ['active', 'moderate', 'passive'])->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'student_id']);
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_comments');
    }
};
