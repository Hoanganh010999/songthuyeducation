<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_exercises', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Classification
            $table->enum('skill', ['reading', 'writing', 'listening', 'speaking', 'grammar', 'vocabulary', 'math', 'science', 'general'])->default('general');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('medium');
            $table->enum('type', [
                'multiple_choice',
                'multiple_answers',
                'true_false',
                'yes_no',
                'yes_no_ng',
                'fill_blanks',
                'fill_blanks_select',
                'matching',
                'matching_headings',
                'matching_features',
                'matching_sentence_endings',
                'ordering',
                'sentence_completion',
                'summary_completion',
                'note_completion',
                'table_completion',
                'flow_chart_completion',
                'diagram_labeling',
                'short_answer',
                'essay',
                'audio_response',
                'drag_drop',
                'hotspot',
                'labeling'
            ])->default('multiple_choice');

            // Content
            $table->string('title', 500);
            $table->json('content')->nullable(); // Flexible content structure
            $table->text('explanation')->nullable();
            $table->json('correct_answer')->nullable();

            // Media and resources
            $table->string('image_url')->nullable();
            $table->json('media')->nullable(); // Audio, video, etc.

            // Scoring
            $table->decimal('points', 8, 2)->default(1);
            $table->integer('time_limit')->nullable(); // Seconds
            $table->boolean('partial_credit')->default(false);

            // Metadata
            $table->json('tags')->nullable();
            $table->json('settings')->nullable();

            // Organization - IMPORTANT: Branch filtering
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('branch_id'); // Critical for branch filtering
            $table->index('skill');
            $table->index('type');
            $table->index('difficulty');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_exercises');
    }
};
