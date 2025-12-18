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
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->cascadeOnDelete();
            $table->foreignId('test_question_id')->nullable()->constrained('test_questions')->nullOnDelete();
            $table->integer('question_number')->nullable()->comment('Question number for practice tests without question records');

            // Answer content - flexible JSON for all question types
            $table->json('answer')->nullable();

            // For audio responses (Speaking)
            $table->string('audio_file_path', 500)->nullable();
            $table->integer('audio_duration')->nullable();

            // For essay/writing
            $table->longText('text_answer')->nullable();
            $table->integer('word_count')->nullable();

            // Scoring
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->nullable();
            $table->decimal('max_points', 5, 2)->nullable();

            // For partial credit (e.g., 2 out of 3 correct)
            $table->integer('correct_parts')->nullable();
            $table->integer('total_parts')->nullable();

            // Manual grading
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('graded_at')->nullable();

            // IELTS Writing specific criteria
            $table->json('grading_criteria')->nullable()->comment('Task Achievement, Coherence, Lexical, Grammar scores');

            // Tracking
            $table->integer('time_spent')->nullable()->comment('Seconds spent on this question');
            $table->datetime('answered_at')->nullable();
            $table->integer('changes_count')->default(0)->comment('How many times answer was changed');

            $table->timestamps();

            // Indexes
            $table->index(['submission_id', 'question_id']);
            $table->index('is_correct');
            $table->unique(['submission_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
