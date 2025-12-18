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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Classification
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->nullOnDelete();
            $table->enum('skill', ['listening', 'reading', 'writing', 'speaking', 'grammar', 'vocabulary', 'general'])->default('general');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('medium');

            // Question type (H5P-style)
            $table->enum('type', [
                'multiple_choice',      // Single answer MCQ
                'multiple_response',    // Multiple answers
                'fill_blanks',          // Fill in the blanks
                'fill_blanks_drag',     // Fill blanks with drag & drop
                'matching',             // Match columns
                'drag_drop',            // Drag and drop
                'ordering',             // Order items
                'true_false',           // True/False
                'true_false_ng',        // True/False/Not Given (IELTS)
                'essay',                // Long essay
                'short_answer',         // Short text answer
                'audio_response',       // Record audio (Speaking)
                'hotspot',              // Click on image area
                'labeling',             // Label diagram
                'sentence_completion',  // Complete sentences
                'summary_completion',   // Complete summary
                'note_completion',      // Complete notes
                'table_completion',     // Complete table
                'flow_chart',           // Complete flow chart
                'matching_headings',    // Match headings to paragraphs
                'matching_features',    // Match features
                'matching_sentence_endings' // Match sentence endings
            ])->default('multiple_choice');

            // Content
            $table->string('title', 500);
            $table->json('content')->nullable()->comment('Flexible content structure based on type');
            $table->text('explanation')->nullable()->comment('Answer explanation');
            $table->json('correct_answer')->nullable()->comment('Correct answer(s)');

            // Media references
            $table->foreignId('audio_track_id')->nullable()->constrained('audio_tracks')->nullOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained('reading_passages')->nullOnDelete();
            $table->string('image_url', 500)->nullable();
            $table->json('media')->nullable()->comment('Additional media attachments');

            // Scoring
            $table->decimal('points', 5, 2)->default(1);
            $table->integer('time_limit')->nullable()->comment('Time limit in seconds');
            $table->boolean('partial_credit')->default(false)->comment('Allow partial scoring');

            // Metadata
            $table->json('tags')->nullable();
            $table->json('settings')->nullable()->comment('Additional settings');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['category_id', 'skill', 'difficulty', 'status']);
            $table->index(['type', 'status']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
