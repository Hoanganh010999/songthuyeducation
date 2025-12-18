<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VocabularyEntry;
use App\Models\VocabularyAudioRecording;
use App\Models\ClassStudent;
use App\Services\ZaloNotificationService;
use App\Services\CustomerZaloNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendVocabularyDailyReport extends Command
{
    protected $signature = 'vocabulary:send-daily-report';
    protected $description = 'Send daily vocabulary learning report to students and parents via Zalo';

    protected ZaloNotificationService $zaloService;
    protected CustomerZaloNotificationService $customerZaloService;

    public function __construct(
        ZaloNotificationService $zaloService,
        CustomerZaloNotificationService $customerZaloService
    ) {
        parent::__construct();
        $this->zaloService = $zaloService;
        $this->customerZaloService = $customerZaloService;
    }

    public function handle()
    {
        $this->info('📊 Generating vocabulary daily reports...');

        // Check if vocabulary reports are enabled
        $enabled = DB::table('settings')
            ->where('key', 'vocabulary_daily_report_enabled')
            ->value('value');

        if (!$enabled || $enabled === '0') {
            $this->info('⚠️ Vocabulary daily reports are disabled');
            return 0;
        }

        $timezone = $this->getConfiguredTimezone();
        $today = Carbon::now($timezone)->startOfDay();
        $yesterday = $today->copy()->subDay();

        // Get users who have vocabulary activity today
        $activeUserIds = VocabularyAudioRecording::whereDate('created_at', $today->toDateString())
            ->distinct()
            ->pluck('user_id');

        if ($activeUserIds->isEmpty()) {
            $this->info('✅ No vocabulary activity today');
            return 0;
        }

        $sentCount = 0;

        foreach ($activeUserIds as $userId) {
            try {
                $user = User::find($userId);
                if (!$user) {
                    continue;
                }

                // Generate report for this user
                $report = $this->generateReport($user, $today, $yesterday);

                // Send to student
                if ($this->sendReportToStudent($user, $report)) {
                    $sentCount++;
                }

                // Send to parent if student is in any class (has parent contact)
                $this->sendReportToParent($user, $report);

                Log::info('[VocabularyReport] Report sent', [
                    'user_id' => $userId,
                    'words_today' => $report['words_today'],
                ]);
            } catch (\Exception $e) {
                Log::error('[VocabularyReport] Failed to send report', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✅ Sent {$sentCount} vocabulary report(s)");
        return 0;
    }

    /**
     * Generate vocabulary report for a user
     */
    protected function generateReport(User $user, Carbon $today, Carbon $yesterday): array
    {
        // Get recordings today
        $recordingsToday = VocabularyAudioRecording::where('user_id', $user->id)
            ->whereDate('created_at', $today->toDateString())
            ->get();

        // Get recordings yesterday
        $recordingsYesterday = VocabularyAudioRecording::where('user_id', $user->id)
            ->whereDate('created_at', $yesterday->toDateString())
            ->count();

        // Count unique words studied today (by vocabulary_entry_id)
        $wordsToday = $recordingsToday->unique('vocabulary_entry_id')->count();
        $wordsYesterday = $recordingsYesterday;

        // Calculate average scores
        $recordingsWithScores = $recordingsToday->filter(function ($recording) {
            return $recording->overall_score !== null && $recording->check_status === 'completed';
        });

        $avgOverallScore = $recordingsWithScores->avg('overall_score') ?? 0;
        $avgAccuracyScore = $recordingsWithScores->avg('accuracy_score') ?? 0;
        $avgFluencyScore = $recordingsWithScores->avg('fluency_score') ?? 0;
        $avgCompletenessScore = $recordingsWithScores->avg('completeness_score') ?? 0;

        // Pronunciation level based on overall score
        $pronunciationLevel = $this->getPronunciationLevel($avgOverallScore);

        // Total words in vocabulary book
        $totalWords = VocabularyEntry::where('user_id', $user->id)->count();

        return [
            'words_today' => $wordsToday,
            'words_yesterday' => $wordsYesterday,
            'total_words' => $totalWords,
            'recordings_count' => $recordingsToday->count(),
            'avg_overall_score' => round($avgOverallScore, 1),
            'avg_accuracy_score' => round($avgAccuracyScore, 1),
            'avg_fluency_score' => round($avgFluencyScore, 1),
            'avg_completeness_score' => round($avgCompletenessScore, 1),
            'pronunciation_level' => $pronunciationLevel,
        ];
    }

    /**
     * Get pronunciation level description
     */
    protected function getPronunciationLevel(float $score): string
    {
        if ($score >= 90) {
            return '🌟 Xuất sắc';
        } elseif ($score >= 80) {
            return '⭐ Tốt';
        } elseif ($score >= 70) {
            return '👍 Khá';
        } elseif ($score >= 60) {
            return '📝 Trung bình';
        } else {
            return '💪 Cần cố gắng';
        }
    }

    /**
     * Send report to student via Zalo
     */
    protected function sendReportToStudent(User $user, array $report): bool
    {
        if (empty($user->phone)) {
            return false;
        }

        $diff = $report['words_today'] - $report['words_yesterday'];
        $diffText = $diff > 0 ? "+{$diff}" : ($diff < 0 ? $diff : '0');
        $diffIcon = $diff > 0 ? '📈' : ($diff < 0 ? '📉' : '➡️');

        $message = "📊 BÁO CÁO HỌC TỪ VỰNG NGÀY HÔM NAY\n\n";
        $message .= "👋 Xin chào {$user->name},\n\n";
        $message .= "📚 Thống kê hôm nay:\n";
        $message .= "• Từ đã học: {$report['words_today']} từ\n";
        $message .= "• So với hôm qua: {$diffIcon} {$diffText} từ\n";
        $message .= "• Số lần phát âm: {$report['recordings_count']} lần\n\n";

        if ($report['avg_overall_score'] > 0) {
            $message .= "🎯 Đánh giá phát âm:\n";
            $message .= "• Tổng điểm: {$report['avg_overall_score']}/100\n";
            $message .= "• Độ chính xác: {$report['avg_accuracy_score']}/100\n";
            $message .= "• Lưu loát: {$report['avg_fluency_score']}/100\n";
            $message .= "• Hoàn thiện: {$report['avg_completeness_score']}/100\n";
            $message .= "• Xếp loại: {$report['pronunciation_level']}\n\n";
        }

        $message .= "📖 Tổng từ vựng: {$report['total_words']} từ\n\n";
        $message .= "💡 Hãy tiếp tục học tập chăm chỉ! 💪";

        try {
            // Try to send via teacher notification service first
            $account = $this->getZaloAccountForUser();
            if (!$account) {
                return false;
            }

            $result = $this->zaloService->sendMessage(
                $user->phone,
                $message,
                'user',
                $account->id
            );

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('[VocabularyReport] Failed to send to student', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send report to parent via Zalo (if student is in a class)
     */
    protected function sendReportToParent(User $user, array $report): bool
    {
        // Try to find parent via class enrollment
        $classStudent = ClassStudent::where('student_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$classStudent || !$classStudent->customer_id) {
            return false; // No parent found
        }

        $customer = \App\Models\Customer::find($classStudent->customer_id);
        if (!$customer || empty($customer->phone)) {
            return false;
        }

        $diff = $report['words_today'] - $report['words_yesterday'];
        $diffText = $diff > 0 ? "+{$diff}" : ($diff < 0 ? $diff : '0');
        $diffIcon = $diff > 0 ? '📈' : ($diff < 0 ? '📉' : '➡️');

        $message = "📊 BÁO CÁO HỌC TỪ VỰNG CỦA CON\n\n";
        $message .= "👋 Xin chào phụ huynh {$customer->name},\n\n";
        $message .= "Đây là báo cáo học từ vựng hôm nay của con {$user->name}:\n\n";
        $message .= "📚 Thống kê hôm nay:\n";
        $message .= "• Từ đã học: {$report['words_today']} từ\n";
        $message .= "• So với hôm qua: {$diffIcon} {$diffText} từ\n";
        $message .= "• Số lần phát âm: {$report['recordings_count']} lần\n\n";

        if ($report['avg_overall_score'] > 0) {
            $message .= "🎯 Đánh giá phát âm:\n";
            $message .= "• Tổng điểm: {$report['avg_overall_score']}/100\n";
            $message .= "• Xếp loại: {$report['pronunciation_level']}\n\n";
        }

        $message .= "📖 Tổng từ vựng: {$report['total_words']} từ\n\n";
        $message .= "💡 Hãy khuyến khích con tiếp tục học tập nhé! 💪";

        try {
            $account = $this->customerZaloService->getPrimaryZaloAccount();
            if (!$account) {
                return false;
            }

            return $this->customerZaloService->sendZaloMessage(
                $account,
                $customer->phone,
                $message,
                $customer->id
            );
        } catch (\Exception $e) {
            Log::error('[VocabularyReport] Failed to send to parent', [
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Zalo account for sending messages
     */
    protected function getZaloAccountForUser()
    {
        return \App\Models\ZaloAccount::where('status', 'active')
            ->where('purpose', 'student')
            ->orWhere('purpose', 'general')
            ->first();
    }

    /**
     * Get configured timezone
     */
    protected function getConfiguredTimezone(): string
    {
        try {
            $timezone = DB::table('settings')->where('key', 'timezone')->value('value');
            return $timezone ?? 'Asia/Ho_Chi_Minh';
        } catch (\Exception $e) {
            return 'Asia/Ho_Chi_Minh';
        }
    }
}

