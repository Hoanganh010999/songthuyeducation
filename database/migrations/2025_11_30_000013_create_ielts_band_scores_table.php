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
        // IELTS band score conversion table
        Schema::create('ielts_band_scores', function (Blueprint $table) {
            $table->id();
            $table->enum('test_type', ['academic', 'general']);
            $table->enum('skill', ['listening', 'reading']);
            $table->integer('raw_score_min');
            $table->integer('raw_score_max');
            $table->decimal('band_score', 2, 1);
            $table->timestamps();

            $table->index(['test_type', 'skill']);
        });

        // Pre-populate IELTS Listening band scores (both Academic & General use same scale)
        $this->seedListeningBandScores();

        // Pre-populate IELTS Reading band scores
        $this->seedReadingBandScores();
    }

    private function seedListeningBandScores(): void
    {
        $scores = [
            ['min' => 39, 'max' => 40, 'band' => 9.0],
            ['min' => 37, 'max' => 38, 'band' => 8.5],
            ['min' => 35, 'max' => 36, 'band' => 8.0],
            ['min' => 33, 'max' => 34, 'band' => 7.5],
            ['min' => 30, 'max' => 32, 'band' => 7.0],
            ['min' => 27, 'max' => 29, 'band' => 6.5],
            ['min' => 23, 'max' => 26, 'band' => 6.0],
            ['min' => 19, 'max' => 22, 'band' => 5.5],
            ['min' => 15, 'max' => 18, 'band' => 5.0],
            ['min' => 12, 'max' => 14, 'band' => 4.5],
            ['min' => 9, 'max' => 11, 'band' => 4.0],
            ['min' => 6, 'max' => 8, 'band' => 3.5],
            ['min' => 4, 'max' => 5, 'band' => 3.0],
            ['min' => 2, 'max' => 3, 'band' => 2.5],
            ['min' => 1, 'max' => 1, 'band' => 2.0],
            ['min' => 0, 'max' => 0, 'band' => 1.0],
        ];

        foreach (['academic', 'general'] as $type) {
            foreach ($scores as $score) {
                \DB::table('ielts_band_scores')->insert([
                    'test_type' => $type,
                    'skill' => 'listening',
                    'raw_score_min' => $score['min'],
                    'raw_score_max' => $score['max'],
                    'band_score' => $score['band'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedReadingBandScores(): void
    {
        // Academic Reading
        $academicScores = [
            ['min' => 39, 'max' => 40, 'band' => 9.0],
            ['min' => 37, 'max' => 38, 'band' => 8.5],
            ['min' => 35, 'max' => 36, 'band' => 8.0],
            ['min' => 33, 'max' => 34, 'band' => 7.5],
            ['min' => 30, 'max' => 32, 'band' => 7.0],
            ['min' => 27, 'max' => 29, 'band' => 6.5],
            ['min' => 23, 'max' => 26, 'band' => 6.0],
            ['min' => 19, 'max' => 22, 'band' => 5.5],
            ['min' => 15, 'max' => 18, 'band' => 5.0],
            ['min' => 12, 'max' => 14, 'band' => 4.5],
            ['min' => 9, 'max' => 11, 'band' => 4.0],
            ['min' => 6, 'max' => 8, 'band' => 3.5],
            ['min' => 4, 'max' => 5, 'band' => 3.0],
            ['min' => 2, 'max' => 3, 'band' => 2.5],
            ['min' => 1, 'max' => 1, 'band' => 2.0],
            ['min' => 0, 'max' => 0, 'band' => 1.0],
        ];

        foreach ($academicScores as $score) {
            \DB::table('ielts_band_scores')->insert([
                'test_type' => 'academic',
                'skill' => 'reading',
                'raw_score_min' => $score['min'],
                'raw_score_max' => $score['max'],
                'band_score' => $score['band'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // General Training Reading (different scale)
        $generalScores = [
            ['min' => 40, 'max' => 40, 'band' => 9.0],
            ['min' => 39, 'max' => 39, 'band' => 8.5],
            ['min' => 37, 'max' => 38, 'band' => 8.0],
            ['min' => 36, 'max' => 36, 'band' => 7.5],
            ['min' => 34, 'max' => 35, 'band' => 7.0],
            ['min' => 32, 'max' => 33, 'band' => 6.5],
            ['min' => 30, 'max' => 31, 'band' => 6.0],
            ['min' => 27, 'max' => 29, 'band' => 5.5],
            ['min' => 23, 'max' => 26, 'band' => 5.0],
            ['min' => 19, 'max' => 22, 'band' => 4.5],
            ['min' => 15, 'max' => 18, 'band' => 4.0],
            ['min' => 12, 'max' => 14, 'band' => 3.5],
            ['min' => 9, 'max' => 11, 'band' => 3.0],
            ['min' => 6, 'max' => 8, 'band' => 2.5],
            ['min' => 3, 'max' => 5, 'band' => 2.0],
            ['min' => 0, 'max' => 2, 'band' => 1.0],
        ];

        foreach ($generalScores as $score) {
            \DB::table('ielts_band_scores')->insert([
                'test_type' => 'general',
                'skill' => 'reading',
                'raw_score_min' => $score['min'],
                'raw_score_max' => $score['max'],
                'band_score' => $score['band'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ielts_band_scores');
    }
};
