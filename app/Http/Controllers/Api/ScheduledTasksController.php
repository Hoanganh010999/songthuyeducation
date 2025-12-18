<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class ScheduledTasksController extends Controller
{
    /**
     * Available scheduled tasks configuration
     * Each task defines its command, default schedule, and metadata
     */
    protected array $availableTasks = [
        'google_refresh_tokens' => [
            'command' => 'google:refresh-tokens',
            'name' => 'Google Drive Token Refresh',
            'name_vi' => 'Làm mới Token Google Drive',
            'description' => 'Refresh Google Drive access tokens before expiry',
            'description_vi' => 'Làm mới token truy cập Google Drive trước khi hết hạn',
            'default_schedule' => '*/30 * * * *',
            'category' => 'google_drive',
        ],
        'send_appointment_reminders' => [
            'command' => 'appointments:send-reminders',
            'name' => 'Appointment Reminders',
            'name_vi' => 'Nhắc nhở lịch hẹn',
            'description' => 'Send Zalo reminders 1 hour before appointments',
            'description_vi' => 'Gửi nhắc nhở Zalo 1 giờ trước lịch hẹn',
            'default_schedule' => '*/10 * * * *',
            'category' => 'notifications',
        ],
        'send_daily_schedule' => [
            'command' => 'schedule:send-daily-notifications',
            'name' => 'Daily Schedule Notifications',
            'name_vi' => 'Thông báo lịch học hàng ngày',
            'description' => 'Send daily class schedule notifications to students and teachers',
            'description_vi' => 'Gửi thông báo lịch học hàng ngày cho học viên và giáo viên',
            'default_schedule' => '0 9 * * *',
            'category' => 'notifications',
        ],
        'send_homework_reminders' => [
            'command' => 'homework:send-reminders',
            'name' => 'Homework Overdue Reminders',
            'name_vi' => 'Nhắc nhở bài tập quá hạn',
            'description' => 'Send Zalo reminders to class groups for overdue homework',
            'description_vi' => 'Gửi nhắc nhở lên nhóm Zalo về bài tập đã quá hạn nộp và danh sách học viên chưa nộp',
            'default_schedule' => '0 8 * * *',
            'category' => 'notifications',
        ],
        'send_vocabulary_reports' => [
            'command' => 'vocabulary:send-daily-report',
            'name' => 'Vocabulary Daily Reports',
            'name_vi' => 'Báo cáo học từ vựng hàng ngày',
            'description' => 'Send daily vocabulary learning reports to students and parents',
            'description_vi' => 'Gửi báo cáo học từ vựng hàng ngày cho học viên và phụ huynh (số từ học, điểm phát âm)',
            'default_schedule' => '0 20 * * *',
            'category' => 'notifications',
        ],
        'zalo_cleanup_pending' => [
            'command' => 'zalo:cleanup-pending',
            'name' => 'Zalo Cleanup Pending Accounts',
            'name_vi' => 'Dọn dẹp tài khoản Zalo pending',
            'description' => 'Clean up orphan pending Zalo accounts older than 30 minutes',
            'description_vi' => 'Dọn dẹp các tài khoản Zalo pending không sử dụng sau 30 phút',
            'default_schedule' => '*/15 * * * *',
            'category' => 'maintenance',
        ],
        'vocabulary_cleanup_audio' => [
            'command' => 'vocabulary:clean-audio',
            'name' => 'Vocabulary Audio Cleanup',
            'name_vi' => 'Dọn dẹp file âm thanh từ vựng',
            'description' => 'Clean up vocabulary pronunciation audio files older than 24 hours',
            'description_vi' => 'Dọn dẹp các file ghi âm phát âm từ vựng cũ hơn 24 giờ (tiết kiệm dung lượng)',
            'default_schedule' => '0 4 * * *',
            'category' => 'maintenance',
        ],
        'sync_classes_to_calendar' => [
            'command' => 'calendar:sync-classes',
            'name' => 'Sync Classes to Calendar',
            'name_vi' => 'Đồng bộ lớp học vào lịch',
            'description' => 'Sync class schedules to calendar module',
            'description_vi' => 'Đồng bộ lịch học của các lớp vào module lịch',
            'default_schedule' => '0 */6 * * *',
            'category' => 'sync',
            'enabled_by_default' => false,
        ],
        'sync_google_drive_folders' => [
            'command' => 'google-drive:sync-branch-folders',
            'name' => 'Sync Google Drive Folders',
            'name_vi' => 'Đồng bộ thư mục Google Drive',
            'description' => 'Sync Google Drive folder names with branch information',
            'description_vi' => 'Đồng bộ tên thư mục Google Drive với thông tin chi nhánh',
            'default_schedule' => '0 2 * * *',
            'category' => 'google_drive',
            'enabled_by_default' => false,
        ],
        'sync_calendar_teachers' => [
            'command' => 'calendar:sync-teachers',
            'name' => 'Sync Calendar Teachers',
            'name_vi' => 'Đồng bộ giáo viên vào lịch',
            'description' => 'Sync teachers to calendar events',
            'description_vi' => 'Đồng bộ thông tin giáo viên vào các sự kiện lịch',
            'default_schedule' => '0 */4 * * *',
            'category' => 'sync',
            'enabled_by_default' => false,
        ],
    ];

    /**
     * Get all scheduled tasks with their current settings
     */
    public function index()
    {
        $tasks = [];

        foreach ($this->availableTasks as $key => $taskConfig) {
            // Get settings from database
            $scheduleKey = "scheduled_task_{$key}_schedule";
            $enabledKey = "scheduled_task_{$key}_enabled";
            $lastRunKey = "scheduled_task_{$key}_last_run";

            $schedule = DB::table('settings')->where('key', $scheduleKey)->value('value');
            $enabled = DB::table('settings')->where('key', $enabledKey)->value('value');
            $lastRun = DB::table('settings')->where('key', $lastRunKey)->value('value');

            $tasks[] = [
                'key' => $key,
                'command' => $taskConfig['command'],
                'name' => $taskConfig['name'],
                'name_vi' => $taskConfig['name_vi'],
                'description' => $taskConfig['description'],
                'description_vi' => $taskConfig['description_vi'],
                'category' => $taskConfig['category'],
                'schedule' => $schedule ?? $taskConfig['default_schedule'],
                'default_schedule' => $taskConfig['default_schedule'],
                'enabled' => $enabled !== null ? (bool) $enabled : ($taskConfig['enabled_by_default'] ?? true),
                'last_run' => $lastRun,
                'schedule_readable' => $this->cronToReadable($schedule ?? $taskConfig['default_schedule']),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Get task categories
     */
    protected function getCategories(): array
    {
        return [
            'notifications' => [
                'name' => 'Notifications',
                'name_vi' => 'Thông báo',
                'icon' => 'bell',
                'color' => 'blue',
            ],
            'google_drive' => [
                'name' => 'Google Drive',
                'name_vi' => 'Google Drive',
                'icon' => 'cloud',
                'color' => 'green',
            ],
            'sync' => [
                'name' => 'Synchronization',
                'name_vi' => 'Đồng bộ hóa',
                'icon' => 'refresh',
                'color' => 'purple',
            ],
            'maintenance' => [
                'name' => 'Maintenance',
                'name_vi' => 'Bảo trì',
                'icon' => 'wrench',
                'color' => 'orange',
            ],
        ];
    }

    /**
     * Update a scheduled task
     */
    public function update(Request $request, string $key)
    {
        if (!isset($this->availableTasks[$key])) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'schedule' => 'nullable|string',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate cron expression if provided
        if ($request->has('schedule') && $request->schedule) {
            if (!$this->isValidCronExpression($request->schedule)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid cron expression',
                ], 422);
            }
        }

        // Update schedule setting
        if ($request->has('schedule')) {
            $this->upsertSetting(
                "scheduled_task_{$key}_schedule",
                $request->schedule,
                'scheduled_tasks'
            );
        }

        // Update enabled setting
        if ($request->has('enabled')) {
            $this->upsertSetting(
                "scheduled_task_{$key}_enabled",
                $request->enabled ? '1' : '0',
                'scheduled_tasks'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
        ]);
    }

    /**
     * Run a task manually
     */
    public function run(string $key)
    {
        if (!isset($this->availableTasks[$key])) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        $task = $this->availableTasks[$key];

        try {
            // Run the artisan command
            $exitCode = Artisan::call($task['command']);
            $output = Artisan::output();

            // Update last run time
            $this->upsertSetting(
                "scheduled_task_{$key}_last_run",
                now()->toIso8601String(),
                'scheduled_tasks'
            );

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Task executed successfully' : 'Task execution failed',
                'output' => $output,
                'exit_code' => $exitCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset a task to default settings
     */
    public function reset(string $key)
    {
        if (!isset($this->availableTasks[$key])) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }

        $task = $this->availableTasks[$key];

        // Reset to default schedule
        $this->upsertSetting(
            "scheduled_task_{$key}_schedule",
            $task['default_schedule'],
            'scheduled_tasks'
        );

        // Reset to default enabled state
        $this->upsertSetting(
            "scheduled_task_{$key}_enabled",
            ($task['enabled_by_default'] ?? true) ? '1' : '0',
            'scheduled_tasks'
        );

        return response()->json([
            'success' => true,
            'message' => 'Task reset to default settings',
        ]);
    }

    /**
     * Convert cron expression to human-readable format
     */
    protected function cronToReadable(string $cron): string
    {
        $parts = explode(' ', $cron);

        if (count($parts) !== 5) {
            return $cron;
        }

        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

        // Common patterns
        if ($cron === '* * * * *') {
            return 'Every minute';
        }

        if (preg_match('/^\*\/(\d+) \* \* \* \*$/', $cron, $matches)) {
            return "Every {$matches[1]} minutes";
        }

        if (preg_match('/^0 \*\/(\d+) \* \* \*$/', $cron, $matches)) {
            return "Every {$matches[1]} hours";
        }

        if (preg_match('/^(\d+) (\d+) \* \* \*$/', $cron, $matches)) {
            return "Daily at {$matches[2]}:" . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^0 (\d+) \* \* \*$/', $cron, $matches)) {
            return "Daily at {$matches[1]}:00";
        }

        return $cron;
    }

    /**
     * Validate cron expression
     */
    protected function isValidCronExpression(string $cron): bool
    {
        $parts = explode(' ', $cron);

        if (count($parts) !== 5) {
            return false;
        }

        // Basic validation for each part
        $patterns = [
            '/^(\*|\d+|\*\/\d+|\d+-\d+)(,(\*|\d+|\*\/\d+|\d+-\d+))*$/', // minute
            '/^(\*|\d+|\*\/\d+|\d+-\d+)(,(\*|\d+|\*\/\d+|\d+-\d+))*$/', // hour
            '/^(\*|\d+|\*\/\d+|\d+-\d+)(,(\*|\d+|\*\/\d+|\d+-\d+))*$/', // day of month
            '/^(\*|\d+|\*\/\d+|\d+-\d+)(,(\*|\d+|\*\/\d+|\d+-\d+))*$/', // month
            '/^(\*|\d+|\*\/\d+|\d+-\d+)(,(\*|\d+|\*\/\d+|\d+-\d+))*$/', // day of week
        ];

        foreach ($parts as $i => $part) {
            if (!preg_match($patterns[$i], $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Insert or update a setting
     */
    protected function upsertSetting(string $key, string $value, string $group = 'general'): void
    {
        $exists = DB::table('settings')->where('key', $key)->exists();

        if ($exists) {
            DB::table('settings')
                ->where('key', $key)
                ->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'type' => 'string',
                'group' => $group,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
