<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

/**
 * Complete All Missing Translations
 * Bổ sung tất cả translations còn thiếu trong hệ thống
 */
class CompleteAllTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all();

        $translations = [
            // Common translations (bổ sung thêm)
            'common' => [
                'en' => [
                    'dashboard' => 'Dashboard',
                    'status' => 'Status',
                    'actions' => 'Actions',
                    'showing' => 'Showing',
                    'of' => 'of',
                    'view' => 'View',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                    'cancel' => 'Cancel',
                    'ok' => 'OK',
                    'success' => 'Success',
                    'error' => 'Error',
                    'warning' => 'Warning',
                    'confirm_delete' => 'Confirm Delete',
                    'error_occurred' => 'An error occurred',
                    'inactive' => 'Inactive',
                    'active' => 'Active',
                    'no_data' => 'No data available',
                    'all_status' => 'All Status',
                    'note' => 'Note',
                    'saving' => 'Saving...',
                    'save' => 'Save',
                    'search' => 'Search',
                    'processing' => 'Processing...',
                    'unknown' => 'Unknown',
                    'years_old' => 'years old',
                    'add' => 'Add',
                    'adding' => 'Adding...',
                ],
                'vi' => [
                    'dashboard' => 'Trang chủ',
                    'status' => 'Trạng thái',
                    'actions' => 'Thao tác',
                    'showing' => 'Hiển thị',
                    'of' => 'của',
                    'view' => 'Xem',
                    'edit' => 'Sửa',
                    'delete' => 'Xóa',
                    'cancel' => 'Hủy',
                    'ok' => 'Đồng ý',
                    'success' => 'Thành công',
                    'error' => 'Lỗi',
                    'warning' => 'Cảnh báo',
                    'confirm_delete' => 'Xác nhận xóa',
                    'error_occurred' => 'Đã xảy ra lỗi',
                    'inactive' => 'Không hoạt động',
                    'active' => 'Hoạt động',
                    'no_data' => 'Không có dữ liệu',
                    'all_status' => 'Tất cả trạng thái',
                    'note' => 'Lưu ý',
                    'saving' => 'Đang lưu...',
                    'save' => 'Lưu',
                    'search' => 'Tìm kiếm',
                    'processing' => 'Đang xử lý...',
                    'unknown' => 'Không rõ',
                    'years_old' => 'tuổi',
                    'add' => 'Thêm',
                    'adding' => 'Đang thêm...',
                ],
            ],
            
            // Classes translations
            'classes' => [
                'en' => [
                    'syllabus_will_apply' => 'Syllabus will be applied',
                    'sessions_from_syllabus' => 'Sessions from syllabus',
                ],
                'vi' => [
                    'syllabus_will_apply' => 'Sẽ áp dụng giáo trình',
                    'sessions_from_syllabus' => 'Số buổi từ giáo trình',
                ],
            ],
            
            // Quality Management translations
            'quality' => [
                'en' => [
                    'classes' => 'Classes',
                    'no_class' => 'No class',
                    'add_to_class' => 'Add to Class',
                    'no_classes_available' => 'No Classes Available',
                    'create_class_first' => 'Please create a class first before adding students',
                    'add_student_to_class' => 'Add Student to Class',
                    'student' => 'Student',
                    'student_code' => 'Student Code',
                    'select_class' => 'Select a class',
                    'please_select_class' => 'Please select a class',
                    'student_added_to_class' => 'Student added to class successfully',
                    'student_info' => 'Student Information',
                    'current_classes' => 'Current Classes',
                    'no_active_classes' => 'No active classes',
                    'search_by_name_or_code' => 'Search by name or student code',
                ],
                'vi' => [
                    'classes' => 'Lớp học',
                    'no_class' => 'Chưa có lớp',
                    'add_to_class' => 'Thêm vào lớp',
                    'no_classes_available' => 'Không có lớp nào',
                    'create_class_first' => 'Vui lòng tạo lớp học trước khi thêm học viên',
                    'add_student_to_class' => 'Thêm học viên vào lớp',
                    'student' => 'Học viên',
                    'student_code' => 'Mã học viên',
                    'select_class' => 'Chọn lớp học',
                    'please_select_class' => 'Vui lòng chọn lớp học',
                    'student_added_to_class' => 'Đã thêm học viên vào lớp thành công',
                    'student_info' => 'Thông tin học viên',
                    'current_classes' => 'Lớp đang học',
                    'no_active_classes' => 'Chưa có lớp đang học',
                    'search_by_name_or_code' => 'Tìm kiếm theo tên hoặc mã học viên',
                ],
            ],
            
            // Class Detail translations
        'class_detail' => [
            'en' => [
                'time' => 'Time',
                'no_schedule' => 'No schedule',
                'click_to_edit' => 'Click to edit',
                'no_students' => 'No students',
                'session' => 'Session',
                'date' => 'Date',
                'lesson_plan' => 'Lesson Plan',
                'materials' => 'Materials',
                'no_sessions' => 'No sessions',
                'comment_placeholder' => 'Enter comment...',
            ],
            'vi' => [
                'time' => 'Thời gian',
                'no_schedule' => 'Chưa có lịch học',
                'click_to_edit' => 'Click để chỉnh sửa',
                'no_students' => 'Chưa có học viên',
                'session' => 'Buổi học',
                'date' => 'Ngày',
                'lesson_plan' => 'Giáo án',
                'materials' => 'Tài liệu',
                'no_sessions' => 'Chưa có buổi học',
                'comment_placeholder' => 'Nhập nhận xét...',
            ],
        ],

            // Users translations (bổ sung)
            'users' => [
                'en' => [
                    'username' => 'Username',
                    'email_readonly' => 'Email cannot be changed',
                    'password_hint' => 'Leave blank to keep current password',
                    'roles_hint' => 'Select roles for this user',
                    'name_placeholder' => 'Enter full name',
                    'email_placeholder' => 'Enter email address',
                    'password_placeholder' => 'Enter password',
                    'password_confirmation_placeholder' => 'Confirm password',
                ],
                'vi' => [
                    'username' => 'Tên đăng nhập',
                    'email_readonly' => 'Email không thể thay đổi',
                    'password_hint' => 'Để trống để giữ mật khẩu hiện tại',
                    'roles_hint' => 'Chọn vai trò cho người dùng này',
                    'name_placeholder' => 'Nhập họ tên',
                    'email_placeholder' => 'Nhập địa chỉ email',
                    'password_placeholder' => 'Nhập mật khẩu',
                    'password_confirmation_placeholder' => 'Xác nhận mật khẩu',
                ],
            ],

            // Wallets
            'wallets' => [
                'en' => ['balance' => 'Balance'],
                'vi' => ['balance' => 'Số dư'],
            ],

            // Auth
            'auth' => [
                'en' => ['logout' => 'Logout'],
                'vi' => ['logout' => 'Đăng xuất'],
            ],

            // Calendar
            'calendar' => [
                'en' => [
                    'calendar' => 'Calendar',
                    'my_calendar' => 'My Calendar',
                    'events' => 'Events',
                    'add_event' => 'Add Event',
                    'feedback_modal_title_test' => 'Placement Test Feedback',
                    'feedback_modal_title_trial' => 'Trial Class Feedback',
                    'trial_class_teacher' => 'Teacher',
                    'test_score' => 'Test Score',
                    'test_level' => 'Level',
                    'trial_rating' => 'Rating',
                    'feedback_content' => 'Feedback',
                    'feedback_placeholder' => 'Enter your feedback...',
                    'submit_feedback' => 'Submit Feedback',
                    'feedback_required' => 'Please enter feedback',
                    'feedback_success' => 'Feedback submitted successfully',
                    'feedback_error' => 'Failed to submit feedback',
                    'teacher_assigned_success' => 'Teacher assigned successfully',
                    'teacher_assigned_error' => 'Failed to assign teacher',
                    'assign_teacher_modal_title' => 'Assign Teacher',
                    'select_teacher' => 'Select Teacher',
                    'assigned_teacher' => 'Assigned Teacher',
                    'assign_teacher' => 'Assign',
                    'teacher_required' => 'Please select a teacher',
                ],
                'vi' => [
                    'calendar' => 'Lịch',
                    'my_calendar' => 'Lịch của tôi',
                    'events' => 'Sự kiện',
                    'add_event' => 'Thêm sự kiện',
                    'feedback_modal_title_test' => 'Phản hồi bài kiểm tra đầu vào',
                    'feedback_modal_title_trial' => 'Phản hồi buổi học thử',
                    'trial_class_teacher' => 'Giáo viên',
                    'test_score' => 'Điểm kiểm tra',
                    'test_level' => 'Trình độ',
                    'trial_rating' => 'Đánh giá',
                    'feedback_content' => 'Nội dung phản hồi',
                    'feedback_placeholder' => 'Nhập phản hồi của bạn...',
                    'submit_feedback' => 'Gửi phản hồi',
                    'feedback_required' => 'Vui lòng nhập phản hồi',
                    'feedback_success' => 'Gửi phản hồi thành công',
                    'feedback_error' => 'Gửi phản hồi thất bại',
                    'teacher_assigned_success' => 'Phân công giáo viên thành công',
                    'teacher_assigned_error' => 'Phân công giáo viên thất bại',
                    'assign_teacher_modal_title' => 'Phân công giáo viên',
                    'select_teacher' => 'Chọn giáo viên',
                    'assigned_teacher' => 'Giáo viên được phân công',
                    'assign_teacher' => 'Phân công',
                    'teacher_required' => 'Vui lòng chọn giáo viên',
                ],
            ],

            // Quality
            'quality' => [
                'en' => [
                    'students' => 'Students',
                    'students_description' => 'Student management',
                    'parents' => 'Parents',
                    'parents_description' => 'Parent management',
                    'description' => 'Quality management',
                    'industry' => 'Industry',
                    'industry_education' => 'Education',
                    'industry_healthcare' => 'Healthcare',
                    'industry_retail' => 'Retail',
                    'industry_manufacturing' => 'Manufacturing',
                    'coming_soon' => 'Coming Soon',
                    'industry_coming_soon' => 'Coming Soon',
                    'feature_in_development' => 'This feature is under development',
                ],
                'vi' => [
                    'students' => 'Học sinh',
                    'students_description' => 'Quản lý học sinh',
                    'parents' => 'Phụ huynh',
                    'parents_description' => 'Quản lý phụ huynh',
                    'description' => 'Quản lý chất lượng',
                    'industry' => 'Ngành',
                    'industry_education' => 'Giáo dục',
                    'industry_healthcare' => 'Y tế',
                    'industry_retail' => 'Bán lẻ',
                    'industry_manufacturing' => 'Sản xuất',
                    'coming_soon' => 'Sắp ra mắt',
                    'industry_coming_soon' => 'Sắp ra mắt',
                    'feature_in_development' => 'Tính năng đang được phát triển',
                ],
            ],

            // Holidays
            'holidays' => [
                'en' => [
                    'title' => 'Holidays',
                    'module_title' => 'Holidays',
                    'module_description' => 'Manage holidays and breaks',
                    'create_holiday' => 'Create Holiday',
                    'edit_holiday' => 'Edit Holiday',
                    'no_holidays' => 'No holidays found',
                    'name' => 'Holiday Name',
                    'start_date' => 'Start Date',
                    'end_date' => 'End Date',
                    'duration_days' => 'Duration (Days)',
                    'description' => 'Description',
                    'name_placeholder' => 'Enter holiday name',
                    'description_placeholder' => 'Enter description',
                    'confirm_delete' => 'Confirm Delete',
                    'created_success' => 'Holiday created successfully',
                    'updated_success' => 'Holiday updated successfully',
                    'deleted_success' => 'Holiday deleted successfully',
                ],
                'vi' => [
                    'title' => 'Ngày nghỉ',
                    'module_title' => 'Ngày nghỉ',
                    'module_description' => 'Quản lý ngày nghỉ và kỳ nghỉ',
                    'create_holiday' => 'Tạo ngày nghỉ',
                    'edit_holiday' => 'Sửa ngày nghỉ',
                    'no_holidays' => 'Không có ngày nghỉ nào',
                    'name' => 'Tên ngày nghỉ',
                    'start_date' => 'Ngày bắt đầu',
                    'end_date' => 'Ngày kết thúc',
                    'duration_days' => 'Thời lượng (Ngày)',
                    'description' => 'Mô tả',
                    'name_placeholder' => 'Nhập tên ngày nghỉ',
                    'description_placeholder' => 'Nhập mô tả',
                    'confirm_delete' => 'Xác nhận xóa',
                    'created_success' => 'Tạo ngày nghỉ thành công',
                    'updated_success' => 'Cập nhật ngày nghỉ thành công',
                    'deleted_success' => 'Xóa ngày nghỉ thành công',
                ],
            ],

            // Accounting (bổ sung)
            'accounting' => [
                'en' => [
                    'cash_accounts' => 'Cash Accounts',
                    'cash_accounts_subtitle' => 'Manage cash and bank accounts',
                    'add_account' => 'Add Account',
                    'total_cash' => 'Total Cash',
                    'total_bank' => 'Total Bank',
                    'total_balance' => 'Total Balance',
                    'account_type' => 'Account Type',
                    'search_account_placeholder' => 'Name, code, account number...',
                    'account_code' => 'Account Code',
                    'account_number' => 'Account Number',
                    'bank_name' => 'Bank Name',
                    'bank' => 'Bank',
                    'inactive' => 'Inactive',
                    'no_accounts' => 'No accounts yet',
                    'cost_type_hint' => 'Fixed / Variable / Infrastructure',
                ],
                'vi' => [
                    'cash_accounts' => 'Tài khoản tiền',
                    'cash_accounts_subtitle' => 'Quản lý tài khoản tiền mặt và ngân hàng',
                    'add_account' => 'Thêm tài khoản',
                    'total_cash' => 'Tiền mặt',
                    'total_bank' => 'Ngân hàng',
                    'total_balance' => 'Tổng số dư',
                    'account_type' => 'Loại tài khoản',
                    'search_account_placeholder' => 'Tên, mã, STK...',
                    'account_code' => 'Mã TK',
                    'account_number' => 'Số TK',
                    'bank_name' => 'Ngân hàng',
                    'bank' => 'Ngân hàng',
                    'inactive' => 'Ngừng',
                    'no_accounts' => 'Chưa có tài khoản',
                    'cost_type_hint' => 'Định phí / Biến phí / Đầu tư CSVC',
                ],
            ],

            // Settings
            'settings' => [
                'en' => [
                    'description' => 'System configuration',
                    'access_control' => 'Access Control',
                    'manage_access' => 'Manage roles and permissions',
                    'languages' => 'Languages',
                    'manage_languages' => 'Manage system languages',
                    'language_list' => 'Language List',
                    'more_settings_coming' => 'More settings coming soon',
                    'welcome_title' => 'Welcome to Settings',
                    'welcome_message' => 'Configure your system settings here',
                    'manage_translations' => 'Manage Translations',
                    'language_management' => 'Language Management',
                    'add_language' => 'Add Language',
                    'language_name' => 'Language Name',
                    'language_code' => 'Language Code',
                    'language_flag' => 'Flag',
                    'is_default' => 'Default',
                    'set_default' => 'Set as Default',
                    'edit_language' => 'Edit Language',
                    'language_direction' => 'Direction',
                    'sort_order' => 'Sort Order',
                    'is_active' => 'Active',
                    'manage_system_languages' => 'Manage system languages and translations',
                    'default' => 'Default',
                    'direction' => 'Direction',
                    'translations' => 'Translations',
                    'view_translations' => 'View Translations',
                    'translations_for' => 'Translations for',
                    'manage_translations_desc' => 'Manage translations for this language',
                    'all_groups' => 'All Groups',
                    'search_translations' => 'Search translations...',
                    'add_translation' => 'Add Translation',
                    'items' => 'items',
                    'key' => 'Key',
                    'value' => 'Value',
                    'edit_translation' => 'Edit Translation',
                    'language' => 'Language',
                    'group' => 'Group',
                    'select_group' => 'Select group',
                    'new_group' => 'New Group',
                    'new_group_name' => 'New Group Name',
                    'key_hint' => 'e.g., welcome_message',
                    'value_placeholder' => 'Enter translation value',
                ],
                'vi' => [
                    'description' => 'Cấu hình hệ thống',
                    'access_control' => 'Kiểm soát truy cập',
                    'manage_access' => 'Quản lý vai trò và quyền',
                    'languages' => 'Ngôn ngữ',
                    'manage_languages' => 'Quản lý ngôn ngữ hệ thống',
                    'language_list' => 'Danh sách ngôn ngữ',
                    'more_settings_coming' => 'Nhiều cài đặt sắp ra mắt',
                    'welcome_title' => 'Chào mừng đến với Cài đặt',
                    'welcome_message' => 'Cấu hình cài đặt hệ thống của bạn tại đây',
                    'manage_translations' => 'Quản lý bản dịch',
                    'language_management' => 'Quản lý ngôn ngữ',
                    'add_language' => 'Thêm ngôn ngữ',
                    'language_name' => 'Tên ngôn ngữ',
                    'language_code' => 'Mã ngôn ngữ',
                    'language_flag' => 'Cờ',
                    'is_default' => 'Mặc định',
                    'set_default' => 'Đặt làm mặc định',
                    'edit_language' => 'Sửa ngôn ngữ',
                    'language_direction' => 'Hướng',
                    'sort_order' => 'Thứ tự sắp xếp',
                    'is_active' => 'Hoạt động',
                    'manage_system_languages' => 'Quản lý ngôn ngữ và bản dịch hệ thống',
                    'default' => 'Mặc định',
                    'direction' => 'Hướng',
                    'translations' => 'Bản dịch',
                    'view_translations' => 'Xem bản dịch',
                    'translations_for' => 'Bản dịch cho',
                    'manage_translations_desc' => 'Quản lý bản dịch cho ngôn ngữ này',
                    'all_groups' => 'Tất cả nhóm',
                    'search_translations' => 'Tìm kiếm bản dịch...',
                    'add_translation' => 'Thêm bản dịch',
                    'items' => 'mục',
                    'key' => 'Khóa',
                    'value' => 'Giá trị',
                    'edit_translation' => 'Sửa bản dịch',
                    'language' => 'Ngôn ngữ',
                    'group' => 'Nhóm',
                    'select_group' => 'Chọn nhóm',
                    'new_group' => 'Nhóm mới',
                    'new_group_name' => 'Tên nhóm mới',
                    'key_hint' => 'ví dụ: welcome_message',
                    'value_placeholder' => 'Nhập giá trị bản dịch',
                ],
            ],
        ];

        foreach ($translations as $group => $languageTranslations) {
            foreach ($languages as $language) {
                $langCode = $language->code;
                
                if (!isset($languageTranslations[$langCode])) {
                    continue;
                }

                foreach ($languageTranslations[$langCode] as $key => $value) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'group' => $group,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                    
                    $this->command->info("✓ {$group}.{$key} ({$langCode})");
                }
            }
        }

        $this->command->info("\n✅ Complete All translations seeded successfully!");
    }
}

