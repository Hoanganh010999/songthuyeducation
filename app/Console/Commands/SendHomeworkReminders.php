<?php

namespace App\Console\Commands;

use App\Models\HomeworkAssignment;
use App\Models\HomeworkSubmission;
use App\Models\ClassStudent;
use App\Services\ZaloNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendHomeworkReminders extends Command
{
    protected $signature = 'homework:send-reminders';
    protected $description = 'Send Zalo reminders for overdue homework to class groups';

    protected ZaloNotificationService $zaloService;

    public function __construct(ZaloNotificationService $zaloService)
    {
        parent::__construct();
        $this->zaloService = $zaloService;
    }

    public function handle()
    {
        $this->info('🔔 Checking for overdue homework...');

        // Check if homework reminders are enabled
        $enabled = DB::table('settings')
            ->where('key', 'homework_reminder_enabled')
            ->value('value');

        if (!$enabled || $enabled === '0') {
            $this->info('⚠️ Homework reminders are disabled');
            return 0;
        }

        $timezone = $this->getConfiguredTimezone();
        $now = Carbon::now($timezone);

        // Get homework assignments that are overdue (deadline passed)
        // and still active (not closed/cancelled)
        $overdueHomeworks = HomeworkAssignment::where('status', 'active')
            ->where('deadline', '<=', $now)
            ->whereHas('class', function ($query) {
                // Only active classes with Zalo group
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      ->whereNotNull('zalo_group_id');
            })
            ->with(['class.students', 'class.zaloAccount'])
            ->get();

        if ($overdueHomeworks->isEmpty()) {
            $this->info('✅ No overdue homework found');
            return 0;
        }

        $sentCount = 0;

        foreach ($overdueHomeworks as $homework) {
            try {
                // Check if reminder was already sent today
                $reminderKey = "homework_reminder_{$homework->id}_sent";
                $lastSent = DB::table('settings')->where('key', $reminderKey)->value('value');
                $today = $now->toDateString();

                if ($lastSent === $today) {
                    continue; // Already sent today
                }

                // Get students who haven't submitted
                $notSubmittedStudents = $this->getStudentsNotSubmitted($homework);

                if ($notSubmittedStudents->isEmpty()) {
                    continue; // All students have submitted
                }

                // Send reminder to Zalo group
                if ($this->sendReminderToGroup($homework, $notSubmittedStudents)) {
                    $sentCount++;

                    // Mark reminder as sent today
                    $existing = DB::table('settings')->where('key', $reminderKey)->exists();
                    if ($existing) {
                        DB::table('settings')->where('key', $reminderKey)->update([
                            'value' => $today,
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('settings')->insert([
                            'key' => $reminderKey,
                            'value' => $today,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Log::info('[HomeworkReminder] Reminder sent', [
                        'homework_id' => $homework->id,
                        'class_id' => $homework->class_id,
                        'not_submitted_count' => $notSubmittedStudents->count(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[HomeworkReminder] Failed to send reminder', [
                    'homework_id' => $homework->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✅ Sent {$sentCount} homework reminder(s)");
        return 0;
    }

    /**
     * Get students who haven't submitted homework
     */
    protected function getStudentsNotSubmitted(HomeworkAssignment $homework)
    {
        // Get all students who should do this homework
        $assignedStudentIds = $this->getAssignedStudentIds($homework);

        // Get students who have submitted
        $submittedStudentIds = HomeworkSubmission::where('homework_assignment_id', $homework->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->pluck('student_id')
            ->toArray();

        // Students who haven't submitted = assigned - submitted
        $notSubmittedIds = array_diff($assignedStudentIds, $submittedStudentIds);

        // Get User models for not submitted students
        return \App\Models\User::whereIn('id', $notSubmittedIds)->get();
    }

    /**
     * Get IDs of students assigned to this homework
     */
    protected function getAssignedStudentIds(HomeworkAssignment $homework): array
    {
        if ($homework->isForAllStudents()) {
            // All active students in class
            return ClassStudent::where('class_id', $homework->class_id)
                ->where('status', 'active')
                ->pluck('student_id')
                ->toArray();
        }

        // Specific students
        return $homework->assigned_to ?? [];
    }

    /**
     * Send reminder message to class Zalo group
     */
    protected function sendReminderToGroup(HomeworkAssignment $homework, $notSubmittedStudents): bool
    {
        $class = $homework->class;

        if (!$class->zalo_group_id || !$class->zalo_account_id) {
            Log::warning('[HomeworkReminder] Class has no Zalo group', [
                'class_id' => $class->id,
            ]);
            return false;
        }

        $account = $class->zaloAccount;
        if (!$account) {
            Log::warning('[HomeworkReminder] Zalo account not found', [
                'account_id' => $class->zalo_account_id,
            ]);
            return false;
        }

        $timezone = $this->getConfiguredTimezone();
        $deadline = Carbon::parse($homework->deadline)->timezone($timezone);
        $now = Carbon::now($timezone);
        $overdueDuration = $now->diffForHumans($deadline, true);

        // Build message
        $message = "⏰ NHẮC NHỞ: BÀI TẬP QUÁ HẠN NỘP\n\n";
        $message .= "📚 Bài tập: {$homework->title}\n";
        $message .= "📅 Hạn nộp: {$deadline->format('d/m/Y H:i')}\n";
        $message .= "⏱️ Đã quá hạn: {$overdueDuration}\n\n";
        $message .= "❌ Các bạn chưa nộp bài ({$notSubmittedStudents->count()}):\n";

        foreach ($notSubmittedStudents as $index => $student) {
            $num = $index + 1;
            $message .= "{$num}. {$student->name}\n";
        }

        $message .= "\n📝 Vui lòng nộp bài sớm nhất có thể!";

        // Send to group
        try {
            $result = $this->zaloService->sendMessage(
                $class->zalo_group_id,
                $message,
                'group',
                $account->id
            );

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('[HomeworkReminder] Failed to send Zalo message', [
                'class_id' => $class->id,
                'group_id' => $class->zalo_group_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
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

