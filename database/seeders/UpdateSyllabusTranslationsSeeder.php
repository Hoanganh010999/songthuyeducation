<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class UpdateSyllabusTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $vietnamese = Language::where('code', 'vi')->first();
        $english = Language::where('code', 'en')->first();

        if (!$vietnamese || !$english) {
            $this->command->error('Languages not found!');
            return;
        }

        // Delete old lesson_plans translations
        Translation::whereIn('language_id', [$vietnamese->id, $english->id])
            ->where('group', 'lesson_plans')
            ->delete();

        // Delete old valuation_forms translations (will recreate with new structure)
        Translation::whereIn('language_id', [$vietnamese->id, $english->id])
            ->where('group', 'valuation_forms')
            ->delete();

        $translations = [
            // Syllabus - General
            ['group' => 'syllabus', 'key' => 'module_title', 'vi' => 'Quản lý Syllabus', 'en' => 'Syllabus Management'],
            ['group' => 'syllabus', 'key' => 'module_description', 'vi' => 'Quản lý chương trình học và form đánh giá', 'en' => 'Manage syllabi and evaluation forms'],
            ['group' => 'syllabus', 'key' => 'list_title', 'vi' => 'Danh sách Syllabus', 'en' => 'Syllabus List'],
            ['group' => 'syllabus', 'key' => 'create_new', 'vi' => 'Tạo syllabus mới', 'en' => 'Create New Syllabus'],
            ['group' => 'syllabus', 'key' => 'edit', 'vi' => 'Sửa syllabus', 'en' => 'Edit Syllabus'],
            ['group' => 'syllabus', 'key' => 'delete', 'vi' => 'Xóa syllabus', 'en' => 'Delete Syllabus'],
            ['group' => 'syllabus', 'key' => 'no_items', 'vi' => 'Chưa có syllabus nào', 'en' => 'No syllabi yet'],
            ['group' => 'syllabus', 'key' => 'view_details', 'vi' => 'Xem chi tiết', 'en' => 'View Details'],
            
            // Syllabus Fields
            ['group' => 'syllabus', 'key' => 'name', 'vi' => 'Tên syllabus', 'en' => 'Syllabus Name'],
            ['group' => 'syllabus', 'key' => 'code', 'vi' => 'Mã syllabus', 'en' => 'Syllabus Code'],
            ['group' => 'syllabus', 'key' => 'subject', 'vi' => 'Môn học', 'en' => 'Subject'],
            ['group' => 'syllabus', 'key' => 'academic_year', 'vi' => 'Năm học', 'en' => 'Academic Year'],
            ['group' => 'syllabus', 'key' => 'total_units', 'vi' => 'Tổng số unit', 'en' => 'Total Units'],
            ['group' => 'syllabus', 'key' => 'description', 'vi' => 'Mô tả', 'en' => 'Description'],
            ['group' => 'syllabus', 'key' => 'status', 'vi' => 'Trạng thái', 'en' => 'Status'],
            ['group' => 'syllabus', 'key' => 'status_draft', 'vi' => 'Nháp', 'en' => 'Draft'],
            ['group' => 'syllabus', 'key' => 'status_approved', 'vi' => 'Đã duyệt', 'en' => 'Approved'],
            ['group' => 'syllabus', 'key' => 'status_in_use', 'vi' => 'Đang sử dụng', 'en' => 'In Use'],
            ['group' => 'syllabus', 'key' => 'status_archived', 'vi' => 'Lưu trữ', 'en' => 'Archived'],
            
            // Units
            ['group' => 'syllabus', 'key' => 'units_tab', 'vi' => 'Danh sách Units', 'en' => 'Units List'],
            ['group' => 'syllabus', 'key' => 'units_management', 'vi' => 'Quản lý các buổi học trong giáo án', 'en' => 'Manage sessions in syllabus'],
            ['group' => 'syllabus', 'key' => 'add_unit', 'vi' => 'Thêm unit', 'en' => 'Add Unit'],
            ['group' => 'syllabus', 'key' => 'add_session', 'vi' => 'Thêm buổi học', 'en' => 'Add Session'],
            ['group' => 'syllabus', 'key' => 'click_to_start', 'vi' => 'Nhấn "Thêm buổi học" để bắt đầu', 'en' => 'Click "Add Session" to start'],
            ['group' => 'syllabus', 'key' => 'unit_number', 'vi' => 'Unit', 'en' => 'Unit'],
            ['group' => 'syllabus', 'key' => 'unit_name', 'vi' => 'Tên unit', 'en' => 'Unit Name'],
            ['group' => 'syllabus', 'key' => 'no_title', 'vi' => 'Chưa có tên', 'en' => 'No title'],
            ['group' => 'syllabus', 'key' => 'minutes', 'vi' => 'phút', 'en' => 'minutes'],
            ['group' => 'syllabus', 'key' => 'objectives_label', 'vi' => 'Mục tiêu:', 'en' => 'Objectives:'],
            ['group' => 'syllabus', 'key' => 'content_label', 'vi' => 'Nội dung:', 'en' => 'Content:'],
            ['group' => 'syllabus', 'key' => 'notes_label', 'vi' => 'Ghi chú:', 'en' => 'Notes:'],
            ['group' => 'syllabus', 'key' => 'lesson_plan_badge', 'vi' => 'Giáo án', 'en' => 'Lesson Plan'],
            ['group' => 'syllabus', 'key' => 'lesson_plans_badge', 'vi' => 'Giáo án', 'en' => 'Lesson Plans'],
            ['group' => 'syllabus', 'key' => 'materials_badge', 'vi' => 'Tài liệu', 'en' => 'Materials'],
            ['group' => 'syllabus', 'key' => 'homework_badge', 'vi' => 'Bài tập', 'en' => 'Homework'],
            ['group' => 'syllabus', 'key' => 'create_homework_btn', 'vi' => 'Tạo Homework', 'en' => 'Create Homework'],
            ['group' => 'syllabus', 'key' => 'create_lesson_plan_btn', 'vi' => 'Tạo Giáo án', 'en' => 'Create Lesson Plan'],
            ['group' => 'syllabus', 'key' => 'manage_materials_btn', 'vi' => 'Quản lý Tài liệu', 'en' => 'Manage Materials'],
            ['group' => 'syllabus', 'key' => 'edit_evaluation_btn', 'vi' => 'Sửa đánh giá', 'en' => 'Edit Evaluation'],
            ['group' => 'syllabus', 'key' => 'create_evaluation_btn', 'vi' => 'Tạo đánh giá', 'en' => 'Create Evaluation'],
            ['group' => 'syllabus', 'key' => 'teaching_materials', 'vi' => 'Tài nguyên giảng dạy', 'en' => 'Teaching Materials'],
            ['group' => 'syllabus', 'key' => 'homework', 'vi' => 'Bài tập về nhà', 'en' => 'Homework'],
            ['group' => 'syllabus', 'key' => 'evaluation_form', 'vi' => 'Evaluation Form', 'en' => 'Evaluation Form'],
            ['group' => 'syllabus', 'key' => 'google_drive_link', 'vi' => 'Link Google Drive', 'en' => 'Google Drive Link'],
            ['group' => 'syllabus', 'key' => 'no_link', 'vi' => 'Chưa có', 'en' => 'Not set'],
            ['group' => 'syllabus', 'key' => 'create_evaluation', 'vi' => 'Tạo form đánh giá', 'en' => 'Create Evaluation Form'],
            ['group' => 'syllabus', 'key' => 'edit_evaluation', 'vi' => 'Sửa form đánh giá', 'en' => 'Edit Evaluation Form'],
            ['group' => 'syllabus', 'key' => 'no_units', 'vi' => 'Chưa có unit nào. Click "Thêm unit" để bắt đầu.', 'en' => 'No units yet. Click "Add Unit" to start.'],
            
            // Lesson Session Editor
            ['group' => 'syllabus', 'key' => 'edit_session', 'vi' => 'Chỉnh sửa buổi học', 'en' => 'Edit Session'],
            ['group' => 'syllabus', 'key' => 'add_new_session', 'vi' => 'Thêm buổi học mới', 'en' => 'Add New Session'],
            ['group' => 'syllabus', 'key' => 'session_number_label', 'vi' => 'Buổi học số', 'en' => 'Session number'],
            ['group' => 'syllabus', 'key' => 'tab_basic_info', 'vi' => 'Thông tin cơ bản', 'en' => 'Basic Information'],
            ['group' => 'syllabus', 'key' => 'tab_materials', 'vi' => 'Tài liệu', 'en' => 'Materials'],
            ['group' => 'syllabus', 'key' => 'session_order', 'vi' => 'Số thứ tự buổi học', 'en' => 'Session Order'],
            ['group' => 'syllabus', 'key' => 'session_order_placeholder', 'vi' => 'Nhập số thứ tự', 'en' => 'Enter session order'],
            ['group' => 'syllabus', 'key' => 'duration', 'vi' => 'Thời lượng (phút)', 'en' => 'Duration (minutes)'],
            ['group' => 'syllabus', 'key' => 'duration_placeholder', 'vi' => '45', 'en' => '45'],
            ['group' => 'syllabus', 'key' => 'lesson_title', 'vi' => 'Tên bài học', 'en' => 'Lesson Title'],
            ['group' => 'syllabus', 'key' => 'lesson_title_placeholder', 'vi' => 'Ví dụ: Phương trình bậc nhất một ẩn', 'en' => 'E.g: Linear equations with one variable'],
            ['group' => 'syllabus', 'key' => 'lesson_objectives', 'vi' => 'Mục tiêu bài học', 'en' => 'Lesson Objectives'],
            ['group' => 'syllabus', 'key' => 'lesson_objectives_placeholder', 'vi' => 'Nhập mục tiêu bài học...', 'en' => 'Enter lesson objectives...'],
            ['group' => 'syllabus', 'key' => 'lesson_content', 'vi' => 'Nội dung bài học', 'en' => 'Lesson Content'],
            ['group' => 'syllabus', 'key' => 'lesson_content_placeholder', 'vi' => 'Nhập nội dung chi tiết bài học...', 'en' => 'Enter detailed lesson content...'],
            ['group' => 'syllabus', 'key' => 'notes', 'vi' => 'Ghi chú', 'en' => 'Notes'],
            ['group' => 'syllabus', 'key' => 'notes_placeholder', 'vi' => 'Ghi chú thêm về buổi học...', 'en' => 'Additional notes about the session...'],
            
            // Materials Tab
            ['group' => 'syllabus', 'key' => 'materials_guide_title', 'vi' => 'Hướng dẫn:', 'en' => 'Instructions:'],
            ['group' => 'syllabus', 'key' => 'materials_guide_1', 'vi' => 'Tải tài liệu lên Google Drive và chia sẻ link', 'en' => 'Upload materials to Google Drive and share the link'],
            ['group' => 'syllabus', 'key' => 'materials_guide_2', 'vi' => 'Có thể dùng link Google Docs, Sheets, Slides, hoặc folder', 'en' => 'You can use links from Google Docs, Sheets, Slides, or folders'],
            ['group' => 'syllabus', 'key' => 'materials_guide_3', 'vi' => 'Đảm bảo quyền truy cập phù hợp (ai có link có thể xem)', 'en' => 'Ensure appropriate access permissions (anyone with the link can view)'],
            ['group' => 'syllabus', 'key' => 'lesson_plan_link', 'vi' => 'Link Giáo án chi tiết', 'en' => 'Detailed Lesson Plan Link'],
            ['group' => 'syllabus', 'key' => 'lesson_plan_link_placeholder', 'vi' => 'https://drive.google.com/file/d/...', 'en' => 'https://drive.google.com/file/d/...'],
            ['group' => 'syllabus', 'key' => 'lesson_plan_description', 'vi' => 'File giáo án chi tiết cho buổi học này', 'en' => 'Detailed lesson plan file for this session'],
            ['group' => 'syllabus', 'key' => 'materials_link', 'vi' => 'Link Tài liệu giảng dạy', 'en' => 'Teaching Materials Link'],
            ['group' => 'syllabus', 'key' => 'materials_link_placeholder', 'vi' => 'https://drive.google.com/file/d/...', 'en' => 'https://drive.google.com/file/d/...'],
            ['group' => 'syllabus', 'key' => 'materials_description', 'vi' => 'Slide, tài liệu giảng dạy, video...', 'en' => 'Slides, teaching materials, videos...'],
            ['group' => 'syllabus', 'key' => 'homework_link', 'vi' => 'Link Bài tập về nhà', 'en' => 'Homework Link'],
            ['group' => 'syllabus', 'key' => 'homework_link_placeholder', 'vi' => 'https://drive.google.com/file/d/...', 'en' => 'https://drive.google.com/file/d/...'],
            ['group' => 'syllabus', 'key' => 'homework_description', 'vi' => 'Bài tập, đề kiểm tra cho học sinh', 'en' => 'Homework assignments, tests for students'],
            
            // Buttons & Actions
            ['group' => 'syllabus', 'key' => 'saving', 'vi' => 'Đang lưu...', 'en' => 'Saving...'],
            ['group' => 'syllabus', 'key' => 'btn_edit', 'vi' => 'Chỉnh sửa', 'en' => 'Edit'],
            ['group' => 'syllabus', 'key' => 'btn_delete', 'vi' => 'Xóa', 'en' => 'Delete'],
            ['group' => 'syllabus', 'key' => 'open_lesson_plan', 'vi' => 'Mở giáo án', 'en' => 'Open lesson plan'],
            ['group' => 'syllabus', 'key' => 'open_lesson_plans', 'vi' => 'Mở giáo án', 'en' => 'Open lesson plans'],
            ['group' => 'syllabus', 'key' => 'open_materials', 'vi' => 'Mở tài liệu', 'en' => 'Open materials'],
            ['group' => 'syllabus', 'key' => 'open_homework', 'vi' => 'Mở bài tập', 'en' => 'Open homework'],
            
            // Evaluation Form Builder
            ['group' => 'evaluation', 'key' => 'form_name', 'vi' => 'Tên form đánh giá', 'en' => 'Evaluation Form Name'],
            ['group' => 'evaluation', 'key' => 'builder_title', 'vi' => 'Thiết kế Form Đánh giá', 'en' => 'Design Evaluation Form'],
            ['group' => 'evaluation', 'key' => 'add_field', 'vi' => 'Thêm trường', 'en' => 'Add Field'],
            ['group' => 'evaluation', 'key' => 'field_type', 'vi' => 'Loại trường', 'en' => 'Field Type'],
            ['group' => 'evaluation', 'key' => 'field_label', 'vi' => 'Nhãn trường', 'en' => 'Field Label'],
            ['group' => 'evaluation', 'key' => 'field_required', 'vi' => 'Bắt buộc', 'en' => 'Required'],
            
            // Field Types
            ['group' => 'evaluation', 'key' => 'type_text', 'vi' => 'Văn bản', 'en' => 'Text'],
            ['group' => 'evaluation', 'key' => 'type_checkbox', 'vi' => 'Checkbox', 'en' => 'Checkbox'],
            ['group' => 'evaluation', 'key' => 'type_dropdown', 'vi' => 'Dropdown', 'en' => 'Dropdown'],
            
            // Text Field Config
            ['group' => 'evaluation', 'key' => 'text_is_title', 'vi' => 'Là tiêu đề', 'en' => 'Is Title'],
            ['group' => 'evaluation', 'key' => 'text_font_family', 'vi' => 'Font chữ', 'en' => 'Font Family'],
            ['group' => 'evaluation', 'key' => 'text_font_size', 'vi' => 'Cỡ chữ', 'en' => 'Font Size'],
            ['group' => 'evaluation', 'key' => 'text_bold', 'vi' => 'In đậm', 'en' => 'Bold'],
            
            // Dropdown Config
            ['group' => 'evaluation', 'key' => 'dropdown_options', 'vi' => 'Các lựa chọn', 'en' => 'Options'],
            ['group' => 'evaluation', 'key' => 'dropdown_add_option', 'vi' => 'Thêm lựa chọn', 'en' => 'Add Option'],
            ['group' => 'evaluation', 'key' => 'dropdown_option_value', 'vi' => 'Giá trị', 'en' => 'Value'],
            
            // Actions & Messages
            ['group' => 'syllabus', 'key' => 'save', 'vi' => 'Lưu', 'en' => 'Save'],
            ['group' => 'syllabus', 'key' => 'cancel', 'vi' => 'Hủy', 'en' => 'Cancel'],
            ['group' => 'syllabus', 'key' => 'created', 'vi' => 'Đã tạo syllabus thành công', 'en' => 'Syllabus created successfully'],
            ['group' => 'syllabus', 'key' => 'updated', 'vi' => 'Đã cập nhật syllabus', 'en' => 'Syllabus updated'],
            ['group' => 'syllabus', 'key' => 'deleted', 'vi' => 'Đã xóa syllabus', 'en' => 'Syllabus deleted'],
            ['group' => 'syllabus', 'key' => 'unit_added', 'vi' => 'Đã thêm unit', 'en' => 'Unit added'],
            ['group' => 'syllabus', 'key' => 'unit_updated', 'vi' => 'Đã cập nhật unit', 'en' => 'Unit updated'],
            ['group' => 'syllabus', 'key' => 'unit_deleted', 'vi' => 'Đã xóa unit', 'en' => 'Unit deleted'],
            ['group' => 'syllabus', 'key' => 'session_created_success', 'vi' => 'Đã tạo buổi học thành công', 'en' => 'Session created successfully'],
            ['group' => 'syllabus', 'key' => 'session_updated_success', 'vi' => 'Đã cập nhật buổi học thành công', 'en' => 'Session updated successfully'],
            ['group' => 'syllabus', 'key' => 'session_save_error', 'vi' => 'Lỗi khi lưu buổi học', 'en' => 'Error saving session'],
            ['group' => 'syllabus', 'key' => 'evaluation_linked_success', 'vi' => 'Đã liên kết form đánh giá với buổi học', 'en' => 'Evaluation form linked to session successfully'],
            ['group' => 'syllabus', 'key' => 'session_update_error', 'vi' => 'Không thể cập nhật buổi học', 'en' => 'Cannot update session'],
            ['group' => 'syllabus', 'key' => 'required_lesson_title', 'vi' => 'Vui lòng nhập tên bài học', 'en' => 'Please enter lesson title'],
            
            ['group' => 'evaluation', 'key' => 'form_created', 'vi' => 'Đã tạo form đánh giá', 'en' => 'Evaluation form created'],
            ['group' => 'evaluation', 'key' => 'form_updated', 'vi' => 'Đã cập nhật form đánh giá', 'en' => 'Evaluation form updated'],
            ['group' => 'evaluation', 'key' => 'form_deleted', 'vi' => 'Đã xóa form đánh giá', 'en' => 'Evaluation form deleted'],
            
            // Confirm Messages
            ['group' => 'syllabus', 'key' => 'confirm_delete', 'vi' => 'Xác nhận xóa syllabus này?', 'en' => 'Confirm delete this syllabus?'],
            ['group' => 'syllabus', 'key' => 'confirm_delete_unit', 'vi' => 'Xác nhận xóa unit này?', 'en' => 'Confirm delete this unit?'],
            
            // Errors
            ['group' => 'syllabus', 'key' => 'error_load', 'vi' => 'Không thể tải syllabus', 'en' => 'Cannot load syllabus'],
            ['group' => 'syllabus', 'key' => 'error_save', 'vi' => 'Không thể lưu syllabus', 'en' => 'Cannot save syllabus'],
            ['group' => 'syllabus', 'key' => 'error_delete', 'vi' => 'Không thể xóa syllabus', 'en' => 'Cannot delete syllabus'],
        ];

        foreach ($translations as $translation) {
            Translation::updateOrCreate(
                [
                    'language_id' => $vietnamese->id,
                    'group' => $translation['group'],
                    'key' => $translation['key']
                ],
                ['value' => $translation['vi']]
            );

            Translation::updateOrCreate(
                [
                    'language_id' => $english->id,
                    'group' => $translation['group'],
                    'key' => $translation['key']
                ],
                ['value' => $translation['en']]
            );
        }

        $this->command->info('Syllabus translations updated successfully!');
        $this->command->info('Total translations: ' . (count($translations) * 2));
    }
}
