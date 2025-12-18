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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Basic info
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();

            // Classification
            $table->enum('type', ['ielts', 'cambridge', 'toeic', 'custom', 'placement', 'quiz', 'practice'])->default('custom');
            $table->string('subtype', 50)->nullable()->comment('academic, general, ket, pet, fce, cae, cpe...');

            // Configuration
            $table->integer('time_limit')->nullable()->comment('Time limit in minutes');
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->decimal('total_points', 8, 2)->nullable();
            $table->integer('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->enum('show_results', ['immediately', 'after_submit', 'after_deadline', 'after_grading', 'never'])->default('after_submit');
            $table->boolean('show_answers')->default(false);
            $table->boolean('show_explanation')->default(false);
            $table->boolean('allow_review')->default(true);
            $table->boolean('require_camera')->default(false);
            $table->boolean('prevent_copy')->default(true);
            $table->boolean('prevent_tab_switch')->default(false);

            // Scheduling
            $table->datetime('available_from')->nullable();
            $table->datetime('available_until')->nullable();

            // Media
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('cover_image_url', 500)->nullable();

            // Metadata
            $table->json('tags')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'subtype', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
