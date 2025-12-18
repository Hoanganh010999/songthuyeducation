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
        Schema::create('lesson_plan_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_stage_id')->constrained('lesson_plan_stages')->onDelete('cascade');

            // Lesson Procedure fields based on TECP template
            $table->text('stage_content')->nullable()->comment('Main stage content/description');

            // Instructions section
            $table->text('instructions')->nullable();
            $table->text('icqs')->nullable()->comment('Instruction Checking Questions');
            $table->integer('instruction_timing')->default(0)->comment('Time for instructions in minutes');
            $table->enum('instruction_interaction', ['T-Ss', 'Ss-T', 'SS-Ss', 'Ss-text', 'T-S'])->default('T-Ss');

            // Task completion & Monitoring section
            $table->text('task_completion')->nullable();
            $table->text('monitoring_points')->nullable();
            $table->integer('task_timing')->default(0)->comment('Time for task in minutes');
            $table->enum('task_interaction', ['T-Ss', 'Ss-T', 'SS-Ss', 'Ss-text', 'T-S'])->default('SS-Ss');

            // Feedback section
            $table->text('feedback')->nullable();
            $table->integer('feedback_timing')->default(0)->comment('Time for feedback in minutes');
            $table->enum('feedback_interaction', ['T-Ss', 'Ss-T', 'SS-Ss', 'Ss-text', 'T-S'])->default('T-Ss');

            // Problems and Solutions
            $table->json('learner_problems')->nullable()->comment('Array of {problem, solution}');
            $table->json('task_problems')->nullable()->comment('Array of {problem, solution}');

            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['lesson_plan_stage_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_procedures');
    }
};
