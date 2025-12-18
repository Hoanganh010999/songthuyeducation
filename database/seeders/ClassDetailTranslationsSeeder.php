<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassDetailTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $languages = DB::table('languages')->pluck('id', 'code');
        $viId = $languages['vi'];
        $enId = $languages['en'];

        $translations = [
            // Class Detail - General
            ['group' => 'class_detail', 'key' => 'title', 'language_id' => $viId, 'value' => 'Chi tiết lớp học'],
            ['group' => 'class_detail', 'key' => 'title', 'language_id' => $enId, 'value' => 'Class Details'],
            ['group' => 'class_detail', 'key' => 'back_to_list', 'language_id' => $viId, 'value' => 'Quay lại danh sách'],
            ['group' => 'class_detail', 'key' => 'back_to_list', 'language_id' => $enId, 'value' => 'Back to List'],
            
            // Tabs
            ['group' => 'class_detail', 'key' => 'tab_schedule', 'language_id' => $viId, 'value' => 'Lịch học'],
            ['group' => 'class_detail', 'key' => 'tab_schedule', 'language_id' => $enId, 'value' => 'Schedule'],
            ['group' => 'class_detail', 'key' => 'tab_lessons', 'language_id' => $viId, 'value' => 'Chi tiết bài học'],
            ['group' => 'class_detail', 'key' => 'tab_lessons', 'language_id' => $enId, 'value' => 'Lesson Details'],
            ['group' => 'class_detail', 'key' => 'tab_students', 'language_id' => $viId, 'value' => 'Danh sách học viên'],
            ['group' => 'class_detail', 'key' => 'tab_students', 'language_id' => $enId, 'value' => 'Students'],
            ['group' => 'class_detail', 'key' => 'tab_overview', 'language_id' => $viId, 'value' => 'Tổng quan'],
            ['group' => 'class_detail', 'key' => 'tab_overview', 'language_id' => $enId, 'value' => 'Overview'],
            
            // Tab 1: Weekly Schedule
            ['group' => 'class_detail', 'key' => 'weekly_schedule', 'language_id' => $viId, 'value' => 'Lịch học trong tuần'],
            ['group' => 'class_detail', 'key' => 'weekly_schedule', 'language_id' => $enId, 'value' => 'Weekly Schedule'],
            ['group' => 'class_detail', 'key' => 'previous_week', 'language_id' => $viId, 'value' => 'Tuần trước'],
            ['group' => 'class_detail', 'key' => 'previous_week', 'language_id' => $enId, 'value' => 'Previous Week'],
            ['group' => 'class_detail', 'key' => 'next_week', 'language_id' => $viId, 'value' => 'Tuần sau'],
            ['group' => 'class_detail', 'key' => 'next_week', 'language_id' => $enId, 'value' => 'Next Week'],
            ['group' => 'class_detail', 'key' => 'this_week', 'language_id' => $viId, 'value' => 'Tuần này'],
            ['group' => 'class_detail', 'key' => 'this_week', 'language_id' => $enId, 'value' => 'This Week'],
            ['group' => 'class_detail', 'key' => 'monday', 'language_id' => $viId, 'value' => 'Thứ 2'],
            ['group' => 'class_detail', 'key' => 'monday', 'language_id' => $enId, 'value' => 'Monday'],
            ['group' => 'class_detail', 'key' => 'tuesday', 'language_id' => $viId, 'value' => 'Thứ 3'],
            ['group' => 'class_detail', 'key' => 'tuesday', 'language_id' => $enId, 'value' => 'Tuesday'],
            ['group' => 'class_detail', 'key' => 'wednesday', 'language_id' => $viId, 'value' => 'Thứ 4'],
            ['group' => 'class_detail', 'key' => 'wednesday', 'language_id' => $enId, 'value' => 'Wednesday'],
            ['group' => 'class_detail', 'key' => 'thursday', 'language_id' => $viId, 'value' => 'Thứ 5'],
            ['group' => 'class_detail', 'key' => 'thursday', 'language_id' => $enId, 'value' => 'Thursday'],
            ['group' => 'class_detail', 'key' => 'friday', 'language_id' => $viId, 'value' => 'Thứ 6'],
            ['group' => 'class_detail', 'key' => 'friday', 'language_id' => $enId, 'value' => 'Friday'],
            ['group' => 'class_detail', 'key' => 'saturday', 'language_id' => $viId, 'value' => 'Thứ 7'],
            ['group' => 'class_detail', 'key' => 'saturday', 'language_id' => $enId, 'value' => 'Saturday'],
            ['group' => 'class_detail', 'key' => 'sunday', 'language_id' => $viId, 'value' => 'Chủ nhật'],
            ['group' => 'class_detail', 'key' => 'sunday', 'language_id' => $enId, 'value' => 'Sunday'],
            ['group' => 'class_detail', 'key' => 'teacher', 'language_id' => $viId, 'value' => 'Giáo viên'],
            ['group' => 'class_detail', 'key' => 'teacher', 'language_id' => $enId, 'value' => 'Teacher'],
            ['group' => 'class_detail', 'key' => 'room', 'language_id' => $viId, 'value' => 'Phòng'],
            ['group' => 'class_detail', 'key' => 'room', 'language_id' => $enId, 'value' => 'Room'],
            
            // Tab 2: Lesson Details
            ['group' => 'class_detail', 'key' => 'lesson_list', 'language_id' => $viId, 'value' => 'Danh sách bài học'],
            ['group' => 'class_detail', 'key' => 'lesson_list', 'language_id' => $enId, 'value' => 'Lesson List'],
            ['group' => 'class_detail', 'key' => 'lesson_number', 'language_id' => $viId, 'value' => 'Buổi'],
            ['group' => 'class_detail', 'key' => 'lesson_number', 'language_id' => $enId, 'value' => 'Lesson'],
            ['group' => 'class_detail', 'key' => 'lesson_name', 'language_id' => $viId, 'value' => 'Tên bài học'],
            ['group' => 'class_detail', 'key' => 'lesson_name', 'language_id' => $enId, 'value' => 'Lesson Name'],
            ['group' => 'class_detail', 'key' => 'scheduled_date', 'language_id' => $viId, 'value' => 'Ngày học'],
            ['group' => 'class_detail', 'key' => 'scheduled_date', 'language_id' => $enId, 'value' => 'Date'],
            ['group' => 'class_detail', 'key' => 'lesson_plan_link', 'language_id' => $viId, 'value' => 'Giáo án'],
            ['group' => 'class_detail', 'key' => 'lesson_plan_link', 'language_id' => $enId, 'value' => 'Lesson Plan'],
            ['group' => 'class_detail', 'key' => 'teaching_resources', 'language_id' => $viId, 'value' => 'Tài liệu giảng dạy'],
            ['group' => 'class_detail', 'key' => 'teaching_resources', 'language_id' => $enId, 'value' => 'Teaching Resources'],
            ['group' => 'class_detail', 'key' => 'homework', 'language_id' => $viId, 'value' => 'Bài tập'],
            ['group' => 'class_detail', 'key' => 'homework', 'language_id' => $enId, 'value' => 'Homework'],
            ['group' => 'class_detail', 'key' => 'view_details', 'language_id' => $viId, 'value' => 'Xem chi tiết'],
            ['group' => 'class_detail', 'key' => 'view_details', 'language_id' => $enId, 'value' => 'View Details'],
            ['group' => 'class_detail', 'key' => 'attendance', 'language_id' => $viId, 'value' => 'Điểm danh'],
            ['group' => 'class_detail', 'key' => 'attendance', 'language_id' => $enId, 'value' => 'Attendance'],
            ['group' => 'class_detail', 'key' => 'comments', 'language_id' => $viId, 'value' => 'Nhận xét'],
            ['group' => 'class_detail', 'key' => 'comments', 'language_id' => $enId, 'value' => 'Comments'],
            ['group' => 'class_detail', 'key' => 'status_scheduled', 'language_id' => $viId, 'value' => 'Đã lên lịch'],
            ['group' => 'class_detail', 'key' => 'status_scheduled', 'language_id' => $enId, 'value' => 'Scheduled'],
            ['group' => 'class_detail', 'key' => 'status_completed', 'language_id' => $viId, 'value' => 'Đã hoàn thành'],
            ['group' => 'class_detail', 'key' => 'status_completed', 'language_id' => $enId, 'value' => 'Completed'],
            ['group' => 'class_detail', 'key' => 'status_cancelled', 'language_id' => $viId, 'value' => 'Đã hủy'],
            ['group' => 'class_detail', 'key' => 'status_cancelled', 'language_id' => $enId, 'value' => 'Cancelled'],
            ['group' => 'class_detail', 'key' => 'status_rescheduled', 'language_id' => $viId, 'value' => 'Đã dời lịch'],
            ['group' => 'class_detail', 'key' => 'status_rescheduled', 'language_id' => $enId, 'value' => 'Rescheduled'],
            
            // Attendance Modal
            ['group' => 'class_detail', 'key' => 'mark_attendance', 'language_id' => $viId, 'value' => 'Điểm danh'],
            ['group' => 'class_detail', 'key' => 'mark_attendance', 'language_id' => $enId, 'value' => 'Mark Attendance'],
            ['group' => 'class_detail', 'key' => 'student_name', 'language_id' => $viId, 'value' => 'Tên học viên'],
            ['group' => 'class_detail', 'key' => 'student_name', 'language_id' => $enId, 'value' => 'Student Name'],
            ['group' => 'class_detail', 'key' => 'attendance_status', 'language_id' => $viId, 'value' => 'Trạng thái'],
            ['group' => 'class_detail', 'key' => 'attendance_status', 'language_id' => $enId, 'value' => 'Status'],
            ['group' => 'class_detail', 'key' => 'present', 'language_id' => $viId, 'value' => 'Có mặt'],
            ['group' => 'class_detail', 'key' => 'present', 'language_id' => $enId, 'value' => 'Present'],
            ['group' => 'class_detail', 'key' => 'absent', 'language_id' => $viId, 'value' => 'Vắng'],
            ['group' => 'class_detail', 'key' => 'absent', 'language_id' => $enId, 'value' => 'Absent'],
            ['group' => 'class_detail', 'key' => 'late', 'language_id' => $viId, 'value' => 'Đi muộn'],
            ['group' => 'class_detail', 'key' => 'late', 'language_id' => $enId, 'value' => 'Late'],
            ['group' => 'class_detail', 'key' => 'excused', 'language_id' => $viId, 'value' => 'Có phép'],
            ['group' => 'class_detail', 'key' => 'excused', 'language_id' => $enId, 'value' => 'Excused'],
            ['group' => 'class_detail', 'key' => 'check_in_time', 'language_id' => $viId, 'value' => 'Giờ vào lớp'],
            ['group' => 'class_detail', 'key' => 'check_in_time', 'language_id' => $enId, 'value' => 'Check-in Time'],
            ['group' => 'class_detail', 'key' => 'notes', 'language_id' => $viId, 'value' => 'Ghi chú'],
            ['group' => 'class_detail', 'key' => 'notes', 'language_id' => $enId, 'value' => 'Notes'],
            ['group' => 'class_detail', 'key' => 'save_attendance', 'language_id' => $viId, 'value' => 'Lưu điểm danh'],
            ['group' => 'class_detail', 'key' => 'save_attendance', 'language_id' => $enId, 'value' => 'Save Attendance'],
            ['group' => 'class_detail', 'key' => 'homework_score', 'language_id' => $viId, 'value' => 'Điểm bài tập'],
            ['group' => 'class_detail', 'key' => 'homework_score', 'language_id' => $enId, 'value' => 'Homework Score'],
            ['group' => 'class_detail', 'key' => 'participation_level', 'language_id' => $viId, 'value' => 'Mức độ tương tác'],
            ['group' => 'class_detail', 'key' => 'participation_level', 'language_id' => $enId, 'value' => 'Participation Level'],
            ['group' => 'class_detail', 'key' => 'select_level', 'language_id' => $viId, 'value' => 'Chọn mức độ'],
            ['group' => 'class_detail', 'key' => 'select_level', 'language_id' => $enId, 'value' => 'Select Level'],
            ['group' => 'class_detail', 'key' => 'excellent', 'language_id' => $viId, 'value' => 'Rất tốt'],
            ['group' => 'class_detail', 'key' => 'excellent', 'language_id' => $enId, 'value' => 'Excellent'],
            ['group' => 'class_detail', 'key' => 'good', 'language_id' => $viId, 'value' => 'Tốt'],
            ['group' => 'class_detail', 'key' => 'good', 'language_id' => $enId, 'value' => 'Good'],
            ['group' => 'class_detail', 'key' => 'average', 'language_id' => $viId, 'value' => 'Trung bình'],
            ['group' => 'class_detail', 'key' => 'average', 'language_id' => $enId, 'value' => 'Average'],
            ['group' => 'class_detail', 'key' => 'needs_attention', 'language_id' => $viId, 'value' => 'Cần nhắc nhở'],
            ['group' => 'class_detail', 'key' => 'needs_attention', 'language_id' => $enId, 'value' => 'Needs Attention'],
            ['group' => 'class_detail', 'key' => 'poor', 'language_id' => $viId, 'value' => 'Rất tệ'],
            ['group' => 'class_detail', 'key' => 'poor', 'language_id' => $enId, 'value' => 'Poor'],
            
            // Comments Modal
            ['group' => 'class_detail', 'key' => 'add_comment', 'language_id' => $viId, 'value' => 'Thêm nhận xét'],
            ['group' => 'class_detail', 'key' => 'add_comment', 'language_id' => $enId, 'value' => 'Add Comment'],
            ['group' => 'class_detail', 'key' => 'comment_text', 'language_id' => $viId, 'value' => 'Nội dung nhận xét'],
            ['group' => 'class_detail', 'key' => 'comment_text', 'language_id' => $enId, 'value' => 'Comment'],
            ['group' => 'class_detail', 'key' => 'rating', 'language_id' => $viId, 'value' => 'Đánh giá'],
            ['group' => 'class_detail', 'key' => 'rating', 'language_id' => $enId, 'value' => 'Rating'],
            ['group' => 'class_detail', 'key' => 'behavior', 'language_id' => $viId, 'value' => 'Hành vi'],
            ['group' => 'class_detail', 'key' => 'behavior', 'language_id' => $enId, 'value' => 'Behavior'],
            ['group' => 'class_detail', 'key' => 'behavior_excellent', 'language_id' => $viId, 'value' => 'Xuất sắc'],
            ['group' => 'class_detail', 'key' => 'behavior_excellent', 'language_id' => $enId, 'value' => 'Excellent'],
            ['group' => 'class_detail', 'key' => 'behavior_good', 'language_id' => $viId, 'value' => 'Tốt'],
            ['group' => 'class_detail', 'key' => 'behavior_good', 'language_id' => $enId, 'value' => 'Good'],
            ['group' => 'class_detail', 'key' => 'behavior_average', 'language_id' => $viId, 'value' => 'Trung bình'],
            ['group' => 'class_detail', 'key' => 'behavior_average', 'language_id' => $enId, 'value' => 'Average'],
            ['group' => 'class_detail', 'key' => 'behavior_needs_improvement', 'language_id' => $viId, 'value' => 'Cần cải thiện'],
            ['group' => 'class_detail', 'key' => 'behavior_needs_improvement', 'language_id' => $enId, 'value' => 'Needs Improvement'],
            ['group' => 'class_detail', 'key' => 'participation', 'language_id' => $viId, 'value' => 'Tham gia'],
            ['group' => 'class_detail', 'key' => 'participation', 'language_id' => $enId, 'value' => 'Participation'],
            ['group' => 'class_detail', 'key' => 'participation_active', 'language_id' => $viId, 'value' => 'Tích cực'],
            ['group' => 'class_detail', 'key' => 'participation_active', 'language_id' => $enId, 'value' => 'Active'],
            ['group' => 'class_detail', 'key' => 'participation_moderate', 'language_id' => $viId, 'value' => 'Vừa phải'],
            ['group' => 'class_detail', 'key' => 'participation_moderate', 'language_id' => $enId, 'value' => 'Moderate'],
            ['group' => 'class_detail', 'key' => 'participation_passive', 'language_id' => $viId, 'value' => 'Thụ động'],
            ['group' => 'class_detail', 'key' => 'participation_passive', 'language_id' => $enId, 'value' => 'Passive'],
            
            // Tab 3: Students
            ['group' => 'class_detail', 'key' => 'add_student', 'language_id' => $viId, 'value' => 'Thêm học viên'],
            ['group' => 'class_detail', 'key' => 'add_student', 'language_id' => $enId, 'value' => 'Add Student'],
            ['group' => 'class_detail', 'key' => 'student_code', 'language_id' => $viId, 'value' => 'Mã học viên'],
            ['group' => 'class_detail', 'key' => 'student_code', 'language_id' => $enId, 'value' => 'Student Code'],
            ['group' => 'class_detail', 'key' => 'enrollment_date', 'language_id' => $viId, 'value' => 'Ngày nhập học'],
            ['group' => 'class_detail', 'key' => 'enrollment_date', 'language_id' => $enId, 'value' => 'Enrollment Date'],
            ['group' => 'class_detail', 'key' => 'student_status', 'language_id' => $viId, 'value' => 'Trạng thái'],
            ['group' => 'class_detail', 'key' => 'student_status', 'language_id' => $enId, 'value' => 'Status'],
            ['group' => 'class_detail', 'key' => 'status_active', 'language_id' => $viId, 'value' => 'Đang học'],
            ['group' => 'class_detail', 'key' => 'status_active', 'language_id' => $enId, 'value' => 'Active'],
            ['group' => 'class_detail', 'key' => 'status_dropped', 'language_id' => $viId, 'value' => 'Đã nghỉ'],
            ['group' => 'class_detail', 'key' => 'status_dropped', 'language_id' => $enId, 'value' => 'Dropped'],
            ['group' => 'class_detail', 'key' => 'status_transferred', 'language_id' => $viId, 'value' => 'Chuyển lớp'],
            ['group' => 'class_detail', 'key' => 'status_transferred', 'language_id' => $enId, 'value' => 'Transferred'],
            ['group' => 'class_detail', 'key' => 'homework_completion_rate', 'language_id' => $viId, 'value' => 'Tỷ lệ hoàn thành bài tập'],
            ['group' => 'class_detail', 'key' => 'homework_completion_rate', 'language_id' => $enId, 'value' => 'Homework Completion Rate'],
            ['group' => 'class_detail', 'key' => 'absence_rate', 'language_id' => $viId, 'value' => 'Tỷ lệ vắng'],
            ['group' => 'class_detail', 'key' => 'absence_rate', 'language_id' => $enId, 'value' => 'Absence Rate'],
            ['group' => 'class_detail', 'key' => 'average_grade', 'language_id' => $viId, 'value' => 'Điểm trung bình'],
            ['group' => 'class_detail', 'key' => 'average_grade', 'language_id' => $enId, 'value' => 'Average Grade'],
            ['group' => 'class_detail', 'key' => 'discount_percent', 'language_id' => $viId, 'value' => 'Giảm giá (%)'],
            ['group' => 'class_detail', 'key' => 'discount_percent', 'language_id' => $enId, 'value' => 'Discount (%)'],
            ['group' => 'class_detail', 'key' => 'actions', 'language_id' => $viId, 'value' => 'Thao tác'],
            ['group' => 'class_detail', 'key' => 'actions', 'language_id' => $enId, 'value' => 'Actions'],
            ['group' => 'class_detail', 'key' => 'edit_student', 'language_id' => $viId, 'value' => 'Sửa'],
            ['group' => 'class_detail', 'key' => 'edit_student', 'language_id' => $enId, 'value' => 'Edit'],
            ['group' => 'class_detail', 'key' => 'remove_student', 'language_id' => $viId, 'value' => 'Xóa'],
            ['group' => 'class_detail', 'key' => 'remove_student', 'language_id' => $enId, 'value' => 'Remove'],
            ['group' => 'class_detail', 'key' => 'confirm_remove', 'language_id' => $viId, 'value' => 'Bạn có chắc muốn xóa học viên này?'],
            ['group' => 'class_detail', 'key' => 'confirm_remove', 'language_id' => $enId, 'value' => 'Are you sure you want to remove this student?'],
            
            // Tab 4: Overview
            ['group' => 'class_detail', 'key' => 'class_overview', 'language_id' => $viId, 'value' => 'Tổng quan lớp học'],
            ['group' => 'class_detail', 'key' => 'class_overview', 'language_id' => $enId, 'value' => 'Class Overview'],
            ['group' => 'class_detail', 'key' => 'total_sessions', 'language_id' => $viId, 'value' => 'Tổng số buổi'],
            ['group' => 'class_detail', 'key' => 'total_sessions', 'language_id' => $enId, 'value' => 'Total Sessions'],
            ['group' => 'class_detail', 'key' => 'completed_sessions', 'language_id' => $viId, 'value' => 'Đã hoàn thành'],
            ['group' => 'class_detail', 'key' => 'completed_sessions', 'language_id' => $enId, 'value' => 'Completed'],
            ['group' => 'class_detail', 'key' => 'scheduled_sessions', 'language_id' => $viId, 'value' => 'Đã lên lịch'],
            ['group' => 'class_detail', 'key' => 'scheduled_sessions', 'language_id' => $enId, 'value' => 'Scheduled'],
            ['group' => 'class_detail', 'key' => 'cancelled_sessions', 'language_id' => $viId, 'value' => 'Đã hủy'],
            ['group' => 'class_detail', 'key' => 'cancelled_sessions', 'language_id' => $enId, 'value' => 'Cancelled'],
            ['group' => 'class_detail', 'key' => 'remaining_sessions', 'language_id' => $viId, 'value' => 'Còn lại'],
            ['group' => 'class_detail', 'key' => 'remaining_sessions', 'language_id' => $enId, 'value' => 'Remaining'],
            ['group' => 'class_detail', 'key' => 'progress_percentage', 'language_id' => $viId, 'value' => 'Tiến độ'],
            ['group' => 'class_detail', 'key' => 'progress_percentage', 'language_id' => $enId, 'value' => 'Progress'],
            ['group' => 'class_detail', 'key' => 'active_students', 'language_id' => $viId, 'value' => 'Học viên đang học'],
            ['group' => 'class_detail', 'key' => 'active_students', 'language_id' => $enId, 'value' => 'Active Students'],
            ['group' => 'class_detail', 'key' => 'total_students', 'language_id' => $viId, 'value' => 'Tổng học viên'],
            ['group' => 'class_detail', 'key' => 'total_students', 'language_id' => $enId, 'value' => 'Total Students'],
            ['group' => 'class_detail', 'key' => 'capacity', 'language_id' => $viId, 'value' => 'Sức chứa'],
            ['group' => 'class_detail', 'key' => 'capacity', 'language_id' => $enId, 'value' => 'Capacity'],
            ['group' => 'class_detail', 'key' => 'occupancy_rate', 'language_id' => $viId, 'value' => 'Tỷ lệ lấp đầy'],
            ['group' => 'class_detail', 'key' => 'occupancy_rate', 'language_id' => $enId, 'value' => 'Occupancy Rate'],
            ['group' => 'class_detail', 'key' => 'expected_revenue', 'language_id' => $viId, 'value' => 'Doanh thu dự kiến'],
            ['group' => 'class_detail', 'key' => 'expected_revenue', 'language_id' => $enId, 'value' => 'Expected Revenue'],
            ['group' => 'class_detail', 'key' => 'start_date', 'language_id' => $viId, 'value' => 'Ngày bắt đầu'],
            ['group' => 'class_detail', 'key' => 'start_date', 'language_id' => $enId, 'value' => 'Start Date'],
            ['group' => 'class_detail', 'key' => 'end_date', 'language_id' => $viId, 'value' => 'Ngày kết thúc'],
            ['group' => 'class_detail', 'key' => 'end_date', 'language_id' => $enId, 'value' => 'End Date'],
            ['group' => 'class_detail', 'key' => 'class_status', 'language_id' => $viId, 'value' => 'Trạng thái lớp'],
            ['group' => 'class_detail', 'key' => 'class_status', 'language_id' => $enId, 'value' => 'Class Status'],
            
            // Messages
            ['group' => 'class_detail', 'key' => 'student_added', 'language_id' => $viId, 'value' => 'Đã thêm học viên'],
            ['group' => 'class_detail', 'key' => 'student_added', 'language_id' => $enId, 'value' => 'Student Added'],
            ['group' => 'class_detail', 'key' => 'student_updated', 'language_id' => $viId, 'value' => 'Đã cập nhật học viên'],
            ['group' => 'class_detail', 'key' => 'student_updated', 'language_id' => $enId, 'value' => 'Student Updated'],
            ['group' => 'class_detail', 'key' => 'student_removed', 'language_id' => $viId, 'value' => 'Đã xóa học viên'],
            ['group' => 'class_detail', 'key' => 'student_removed', 'language_id' => $enId, 'value' => 'Student Removed'],
            ['group' => 'class_detail', 'key' => 'attendance_saved', 'language_id' => $viId, 'value' => 'Đã lưu điểm danh'],
            ['group' => 'class_detail', 'key' => 'attendance_saved', 'language_id' => $enId, 'value' => 'Attendance Saved'],
            ['group' => 'class_detail', 'key' => 'comment_saved', 'language_id' => $viId, 'value' => 'Đã lưu nhận xét'],
            ['group' => 'class_detail', 'key' => 'comment_saved', 'language_id' => $enId, 'value' => 'Comment Saved'],
            ['group' => 'class_detail', 'key' => 'no_lesson_plan', 'language_id' => $viId, 'value' => 'Chưa có giáo án'],
            ['group' => 'class_detail', 'key' => 'no_lesson_plan', 'language_id' => $enId, 'value' => 'No Lesson Plan'],
            ['group' => 'class_detail', 'key' => 'loading', 'language_id' => $viId, 'value' => 'Đang tải...'],
            ['group' => 'class_detail', 'key' => 'loading', 'language_id' => $enId, 'value' => 'Loading...'],
            
            // Additional translations
            ['group' => 'class_detail', 'key' => 'edit_schedule_time', 'language_id' => $viId, 'value' => 'Chỉnh sửa giờ học'],
            ['group' => 'class_detail', 'key' => 'edit_schedule_time', 'language_id' => $enId, 'value' => 'Edit Schedule Time'],
            ['group' => 'class_detail', 'key' => 'day_of_week', 'language_id' => $viId, 'value' => 'Thứ'],
            ['group' => 'class_detail', 'key' => 'day_of_week', 'language_id' => $enId, 'value' => 'Day of Week'],
            ['group' => 'class_detail', 'key' => 'start_time', 'language_id' => $viId, 'value' => 'Giờ bắt đầu'],
            ['group' => 'class_detail', 'key' => 'start_time', 'language_id' => $enId, 'value' => 'Start Time'],
            ['group' => 'class_detail', 'key' => 'end_time', 'language_id' => $viId, 'value' => 'Giờ kết thúc'],
            ['group' => 'class_detail', 'key' => 'end_time', 'language_id' => $enId, 'value' => 'End Time'],
            ['group' => 'class_detail', 'key' => 'schedule_updated', 'language_id' => $viId, 'value' => 'Lịch học đã được cập nhật'],
            ['group' => 'class_detail', 'key' => 'schedule_updated', 'language_id' => $enId, 'value' => 'Schedule updated successfully'],
            ['group' => 'class_detail', 'key' => 'no_sessions_generated', 'language_id' => $viId, 'value' => 'Chưa có buổi học nào được tạo'],
            ['group' => 'class_detail', 'key' => 'no_sessions_generated', 'language_id' => $enId, 'value' => 'No sessions generated yet'],
            ['group' => 'class_detail', 'key' => 'sessions_preview_below', 'language_id' => $viId, 'value' => 'Dưới đây là danh sách ngày học dự kiến dựa trên lịch học của lớp'],
            ['group' => 'class_detail', 'key' => 'sessions_preview_below', 'language_id' => $enId, 'value' => 'Below is a preview of scheduled lesson dates based on class schedule'],
            ['group' => 'class_detail', 'key' => 'status_preview', 'language_id' => $viId, 'value' => 'Dự kiến'],
            ['group' => 'class_detail', 'key' => 'status_preview', 'language_id' => $enId, 'value' => 'Preview'],
            
            // Sync from Syllabus
            ['group' => 'class_detail', 'key' => 'sync_from_syllabus', 'language_id' => $viId, 'value' => 'Cập nhật từ Giáo án'],
            ['group' => 'class_detail', 'key' => 'sync_from_syllabus', 'language_id' => $enId, 'value' => 'Sync from Syllabus'],
            ['group' => 'class_detail', 'key' => 'confirm_sync_title', 'language_id' => $viId, 'value' => 'Cập nhật từ Giáo án?'],
            ['group' => 'class_detail', 'key' => 'confirm_sync_title', 'language_id' => $enId, 'value' => 'Sync from Syllabus?'],
            ['group' => 'class_detail', 'key' => 'confirm_sync_message', 'language_id' => $viId, 'value' => 'Hệ thống sẽ cập nhật lại nội dung bài học từ giáo án.<br><br><strong>Lưu ý:</strong> Chỉ cập nhật những buổi học chưa điểm danh.<br>Những buổi đã điểm danh sẽ không bị thay đổi.'],
            ['group' => 'class_detail', 'key' => 'confirm_sync_message', 'language_id' => $enId, 'value' => 'The system will update lesson content from the syllabus.<br><br><strong>Note:</strong> Only sessions without attendance will be updated.<br>Sessions with attendance records will remain unchanged.'],
            ['group' => 'class_detail', 'key' => 'sync_success', 'language_id' => $viId, 'value' => 'Cập nhật thành công!'],
            ['group' => 'class_detail', 'key' => 'sync_success', 'language_id' => $enId, 'value' => 'Sync Successful!'],
            ['group' => 'class_detail', 'key' => 'sync_error', 'language_id' => $viId, 'value' => 'Không thể cập nhật từ giáo án'],
            ['group' => 'class_detail', 'key' => 'sync_error', 'language_id' => $enId, 'value' => 'Failed to sync from syllabus'],
        ];

        foreach ($translations as $translation) {
            $existing = DB::table('translations')
                ->where('group', $translation['group'])
                ->where('key', $translation['key'])
                ->where('language_id', $translation['language_id'])
                ->first();

            if ($existing) {
                DB::table('translations')
                    ->where('id', $existing->id)
                    ->update(['value' => $translation['value']]);
            } else {
                DB::table('translations')->insert($translation);
            }
        }

        $this->command->info('Class Detail translations seeded successfully!');
    }
}
