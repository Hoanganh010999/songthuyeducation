<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IeltsBandScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_type',
        'skill',
        'raw_score_min',
        'raw_score_max',
        'band_score',
    ];

    protected $casts = [
        'raw_score_min' => 'integer',
        'raw_score_max' => 'integer',
        'band_score' => 'decimal:1',
    ];

    /**
     * Get band score for a raw score
     */
    public static function getBandScore(string $testType, string $skill, int $rawScore): ?float
    {
        return static::where('test_type', $testType)
            ->where('skill', $skill)
            ->where('raw_score_min', '<=', $rawScore)
            ->where('raw_score_max', '>=', $rawScore)
            ->value('band_score');
    }

    /**
     * Calculate overall band score from skill scores
     */
    public static function calculateOverallBand(array $skillScores): float
    {
        if (empty($skillScores)) {
            return 0;
        }

        $average = array_sum($skillScores) / count($skillScores);

        // Round to nearest 0.5
        return round($average * 2) / 2;
    }
}
