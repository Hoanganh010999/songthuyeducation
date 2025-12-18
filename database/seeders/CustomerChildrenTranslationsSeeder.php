<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerChildrenTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $translations = [
            // English (language_id: 1)
            ['language_id' => 1, 'group' => 'customers', 'key' => 'info_and_children', 'value' => 'Info & Children'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interaction_history', 'value' => 'Interaction History'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'basic_info', 'value' => 'Basic Information'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'children_list', 'value' => 'Children List'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'add_child', 'value' => 'Add Child'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'edit_child', 'value' => 'Edit Child Info'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'no_children', 'value' => 'No children information'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'child_name', 'value' => 'Child Name'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'child_name_placeholder', 'value' => 'Enter child name'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'date_of_birth', 'value' => 'Date of Birth'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'gender', 'value' => 'Gender'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'male', 'value' => 'Male'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'female', 'value' => 'Female'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'other', 'value' => 'Other'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'school', 'value' => 'School'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'school_placeholder', 'value' => 'Enter school name'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'grade', 'value' => 'Grade/Class'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'grade_placeholder', 'value' => 'e.g., Grade 5, Class 10'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interests', 'value' => 'Interests'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interests_placeholder', 'value' => 'e.g., Math, English, Soccer'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'child_notes_placeholder', 'value' => 'Notes about child (strengths, weaknesses, learning goals...)'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'no_interactions', 'value' => 'No interaction history'],

            // Actions
            ['language_id' => 1, 'group' => 'customers', 'key' => 'schedule_test', 'value' => 'Schedule Test'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'trial_class', 'value' => 'Trial Class'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'age_suffix', 'value' => 'years old'],

            // Error messages
            ['language_id' => 1, 'group' => 'customers', 'key' => 'error_load_children', 'value' => 'An error occurred while loading children list'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'confirm_delete_child', 'value' => 'Are you sure you want to delete {name}\'s information?'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'error_delete_child', 'value' => 'An error occurred while deleting'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'trial_registered_success', 'value' => 'Trial class registered successfully!'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'error_register_trial', 'value' => 'An error occurred while registering for trial class'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'error_save_interaction', 'value' => 'An error occurred while saving'],

            // Vietnamese (language_id: 2)
            ['language_id' => 2, 'group' => 'customers', 'key' => 'info_and_children', 'value' => 'Thông tin & Con cái'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interaction_history', 'value' => 'Lịch sử tương tác'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'basic_info', 'value' => 'Thông tin cơ bản'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'children_list', 'value' => 'Danh sách con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'add_child', 'value' => 'Thêm con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'edit_child', 'value' => 'Sửa thông tin con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'no_children', 'value' => 'Chưa có thông tin con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'child_name', 'value' => 'Tên con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'child_name_placeholder', 'value' => 'Nhập tên con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'date_of_birth', 'value' => 'Ngày sinh'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'gender', 'value' => 'Giới tính'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'male', 'value' => 'Nam'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'female', 'value' => 'Nữ'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'other', 'value' => 'Khác'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'school', 'value' => 'Trường học'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'school_placeholder', 'value' => 'Nhập tên trường'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'grade', 'value' => 'Lớp/Khối'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'grade_placeholder', 'value' => 'Ví dụ: Lớp 5, Khối 10'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interests', 'value' => 'Sở thích'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interests_placeholder', 'value' => 'Ví dụ: Toán, Tiếng Anh, Bóng đá'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'child_notes_placeholder', 'value' => 'Ghi chú về con (điểm mạnh, điểm yếu, mục tiêu học tập...)'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'no_interactions', 'value' => 'Chưa có lịch sử tương tác'],

            // Actions
            ['language_id' => 2, 'group' => 'customers', 'key' => 'schedule_test', 'value' => 'Đặt lịch test'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'trial_class', 'value' => 'Học thử'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'age_suffix', 'value' => 'tuổi'],

            // Error messages
            ['language_id' => 2, 'group' => 'customers', 'key' => 'error_load_children', 'value' => 'Có lỗi xảy ra khi tải danh sách con'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'confirm_delete_child', 'value' => 'Bạn có chắc chắn muốn xóa thông tin của {name}?'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'error_delete_child', 'value' => 'Có lỗi xảy ra khi xóa'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'trial_registered_success', 'value' => 'Đăng ký học thử thành công!'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'error_register_trial', 'value' => 'Có lỗi xảy ra khi đăng ký học thử'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'error_save_interaction', 'value' => 'Có lỗi xảy ra khi lưu'],
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ Customer Children translations seeded successfully!');
    }
}
