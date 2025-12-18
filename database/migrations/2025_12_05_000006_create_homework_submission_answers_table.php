<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('homework_submissions')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('homework_exercises')->onDelete('cascade');

            // Answer data
            $table->json('answer')->nullable(); // Student's answer
            $table->text('answer_text')->nullable(); // For text-based answers

            // Scoring
            $table->decimal('points_earned', 8, 2)->nullable();
            $table->decimal('points_possible', 8, 2)->nullable();
            $table->boolean('is_correct')->nullable();

            // Feedback
            $table->text('auto_feedback')->nullable(); // Automated feedback
            $table->text('teacher_feedback')->nullable(); // Manual feedback

            // Metadata
            $table->integer('time_spent')->nullable(); // Seconds spent on this exercise
            $table->integer('attempt_count')->default(0);
            $table->json('metadata')->nullable(); // Additional data

            $table->timestamps();

            // Unique constraint
            $table->unique(['submission_id', 'exercise_id']);

            // Indexes
            $table->index('submission_id');
            $table->index('exercise_id');
            $table->index('is_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submission_answers');
    }
};
