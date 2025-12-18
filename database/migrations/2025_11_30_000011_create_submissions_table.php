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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('attempt_number')->default(1);

            // Timing
            $table->datetime('started_at')->nullable();
            $table->datetime('submitted_at')->nullable();
            $table->integer('time_spent')->nullable()->comment('Time spent in seconds');
            $table->integer('time_remaining')->nullable()->comment('Remaining time when submitted');

            // Scoring
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();

            // For IELTS
            $table->decimal('band_score', 3, 1)->nullable();
            $table->json('skill_scores')->nullable()->comment('Scores per skill');

            // Status
            $table->enum('status', [
                'not_started',
                'in_progress',
                'submitted',
                'grading',
                'graded',
                'late',
                'expired'
            ])->default('not_started');

            // Grading
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('graded_at')->nullable();

            // Anti-cheating
            $table->integer('tab_switches')->default(0);
            $table->integer('focus_lost_count')->default(0);
            $table->json('activity_log')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['assignment_id', 'user_id', 'attempt_number']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->unique(['assignment_id', 'user_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
