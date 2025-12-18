<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkSubmission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkPerformanceCalculator
{
    /**
     * Calculate comprehensive performance metrics for HAY System
     */
    public function calculatePerformanceMetrics(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Get work items as executor
        $workItems = $this->getUserWorkItems($user, $startDate, $endDate);

        // Calculate individual metrics
        $completionMetrics = $this->calculateCompletionMetrics($workItems);
        $qualityMetrics = $this->calculateQualityMetrics($workItems, $user);
        $timelinessMetrics = $this->calculateTimelinessMetrics($workItems);
        $complexityMetrics = $this->calculateComplexityMetrics($workItems);
        $volumeMetrics = $this->calculateVolumeMetrics($workItems);

        // Calculate weighted overall score (0-100 scale)
        $overallScore = $this->calculateOverallScore(
            $completionMetrics,
            $qualityMetrics,
            $timelinessMetrics,
            $complexityMetrics,
            $volumeMetrics
        );

        return [
            'user_id' => $user->id,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'completion' => $completionMetrics,
            'quality' => $qualityMetrics,
            'timeliness' => $timelinessMetrics,
            'complexity' => $complexityMetrics,
            'volume' => $volumeMetrics,
            'overall_score' => $overallScore,
            'hay_system_ready' => true,
        ];
    }

    /**
     * Get user's work items for the period
     */
    private function getUserWorkItems(User $user, Carbon $startDate, Carbon $endDate)
    {
        return WorkItem::whereHas('assignments', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where('role', 'executor');
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->with(['submissions', 'assignments'])
        ->get();
    }

    /**
     * Calculate completion metrics
     */
    private function calculateCompletionMetrics($workItems): array
    {
        $total = $workItems->count();
        $completed = $workItems->where('status', 'completed')->count();
        $inProgress = $workItems->whereIn('status', ['assigned', 'in_progress', 'submitted'])->count();
        $cancelled = $workItems->where('status', 'cancelled')->count();

        $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;

        return [
            'total_assigned' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'cancelled' => $cancelled,
            'completion_rate' => round($completionRate, 2),
            'score' => round($completionRate, 2), // 0-100 scale
        ];
    }

    /**
     * Calculate quality metrics
     */
    private function calculateQualityMetrics($workItems, User $user): array
    {
        $submissions = WorkSubmission::whereIn('work_item_id', $workItems->pluck('id'))
            ->where('submitted_by', $user->id)
            ->get();

        $totalSubmissions = $submissions->count();
        $withRatings = $submissions->whereNotNull('quality_rating');

        // Average quality rating (1-5 scale)
        $avgRating = $withRatings->avg('quality_rating') ?? 0;

        // First-time approval rate
        $workItemsWithSubmissions = $workItems->filter(function ($item) {
            return $item->submissions->count() > 0;
        });

        $firstTimeApprovals = $workItemsWithSubmissions->filter(function ($item) {
            return $item->submissions->count() === 1 &&
                   $item->submissions->first()->status === 'approved';
        })->count();

        $firstTimeApprovalRate = $workItemsWithSubmissions->count() > 0
            ? ($firstTimeApprovals / $workItemsWithSubmissions->count()) * 100
            : 0;

        // Revision count
        $avgRevisions = $workItemsWithSubmissions->count() > 0
            ? $workItemsWithSubmissions->sum(fn($item) => $item->submissions->count() - 1) / $workItemsWithSubmissions->count()
            : 0;

        // Quality score (0-100 scale)
        $qualityScore = (
            ($avgRating / 5 * 60) +                    // 60% from rating
            ($firstTimeApprovalRate * 0.4)              // 40% from first-time approval
        );

        return [
            'total_submissions' => $totalSubmissions,
            'avg_quality_rating' => round($avgRating, 2),
            'first_time_approval_rate' => round($firstTimeApprovalRate, 2),
            'avg_revisions' => round($avgRevisions, 2),
            'score' => round($qualityScore, 2), // 0-100 scale
        ];
    }

    /**
     * Calculate timeliness metrics
     */
    private function calculateTimelinessMetrics($workItems): array
    {
        $completedItems = $workItems->filter(function ($item) {
            return $item->status === 'completed' &&
                   $item->due_date &&
                   $item->completed_at;
        });

        $total = $completedItems->count();

        if ($total === 0) {
            return [
                'total_completed_with_deadline' => 0,
                'on_time' => 0,
                'late' => 0,
                'on_time_rate' => 0,
                'avg_delay_days' => 0,
                'score' => 0,
            ];
        }

        $onTime = 0;
        $late = 0;
        $totalDelayDays = 0;

        foreach ($completedItems as $item) {
            $dueDate = Carbon::parse($item->due_date);
            $completedDate = Carbon::parse($item->completed_at);

            if ($completedDate->lte($dueDate)) {
                $onTime++;
            } else {
                $late++;
                $totalDelayDays += $completedDate->diffInDays($dueDate);
            }
        }

        $onTimeRate = ($onTime / $total) * 100;
        $avgDelayDays = $late > 0 ? $totalDelayDays / $late : 0;

        // Timeliness score (0-100 scale)
        // Penalize based on average delay
        $delayPenalty = min($avgDelayDays * 5, 50); // Max 50 points penalty
        $timelinessScore = max($onTimeRate - $delayPenalty, 0);

        return [
            'total_completed_with_deadline' => $total,
            'on_time' => $onTime,
            'late' => $late,
            'on_time_rate' => round($onTimeRate, 2),
            'avg_delay_days' => round($avgDelayDays, 2),
            'score' => round($timelinessScore, 2), // 0-100 scale
        ];
    }

    /**
     * Calculate complexity metrics
     */
    private function calculateComplexityMetrics($workItems): array
    {
        $withComplexity = $workItems->filter(function ($item) {
            return isset($item->metadata['complexity']);
        });

        if ($withComplexity->count() === 0) {
            return [
                'total_with_complexity' => 0,
                'avg_complexity' => 0,
                'complexity_distribution' => [],
                'score' => 0,
            ];
        }

        $avgComplexity = $withComplexity->avg('metadata.complexity');

        // Complexity distribution (1-10 scale)
        $distribution = [
            'low' => $withComplexity->filter(fn($item) => $item->metadata['complexity'] <= 3)->count(),
            'medium' => $withComplexity->filter(fn($item) => $item->metadata['complexity'] > 3 && $item->metadata['complexity'] <= 7)->count(),
            'high' => $withComplexity->filter(fn($item) => $item->metadata['complexity'] > 7)->count(),
        ];

        // Complexity score (0-100 scale)
        // Higher complexity = higher score (reward for difficult work)
        $complexityScore = ($avgComplexity / 10) * 100;

        return [
            'total_with_complexity' => $withComplexity->count(),
            'avg_complexity' => round($avgComplexity, 2),
            'complexity_distribution' => $distribution,
            'score' => round($complexityScore, 2), // 0-100 scale
        ];
    }

    /**
     * Calculate volume metrics
     */
    private function calculateVolumeMetrics($workItems): array
    {
        $totalEstimatedHours = $workItems->sum('estimated_hours') ?? 0;
        $totalActualHours = $workItems->sum('actual_hours') ?? 0;

        $efficiencyRate = $totalEstimatedHours > 0
            ? ($totalEstimatedHours / max($totalActualHours, 1)) * 100
            : 0;

        // Volume score based on workload
        // Normalized to 0-100 scale (assume 160 hours/month as baseline)
        $volumeScore = min(($totalEstimatedHours / 160) * 100, 100);

        return [
            'total_estimated_hours' => round($totalEstimatedHours, 2),
            'total_actual_hours' => round($totalActualHours, 2),
            'efficiency_rate' => round($efficiencyRate, 2),
            'score' => round($volumeScore, 2), // 0-100 scale
        ];
    }

    /**
     * Calculate overall weighted performance score for HAY System
     */
    private function calculateOverallScore(
        array $completion,
        array $quality,
        array $timeliness,
        array $complexity,
        array $volume
    ): array {
        // Weighted scoring (customizable based on organization needs)
        $weights = [
            'completion' => 0.25,    // 25%
            'quality' => 0.30,       // 30%
            'timeliness' => 0.20,    // 20%
            'complexity' => 0.15,    // 15%
            'volume' => 0.10,        // 10%
        ];

        $weightedScore = (
            ($completion['score'] * $weights['completion']) +
            ($quality['score'] * $weights['quality']) +
            ($timeliness['score'] * $weights['timeliness']) +
            ($complexity['score'] * $weights['complexity']) +
            ($volume['score'] * $weights['volume'])
        );

        // Performance rating
        $rating = $this->getPerformanceRating($weightedScore);

        return [
            'score' => round($weightedScore, 2),
            'rating' => $rating,
            'weights_used' => $weights,
            'breakdown' => [
                'completion' => round($completion['score'] * $weights['completion'], 2),
                'quality' => round($quality['score'] * $weights['quality'], 2),
                'timeliness' => round($timeliness['score'] * $weights['timeliness'], 2),
                'complexity' => round($complexity['score'] * $weights['complexity'], 2),
                'volume' => round($volume['score'] * $weights['volume'], 2),
            ],
        ];
    }

    /**
     * Get performance rating based on score
     */
    private function getPerformanceRating(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Outstanding',      // Xuất sắc
            $score >= 80 => 'Excellent',        // Tốt
            $score >= 70 => 'Good',             // Khá
            $score >= 60 => 'Satisfactory',     // Trung bình
            $score >= 50 => 'Needs Improvement', // Cần cải thiện
            default => 'Unsatisfactory',        // Không đạt
        };
    }

    /**
     * Get monthly performance for HAY System
     */
    public function getMonthlyPerformance(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return $this->calculatePerformanceMetrics($user, $startDate, $endDate);
    }

    /**
     * Get yearly performance for HAY System
     */
    public function getYearlyPerformance(User $user, int $year): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = Carbon::create($year, 12, 31)->endOfYear();

        $yearlyMetrics = $this->calculatePerformanceMetrics($user, $startDate, $endDate);

        // Also calculate monthly breakdown
        $monthlyBreakdown = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyBreakdown[$month] = $this->getMonthlyPerformance($user, $year, $month);
        }

        $yearlyMetrics['monthly_breakdown'] = $monthlyBreakdown;

        return $yearlyMetrics;
    }

    /**
     * Compare performance between two periods
     */
    public function comparePerformance(
        User $user,
        Carbon $period1Start,
        Carbon $period1End,
        Carbon $period2Start,
        Carbon $period2End
    ): array {
        $period1Metrics = $this->calculatePerformanceMetrics($user, $period1Start, $period1End);
        $period2Metrics = $this->calculatePerformanceMetrics($user, $period2Start, $period2End);

        return [
            'period1' => $period1Metrics,
            'period2' => $period2Metrics,
            'improvement' => [
                'overall_score' => $period2Metrics['overall_score']['score'] - $period1Metrics['overall_score']['score'],
                'completion_rate' => $period2Metrics['completion']['completion_rate'] - $period1Metrics['completion']['completion_rate'],
                'quality_score' => $period2Metrics['quality']['score'] - $period1Metrics['quality']['score'],
                'timeliness_score' => $period2Metrics['timeliness']['score'] - $period1Metrics['timeliness']['score'],
            ],
        ];
    }
}
