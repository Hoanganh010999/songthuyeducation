<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class ErrorMessagesTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌐 Seeding error message translations...');
        
        $languages = Language::all();
        
        if ($languages->isEmpty()) {
            $this->command->warn('⚠️ No languages found. Please seed languages first.');
            return;
        }
        
        $englishLang = $languages->where('code', 'en')->first();
        $vietnameseLang = $languages->where('code', 'vi')->first();
        
        // Error messages translations
        $errorMessages = [
            // Student related errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_students',
                'vi' => 'Bạn không có quyền xem danh sách học viên',
                'en' => 'You do not have permission to view students list'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_student',
                'vi' => 'Bạn không có quyền xem thông tin học viên này',
                'en' => 'You do not have permission to view this student'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_student_classes',
                'vi' => 'Bạn không có quyền xem lớp học của học viên này',
                'en' => 'You do not have permission to view this student\'s classes'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.student_not_found',
                'vi' => 'Không tìm thấy thông tin học viên cho tài khoản này',
                'en' => 'No student record found for this account'
            ],
            
            // Parent related errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_parents',
                'vi' => 'Bạn không có quyền xem danh sách phụ huynh',
                'en' => 'You do not have permission to view parents list'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_parent',
                'vi' => 'Bạn không có quyền xem thông tin phụ huynh này',
                'en' => 'You do not have permission to view this parent'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.parent_not_found',
                'vi' => 'Không tìm thấy thông tin phụ huynh cho tài khoản này',
                'en' => 'No parent record found for this account'
            ],
            
            // Syllabus related errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_syllabus',
                'vi' => 'Bạn không có quyền xem giáo án',
                'en' => 'You do not have permission to view syllabus'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_create_syllabus',
                'vi' => 'Bạn không có quyền tạo giáo án',
                'en' => 'You do not have permission to create syllabus'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_edit_syllabus',
                'vi' => 'Bạn không có quyền sửa giáo án',
                'en' => 'You do not have permission to edit syllabus'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_delete_syllabus',
                'vi' => 'Bạn không có quyền xóa giáo án',
                'en' => 'You do not have permission to delete syllabus'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_manage_syllabus_content',
                'vi' => 'Bạn không có quyền quản lý nội dung giáo án',
                'en' => 'You do not have permission to manage syllabus content'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_delete_syllabus_content',
                'vi' => 'Bạn không có quyền xóa nội dung giáo án',
                'en' => 'You do not have permission to delete syllabus content'
            ],
            
            // Course related errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_post',
                'vi' => 'Bạn không có quyền đăng bài',
                'en' => 'You do not have permission to post'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_create_event',
                'vi' => 'Bạn không có quyền tạo Event',
                'en' => 'You do not have permission to create events'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_create_homework',
                'vi' => 'Bạn không có quyền tạo Homework',
                'en' => 'You do not have permission to create homework'
            ],
            
            // Google Drive related errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_root_folder',
                'vi' => 'Bạn không có quyền xem thư mục gốc',
                'en' => 'You do not have permission to view root folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_create_in_root_folder',
                'vi' => 'Bạn không có quyền tạo folder trong thư mục gốc',
                'en' => 'You do not have permission to create folder in root folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_upload_to_root_folder',
                'vi' => 'Bạn không có quyền upload file vào thư mục gốc',
                'en' => 'You do not have permission to upload file to root folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.user_phone_required',
                'vi' => 'Số điện thoại người dùng là bắt buộc để tạo folder Google Drive',
                'en' => 'User phone number is required to create Google Drive folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.user_phone_not_unique',
                'vi' => 'Số điện thoại này đã được sử dụng bởi người dùng khác',
                'en' => 'This phone number is already used by another user'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_drive_not_configured',
                'vi' => 'Google Drive chưa được cấu hình cho chi nhánh này',
                'en' => 'Google Drive is not configured for this branch'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_email_assignment_failed',
                'vi' => 'Gán Google email thất bại',
                'en' => 'Google email assignment failed'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_email_update_failed',
                'vi' => 'Cập nhật Google email thất bại',
                'en' => 'Google email update failed'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_email_removal_failed',
                'vi' => 'Xóa Google email thất bại',
                'en' => 'Google email removal failed'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.validation_failed',
                'vi' => 'Dữ liệu không hợp lệ',
                'en' => 'Validation failed'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.user_google_email_not_set',
                'vi' => 'Người dùng chưa được gán Google email',
                'en' => 'User Google email is not set'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.sync_failed',
                'vi' => 'Đồng bộ thất bại',
                'en' => 'Sync failed'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.folder_already_exists',
                'vi' => 'Folder đã tồn tại',
                'en' => 'Folder already exists'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_drive_not_connected',
                'vi' => 'Tài khoản chưa kết nối Google Drive',
                'en' => 'Account is not connected to Google Drive'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.syllabus_folder_not_found',
                'vi' => 'Không tìm thấy thư mục Syllabus',
                'en' => 'Syllabus folder not found'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.no_permission_to_folder',
                'vi' => 'Bạn không có quyền truy cập folder này',
                'en' => 'You do not have permission to access this folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.syllabus_folder_creation_failed',
                'vi' => 'Tạo thư mục giáo án thất bại',
                'en' => 'Failed to create syllabus folder'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.teacher_no_google_email',
                'vi' => 'Giáo viên chưa được gán tài khoản Google Drive. Vui lòng liên hệ Admin để gán Google email trước khi thêm vào môn học.',
                'en' => 'Teacher does not have a Google Drive account assigned. Please contact Admin to assign Google email before adding to subject.'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.google_drive_permission_failed',
                'vi' => 'Không thể cấp quyền Google Drive',
                'en' => 'Failed to grant Google Drive permission'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized_view_subjects',
                'vi' => 'Bạn không có quyền xem danh sách môn học',
                'en' => 'You do not have permission to view subjects list'
            ],
            
            // General errors
            [
                'group' => 'errors',
                'key' => 'errors.unauthorized',
                'vi' => 'Không có quyền truy cập',
                'en' => 'Unauthorized access'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.not_found',
                'vi' => 'Không tìm thấy',
                'en' => 'Not found'
            ],
            [
                'group' => 'errors',
                'key' => 'errors.server_error',
                'vi' => 'Lỗi máy chủ',
                'en' => 'Server error'
            ],
        ];
        
        foreach ($errorMessages as $message) {
            $this->command->info("  Processing: {$message['key']}");
            
            // Create or update Vietnamese translation
            if ($vietnameseLang) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $vietnameseLang->id,
                        'key' => $message['key']
                    ],
                    [
                        'value' => $message['vi'],
                        'group' => $message['group']
                    ]
                );
            }
            
            // Create or update English translation
            if ($englishLang) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $englishLang->id,
                        'key' => $message['key']
                    ],
                    [
                        'value' => $message['en'],
                        'group' => $message['group']
                    ]
                );
            }
        }
        
        $this->command->info('✅ Error message translations seeded successfully!');
        $this->command->info('   Total: ' . count($errorMessages) . ' keys');
    }
}

