<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class HolidaysTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        // Get language IDs
        $vi = Language::where('code', 'vi')->first();
        $en = Language::where('code', 'en')->first();

        if (!$vi || !$en) {
            $this->command->error('Languages not found. Please run LanguagesSeeder first.');
            return;
        }

        $translations = [
            // Vietnamese
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'module_title', 'value' => 'Lịch nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'module_description', 'value' => 'Quản lý lịch nghỉ lễ, tết cho toàn chi nhánh'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'create_holiday', 'value' => 'Tạo lịch nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'edit_holiday', 'value' => 'Sửa lịch nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'delete_holiday', 'value' => 'Xóa lịch nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'name', 'value' => 'Tên ngày nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'start_date', 'value' => 'Ngày bắt đầu'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'end_date', 'value' => 'Ngày kết thúc'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'description', 'value' => 'Mô tả'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'duration_days', 'value' => 'Số ngày nghỉ'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'no_holidays', 'value' => 'Chưa có lịch nghỉ nào'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'confirm_delete', 'value' => 'Bạn có chắc muốn xóa lịch nghỉ này?'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'deleted_success', 'value' => 'Đã xóa lịch nghỉ thành công'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'created_success', 'value' => 'Đã tạo lịch nghỉ thành công'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'updated_success', 'value' => 'Đã cập nhật lịch nghỉ thành công'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'name_placeholder', 'value' => 'VD: Tết Nguyên Đán 2025'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'description_placeholder', 'value' => 'Nhập ghi chú (nếu có)'],
            ['language_id' => $vi->id, 'group' => 'holidays', 'key' => 'all_branches', 'value' => 'Áp dụng cho toàn chi nhánh'],
            
            // English
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'module_title', 'value' => 'Holidays'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'module_description', 'value' => 'Manage branch-wide holidays'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'create_holiday', 'value' => 'Create Holiday'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'edit_holiday', 'value' => 'Edit Holiday'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'delete_holiday', 'value' => 'Delete Holiday'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'name', 'value' => 'Holiday Name'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'start_date', 'value' => 'Start Date'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'end_date', 'value' => 'End Date'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'description', 'value' => 'Description'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'duration_days', 'value' => 'Duration (Days)'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'no_holidays', 'value' => 'No holidays yet'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'confirm_delete', 'value' => 'Are you sure you want to delete this holiday?'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'deleted_success', 'value' => 'Holiday deleted successfully'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'created_success', 'value' => 'Holiday created successfully'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'updated_success', 'value' => 'Holiday updated successfully'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'name_placeholder', 'value' => 'e.g., Lunar New Year 2025'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'description_placeholder', 'value' => 'Add note (optional)'],
            ['language_id' => $en->id, 'group' => 'holidays', 'key' => 'all_branches', 'value' => 'Applied to all branches'],
        ];

        foreach ($translations as $translation) {
            Translation::updateOrCreate(
                [
                    'language_id' => $translation['language_id'],
                    'group' => $translation['group'],
                    'key' => $translation['key']
                ],
                ['value' => $translation['value']]
            );
        }

        $this->command->info('✅ Holidays translations seeded successfully! (38 translations)');
    }
}
