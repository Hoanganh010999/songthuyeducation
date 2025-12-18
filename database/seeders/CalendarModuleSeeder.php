<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Permissions cho Calendar Module
        $permissions = [
            ['module' => 'calendar', 'name' => 'calendar.view', 'action' => 'view', 'description' => 'Xem lịch'],
            ['module' => 'calendar', 'name' => 'calendar.create', 'action' => 'create', 'description' => 'Tạo sự kiện'],
            ['module' => 'calendar', 'name' => 'calendar.edit', 'action' => 'edit', 'description' => 'Sửa sự kiện'],
            ['module' => 'calendar', 'name' => 'calendar.delete', 'action' => 'delete', 'description' => 'Xóa sự kiện'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ Calendar permissions created!');

        // 2. Tạo Translations
        $translations = [
            // English
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'calendar', 'value' => 'Calendar'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'events', 'value' => 'Events'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'my_calendar', 'value' => 'My Calendar'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'add_event', 'value' => 'Add Event'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'edit_event', 'value' => 'Edit Event'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'event_title', 'value' => 'Event Title'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'event_description', 'value' => 'Description'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'start_date', 'value' => 'Start Date'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'end_date', 'value' => 'End Date'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'all_day', 'value' => 'All Day'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'category', 'value' => 'Category'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'status', 'value' => 'Status'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'location', 'value' => 'Location'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'today', 'value' => 'Today'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'month', 'value' => 'Month'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'week', 'value' => 'Week'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'day', 'value' => 'Day'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'upcoming_events', 'value' => 'Upcoming Events'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'overdue_events', 'value' => 'Overdue Events'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'no_events', 'value' => 'No events'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'status_pending', 'value' => 'Pending'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'status_in_progress', 'value' => 'In Progress'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'status_completed', 'value' => 'Completed'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'status_cancelled', 'value' => 'Cancelled'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'customer_follow_up', 'value' => 'Customer Follow-up'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'meeting', 'value' => 'Meeting'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'task', 'value' => 'Task'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'deadline', 'value' => 'Deadline'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'event', 'value' => 'Event'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'reminder', 'value' => 'Reminder'],
            ['language_id' => 2, 'group' => 'calendar', 'key' => 'general', 'value' => 'General'],

            // Vietnamese
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'calendar', 'value' => 'Lịch'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'events', 'value' => 'Sự Kiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'my_calendar', 'value' => 'Lịch Của Tôi'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'add_event', 'value' => 'Thêm Sự Kiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'edit_event', 'value' => 'Sửa Sự Kiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'event_title', 'value' => 'Tiêu Đề'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'event_description', 'value' => 'Mô Tả'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'start_date', 'value' => 'Ngày Bắt Đầu'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'end_date', 'value' => 'Ngày Kết Thúc'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'all_day', 'value' => 'Cả Ngày'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'category', 'value' => 'Loại'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'status', 'value' => 'Trạng Thái'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'location', 'value' => 'Địa Điểm'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'today', 'value' => 'Hôm Nay'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'month', 'value' => 'Tháng'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'week', 'value' => 'Tuần'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'day', 'value' => 'Ngày'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'upcoming_events', 'value' => 'Sự Kiện Sắp Tới'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'overdue_events', 'value' => 'Sự Kiện Quá Hạn'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'no_events', 'value' => 'Không có sự kiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'status_pending', 'value' => 'Chờ Xử Lý'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'status_in_progress', 'value' => 'Đang Thực Hiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'status_completed', 'value' => 'Hoàn Thành'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'status_cancelled', 'value' => 'Đã Hủy'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'customer_follow_up', 'value' => 'Liên Hệ Lại Khách Hàng'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'meeting', 'value' => 'Cuộc Họp'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'task', 'value' => 'Công Việc'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'deadline', 'value' => 'Deadline'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'event', 'value' => 'Sự Kiện'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'reminder', 'value' => 'Nhắc Nhở'],
            ['language_id' => 1, 'group' => 'calendar', 'key' => 'general', 'value' => 'Chung'],
        ];

        foreach ($translations as $translation) {
            DB::table('translations')->updateOrInsert(
                [
                    'language_id' => $translation['language_id'],
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                ],
                ['value' => $translation['value']]
            );
        }

        $this->command->info('✅ Calendar translations created!');
    }
}
