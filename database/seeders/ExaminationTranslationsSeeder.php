<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class ExaminationTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = Language::all();

        if ($languages->isEmpty()) {
            $this->command->error('No languages found. Please run LanguageSeeder first.');
            return;
        }

        $vi = $languages->where('code', 'vi')->first();
        $en = $languages->where('code', 'en')->first();

        if (!$vi || !$en) {
            $this->command->error('Vietnamese or English language not found.');
            return;
        }

        $translations = [
            // Common Examination
            'examination.common.create' => ['vi' => 'Tạo mới', 'en' => 'Create'],
            'examination.common.edit' => ['vi' => 'Sửa', 'en' => 'Edit'],
            'examination.common.delete' => ['vi' => 'Xóa', 'en' => 'Delete'],
            'examination.common.duplicate' => ['vi' => 'Nhân bản', 'en' => 'Duplicate'],
            'examination.common.preview' => ['vi' => 'Xem trước', 'en' => 'Preview'],
            'examination.common.save' => ['vi' => 'Lưu', 'en' => 'Save'],
            'examination.common.saving' => ['vi' => 'Đang lưu...', 'en' => 'Saving...'],
            'examination.common.cancel' => ['vi' => 'Hủy', 'en' => 'Cancel'],
            'examination.common.close' => ['vi' => 'Đóng', 'en' => 'Close'],
            'examination.common.search' => ['vi' => 'Tìm kiếm', 'en' => 'Search'],
            'examination.common.filter' => ['vi' => 'Lọc', 'en' => 'Filter'],
            'examination.common.all' => ['vi' => 'Tất cả', 'en' => 'All'],
            'examination.common.select' => ['vi' => 'Chọn', 'en' => 'Select'],
            'examination.common.selected' => ['vi' => 'Đã chọn', 'en' => 'Selected'],
            'examination.common.update' => ['vi' => 'Cập nhật', 'en' => 'Update'],
            'examination.common.actions' => ['vi' => 'Hành động', 'en' => 'Actions'],
            'examination.common.title' => ['vi' => 'Tiêu đề', 'en' => 'Title'],
            'examination.common.description' => ['vi' => 'Mô tả', 'en' => 'Description'],
            'examination.common.status' => ['vi' => 'Trạng thái', 'en' => 'Status'],
            'examination.common.subject' => ['vi' => 'Môn học', 'en' => 'Subject'],
            'examination.common.category' => ['vi' => 'Phân loại', 'en' => 'Category'],
            'examination.common.no_select' => ['vi' => 'Không chọn', 'en' => 'Not selected'],
            'examination.common.select_subject_first' => ['vi' => 'Chọn môn học trước', 'en' => 'Select subject first'],
            'examination.common.question' => ['vi' => 'Câu hỏi', 'en' => 'Question'],
            'examination.common.questions' => ['vi' => 'câu hỏi', 'en' => 'questions'],
            'examination.common.deselect_all' => ['vi' => 'Bỏ chọn tất cả', 'en' => 'Deselect All'],
            'examination.common.loading' => ['vi' => 'Đang tải...', 'en' => 'Loading...'],
            'examination.common.showing' => ['vi' => 'Hiển thị', 'en' => 'Showing'],
            'examination.common.previous' => ['vi' => 'Trước', 'en' => 'Previous'],
            'examination.common.next' => ['vi' => 'Tiếp', 'en' => 'Next'],
            'examination.common.clear_filters' => ['vi' => 'Xóa bộ lọc', 'en' => 'Clear Filters'],

            // Question Bank
            'examination.question_bank.title' => ['vi' => 'Ngân hàng câu hỏi', 'en' => 'Question Bank'],
            'examination.question_bank.description' => ['vi' => 'Quản lý và tổ chức câu hỏi cho các bài kiểm tra', 'en' => 'Manage and organize questions for tests'],
            'examination.question_bank.create' => ['vi' => 'Tạo câu hỏi', 'en' => 'Create Question'],
            'examination.question_bank.create_new' => ['vi' => 'Tạo câu hỏi mới', 'en' => 'Create New Question'],
            'examination.question_bank.edit_question' => ['vi' => 'Sửa câu hỏi', 'en' => 'Edit Question'],
            'examination.question_bank.search_placeholder' => ['vi' => 'Tìm kiếm câu hỏi...', 'en' => 'Search questions...'],
            'examination.question_bank.no_questions' => ['vi' => 'Không có câu hỏi nào', 'en' => 'No questions found'],
            'examination.question_bank.all_categories' => ['vi' => 'Tất cả danh mục', 'en' => 'All categories'],
            'examination.question_bank.all_types' => ['vi' => 'Tất cả loại', 'en' => 'All types'],
            'examination.question_bank.all_difficulties' => ['vi' => 'Tất cả độ khó', 'en' => 'All difficulties'],
            'examination.question_bank.all_tags' => ['vi' => 'Tất cả tag', 'en' => 'All tags'],
            'examination.question_bank.all_subjects' => ['vi' => 'Tất cả môn học', 'en' => 'All subjects'],
            'examination.question_bank.subject_settings' => ['vi' => 'Môn học', 'en' => 'Subjects'],
            'examination.question_bank.ai_generate' => ['vi' => 'AI Tạo câu hỏi', 'en' => 'AI Generate'],

            // Test Bank
            'examination.test_bank.title' => ['vi' => 'Ngân hàng bài test', 'en' => 'Test Bank'],
            'examination.test_bank.create' => ['vi' => 'Tạo bài test', 'en' => 'Create Test'],
            'examination.test_bank.search_placeholder' => ['vi' => 'Tìm kiếm bài test...', 'en' => 'Search tests...'],
            'examination.test_bank.no_tests' => ['vi' => 'Chưa có bài test nào', 'en' => 'No tests found'],
            'examination.test_bank.create_first' => ['vi' => 'Tạo bài test đầu tiên', 'en' => 'Create first test'],
            'examination.test_bank.all_status' => ['vi' => 'Tất cả trạng thái', 'en' => 'All status'],
            'examination.test_bank.all_types' => ['vi' => 'Tất cả loại', 'en' => 'All types'],
            'examination.test_bank.questions_count' => ['vi' => 'câu hỏi', 'en' => 'questions'],
            'examination.test_bank.minutes' => ['vi' => 'phút', 'en' => 'minutes'],
            'examination.test_bank.unlimited' => ['vi' => 'Không giới hạn', 'en' => 'Unlimited'],
            'examination.test_bank.assign' => ['vi' => 'Giao bài', 'en' => 'Assign'],
            'examination.test_bank.publish' => ['vi' => 'Xuất bản', 'en' => 'Publish'],
            'examination.test_bank.ai_prompt_setup' => ['vi' => 'Thiết lập Prompt AI', 'en' => 'AI Prompt Setup'],
            'examination.test_bank.type_custom' => ['vi' => 'Tự tạo', 'en' => 'Custom'],
            'examination.test_bank.type_ielts' => ['vi' => 'IELTS', 'en' => 'IELTS'],
            'examination.test_bank.type_cambridge' => ['vi' => 'Cambridge', 'en' => 'Cambridge'],
            'examination.test_bank.type_toeic' => ['vi' => 'TOEIC', 'en' => 'TOEIC'],
            'examination.test_bank.type_quiz' => ['vi' => 'Quiz', 'en' => 'Quiz'],
            'examination.test_bank.type_practice' => ['vi' => 'Luyện tập', 'en' => 'Practice'],
            'examination.test_bank.passing_score' => ['vi' => 'Đạt', 'en' => 'Pass'],
            'examination.test_bank.attempts' => ['vi' => 'lần', 'en' => 'attempts'],
            'examination.test_bank.preview_mode' => ['vi' => 'CHẾ ĐỘ XEM TRƯỚC', 'en' => 'PREVIEW MODE'],
            'examination.test_bank.close_preview' => ['vi' => 'Đóng xem trước', 'en' => 'Close Preview'],
            'examination.test_bank.loading_preview' => ['vi' => 'Đang tải dữ liệu xem trước...', 'en' => 'Loading preview data...'],
            'examination.test_bank.no_questions' => ['vi' => 'Chưa có câu hỏi nào', 'en' => 'No questions yet'],
            'examination.test_bank.no_audio' => ['vi' => 'Chưa có file audio cho phần này', 'en' => 'No audio file for this part'],
            'examination.test_bank.no_transcript' => ['vi' => 'Chưa có transcript cho phần này', 'en' => 'No transcript for this part'],
            'examination.test_bank.answer' => ['vi' => 'Đáp án:', 'en' => 'Answer:'],
            'examination.test_bank.task' => ['vi' => 'Đề bài', 'en' => 'Task'],
            'examination.test_bank.sample_essay' => ['vi' => 'Bài mẫu (Band 8-9)', 'en' => 'Sample Essay (Band 8-9)'],
            'examination.test_bank.grading_criteria' => ['vi' => 'Tiêu chí chấm điểm', 'en' => 'Grading Criteria'],
            'examination.test_bank.student_work' => ['vi' => 'Bài làm', 'en' => 'Student Work'],
            'examination.test_bank.work_area' => ['vi' => 'Khu vực làm bài - Học sinh sẽ viết bài tại đây', 'en' => 'Work area - Students will write here'],
            'examination.test_bank.time_limit' => ['vi' => 'phút', 'en' => 'minutes'],
            'examination.test_bank.min_words' => ['vi' => 'Tối thiểu {count} từ', 'en' => 'Minimum {count} words'],
            'examination.test_bank.no_prompt' => ['vi' => 'Chưa có đề bài', 'en' => 'No prompt yet'],
            'examination.test_bank.no_data' => ['vi' => 'Không có dữ liệu xem trước', 'en' => 'No preview data'],
            'examination.test_bank.preview_developing' => ['vi' => 'Xem trước chi tiết cho loại bài này đang được phát triển.', 'en' => 'Preview for this test type is under development.'],
            'examination.test_bank.questions' => ['vi' => 'Câu hỏi', 'en' => 'Questions'],
            'examination.test_bank.test_type' => ['vi' => 'Loại bài', 'en' => 'Test Type'],
            'examination.test_bank.ai_prompt_desc' => ['vi' => 'Tùy chỉnh prompt cho việc tạo đề thi IELTS bằng AI', 'en' => 'Customize prompts for AI-generated IELTS tests'],
            'examination.test_bank.enter_prompt_listening' => ['vi' => 'Nhập prompt cho AI tạo đề Listening...', 'en' => 'Enter prompt for AI to generate Listening tests...'],
            'examination.test_bank.prompt_help_listening' => ['vi' => 'Prompt này sẽ được sử dụng khi AI tạo đề thi IELTS Listening. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.', 'en' => 'This prompt will be used when AI generates IELTS Listening tests. Ensure the prompt includes JSON output format requirements.'],
            'examination.test_bank.enter_prompt_reading' => ['vi' => 'Nhập prompt cho AI tạo đề Reading...', 'en' => 'Enter prompt for AI to generate Reading tests...'],
            'examination.test_bank.prompt_help_reading' => ['vi' => 'Prompt này sẽ được sử dụng khi AI tạo đề thi IELTS Reading. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.', 'en' => 'This prompt will be used when AI generates IELTS Reading tests. Ensure the prompt includes JSON output format requirements.'],
            'examination.test_bank.enter_prompt_writing' => ['vi' => 'Nhập prompt cho AI tạo đề Writing...', 'en' => 'Enter prompt for AI to generate Writing tests...'],
            'examination.test_bank.prompt_help_writing' => ['vi' => 'Prompt này sẽ được sử dụng khi AI tạo đề thi IELTS Writing. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.', 'en' => 'This prompt will be used when AI generates IELTS Writing tests. Ensure the prompt includes JSON output format requirements.'],
            'examination.test_bank.enter_prompt_speaking' => ['vi' => 'Nhập prompt cho AI tạo đề Speaking...', 'en' => 'Enter prompt for AI to generate Speaking tests...'],
            'examination.test_bank.prompt_help_speaking' => ['vi' => 'Prompt này sẽ được sử dụng khi AI tạo đề thi IELTS Speaking. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.', 'en' => 'This prompt will be used when AI generates IELTS Speaking tests. Ensure the prompt includes JSON output format requirements.'],
            'examination.test_bank.restore_default' => ['vi' => 'Khôi phục mặc định', 'en' => 'Restore Default'],

            // Question Types
            'examination.question_types.label' => ['vi' => 'Loại câu hỏi', 'en' => 'Question Type'],
            'examination.question_types.select_type' => ['vi' => 'Chọn loại', 'en' => 'Select type'],
            'examination.question_types.multiple_choice' => ['vi' => 'Trắc nghiệm (1 đáp án)', 'en' => 'Multiple Choice'],
            'examination.question_types.multiple_response' => ['vi' => 'Trắc nghiệm (nhiều đáp án)', 'en' => 'Multiple Response'],
            'examination.question_types.true_false' => ['vi' => 'Đúng/Sai', 'en' => 'True/False'],
            'examination.question_types.true_false_ng' => ['vi' => 'True/False/Not Given', 'en' => 'True/False/Not Given'],
            'examination.question_types.fill_blanks' => ['vi' => 'Điền vào chỗ trống', 'en' => 'Fill in the Blanks'],
            'examination.question_types.fill_blanks_drag' => ['vi' => 'Kéo thả điền chỗ trống', 'en' => 'Drag & Drop Fill Blanks'],
            'examination.question_types.matching' => ['vi' => 'Nối cột', 'en' => 'Matching'],
            'examination.question_types.matching_headings' => ['vi' => 'Matching Headings', 'en' => 'Matching Headings'],
            'examination.question_types.matching_features' => ['vi' => 'Matching Features', 'en' => 'Matching Features'],
            'examination.question_types.matching_sentence_endings' => ['vi' => 'Matching Sentence Endings', 'en' => 'Matching Sentence Endings'],
            'examination.question_types.short_answer' => ['vi' => 'Trả lời ngắn', 'en' => 'Short Answer'],
            'examination.question_types.sentence_completion' => ['vi' => 'Sentence Completion', 'en' => 'Sentence Completion'],
            'examination.question_types.summary_completion' => ['vi' => 'Summary Completion', 'en' => 'Summary Completion'],
            'examination.question_types.note_completion' => ['vi' => 'Note Completion', 'en' => 'Note Completion'],
            'examination.question_types.table_completion' => ['vi' => 'Table Completion', 'en' => 'Table Completion'],
            'examination.question_types.ordering' => ['vi' => 'Sắp xếp thứ tự', 'en' => 'Ordering'],
            'examination.question_types.essay' => ['vi' => 'Viết luận', 'en' => 'Essay'],
            'examination.question_types.audio_response' => ['vi' => 'Trả lời bằng audio', 'en' => 'Audio Response'],

            // Difficulty Levels
            'examination.difficulty.label' => ['vi' => 'Độ khó', 'en' => 'Difficulty'],
            'examination.difficulty.easy' => ['vi' => 'Dễ', 'en' => 'Easy'],
            'examination.difficulty.medium' => ['vi' => 'Trung bình', 'en' => 'Medium'],
            'examination.difficulty.hard' => ['vi' => 'Khó', 'en' => 'Hard'],
            'examination.difficulty.expert' => ['vi' => 'Chuyên gia', 'en' => 'Expert'],

            // Status Labels
            'examination.status.draft' => ['vi' => 'Nháp', 'en' => 'Draft'],
            'examination.status.active' => ['vi' => 'Hoạt động', 'en' => 'Active'],
            'examination.status.published' => ['vi' => 'Đã xuất bản', 'en' => 'Published'],
            'examination.status.archived' => ['vi' => 'Đã lưu trữ', 'en' => 'Archived'],
            'examination.status.pending' => ['vi' => 'Chờ xử lý', 'en' => 'Pending'],
            'examination.status.completed' => ['vi' => 'Đã hoàn thành', 'en' => 'Completed'],
            'examination.status.in_progress' => ['vi' => 'Đang thực hiện', 'en' => 'In Progress'],

            // AI Generator
            'examination.ai.title' => ['vi' => 'Tạo câu hỏi bằng AI', 'en' => 'AI Question Generator'],
            'examination.ai.description' => ['vi' => 'Sử dụng AI để tạo câu hỏi tự động từ chủ đề của bạn', 'en' => 'Use AI to automatically generate questions from your topic'],
            'examination.ai.topic' => ['vi' => 'Chủ đề / Mô tả', 'en' => 'Topic / Description'],
            'examination.ai.topic_placeholder' => ['vi' => 'Mô tả chủ đề hoặc nội dung cần tạo câu hỏi', 'en' => 'Describe the topic or content for question generation'],
            'examination.ai.question_type' => ['vi' => 'Loại câu hỏi', 'en' => 'Question Type'],
            'examination.ai.difficulty' => ['vi' => 'Độ khó', 'en' => 'Difficulty'],
            'examination.ai.count' => ['vi' => 'Số lượng câu hỏi', 'en' => 'Number of Questions'],
            'examination.ai.max_questions' => ['vi' => 'Tối đa 20 câu', 'en' => 'Maximum 20 questions'],
            'examination.ai.generate' => ['vi' => 'Tạo câu hỏi', 'en' => 'Generate Questions'],
            'examination.ai.generating' => ['vi' => 'Đang tạo...', 'en' => 'Generating...'],
            'examination.ai.generating_wait' => ['vi' => 'Vui lòng đợi, AI đang xử lý...', 'en' => 'Please wait, AI is processing...'],
            'examination.ai.generated_title' => ['vi' => 'Câu hỏi đã tạo', 'en' => 'Generated Questions'],
            'examination.ai.select_all' => ['vi' => 'Chọn tất cả', 'en' => 'Select All'],
            'examination.ai.save_selected' => ['vi' => 'Lưu {count} câu hỏi', 'en' => 'Save {count} questions'],
            'examination.ai.tags' => ['vi' => 'Tags', 'en' => 'Tags'],
            'examination.ai.create_tag' => ['vi' => 'Tạo tag mới', 'en' => 'Create new tag'],
            'examination.ai.no_tags' => ['vi' => 'Chưa có tag nào', 'en' => 'No tags yet'],
            'examination.ai.click_to_add' => ['vi' => 'Click để thêm tag:', 'en' => 'Click to add tag:'],
            'examination.ai.no_tags_create' => ['vi' => 'Không có tag nào. Hãy tạo tag mới!', 'en' => 'No tags available. Create a new one!'],
            'examination.ai.manage_tags' => ['vi' => 'Quản lý Tags', 'en' => 'Manage Tags'],
            'examination.ai.my_tags' => ['vi' => 'Tags của tôi', 'en' => 'My Tags'],
            'examination.ai.no_created_tags' => ['vi' => 'Bạn chưa tạo tag nào.', 'en' => 'You haven\'t created any tags.'],
            'examination.ai.tag_name' => ['vi' => 'Tên tag', 'en' => 'Tag Name'],
            'examination.ai.tag_placeholder' => ['vi' => 'Nhập tên tag...', 'en' => 'Enter tag name...'],
            'examination.ai.creating_tag' => ['vi' => 'Đang tạo...', 'en' => 'Creating...'],

            // Assignment
            'examination.assignment.title' => ['vi' => 'Quản lý giao bài', 'en' => 'Assignment Management'],
            'examination.assignment.new' => ['vi' => 'Giao bài mới', 'en' => 'New Assignment'],
            'examination.assignment.edit' => ['vi' => 'Chỉnh sửa bài giao', 'en' => 'Edit Assignment'],
            'examination.assignment.students' => ['vi' => 'Học viên', 'en' => 'Students'],
            'examination.assignment.classes' => ['vi' => 'Lớp học', 'en' => 'Classes'],
            'examination.assignment.branch' => ['vi' => 'Chi nhánh', 'en' => 'Branch'],
            'examination.assignment.deadline' => ['vi' => 'Hạn nộp', 'en' => 'Deadline'],
            'examination.assignment.start_from' => ['vi' => 'Bắt đầu từ', 'en' => 'Start From'],
            'examination.assignment.max_attempts' => ['vi' => 'Số lần làm tối đa', 'en' => 'Maximum Attempts'],
            'examination.assignment.time_limit' => ['vi' => 'Thời gian làm (phút)', 'en' => 'Time Limit (minutes)'],
            'examination.assignment.empty_follow_test' => ['vi' => 'Để trống = theo bài test', 'en' => 'Empty = follow test settings'],
            'examination.assignment.select_test' => ['vi' => 'Chọn bài test', 'en' => 'Select Test'],
            'examination.assignment.search_students' => ['vi' => 'Tìm học viên...', 'en' => 'Search students...'],
            'examination.assignment.select_class' => ['vi' => 'Chọn lớp', 'en' => 'Select Class'],
            'examination.assignment.select_branch' => ['vi' => 'Chọn chi nhánh', 'en' => 'Select Branch'],
            'examination.assignment.shuffle_questions' => ['vi' => 'Xáo trộn câu hỏi', 'en' => 'Shuffle Questions'],
            'examination.assignment.show_results' => ['vi' => 'Cho phép xem kết quả sau khi nộp', 'en' => 'Allow viewing results after submission'],
            'examination.assignment.show_answers' => ['vi' => 'Cho phép xem đáp án đúng', 'en' => 'Allow viewing correct answers'],
            'examination.assignment.instructions' => ['vi' => 'Hướng dẫn hoặc ghi chú cho học viên', 'en' => 'Instructions or notes for students'],
            'examination.assignment.ongoing' => ['vi' => 'Đang diễn ra', 'en' => 'Ongoing'],
            'examination.assignment.upcoming' => ['vi' => 'Sắp tới', 'en' => 'Upcoming'],
            'examination.assignment.ended' => ['vi' => 'Đã kết thúc', 'en' => 'Ended'],
            'examination.assignment.test' => ['vi' => 'Bài test', 'en' => 'Test'],
            'examination.assignment.target' => ['vi' => 'Đối tượng', 'en' => 'Target'],
            'examination.assignment.time' => ['vi' => 'Thời gian', 'en' => 'Time'],
            'examination.assignment.progress' => ['vi' => 'Tiến độ', 'en' => 'Progress'],

            // Grading
            'examination.grading.title' => ['vi' => 'Chấm bài', 'en' => 'Grading'],
            'examination.grading.list_title' => ['vi' => 'Danh sách bài làm cần chấm điểm', 'en' => 'List of submissions to grade'],
            'examination.grading.waiting' => ['vi' => 'Chờ chấm', 'en' => 'Waiting'],
            'examination.grading.in_progress' => ['vi' => 'Đang chấm', 'en' => 'Grading'],
            'examination.grading.completed' => ['vi' => 'Đã chấm', 'en' => 'Graded'],
            'examination.grading.completed_today' => ['vi' => 'Đã chấm hôm nay', 'en' => 'Graded today'],
            'examination.grading.total_to_process' => ['vi' => 'Tổng cần xử lý', 'en' => 'Total to process'],
            'examination.grading.search_students' => ['vi' => 'Tìm học sinh...', 'en' => 'Search students...'],

            // Index/Home
            'examination.index.title' => ['vi' => 'Quản lý bài kiểm tra và đánh giá năng lực', 'en' => 'Test and Assessment Management'],
            'examination.index.my_assignments' => ['vi' => 'Bài tập của tôi', 'en' => 'My Assignments'],
            'examination.index.my_assignments_desc' => ['vi' => 'Xem và làm bài được giao', 'en' => 'View and complete assigned tests'],
            'examination.index.ielts_practice' => ['vi' => 'Luyện tập IELTS thực tế', 'en' => 'IELTS Practice'],
            'examination.index.management' => ['vi' => 'Quản lý', 'en' => 'Management'],
            'examination.index.test_bank' => ['vi' => 'Ngân hàng đề thi', 'en' => 'Test Bank'],
            'examination.index.test_bank_desc' => ['vi' => 'Tạo và quản lý bài test', 'en' => 'Create and manage tests'],
            'examination.index.question_bank' => ['vi' => 'Ngân hàng câu hỏi', 'en' => 'Question Bank'],
            'examination.index.question_bank_desc' => ['vi' => 'Tạo và quản lý câu hỏi', 'en' => 'Create and manage questions'],
            'examination.index.assignments' => ['vi' => 'Quản lý giao bài', 'en' => 'Assignments'],
            'examination.index.assignments_desc' => ['vi' => 'Giao bài test cho học viên', 'en' => 'Assign tests to students'],
            'examination.index.grading' => ['vi' => 'Chấm bài', 'en' => 'Grading'],
            'examination.index.grading_desc' => ['vi' => 'Chấm và đánh giá bài làm', 'en' => 'Grade and evaluate submissions'],
            'examination.index.statistics' => ['vi' => 'Thống kê nhanh', 'en' => 'Quick Statistics'],
            'examination.index.not_completed' => ['vi' => 'Bài chưa làm', 'en' => 'Not Completed'],
            'examination.index.completed' => ['vi' => 'Đã hoàn thành', 'en' => 'Completed'],
            'examination.index.settings' => ['vi' => 'Cài đặt', 'en' => 'Settings'],
            'examination.index.settings_desc' => ['vi' => 'Thiết lập module Examination', 'en' => 'Configure Examination module'],

            // Messages
            'examination.messages.confirm_delete' => ['vi' => 'Bạn có chắc chắn muốn xóa?', 'en' => 'Are you sure you want to delete?'],
            'examination.messages.delete_question_confirm' => ['vi' => 'Bạn có chắc chắn muốn xóa câu hỏi này?', 'en' => 'Are you sure you want to delete this question?'],
            'examination.messages.delete_test_confirm' => ['vi' => 'Bạn có chắc muốn xóa bài test này?', 'en' => 'Are you sure you want to delete this test?'],
            'examination.messages.delete_tag_confirm' => ['vi' => 'Bạn có chắc chắn muốn xóa tag này?', 'en' => 'Are you sure you want to delete this tag?'],
            'examination.messages.confirm_duplicate' => ['vi' => 'Bạn có muốn nhân bản câu hỏi này?', 'en' => 'Do you want to duplicate this question?'],
            'examination.messages.duplicate_test_confirm' => ['vi' => 'Bạn có chắc muốn nhân bản bài test này?', 'en' => 'Are you sure you want to duplicate this test?'],
            'examination.messages.cannot_create_tag' => ['vi' => 'Không thể tạo tag', 'en' => 'Cannot create tag'],
            'examination.messages.cannot_delete_tag' => ['vi' => 'Không thể xóa tag', 'en' => 'Cannot delete tag'],
            'examination.messages.error_generating' => ['vi' => 'Có lỗi khi tạo câu hỏi', 'en' => 'Error generating questions'],
            'examination.messages.error_ai_connection' => ['vi' => 'Không thể kết nối đến dịch vụ AI', 'en' => 'Cannot connect to AI service'],
            'examination.messages.prompt_saved' => ['vi' => 'Prompt đã được lưu!', 'en' => 'Prompt saved successfully!'],
            'examination.messages.error_saving_prompt' => ['vi' => 'Có lỗi xảy ra khi lưu prompt!', 'en' => 'Error saving prompt!'],
            'examination.messages.restore_default_prompt' => ['vi' => 'Khôi phục prompt mặc định?', 'en' => 'Restore default prompt?'],
            'examination.messages.prompt_replaced' => ['vi' => 'Prompt hiện tại sẽ bị thay thế bằng prompt mặc định.', 'en' => 'Current prompt will be replaced with default.'],
            'examination.messages.restore' => ['vi' => 'Khôi phục', 'en' => 'Restore'],
            'examination.messages.restored' => ['vi' => 'Đã khôi phục', 'en' => 'Restored'],
            'examination.messages.default_restored' => ['vi' => 'Prompt mặc định đã được khôi phục. Nhớ bấm Lưu để lưu thay đổi.', 'en' => 'Default prompt has been restored. Remember to click Save.'],
        ];

        foreach ($translations as $key => $values) {
            // Split only on FIRST dot: group = "examination", key = "index.title"
            $firstDotPos = strpos($key, '.');
            $group = substr($key, 0, $firstDotPos);
            $translationKey = substr($key, $firstDotPos + 1);

            // Vietnamese
            Translation::updateOrCreate(
                [
                    'language_id' => $vi->id,
                    'group' => $group,
                    'key' => $translationKey,
                ],
                ['value' => $values['vi']]
            );

            // English
            Translation::updateOrCreate(
                [
                    'language_id' => $en->id,
                    'group' => $group,
                    'key' => $translationKey,
                ],
                ['value' => $values['en']]
            );
        }

        $this->command->info('Examination translations seeded successfully!');
        $this->command->info('Total: ' . count($translations) . ' translation keys created.');
    }
}
