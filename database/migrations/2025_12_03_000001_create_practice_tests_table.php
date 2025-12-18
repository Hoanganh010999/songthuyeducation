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
        Schema::create('practice_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Foreign keys to tests table for each skill
            $table->foreignId('reading_test_id')->nullable()->constrained('tests')->onDelete('set null');
            $table->foreignId('writing_test_id')->nullable()->constrained('tests')->onDelete('set null');
            $table->foreignId('listening_test_id')->nullable()->constrained('tests')->onDelete('set null');
            $table->foreignId('speaking_test_id')->nullable()->constrained('tests')->onDelete('set null');

            // Additional fields
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->boolean('is_active')->default(true);
            $table->integer('duration')->nullable()->comment('Total duration in minutes');
            $table->integer('order')->default(0)->comment('Display order');

            // Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('difficulty');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_tests');
    }
};
