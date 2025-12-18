<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $viLang = DB::table('languages')->where('code', 'vi')->first();
        $enLang = DB::table('languages')->where('code', 'en')->first();

        if (!$viLang || !$enLang) {
            $this->command->error('Languages not found!');
            return;
        }

        $translations = [
            // Module
            'course.title' => ['vi' => 'Học liệu', 'en' => 'Course'],
            'course.description' => ['vi' => 'Quản lý bài giảng, bài tập và lịch sử học tập', 'en' => 'Manage lessons, assignments and learning history'],
            
            // Classroom Board
            'course.classroom_board' => ['vi' => 'Bảng lớp học', 'en' => 'Classroom Board'],
            'course.classroom_description' => ['vi' => 'Nơi chia sẻ thông tin và tài liệu lớp học', 'en' => 'Share information and materials'],
            'course.new_post' => ['vi' => 'Đăng bài mới', 'en' => 'New Post'],
            'course.write_something' => ['vi' => 'Viết gì đó...', 'en' => 'Write something...'],
            'course.post_type' => ['vi' => 'Loại bài đăng', 'en' => 'Post Type'],
            'course.text_post' => ['vi' => 'Bài viết', 'en' => 'Text'],
            'course.announcement' => ['vi' => 'Thông báo', 'en' => 'Announcement'],
            'course.material' => ['vi' => 'Tài liệu', 'en' => 'Material'],
            'course.add_media' => ['vi' => 'Thêm ảnh/file', 'en' => 'Add media'],
            'course.post' => ['vi' => 'Đăng bài', 'en' => 'Post'],
            'course.comment' => ['vi' => 'Bình luận', 'en' => 'Comment'],
            'course.like' => ['vi' => 'Thích', 'en' => 'Like'],
            'course.reply' => ['vi' => 'Trả lời', 'en' => 'Reply'],
            
            // Reactions
            'course.thumbsup' => ['vi' => 'Thích', 'en' => 'Like'],
            'course.heart' => ['vi' => 'Yêu thích', 'en' => 'Love'],
            'course.pray' => ['vi' => 'Cảm ơn', 'en' => 'Thank'],
            'course.celebrate' => ['vi' => 'Chúc mừng', 'en' => 'Celebrate'],
            'course.bulb' => ['vi' => 'Hay', 'en' => 'Insight'],
            'course.smile' => ['vi' => 'Vui', 'en' => 'Happy'],
            'course.pinned' => ['vi' => 'Đã ghim', 'en' => 'Pinned'],
            'course.no_posts' => ['vi' => 'Chưa có bài đăng nào', 'en' => 'No posts yet'],
            
            // Learning History
            'course.learning_history' => ['vi' => 'Lịch sử học tập', 'en' => 'Learning History'],
            'course.history_description' => ['vi' => 'Xem lịch sử điểm danh, bài tập và kết quả học tập', 'en' => 'View attendance, assignments and results'],
            'course.select_student' => ['vi' => 'Chọn học viên', 'en' => 'Select student'],
            'course.select_student_or_class' => ['vi' => 'Chọn học viên hoặc lớp học', 'en' => 'Select student or class'],
            'course.select_class' => ['vi' => 'Chọn lớp', 'en' => 'Select class'],
            'course.all_classes' => ['vi' => 'Tất cả lớp', 'en' => 'All classes'],
            'course.attendance_history' => ['vi' => 'Lịch sử điểm danh', 'en' => 'Attendance History'],
            'course.assignments_history' => ['vi' => 'Lịch sử bài tập', 'en' => 'Assignments History'],
            'course.evaluations' => ['vi' => 'Đánh giá', 'en' => 'Evaluations'],
            'course.statistics' => ['vi' => 'Thống kê', 'en' => 'Statistics'],
            'course.total_sessions' => ['vi' => 'Tổng buổi học', 'en' => 'Total Sessions'],
            'course.total_students' => ['vi' => 'Tổng học viên', 'en' => 'Total Students'],
            'course.present' => ['vi' => 'Có mặt', 'en' => 'Present'],
            'course.absent' => ['vi' => 'Vắng', 'en' => 'Absent'],
            'course.late' => ['vi' => 'Muộn', 'en' => 'Late'],
            'course.excused' => ['vi' => 'Có phép', 'en' => 'Excused'],
            
            // Assignments
            'course.assignments' => ['vi' => 'Bài tập', 'en' => 'Assignments'],
            'course.create_assignment' => ['vi' => 'Tạo bài tập', 'en' => 'Create Assignment'],
            'course.assignment_title' => ['vi' => 'Tiêu đề bài tập', 'en' => 'Assignment Title'],
            'course.description' => ['vi' => 'Mô tả', 'en' => 'Description'],
            'course.instructions' => ['vi' => 'Hướng dẫn', 'en' => 'Instructions'],
            'course.due_date' => ['vi' => 'Hạn nộp', 'en' => 'Due Date'],
            'course.max_score' => ['vi' => 'Điểm tối đa', 'en' => 'Max Score'],
            'course.attachment' => ['vi' => 'File đính kèm', 'en' => 'Attachment'],
            'course.submit_work' => ['vi' => 'Nộp bài', 'en' => 'Submit Work'],
            'course.submitted' => ['vi' => 'Đã nộp', 'en' => 'Submitted'],
            'course.graded' => ['vi' => 'Đã chấm', 'en' => 'Graded'],
            'course.draft' => ['vi' => 'Nháp', 'en' => 'Draft'],
            'course.score' => ['vi' => 'Điểm', 'en' => 'Score'],
            'course.feedback' => ['vi' => 'Nhận xét', 'en' => 'Feedback'],
            'course.grade_submission' => ['vi' => 'Chấm bài', 'en' => 'Grade Submission'],
            'course.is_late' => ['vi' => 'Nộp trễ', 'en' => 'Late'],
            'course.no_assignments' => ['vi' => 'Chưa có bài tập nào', 'en' => 'No assignments yet'],
            
            // Events
            'course.events' => ['vi' => 'Sự kiện', 'en' => 'Events'],
            'course.upcoming_events' => ['vi' => 'Sự kiện sắp tới', 'en' => 'Upcoming Events'],
            'course.create_event' => ['vi' => 'Tạo sự kiện', 'en' => 'Create Event'],
            'course.add_event' => ['vi' => 'Thêm sự kiện', 'en' => 'Add Event'],
            'course.event_details' => ['vi' => 'Chi tiết sự kiện', 'en' => 'Event Details'],
            'course.event_start_date' => ['vi' => 'Ngày bắt đầu', 'en' => 'Start Date'],
            'course.event_end_date' => ['vi' => 'Ngày kết thúc', 'en' => 'End Date'],
            'course.event_location' => ['vi' => 'Địa điểm', 'en' => 'Location'],
            'course.all_day' => ['vi' => 'Cả ngày', 'en' => 'All Day'],
            'course.tag_people' => ['vi' => 'Tag người tham gia', 'en' => 'Tag People'],
            'course.tag_whole_class' => ['vi' => 'Tag cả lớp', 'en' => 'Tag Whole Class'],
            'course.attendees' => ['vi' => 'Người tham gia', 'en' => 'Attendees'],
            'course.no_events' => ['vi' => 'Chưa có sự kiện nào', 'en' => 'No events yet'],
            'course.event_created' => ['vi' => 'Đã tạo sự kiện', 'en' => 'Event Created'],
            'course.author' => ['vi' => 'Tác giả', 'en' => 'Author'],
            'course.replying_to' => ['vi' => 'Trả lời', 'en' => 'Replying to'],
            'course.comments' => ['vi' => 'bình luận', 'en' => 'comments'],
            'course.view_all_comments' => ['vi' => 'Xem tất cả {count} bình luận', 'en' => 'View all {count} comments'],
            'course.write_comment' => ['vi' => 'Viết bình luận...', 'en' => 'Write a comment...'],
            'course.reply_to' => ['vi' => 'Trả lời {name}', 'en' => 'Reply to {name}'],
            'course.hide_comments' => ['vi' => 'Ẩn bình luận', 'en' => 'Hide comments'],
            'course.confirm_delete_post' => ['vi' => 'Bạn có chắc chắn muốn xóa bài đăng này?', 'en' => 'Are you sure you want to delete this post?'],
            'course.confirm_delete_comment' => ['vi' => 'Xác nhận xóa bình luận', 'en' => 'Confirm delete comment'],
            
            // Common
            'course.loading' => ['vi' => 'Đang tải...', 'en' => 'Loading...'],
            'course.error' => ['vi' => 'Đã có lỗi xảy ra', 'en' => 'An error occurred'],
            'course.success' => ['vi' => 'Thành công', 'en' => 'Success'],
            'course.no_data' => ['vi' => 'Không có dữ liệu', 'en' => 'No data'],
            
            // Common translations (if not exists in common group)
            'common.posting' => ['vi' => 'Đang đăng...', 'en' => 'Posting...'],
            'common.cancel' => ['vi' => 'Hủy', 'en' => 'Cancel'],
            'common.creating' => ['vi' => 'Đang tạo...', 'en' => 'Creating...'],
            'common.delete' => ['vi' => 'Xóa', 'en' => 'Delete'],
            'common.deleted_successfully' => ['vi' => 'Đã xóa thành công', 'en' => 'Deleted successfully'],
            'common.error_occurred' => ['vi' => 'Có lỗi xảy ra', 'en' => 'An error occurred'],
            'common.this_action_cannot_be_undone' => ['vi' => 'Hành động này không thể hoàn tác', 'en' => 'This action cannot be undone'],
        ];

        foreach ($translations as $key => $values) {
            $parts = explode('.', $key, 2);
            $group = $parts[0];
            $keyName = $parts[1];

            // Vietnamese
            DB::table('translations')->updateOrInsert(
                ['language_id' => $viLang->id, 'group' => $group, 'key' => $keyName],
                ['value' => $values['vi'], 'updated_at' => now(), 'created_at' => now()]
            );

            // English
            DB::table('translations')->updateOrInsert(
                ['language_id' => $enLang->id, 'group' => $group, 'key' => $keyName],
                ['value' => $values['en'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->command->info('✅ Course translations seeded successfully!');
    }
}
