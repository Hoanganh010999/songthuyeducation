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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Basic info
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();

            // Scheduling
            $table->datetime('start_date')->nullable();
            $table->datetime('due_date')->nullable();
            $table->boolean('late_submission')->default(false);
            $table->decimal('late_penalty', 5, 2)->default(0)->comment('Percentage penalty for late submission');
            $table->integer('late_days_allowed')->default(0);

            // Assignment target
            $table->enum('assign_type', ['user', 'class', 'branch', 'course', 'all'])->default('user');

            // Configuration
            $table->integer('max_attempts')->nullable()->comment('Override test max_attempts');
            $table->integer('time_limit')->nullable()->comment('Override test time_limit');
            $table->boolean('shuffle_questions')->nullable();
            $table->boolean('shuffle_options')->nullable();

            // Grading
            $table->enum('grading_type', ['auto', 'manual', 'mixed'])->default('auto');
            $table->decimal('passing_score', 5, 2)->nullable();

            // Notification
            $table->boolean('notify_on_assign')->default(true);
            $table->boolean('notify_on_due')->default(true);
            $table->boolean('notify_on_grade')->default(true);

            // Metadata
            $table->json('settings')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['test_id', 'status']);
            $table->index(['start_date', 'due_date']);
            $table->index(['branch_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
