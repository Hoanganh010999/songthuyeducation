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
        // Test analytics
        Schema::create('test_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();

            $table->integer('total_attempts')->default(0);
            $table->integer('unique_users')->default(0);
            $table->decimal('avg_score', 5, 2)->nullable();
            $table->decimal('highest_score', 5, 2)->nullable();
            $table->decimal('lowest_score', 5, 2)->nullable();
            $table->integer('avg_time_spent')->nullable()->comment('Average seconds');
            $table->decimal('pass_rate', 5, 2)->nullable();
            $table->decimal('completion_rate', 5, 2)->nullable();

            $table->json('score_distribution')->nullable();
            $table->json('daily_attempts')->nullable();

            $table->datetime('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique('test_id');
        });

        // Question analytics
        Schema::create('question_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();

            $table->integer('total_attempts')->default(0);
            $table->decimal('correct_rate', 5, 2)->nullable();
            $table->integer('avg_time_spent')->nullable();

            $table->json('option_distribution')->nullable()->comment('Distribution of selected options');
            $table->json('common_wrong_answers')->nullable();

            $table->decimal('discrimination_index', 4, 3)->nullable()->comment('How well question differentiates students');
            $table->decimal('difficulty_index', 4, 3)->nullable();

            $table->datetime('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique('question_id');
        });

        // User progress tracking
        Schema::create('user_exam_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Overall stats
            $table->integer('total_tests_taken')->default(0);
            $table->integer('total_questions_answered')->default(0);
            $table->decimal('overall_accuracy', 5, 2)->nullable();
            $table->integer('total_time_spent')->default(0)->comment('Total seconds');

            // Skill-specific stats (for IELTS)
            $table->json('skill_scores')->nullable()->comment('Average scores per skill');
            $table->json('recent_scores')->nullable()->comment('Last 10 test scores');
            $table->json('strengths')->nullable()->comment('Strong categories');
            $table->json('weaknesses')->nullable()->comment('Weak categories');

            // Streaks and achievements
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_exam_progress');
        Schema::dropIfExists('question_analytics');
        Schema::dropIfExists('test_analytics');
    }
};
