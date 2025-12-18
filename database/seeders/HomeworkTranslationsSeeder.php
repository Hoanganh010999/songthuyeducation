<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class HomeworkTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        // Get languages
        $vi = Language::where('code', 'vi')->first();
        $en = Language::where('code', 'en')->first();

        if (!$vi || !$en) {
            $this->command->error('Vietnamese or English language not found!');
            return;
        }

        $translations = [
            // Homework General
            'course.homework' => ['vi' => 'Bài tập về nhà', 'en' => 'Homework'],
            'course.add_homework' => ['vi' => 'Thêm bài tập', 'en' => 'Add Homework'],
            'course.create_homework' => ['vi' => 'Tạo bài tập', 'en' => 'Create Homework'],
            'course.homework_title' => ['vi' => 'Tiêu đề bài tập', 'en' => 'Homework Title'],
            'course.homework_description' => ['vi' => 'Mô tả bài tập', 'en' => 'Homework Description'],
            'course.select_session' => ['vi' => 'Chọn buổi học', 'en' => 'Select Session'],
            'course.session_required' => ['vi' => 'Vui lòng chọn buổi học', 'en' => 'Please select a session'],
            'course.select_files' => ['vi' => 'Chọn tài liệu', 'en' => 'Select Files'],
            'course.no_files_available' => ['vi' => 'Không có tài liệu nào', 'en' => 'No files available'],
            'course.deadline' => ['vi' => 'Hạn nộp', 'en' => 'Deadline'],
            'course.no_deadline' => ['vi' => 'Không giới hạn', 'en' => 'No deadline'],
            'course.assign_to_students' => ['vi' => 'Giao cho học viên', 'en' => 'Assign to Students'],
            'course.all_students' => ['vi' => 'Tất cả học viên', 'en' => 'All Students'],
            'course.specific_students' => ['vi' => 'Học viên cụ thể', 'en' => 'Specific Students'],
            'course.select_students' => ['vi' => 'Chọn học viên', 'en' => 'Select Students'],
            'course.no_students_selected' => ['vi' => 'Chưa chọn học viên nào', 'en' => 'No students selected'],
            'course.files_selected' => ['vi' => 'tài liệu đã chọn', 'en' => 'files selected'],
            'course.homework_created' => ['vi' => 'Đã tạo bài tập thành công', 'en' => 'Homework created successfully'],
            'course.homework_deleted' => ['vi' => 'Đã xóa bài tập', 'en' => 'Homework deleted'],
            'course.homework_updated' => ['vi' => 'Đã cập nhật bài tập', 'en' => 'Homework updated'],
            'course.upcoming_homework' => ['vi' => 'Bài tập sắp tới', 'en' => 'Upcoming Homework'],
            'course.no_homework' => ['vi' => 'Chưa có bài tập nào', 'en' => 'No homework yet'],
            'course.view_files' => ['vi' => 'Xem tài liệu', 'en' => 'View Files'],
            'course.assigned_to_all' => ['vi' => 'Giao cho tất cả', 'en' => 'Assigned to all'],
            'course.assigned_to_count' => ['vi' => 'học viên', 'en' => 'students'],
            'course.event_visible_to_all_students' => ['vi' => 'Sự kiện này sẽ hiển thị cho tất cả học viên trong lớp', 'en' => 'This event will be visible to all students in the class'],
            'course.homework_title_required' => ['vi' => 'Vui lòng nhập tiêu đề bài tập', 'en' => 'Please enter homework title'],
            'course.files_required' => ['vi' => 'Vui lòng chọn ít nhất 1 tệp', 'en' => 'Please select at least 1 file'],
            'course.event' => ['vi' => 'Sự kiện', 'en' => 'Event'],
            
            // Homework Status
            'course.homework_active' => ['vi' => 'Đang hoạt động', 'en' => 'Active'],
            'course.homework_completed' => ['vi' => 'Đã hoàn thành', 'en' => 'Completed'],
            'course.homework_cancelled' => ['vi' => 'Đã hủy', 'en' => 'Cancelled'],
        ];

        foreach ($translations as $fullKey => $values) {
            // Split key into group and key (e.g., 'course.homework' => group='course', key='homework')
            $parts = explode('.', $fullKey, 2);
            $group = $parts[0];
            $key = $parts[1] ?? $fullKey;

            // Vietnamese translation
            if (isset($values['vi'])) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $vi->id,
                        'group' => $group,
                        'key' => $key,
                    ],
                    ['value' => $values['vi']]
                );
            }

            // English translation
            if (isset($values['en'])) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $en->id,
                        'group' => $group,
                        'key' => $key,
                    ],
                    ['value' => $values['en']]
                );
            }
        }

        $this->command->info('✅ Homework translations seeded successfully!');
    }
}

