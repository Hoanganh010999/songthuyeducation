<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SubjectsTranslationsSeeder extends Seeder
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
            // Subjects - Vietnamese
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'title', 'value' => 'Danh sách Môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'description', 'value' => 'Quản lý môn học và giáo viên bộ môn'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'create_subject', 'value' => 'Tạo môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'edit_subject', 'value' => 'Sửa môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'delete_subject', 'value' => 'Xóa môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'subject_name', 'value' => 'Tên môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'subject_code', 'value' => 'Mã môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'subject_color', 'value' => 'Màu đại diện'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'head_teacher', 'value' => 'Trưởng bộ môn'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'teachers_count', 'value' => 'giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'no_head', 'value' => 'Chưa có trưởng bộ môn'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'add_teachers', 'value' => 'Thêm giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'manage_teachers', 'value' => 'Quản lý giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'no_subjects', 'value' => 'Chưa có môn học nào'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'no_subjects_desc', 'value' => 'Tạo môn học đầu tiên để bắt đầu'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'subject_info', 'value' => 'Thông tin môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'required_name', 'value' => 'Vui lòng nhập tên môn học'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'required_branch', 'value' => 'Vui lòng chọn chi nhánh'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'create_success', 'value' => 'Đã tạo môn học thành công'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'update_success', 'value' => 'Đã cập nhật môn học thành công'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'delete_success', 'value' => 'Đã xóa môn học thành công'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'delete_confirm', 'value' => 'Bạn có chắc muốn xóa môn học này?'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'delete_warning', 'value' => 'Hành động này không thể hoàn tác'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'assign_teacher', 'value' => 'Gán giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'assign_success', 'value' => 'Đã gán giáo viên thành công'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'remove_teacher', 'value' => 'Gỡ giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'remove_success', 'value' => 'Đã gỡ giáo viên thành công'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'set_head', 'value' => 'Đặt làm trưởng bộ môn'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'set_head_success', 'value' => 'Đã đặt trưởng bộ môn'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'select_teacher', 'value' => 'Chọn giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'search_teacher', 'value' => 'Tìm kiếm giáo viên...'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'teacher_list', 'value' => 'Danh sách giáo viên'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'no_teachers', 'value' => 'Chưa có giáo viên nào'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'active', 'value' => 'Hoạt động'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'inactive', 'value' => 'Không hoạt động'],
            ['language_id' => $vi->id, 'group' => 'subjects', 'key' => 'status', 'value' => 'Trạng thái'],

            // Subjects - English
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'title', 'value' => 'Subjects List'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'description', 'value' => 'Manage subjects and department teachers'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'create_subject', 'value' => 'Create Subject'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'edit_subject', 'value' => 'Edit Subject'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'delete_subject', 'value' => 'Delete Subject'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'subject_name', 'value' => 'Subject Name'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'subject_code', 'value' => 'Subject Code'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'subject_color', 'value' => 'Color'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'head_teacher', 'value' => 'Head Teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'teachers_count', 'value' => 'teachers'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'no_head', 'value' => 'No head teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'add_teachers', 'value' => 'Add Teachers'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'manage_teachers', 'value' => 'Manage Teachers'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'no_subjects', 'value' => 'No subjects yet'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'no_subjects_desc', 'value' => 'Create your first subject to get started'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'subject_info', 'value' => 'Subject Information'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'required_name', 'value' => 'Please enter subject name'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'required_branch', 'value' => 'Please select branch'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'create_success', 'value' => 'Subject created successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'update_success', 'value' => 'Subject updated successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'delete_success', 'value' => 'Subject deleted successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'delete_confirm', 'value' => 'Are you sure you want to delete this subject?'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'delete_warning', 'value' => 'This action cannot be undone'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'assign_teacher', 'value' => 'Assign Teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'assign_success', 'value' => 'Teacher assigned successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'remove_teacher', 'value' => 'Remove Teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'remove_success', 'value' => 'Teacher removed successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'set_head', 'value' => 'Set as Head Teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'set_head_success', 'value' => 'Head teacher set successfully'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'select_teacher', 'value' => 'Select Teacher'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'search_teacher', 'value' => 'Search teacher...'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'teacher_list', 'value' => 'Teacher List'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'no_teachers', 'value' => 'No teachers yet'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'active', 'value' => 'Active'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'inactive', 'value' => 'Inactive'],
            ['language_id' => $en->id, 'group' => 'subjects', 'key' => 'status', 'value' => 'Status'],
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

        $this->command->info('Subjects translations seeded successfully!');
        $this->command->info('Total translations: ' . count($translations));
    }
}
