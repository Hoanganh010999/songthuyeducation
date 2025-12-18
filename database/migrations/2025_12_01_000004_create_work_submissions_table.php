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
        Schema::create('work_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');

            $table->integer('submission_number')->default(1);
            $table->text('description')->nullable();

            $table->enum('status', ['pending_review', 'revision_requested', 'approved'])->default('pending_review');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->integer('quality_rating')->nullable(); // 1-5 stars

            // Metadata cho tính điểm HAY System
            $table->json('evaluation_data')->nullable(); // {quality_score, timeliness_score}

            $table->timestamps();

            $table->index(['work_item_id', 'submission_number']);
            $table->index(['submitted_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_submissions');
    }
};
