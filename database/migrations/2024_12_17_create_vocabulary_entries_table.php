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
        Schema::create('vocabulary_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('branch_id')->default(0)->index();
            $table->string('word')->index(); // English word
            $table->string('word_form')->nullable(); // noun, verb, adjective, etc.
            $table->text('definition')->nullable(); // Definition in Vietnamese or English
            $table->text('synonym')->nullable(); // Synonyms
            $table->text('antonym')->nullable(); // Antonyms
            $table->text('example')->nullable(); // Example sentence
            $table->integer('review_count')->default(0); // Number of times reviewed
            $table->timestamp('last_reviewed_at')->nullable(); // Last review date
            $table->tinyInteger('mastery_level')->default(0); // 0-5 mastery level
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index for faster queries
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'mastery_level']);
        });
        
        // Table for pronunciation audio recordings (for cleanup cronjob)
        Schema::create('vocabulary_audio_recordings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('vocabulary_entry_id')->nullable();
            $table->string('file_path'); // Path to audio file
            $table->integer('duration_seconds')->default(3);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vocabulary_entry_id')->references('id')->on('vocabulary_entries')->onDelete('cascade');
            
            // Index for cronjob cleanup
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabulary_audio_recordings');
        Schema::dropIfExists('vocabulary_entries');
    }
};

