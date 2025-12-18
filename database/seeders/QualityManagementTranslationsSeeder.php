<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class QualityManagementTranslationsSeeder extends Seeder
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

        // Translations data
        $translations = [
            // Quality Management Module - Vietnamese
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'title', 'value' => 'Quản lý Chất lượng'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'description', 'value' => 'Quản lý và giám sát chất lượng theo ngành'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry', 'value' => 'Ngành'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry_education', 'value' => 'Giáo dục'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry_healthcare', 'value' => 'Y tế'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry_retail', 'value' => 'Bán lẻ'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry_manufacturing', 'value' => 'Sản xuất'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'coming_soon', 'value' => 'Sắp có'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'industry_coming_soon', 'value' => 'Ngành này sắp có'],
            ['language_id' => $vi->id, 'group' => 'quality', 'key' => 'feature_in_development', 'value' => 'Chức năng đang được phát triển'],

            // Teachers Management - Vietnamese
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'title', 'value' => 'Danh sách Giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'description', 'value' => 'Danh sách giáo viên được lọc theo mã vị trí đã thiết lập'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_title', 'value' => 'Thiết lập Mã vị trí Giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_description', 'value' => 'Chọn các mã vị trí để lọc danh sách giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_button', 'value' => 'Thiết lập mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'refresh', 'value' => 'Làm mới'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'position_code', 'value' => 'Mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'position_name', 'value' => 'Vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'department', 'value' => 'Phòng ban'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'role', 'value' => 'Vai trò'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'contact', 'value' => 'Liên hệ'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'start_date', 'value' => 'Ngày bắt đầu'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'teacher', 'value' => 'Giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'head', 'value' => 'Trưởng phòng'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'deputy', 'value' => 'Phó phòng'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'employee', 'value' => 'Nhân viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'loading', 'value' => 'Đang tải...'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'loading_list', 'value' => 'Đang tải danh sách giáo viên...'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_settings', 'value' => 'Chưa thiết lập mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_settings_description', 'value' => 'Vui lòng thiết lập mã vị trí giáo viên trước khi xem danh sách'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'setup_now', 'value' => 'Thiết lập ngay'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'filter_by', 'value' => 'Lọc theo mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'found', 'value' => 'Tìm thấy'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'teachers_count', 'value' => 'giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_teachers', 'value' => 'Không tìm thấy giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_teachers_with_code', 'value' => 'Không có nhân viên nào được gán vị trí với mã'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_hint_1', 'value' => 'Chọn một hoặc nhiều mã vị trí để lọc danh sách giáo viên'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_hint_2', 'value' => 'Thiết lập này chỉ áp dụng cho chi nhánh hiện tại'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_hint_3', 'value' => 'Chuyển chi nhánh sẽ có thiết lập riêng'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'selected_codes', 'value' => 'Đã chọn'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'codes', 'value' => 'mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'deselect_all', 'value' => 'Bỏ chọn tất cả'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_positions', 'value' => 'Không có vị trí nào có mã'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'add_position_code', 'value' => 'Vui lòng thêm mã vào vị trí trong Quản lý Job Title'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'save_settings', 'value' => 'Lưu thiết lập'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'saving', 'value' => 'Đang lưu...'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'cancel', 'value' => 'Hủy'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'close', 'value' => 'Đóng'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'level', 'value' => 'Level'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'no_code_selected', 'value' => 'Chưa chọn mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'select_at_least_one', 'value' => 'Vui lòng chọn ít nhất một mã vị trí'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'settings_saved', 'value' => 'Đã lưu thiết lập mã vị trí giáo viên cho chi nhánh này'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'error_loading', 'value' => 'Không thể tải danh sách'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'error_saving', 'value' => 'Không thể lưu thiết lập'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'branch_required', 'value' => 'Không xác định được chi nhánh hiện tại'],
            ['language_id' => $vi->id, 'group' => 'teachers', 'key' => 'guide', 'value' => 'Hướng dẫn'],

            // Quality Management Module - English
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'title', 'value' => 'Quality Management'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'description', 'value' => 'Manage and monitor quality by industry'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry', 'value' => 'Industry'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry_education', 'value' => 'Education'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry_healthcare', 'value' => 'Healthcare'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry_retail', 'value' => 'Retail'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry_manufacturing', 'value' => 'Manufacturing'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'coming_soon', 'value' => 'Coming Soon'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'industry_coming_soon', 'value' => 'This industry is coming soon'],
            ['language_id' => $en->id, 'group' => 'quality', 'key' => 'feature_in_development', 'value' => 'Feature is in development'],

            // Teachers Management - English
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'title', 'value' => 'Teachers List'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'description', 'value' => 'List of teachers filtered by configured position codes'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_title', 'value' => 'Teacher Position Code Settings'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_description', 'value' => 'Select position codes to filter teachers list'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_button', 'value' => 'Position Code Settings'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'refresh', 'value' => 'Refresh'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'position_code', 'value' => 'Position Code'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'position_name', 'value' => 'Position'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'department', 'value' => 'Department'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'role', 'value' => 'Role'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'contact', 'value' => 'Contact'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'start_date', 'value' => 'Start Date'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'teacher', 'value' => 'Teacher'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'head', 'value' => 'Head'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'deputy', 'value' => 'Deputy'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'employee', 'value' => 'Employee'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'loading', 'value' => 'Loading...'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'loading_list', 'value' => 'Loading teachers list...'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_settings', 'value' => 'Position codes not configured'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_settings_description', 'value' => 'Please configure position codes before viewing the list'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'setup_now', 'value' => 'Setup Now'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'filter_by', 'value' => 'Filter by position code'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'found', 'value' => 'Found'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'teachers_count', 'value' => 'teachers'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_teachers', 'value' => 'No teachers found'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_teachers_with_code', 'value' => 'No employees assigned positions with code'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_hint_1', 'value' => 'Select one or more position codes to filter teachers list'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_hint_2', 'value' => 'This setting applies to the current branch only'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_hint_3', 'value' => 'Switching branches will have separate settings'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'selected_codes', 'value' => 'Selected'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'codes', 'value' => 'position codes'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'deselect_all', 'value' => 'Deselect All'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_positions', 'value' => 'No positions with codes'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'add_position_code', 'value' => 'Please add codes to positions in Job Title Management'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'save_settings', 'value' => 'Save Settings'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'saving', 'value' => 'Saving...'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'cancel', 'value' => 'Cancel'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'close', 'value' => 'Close'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'level', 'value' => 'Level'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'no_code_selected', 'value' => 'No position code selected'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'select_at_least_one', 'value' => 'Please select at least one position code'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'settings_saved', 'value' => 'Teacher position code settings saved for this branch'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'error_loading', 'value' => 'Unable to load list'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'error_saving', 'value' => 'Unable to save settings'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'branch_required', 'value' => 'Unable to identify current branch'],
            ['language_id' => $en->id, 'group' => 'teachers', 'key' => 'guide', 'value' => 'Guide'],
        ];

        foreach ($translations as $translationData) {
            Translation::updateOrCreate(
                [
                    'language_id' => $translationData['language_id'],
                    'group' => $translationData['group'],
                    'key' => $translationData['key']
                ],
                ['value' => $translationData['value']]
            );
        }

        $this->command->info('Quality Management translations seeded successfully!');
        $this->command->info('Total translations: ' . count($translations));
    }
}
