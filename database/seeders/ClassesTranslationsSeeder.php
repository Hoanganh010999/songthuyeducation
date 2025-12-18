<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class ClassesTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $vietnamese = Language::where('code', 'vi')->first();
        $english = Language::where('code', 'en')->first();

        if (!$vietnamese || !$english) {
            $this->command->error('Languages not found!');
            return;
        }

        $translations = [
            // Academic Years
            ['group' => 'academic_years', 'key' => 'title', 'vi' => 'Năm học', 'en' => 'Academic Years'],
            ['group' => 'academic_years', 'key' => 'create', 'vi' => 'Tạo năm học', 'en' => 'Create Academic Year'],
            ['group' => 'academic_years', 'key' => 'edit', 'vi' => 'Sửa năm học', 'en' => 'Edit Academic Year'],
            ['group' => 'academic_years', 'key' => 'name', 'vi' => 'Tên năm học', 'en' => 'Year Name'],
            ['group' => 'academic_years', 'key' => 'code', 'vi' => 'Mã', 'en' => 'Code'],
            ['group' => 'academic_years', 'key' => 'start_date', 'vi' => 'Ngày bắt đầu', 'en' => 'Start Date'],
            ['group' => 'academic_years', 'key' => 'end_date', 'vi' => 'Ngày kết thúc', 'en' => 'End Date'],
            ['group' => 'academic_years', 'key' => 'is_current', 'vi' => 'Năm học hiện tại', 'en' => 'Current Year'],
            
            // Semesters
            ['group' => 'semesters', 'key' => 'title', 'vi' => 'Học kỳ', 'en' => 'Semesters'],
            ['group' => 'semesters', 'key' => 'create', 'vi' => 'Tạo học kỳ', 'en' => 'Create Semester'],
            ['group' => 'semesters', 'key' => 'name', 'vi' => 'Tên học kỳ', 'en' => 'Semester Name'],
            ['group' => 'semesters', 'key' => 'total_weeks', 'vi' => 'Tổng số tuần', 'en' => 'Total Weeks'],
            
            // Study Periods
            ['group' => 'study_periods', 'key' => 'title', 'vi' => 'Ca học', 'en' => 'Study Periods'],
            ['group' => 'study_periods', 'key' => 'create', 'vi' => 'Tạo ca học', 'en' => 'Create Study Period'],
            ['group' => 'study_periods', 'key' => 'name', 'vi' => 'Tên ca học', 'en' => 'Period Name'],
            ['group' => 'study_periods', 'key' => 'start_time', 'vi' => 'Giờ bắt đầu', 'en' => 'Start Time'],
            ['group' => 'study_periods', 'key' => 'end_time', 'vi' => 'Giờ kết thúc', 'en' => 'End Time'],
            ['group' => 'study_periods', 'key' => 'lesson_duration', 'vi' => 'Thời lượng tiết học (phút)', 'en' => 'Lesson Duration (minutes)'],
            ['group' => 'study_periods', 'key' => 'break_duration', 'vi' => 'Thời gian nghỉ (phút)', 'en' => 'Break Duration (minutes)'],
            
            // Rooms
            ['group' => 'rooms', 'key' => 'title', 'vi' => 'Phòng học', 'en' => 'Rooms'],
            ['group' => 'rooms', 'key' => 'create', 'vi' => 'Tạo phòng học', 'en' => 'Create Room'],
            ['group' => 'rooms', 'key' => 'name', 'vi' => 'Tên phòng', 'en' => 'Room Name'],
            ['group' => 'rooms', 'key' => 'building', 'vi' => 'Tòa nhà', 'en' => 'Building'],
            ['group' => 'rooms', 'key' => 'floor', 'vi' => 'Tầng', 'en' => 'Floor'],
            ['group' => 'rooms', 'key' => 'capacity', 'vi' => 'Sức chứa', 'en' => 'Capacity'],
            ['group' => 'rooms', 'key' => 'room_type', 'vi' => 'Loại phòng', 'en' => 'Room Type'],
            ['group' => 'rooms', 'key' => 'is_available', 'vi' => 'Có sẵn', 'en' => 'Available'],
            
            // Holidays
            ['group' => 'holidays', 'key' => 'title', 'vi' => 'Lịch nghỉ', 'en' => 'Holidays'],
            ['group' => 'holidays', 'key' => 'create', 'vi' => 'Tạo lịch nghỉ', 'en' => 'Create Holiday'],
            ['group' => 'holidays', 'key' => 'name', 'vi' => 'Tên ngày nghỉ', 'en' => 'Holiday Name'],
            ['group' => 'holidays', 'key' => 'type', 'vi' => 'Loại', 'en' => 'Type'],
            ['group' => 'holidays', 'key' => 'total_days', 'vi' => 'Số ngày nghỉ', 'en' => 'Total Days'],
            ['group' => 'holidays', 'key' => 'affects_schedule', 'vi' => 'Ảnh hưởng lịch học', 'en' => 'Affects Schedule'],
            
            // Classes
            ['group' => 'classes', 'key' => 'title', 'vi' => 'Lớp học', 'en' => 'Classes'],
            ['group' => 'classes', 'key' => 'settings_title', 'vi' => 'Thiết lập Lớp học', 'en' => 'Class Settings'],
            ['group' => 'classes', 'key' => 'settings_description', 'vi' => 'Quản lý năm học, học kỳ, ca học, phòng học và lịch nghỉ', 'en' => 'Manage academic years, semesters, study periods, rooms and holidays'],
            ['group' => 'classes', 'key' => 'create', 'vi' => 'Tạo lớp học', 'en' => 'Create Class'],
            ['group' => 'classes', 'key' => 'edit', 'vi' => 'Sửa lớp học', 'en' => 'Edit Class'],
            ['group' => 'classes', 'key' => 'name', 'vi' => 'Tên lớp', 'en' => 'Class Name'],
            ['group' => 'classes', 'key' => 'code', 'vi' => 'Mã lớp', 'en' => 'Class Code'],
            ['group' => 'classes', 'key' => 'homeroom_teacher', 'vi' => 'Giáo viên chủ nhiệm', 'en' => 'Homeroom Teacher'],
            ['group' => 'classes', 'key' => 'semester', 'vi' => 'Học kỳ', 'en' => 'Semester'],
            ['group' => 'classes', 'key' => 'lesson_plan', 'vi' => 'Giáo án', 'en' => 'Lesson Plan'],
            ['group' => 'classes', 'key' => 'total_sessions', 'vi' => 'Tổng số buổi', 'en' => 'Total Sessions'],
            ['group' => 'classes', 'key' => 'completed_sessions', 'vi' => 'Buổi đã học', 'en' => 'Completed Sessions'],
            ['group' => 'classes', 'key' => 'level', 'vi' => 'Cấp học', 'en' => 'Level'],
            ['group' => 'classes', 'key' => 'capacity', 'vi' => 'Sĩ số tối đa', 'en' => 'Max Capacity'],
            ['group' => 'classes', 'key' => 'current_students', 'vi' => 'Sĩ số hiện tại', 'en' => 'Current Students'],
            ['group' => 'classes', 'key' => 'schedule', 'vi' => 'Lịch học', 'en' => 'Schedule'],
            ['group' => 'classes', 'key' => 'create_schedule', 'vi' => 'Tạo lịch học', 'en' => 'Create Schedule'],
            ['group' => 'classes', 'key' => 'generate_sessions', 'vi' => 'Tạo buổi học', 'en' => 'Generate Sessions'],
            ['group' => 'classes', 'key' => 'teacher_conflict', 'vi' => 'Giáo viên bị trùng lịch', 'en' => 'Teacher Conflict'],
            ['group' => 'classes', 'key' => 'room_conflict', 'vi' => 'Phòng học bị trùng', 'en' => 'Room Conflict'],
            
            // Lesson Plans
            ['group' => 'lesson_plans', 'key' => 'title', 'vi' => 'Giáo án', 'en' => 'Lesson Plans'],
            ['group' => 'lesson_plans', 'key' => 'create', 'vi' => 'Tạo giáo án', 'en' => 'Create Lesson Plan'],
            ['group' => 'lesson_plans', 'key' => 'edit', 'vi' => 'Sửa giáo án', 'en' => 'Edit Lesson Plan'],
            ['group' => 'lesson_plans', 'key' => 'name', 'vi' => 'Tên giáo án', 'en' => 'Plan Name'],
            ['group' => 'lesson_plans', 'key' => 'subject', 'vi' => 'Môn học', 'en' => 'Subject'],
            ['group' => 'lesson_plans', 'key' => 'total_sessions', 'vi' => 'Tổng số buổi', 'en' => 'Total Sessions'],
            ['group' => 'lesson_plans', 'key' => 'status', 'vi' => 'Trạng thái', 'en' => 'Status'],
            ['group' => 'lesson_plans', 'key' => 'sessions', 'vi' => 'Danh sách buổi học', 'en' => 'Sessions List'],
            ['group' => 'lesson_plans', 'key' => 'add_session', 'vi' => 'Thêm buổi học', 'en' => 'Add Session'],
            ['group' => 'lesson_plans', 'key' => 'session_number', 'vi' => 'Buổi số', 'en' => 'Session Number'],
            ['group' => 'lesson_plans', 'key' => 'lesson_title', 'vi' => 'Tiêu đề bài học', 'en' => 'Lesson Title'],
            ['group' => 'lesson_plans', 'key' => 'lesson_objectives', 'vi' => 'Mục tiêu', 'en' => 'Objectives'],
            ['group' => 'lesson_plans', 'key' => 'lesson_content', 'vi' => 'Nội dung', 'en' => 'Content'],
            ['group' => 'lesson_plans', 'key' => 'lesson_plan_url', 'vi' => 'Link Giáo án', 'en' => 'Lesson Plan URL'],
            ['group' => 'lesson_plans', 'key' => 'materials_url', 'vi' => 'Link Tài liệu', 'en' => 'Materials URL'],
            ['group' => 'lesson_plans', 'key' => 'homework_url', 'vi' => 'Link Bài tập', 'en' => 'Homework URL'],
            
            // Class Status
            ['group' => 'classes', 'key' => 'status', 'vi' => 'Trạng thái', 'en' => 'Status'],
            ['group' => 'classes', 'key' => 'status_draft', 'vi' => 'Nháp', 'en' => 'Draft'],
            ['group' => 'classes', 'key' => 'status_active', 'vi' => 'Đang học', 'en' => 'Active'],
            ['group' => 'classes', 'key' => 'status_paused', 'vi' => 'Tạm dừng', 'en' => 'Paused'],
            ['group' => 'classes', 'key' => 'status_completed', 'vi' => 'Hoàn thành', 'en' => 'Completed'],
            ['group' => 'classes', 'key' => 'status_cancelled', 'vi' => 'Đã hủy', 'en' => 'Cancelled'],
            ['group' => 'classes', 'key' => 'status_draft_hint', 'vi' => 'Lớp đang chuẩn bị, chưa mở', 'en' => 'Class is being prepared, not yet open'],
            ['group' => 'classes', 'key' => 'status_active_hint', 'vi' => 'Lớp đang hoạt động, có thể điểm danh', 'en' => 'Class is active, attendance available'],
            ['group' => 'classes', 'key' => 'status_paused_hint', 'vi' => 'Lớp tạm dừng, không thể điểm danh', 'en' => 'Class is paused, attendance disabled'],
            ['group' => 'classes', 'key' => 'status_completed_hint', 'vi' => 'Lớp đã hoàn thành tất cả buổi học', 'en' => 'Class completed all sessions'],
            ['group' => 'classes', 'key' => 'status_cancelled_hint', 'vi' => 'Lớp đã bị hủy', 'en' => 'Class has been cancelled'],
            
            // Class Form Fields
            ['group' => 'classes', 'key' => 'subject', 'vi' => 'Môn học', 'en' => 'Subject'],
            ['group' => 'classes', 'key' => 'start_date', 'vi' => 'Ngày bắt đầu', 'en' => 'Start Date'],
            ['group' => 'classes', 'key' => 'hourly_rate', 'vi' => 'Học phí/giờ', 'en' => 'Hourly Rate'],
            ['group' => 'classes', 'key' => 'day_of_week', 'vi' => 'Thứ', 'en' => 'Day of Week'],
            ['group' => 'classes', 'key' => 'study_period', 'vi' => 'Ca học', 'en' => 'Study Period'],
            ['group' => 'classes', 'key' => 'teacher', 'vi' => 'Giáo viên', 'en' => 'Teacher'],
            ['group' => 'classes', 'key' => 'room', 'vi' => 'Phòng học', 'en' => 'Room'],
            ['group' => 'classes', 'key' => 'add_schedule', 'vi' => 'Thêm lịch học', 'en' => 'Add Schedule'],
            ['group' => 'classes', 'key' => 'select_subject_first', 'vi' => 'Vui lòng chọn môn học trước', 'en' => 'Please select subject first'],
            
            // Class List
            ['group' => 'classes', 'key' => 'management_title', 'vi' => 'Quản lý Lớp học', 'en' => 'Class Management'],
            ['group' => 'classes', 'key' => 'management_description', 'vi' => 'Quản lý lớp học, lịch học và buổi học', 'en' => 'Manage classes, schedules and sessions'],
            ['group' => 'classes', 'key' => 'settings', 'vi' => 'Thiết lập', 'en' => 'Settings'],
            ['group' => 'classes', 'key' => 'create_class', 'vi' => 'Tạo lớp học', 'en' => 'Create Class'],
            ['group' => 'classes', 'key' => 'class_name', 'vi' => 'Tên lớp', 'en' => 'Class Name'],
            ['group' => 'classes', 'key' => 'homeroom_teacher_short', 'vi' => 'GVCN', 'en' => 'Homeroom Teacher'],
            ['group' => 'classes', 'key' => 'sessions', 'vi' => 'Số buổi', 'en' => 'Sessions'],
            ['group' => 'classes', 'key' => 'edit_class', 'vi' => 'Sửa lớp học', 'en' => 'Edit Class'],
            ['group' => 'classes', 'key' => 'create_new_class', 'vi' => 'Tạo lớp học mới', 'en' => 'Create New Class'],
            ['group' => 'classes', 'key' => 'view_details', 'vi' => 'Xem', 'en' => 'View'],
            ['group' => 'classes', 'key' => 'students_count', 'vi' => 'Sĩ số', 'en' => 'Students'],
            ['group' => 'classes', 'key' => 'sessions_progress', 'vi' => 'Tiến độ', 'en' => 'Progress'],
            ['group' => 'classes', 'key' => 'no_classes', 'vi' => 'Chưa có lớp học nào', 'en' => 'No classes yet'],
            ['group' => 'classes', 'key' => 'no_schedules', 'vi' => 'Chưa có lịch học nào', 'en' => 'No schedules yet'],
            ['group' => 'classes', 'key' => 'schedule_change_warning', 'vi' => 'Bạn đã thay đổi lịch học của lớp', 'en' => 'You have changed the class schedule'],
            ['group' => 'classes', 'key' => 'schedule_recalculate_note', 'vi' => 'Hệ thống sẽ tự động tính toán lại các buổi học chưa diễn ra', 'en' => 'System will automatically recalculate upcoming sessions'],
            ['group' => 'classes', 'key' => 'completed_sessions_safe', 'vi' => 'Các buổi học đã điểm danh sẽ không bị ảnh hưởng', 'en' => 'Attended sessions will not be affected'],
            ['group' => 'classes', 'key' => 'class_created', 'vi' => 'Đã tạo lớp học thành công', 'en' => 'Class created successfully'],
            ['group' => 'classes', 'key' => 'class_updated', 'vi' => 'Đã cập nhật lớp học thành công', 'en' => 'Class updated successfully'],
            ['group' => 'classes', 'key' => 'load_error', 'vi' => 'Không thể tải danh sách lớp học', 'en' => 'Cannot load classes list'],
            ['group' => 'classes', 'key' => 'delete_confirm_title', 'vi' => 'Xác nhận xóa?', 'en' => 'Confirm Delete?'],
            ['group' => 'classes', 'key' => 'delete_confirm_text', 'vi' => 'Bạn có chắc muốn xóa lớp "{name}"?', 'en' => 'Are you sure you want to delete class "{name}"?'],
            ['group' => 'classes', 'key' => 'class_deleted', 'vi' => 'Đã xóa lớp học', 'en' => 'Class deleted'],
            ['group' => 'classes', 'key' => 'delete_error', 'vi' => 'Không thể xóa lớp học', 'en' => 'Cannot delete class'],
            
            // Common
            ['group' => 'common', 'key' => 'select', 'vi' => 'Chọn', 'en' => 'Select'],
            ['group' => 'common', 'key' => 'none', 'vi' => 'Không chọn', 'en' => 'None'],
            ['group' => 'common', 'key' => 'coming_soon', 'vi' => 'Sắp có', 'en' => 'Coming Soon'],
            ['group' => 'common', 'key' => 'warning', 'vi' => 'Cảnh báo', 'en' => 'Warning'],
            ['group' => 'common', 'key' => 'continue', 'vi' => 'Tiếp tục', 'en' => 'Continue'],
            ['group' => 'common', 'key' => 'success', 'vi' => 'Thành công', 'en' => 'Success'],
            ['group' => 'common', 'key' => 'error', 'vi' => 'Lỗi', 'en' => 'Error'],
            ['group' => 'common', 'key' => 'error_occurred', 'vi' => 'Đã xảy ra lỗi', 'en' => 'An error occurred'],
            ['group' => 'common', 'key' => 'saving', 'vi' => 'Đang lưu...', 'en' => 'Saving...'],
            ['group' => 'common', 'key' => 'save', 'vi' => 'Lưu', 'en' => 'Save'],
            ['group' => 'common', 'key' => 'cancel', 'vi' => 'Hủy', 'en' => 'Cancel'],
            ['group' => 'common', 'key' => 'delete', 'vi' => 'Xóa', 'en' => 'Delete'],
            ['group' => 'common', 'key' => 'edit', 'vi' => 'Sửa', 'en' => 'Edit'],
            ['group' => 'common', 'key' => 'create', 'vi' => 'Tạo', 'en' => 'Create'],
            ['group' => 'common', 'key' => 'actions', 'vi' => 'Hành động', 'en' => 'Actions'],
            ['group' => 'common', 'key' => 'description', 'vi' => 'Mô tả', 'en' => 'Description'],
            ['group' => 'common', 'key' => 'status', 'vi' => 'Trạng thái', 'en' => 'Status'],
            ['group' => 'common', 'key' => 'active', 'vi' => 'Hoạt động', 'en' => 'Active'],
            ['group' => 'common', 'key' => 'inactive', 'vi' => 'Không hoạt động', 'en' => 'Inactive'],
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

        $this->command->info('Classes translations seeded successfully!');
        $this->command->info('Total translations: ' . (count($translations) * 2));
    }
}
