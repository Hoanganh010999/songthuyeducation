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
        Schema::create('test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();

            // For IELTS/Cambridge
            $table->enum('skill', ['listening', 'reading', 'writing', 'speaking', 'use_of_english', 'general'])->nullable();

            // Configuration
            $table->integer('time_limit')->nullable()->comment('Time limit in minutes for this section');
            $table->decimal('total_points', 5, 2)->nullable();
            $table->integer('sort_order')->default(0);

            // Media
            $table->foreignId('audio_track_id')->nullable()->constrained('audio_tracks')->nullOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained('reading_passages')->nullOnDelete();
            $table->json('media')->nullable();

            // Settings
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['test_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_sections');
    }
};
