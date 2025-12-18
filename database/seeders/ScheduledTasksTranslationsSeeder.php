<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduledTasksTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        // Get Vietnamese language ID (assuming it's 1)
        $viLanguageId = DB::table('languages')->where('code', 'vi')->value('id') ?? 1;
        $enLanguageId = DB::table('languages')->where('code', 'en')->value('id') ?? 2;

        $translations = [
            // Vietnamese translations
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'title', 'value' => 'Tác vụ định kỳ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'description', 'value' => 'Quản lý các tác vụ chạy tự động theo lịch'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'menu_description', 'value' => 'Cấu hình cron jobs'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'tasks', 'value' => 'tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enabled', 'value' => 'Đang bật'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disabled', 'value' => 'Đã tắt'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'last_run', 'value' => 'Lần chạy cuối'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_now', 'value' => 'Chạy ngay'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'edit_task', 'value' => 'Chỉnh sửa tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'cron_schedule', 'value' => 'Lịch chạy (Cron)'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'cron_format_help', 'value' => 'Định dạng: phút giờ ngày tháng thứ (VD: */30 * * * * = mỗi 30 phút)'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'quick_select', 'value' => 'Chọn nhanh'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'task_enabled', 'value' => 'Kích hoạt tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_to_default', 'value' => 'Đặt lại mặc định'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enable', 'value' => 'Bật'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disable', 'value' => 'Tắt'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'fetch_error', 'value' => 'Không thể tải danh sách tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'save_success', 'value' => 'Đã lưu cấu hình tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'save_error', 'value' => 'Không thể lưu cấu hình tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_confirm_title', 'value' => 'Đặt lại mặc định?'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_confirm_message', 'value' => 'Cấu hình tác vụ sẽ được đặt lại về mặc định.'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_success', 'value' => 'Đã đặt lại cấu hình mặc định'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_error', 'value' => 'Không thể đặt lại cấu hình'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_confirm_title', 'value' => 'Chạy tác vụ ngay?'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_confirm_message', 'value' => 'Tác vụ "{name}" sẽ được thực thi ngay lập tức.'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_success', 'value' => 'Tác vụ đã thực thi thành công'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_error', 'value' => 'Không thể thực thi tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enabled_success', 'value' => 'Đã bật tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disabled_success', 'value' => 'Đã tắt tác vụ'],
            ['language_id' => $viLanguageId, 'group' => 'scheduled_tasks', 'key' => 'toggle_error', 'value' => 'Không thể thay đổi trạng thái tác vụ'],

            // English translations
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'title', 'value' => 'Scheduled Tasks'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'description', 'value' => 'Manage automated tasks that run on a schedule'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'menu_description', 'value' => 'Configure cron jobs'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'tasks', 'value' => 'tasks'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enabled', 'value' => 'Enabled'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disabled', 'value' => 'Disabled'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'last_run', 'value' => 'Last run'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_now', 'value' => 'Run now'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'edit_task', 'value' => 'Edit Task'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'cron_schedule', 'value' => 'Cron Schedule'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'cron_format_help', 'value' => 'Format: minute hour day month weekday (e.g., */30 * * * * = every 30 minutes)'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'quick_select', 'value' => 'Quick Select'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'task_enabled', 'value' => 'Task Enabled'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_to_default', 'value' => 'Reset to default'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enable', 'value' => 'Enable'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disable', 'value' => 'Disable'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'fetch_error', 'value' => 'Failed to load tasks'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'save_success', 'value' => 'Task configuration saved'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'save_error', 'value' => 'Failed to save task configuration'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_confirm_title', 'value' => 'Reset to default?'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_confirm_message', 'value' => 'Task configuration will be reset to default.'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_success', 'value' => 'Configuration reset to default'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'reset_error', 'value' => 'Failed to reset configuration'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_confirm_title', 'value' => 'Run task now?'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_confirm_message', 'value' => 'Task "{name}" will be executed immediately.'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_success', 'value' => 'Task executed successfully'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'run_error', 'value' => 'Failed to execute task'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'enabled_success', 'value' => 'Task enabled'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'disabled_success', 'value' => 'Task disabled'],
            ['language_id' => $enLanguageId, 'group' => 'scheduled_tasks', 'key' => 'toggle_error', 'value' => 'Failed to toggle task status'],
        ];

        foreach ($translations as $translation) {
            DB::table('translations')->updateOrInsert(
                [
                    'language_id' => $translation['language_id'],
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                ],
                [
                    'value' => $translation['value'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info('Scheduled Tasks translations seeded successfully!');
    }
}
