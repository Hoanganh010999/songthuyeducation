<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Work tabs
            'tabs_dashboard' => ['en' => 'Dashboard', 'vi' => 'Tổng quan'],
            'tabs_items' => ['en' => 'Work Items', 'vi' => 'Công việc'],

            // Dashboard
            'dashboard_total_items' => ['en' => 'Total Work Items', 'vi' => 'Tổng số công việc'],
            'dashboard_in_progress' => ['en' => 'In Progress', 'vi' => 'Đang thực hiện'],
            'dashboard_completed' => ['en' => 'Completed', 'vi' => 'Hoàn thành'],
            'dashboard_overdue' => ['en' => 'Overdue', 'vi' => 'Quá hạn'],
            'dashboard_from_last_month' => ['en' => 'from last month', 'vi' => 'so với tháng trước'],
            'dashboard_by_status' => ['en' => 'By Status', 'vi' => 'Theo trạng thái'],
            'dashboard_by_priority' => ['en' => 'By Priority', 'vi' => 'Theo độ ưu tiên'],
            'dashboard_recent_items' => ['en' => 'Recent Work Items', 'vi' => 'Công việc gần đây'],
            'dashboard_view_all' => ['en' => 'View All', 'vi' => 'Xem tất cả'],
            'dashboard_no_items' => ['en' => 'No work items yet', 'vi' => 'Chưa có công việc nào'],

            // Work Items List
            'items_title' => ['en' => 'Work Items', 'vi' => 'Danh sách công việc'],
            'items_description' => ['en' => 'Manage and track all work items', 'vi' => 'Quản lý và theo dõi tất cả công việc'],
            'items_create' => ['en' => 'Create Work Item', 'vi' => 'Tạo công việc mới'],
            'items_search' => ['en' => 'Search work items...', 'vi' => 'Tìm kiếm công việc...'],
            'items_all_types' => ['en' => 'All Types', 'vi' => 'Tất cả loại'],
            'items_all_statuses' => ['en' => 'All Statuses', 'vi' => 'Tất cả trạng thái'],
            'items_all_priorities' => ['en' => 'All Priorities', 'vi' => 'Tất cả mức độ'],
            'items_overdue' => ['en' => 'Overdue', 'vi' => 'Quá hạn'],
            'items_no_items' => ['en' => 'No work items found', 'vi' => 'Không tìm thấy công việc nào'],
            'items_create_first' => ['en' => 'Create your first work item', 'vi' => 'Tạo công việc đầu tiên'],
            'items_code' => ['en' => 'Code', 'vi' => 'Mã'],
            'items_title' => ['en' => 'Title', 'vi' => 'Tiêu đề'],
            'items_type' => ['en' => 'Type', 'vi' => 'Loại'],
            'items_status' => ['en' => 'Status', 'vi' => 'Trạng thái'],
            'items_priority' => ['en' => 'Priority', 'vi' => 'Ưu tiên'],
            'items_due_date' => ['en' => 'Due Date', 'vi' => 'Hạn hoàn thành'],
            'items_assignees' => ['en' => 'Assignees', 'vi' => 'Người thực hiện'],

            // Work Item Types
            'type_project' => ['en' => 'Project', 'vi' => 'Dự án'],
            'type_task' => ['en' => 'Task', 'vi' => 'Nhiệm vụ'],

            // Work Item Statuses
            'status_pending' => ['en' => 'Pending', 'vi' => 'Chờ xử lý'],
            'status_assigned' => ['en' => 'Assigned', 'vi' => 'Đã gán'],
            'status_in_progress' => ['en' => 'In Progress', 'vi' => 'Đang thực hiện'],
            'status_submitted' => ['en' => 'Submitted', 'vi' => 'Đã nộp'],
            'status_revision_requested' => ['en' => 'Revision Requested', 'vi' => 'Yêu cầu chỉnh sửa'],
            'status_completed' => ['en' => 'Completed', 'vi' => 'Hoàn thành'],
            'status_cancelled' => ['en' => 'Cancelled', 'vi' => 'Đã hủy'],

            // Work Item Priorities
            'priority_low' => ['en' => 'Low', 'vi' => 'Thấp'],
            'priority_medium' => ['en' => 'Medium', 'vi' => 'Trung bình'],
            'priority_high' => ['en' => 'High', 'vi' => 'Cao'],
            'priority_urgent' => ['en' => 'Urgent', 'vi' => 'Khẩn cấp'],

            // General
            'title' => ['en' => 'Title', 'vi' => 'Tiêu đề'],
            'type' => ['en' => 'Type', 'vi' => 'Loại'],
            'priority' => ['en' => 'Priority', 'vi' => 'Ưu tiên'],
            'due_date' => ['en' => 'Due Date', 'vi' => 'Hạn hoàn thành'],

            // Work Item Detail
            'description' => ['en' => 'Description', 'vi' => 'Mô tả'],
            'no_description' => ['en' => 'No description provided', 'vi' => 'Chưa có mô tả'],
            'attachments' => ['en' => 'Attachments', 'vi' => 'File đính kèm'],
            'submissions' => ['en' => 'Submissions', 'vi' => 'Bài nộp'],
            'no_submissions' => ['en' => 'No submissions yet', 'vi' => 'Chưa có bài nộp nào'],
            'discussions' => ['en' => 'Discussions', 'vi' => 'Thảo luận'],
            'no_discussions' => ['en' => 'No discussions yet', 'vi' => 'Chưa có thảo luận nào'],
            'add_comment' => ['en' => 'Add a comment...', 'vi' => 'Thêm bình luận...'],
            'post_comment' => ['en' => 'Post Comment', 'vi' => 'Đăng bình luận'],
            'details' => ['en' => 'Details', 'vi' => 'Chi tiết'],
            'creator' => ['en' => 'Creator', 'vi' => 'Người tạo'],
            'created_at' => ['en' => 'Created At', 'vi' => 'Ngày tạo'],
            'estimated_hours' => ['en' => 'Estimated Hours', 'vi' => 'Thời gian ước tính'],
            'actual_hours' => ['en' => 'Actual Hours', 'vi' => 'Thời gian thực tế'],
            'complexity' => ['en' => 'Complexity', 'vi' => 'Độ phức tạp'],
            'assignments' => ['en' => 'Assignments', 'vi' => 'Phân công'],
            'no_assignments' => ['en' => 'No assignments yet', 'vi' => 'Chưa phân công'],
            'child_items' => ['en' => 'Child Items', 'vi' => 'Công việc con'],
            'submit' => ['en' => 'Submit', 'vi' => 'Nộp bài'],
            'reviewer_comments' => ['en' => 'Reviewer Comments', 'vi' => 'Nhận xét của người duyệt'],

            // Submission Status
            'submission_status_pending' => ['en' => 'Pending Review', 'vi' => 'Chờ duyệt'],
            'submission_status_approved' => ['en' => 'Approved', 'vi' => 'Đã duyệt'],
            'submission_status_rejected' => ['en' => 'Rejected', 'vi' => 'Từ chối'],

            // Assignment Roles
            'role_executor' => ['en' => 'Executor', 'vi' => 'Thực hiện'],
            'role_reviewer' => ['en' => 'Reviewer', 'vi' => 'Duyệt'],
            'role_observer' => ['en' => 'Observer', 'vi' => 'Theo dõi'],

            // Work Item Form
            'create_item' => ['en' => 'Create Work Item', 'vi' => 'Tạo công việc mới'],
            'edit_item' => ['en' => 'Edit Work Item', 'vi' => 'Chỉnh sửa công việc'],
            'create_item_description' => ['en' => 'Fill in the details to create a new work item', 'vi' => 'Điền thông tin để tạo công việc mới'],
            'edit_item_description' => ['en' => 'Update the work item details', 'vi' => 'Cập nhật thông tin công việc'],
            'basic_info' => ['en' => 'Basic Information', 'vi' => 'Thông tin cơ bản'],
            'title_placeholder' => ['en' => 'Enter work item title', 'vi' => 'Nhập tiêu đề công việc'],
            'parent_item' => ['en' => 'Parent Item', 'vi' => 'Công việc cha'],
            'no_parent' => ['en' => 'No parent item', 'vi' => 'Không có công việc cha'],
            'estimated_hours_placeholder' => ['en' => 'e.g., 8', 'vi' => 'Ví dụ: 8'],
            'description_placeholder' => ['en' => 'Enter detailed description...', 'vi' => 'Nhập mô tả chi tiết...'],
            'add_assignment' => ['en' => 'Add Assignment', 'vi' => 'Thêm phân công'],
            'select_user' => ['en' => 'Select user', 'vi' => 'Chọn người dùng'],
            'no_assignments_yet' => ['en' => 'No assignments yet. Click the button above to add.', 'vi' => 'Chưa có phân công. Click nút phía trên để thêm.'],
        ];

        // Get all languages
        $languages = DB::table('languages')->get();

        foreach ($translations as $key => $values) {
            foreach ($languages as $language) {
                $translationValue = $values[$language->code] ?? $values['en'];

                // Check if translation already exists
                $exists = DB::table('translations')
                    ->where('group', 'work')
                    ->where('key', $key)
                    ->where('language_id', $language->id)
                    ->exists();

                if (!$exists) {
                    DB::table('translations')->insert([
                        'group' => 'work',
                        'key' => $key,
                        'value' => $translationValue,
                        'language_id' => $language->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Work module translations seeded successfully!');
    }
}
