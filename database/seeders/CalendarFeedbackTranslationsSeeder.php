<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class CalendarFeedbackTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $viLanguage = Language::where('code', 'vi')->first();
        $enLanguage = Language::where('code', 'en')->first();
        
        if (!$viLanguage || !$enLanguage) {
            $this->command->error('❌ Languages not found. Please seed languages first.');
            return;
        }
        
        $translations = [
            // Feedback general
            ['key' => 'calendar.submit_result', 'vi' => 'Trả kết quả', 'en' => 'Submit Result'],
            ['key' => 'calendar.feedback', 'vi' => 'Đánh giá', 'en' => 'Feedback'],
            ['key' => 'calendar.feedback_content', 'vi' => 'Nội dung đánh giá', 'en' => 'Feedback Content'],
            ['key' => 'calendar.feedback_placeholder', 'vi' => 'Nhập đánh giá chi tiết...', 'en' => 'Enter detailed feedback...'],
            ['key' => 'calendar.submit_feedback', 'vi' => 'Gửi đánh giá', 'en' => 'Submit Feedback'],
            ['key' => 'calendar.feedback_submitted', 'vi' => 'Đã gửi đánh giá', 'en' => 'Feedback Submitted'],
            ['key' => 'calendar.feedback_success', 'vi' => 'Đã lưu đánh giá thành công!', 'en' => 'Feedback saved successfully!'],
            ['key' => 'calendar.feedback_error', 'vi' => 'Lỗi khi lưu đánh giá', 'en' => 'Error saving feedback'],
            ['key' => 'calendar.event_completed', 'vi' => 'Sự kiện đã hoàn thành', 'en' => 'Event completed'],
            
            // Placement test specific
            ['key' => 'calendar.test_result', 'vi' => 'Kết quả test', 'en' => 'Test Result'],
            ['key' => 'calendar.test_score', 'vi' => 'Điểm số', 'en' => 'Score'],
            ['key' => 'calendar.test_level', 'vi' => 'Trình độ', 'en' => 'Level'],
            ['key' => 'calendar.test_notes', 'vi' => 'Ghi chú', 'en' => 'Notes'],
            ['key' => 'calendar.assign_teacher', 'vi' => 'Phân công giáo viên', 'en' => 'Assign Teacher'],
            ['key' => 'calendar.assigned_teacher', 'vi' => 'Giáo viên phụ trách', 'en' => 'Assigned Teacher'],
            ['key' => 'calendar.select_teacher', 'vi' => 'Chọn giáo viên', 'en' => 'Select Teacher'],
            ['key' => 'calendar.teacher_assigned_success', 'vi' => 'Đã phân công giáo viên thành công!', 'en' => 'Teacher assigned successfully!'],
            ['key' => 'calendar.teacher_assigned_error', 'vi' => 'Lỗi khi phân công giáo viên', 'en' => 'Error assigning teacher'],
            
            // Trial class specific
            ['key' => 'calendar.trial_feedback', 'vi' => 'Đánh giá học thử', 'en' => 'Trial Feedback'],
            ['key' => 'calendar.trial_rating', 'vi' => 'Đánh giá', 'en' => 'Rating'],
            ['key' => 'calendar.trial_student', 'vi' => 'Học viên học thử', 'en' => 'Trial Student'],
            ['key' => 'calendar.trial_class_teacher', 'vi' => 'Giáo viên đứng lớp', 'en' => 'Class Teacher'],
            
            // Modal titles
            ['key' => 'calendar.feedback_modal_title_test', 'vi' => 'Trả kết quả Test Đầu Vào', 'en' => 'Submit Placement Test Result'],
            ['key' => 'calendar.feedback_modal_title_trial', 'vi' => 'Đánh giá Học Thử', 'en' => 'Submit Trial Class Feedback'],
            ['key' => 'calendar.assign_teacher_modal_title', 'vi' => 'Phân công Giáo viên', 'en' => 'Assign Teacher'],
            
            // Status
            ['key' => 'calendar.status.pending', 'vi' => 'Chờ xử lý', 'en' => 'Pending'],
            ['key' => 'calendar.status.in_progress', 'vi' => 'Đang thực hiện', 'en' => 'In Progress'],
            ['key' => 'calendar.status.completed', 'vi' => 'Đã hoàn thành', 'en' => 'Completed'],
            ['key' => 'calendar.status.cancelled', 'vi' => 'Đã hủy', 'en' => 'Cancelled'],
            
            // Buttons
            ['key' => 'calendar.save', 'vi' => 'Lưu', 'en' => 'Save'],
            ['key' => 'calendar.cancel', 'vi' => 'Hủy', 'en' => 'Cancel'],
            ['key' => 'calendar.close', 'vi' => 'Đóng', 'en' => 'Close'],
            
            // Validations
            ['key' => 'calendar.feedback_required', 'vi' => 'Vui lòng nhập nội dung đánh giá', 'en' => 'Please enter feedback content'],
            ['key' => 'calendar.teacher_required', 'vi' => 'Vui lòng chọn giáo viên', 'en' => 'Please select a teacher'],
        ];

        foreach ($translations as $translation) {
            // Vietnamese translation
            Translation::updateOrCreate(
                [
                    'language_id' => $viLanguage->id,
                    'group' => 'calendar',
                    'key' => $translation['key'],
                ],
                [
                    'value' => $translation['vi'],
                ]
            );
            
            // English translation
            Translation::updateOrCreate(
                [
                    'language_id' => $enLanguage->id,
                    'group' => 'calendar',
                    'key' => $translation['key'],
                ],
                [
                    'value' => $translation['en'],
                ]
            );
        }

        $this->command->info('✅ Calendar feedback translations created successfully!');
    }
}
