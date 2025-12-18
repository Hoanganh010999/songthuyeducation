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
        // Add pronunciation check results to vocabulary_audio_recordings
        Schema::table('vocabulary_audio_recordings', function (Blueprint $table) {
            // Overall scores
            $table->decimal('overall_score', 5, 2)->nullable()->after('duration_seconds');
            $table->decimal('accuracy_score', 5, 2)->nullable();
            $table->decimal('fluency_score', 5, 2)->nullable();
            $table->decimal('completeness_score', 5, 2)->nullable();
            
            // Transcribed text (what user actually said)
            $table->text('transcribed_text')->nullable();
            
            // Detailed phoneme results (JSON)
            $table->json('phoneme_results')->nullable()->comment('Detailed phoneme-by-phoneme analysis');
            
            // AI feedback
            $table->text('feedback')->nullable();
            
            // Status
            $table->enum('check_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('checked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vocabulary_audio_recordings', function (Blueprint $table) {
            $table->dropColumn([
                'overall_score',
                'accuracy_score',
                'fluency_score',
                'completeness_score',
                'transcribed_text',
                'phoneme_results',
                'feedback',
                'check_status',
                'checked_at'
            ]);
        });
    }
};

