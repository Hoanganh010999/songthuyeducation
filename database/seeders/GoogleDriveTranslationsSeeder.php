<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class GoogleDriveTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');

        $translations = [
            // Menu & Navigation
            'google_drive.menu' => [
                'vi' => 'Google Drive',
                'en' => 'Google Drive',
            ],
            'google_drive.title' => [
                'vi' => 'Quản lý Google Drive',
                'en' => 'Google Drive Management',
            ],

            // Settings
            'google_drive.settings' => [
                'vi' => 'Cài đặt Google Drive',
                'en' => 'Google Drive Settings',
            ],
            'google_drive.api_settings' => [
                'vi' => 'Cài đặt API Google Drive',
                'en' => 'Google Drive API Settings',
            ],
            'google_drive.client_id' => [
                'vi' => 'Client ID',
                'en' => 'Client ID',
            ],
            'google_drive.client_id_placeholder' => [
                'vi' => 'Nhập Google OAuth Client ID',
                'en' => 'Enter Google OAuth Client ID',
            ],
            'google_drive.client_secret' => [
                'vi' => 'Client Secret',
                'en' => 'Client Secret',
            ],
            'google_drive.client_secret_placeholder' => [
                'vi' => 'Nhập Google OAuth Client Secret',
                'en' => 'Enter Google OAuth Client Secret',
            ],
            'google_drive.refresh_token' => [
                'vi' => 'Refresh Token',
                'en' => 'Refresh Token',
            ],
            'google_drive.refresh_token_placeholder' => [
                'vi' => 'Nhập Google OAuth Refresh Token',
                'en' => 'Enter Google OAuth Refresh Token',
            ],
            'google_drive.folder_name' => [
                'vi' => 'Tên thư mục gốc',
                'en' => 'Root Folder Name',
            ],
            'google_drive.get_from_console' => [
                'vi' => 'Lấy từ Google Cloud Console',
                'en' => 'Get from Google Cloud Console',
            ],
            'google_drive.use_oauth_playground' => [
                'vi' => 'Sử dụng OAuth 2.0 Playground để lấy token',
                'en' => 'Use OAuth 2.0 Playground to get token',
            ],
            'google_drive.folder_auto_create' => [
                'vi' => 'Thư mục sẽ được tự động tạo nếu chưa tồn tại',
                'en' => 'Folder will be auto-created if not exists',
            ],
            'google_drive.connection_status' => [
                'vi' => 'Trạng thái kết nối',
                'en' => 'Connection Status',
            ],
            'google_drive.connected' => [
                'vi' => 'Đã kết nối',
                'en' => 'Connected',
            ],
            'google_drive.disconnected' => [
                'vi' => 'Chưa kết nối',
                'en' => 'Disconnected',
            ],
            'google_drive.folder_id' => [
                'vi' => 'ID Thư mục',
                'en' => 'Folder ID',
            ],
            'google_drive.setup_guide' => [
                'vi' => 'Hướng dẫn cài đặt',
                'en' => 'Setup Guide',
            ],
            'google_drive.step_1' => [
                'vi' => 'Truy cập Google Cloud Console và tạo project',
                'en' => 'Visit Google Cloud Console and create a project',
            ],
            'google_drive.step_2' => [
                'vi' => 'Enable Google Drive API cho project',
                'en' => 'Enable Google Drive API for your project',
            ],
            'google_drive.step_3' => [
                'vi' => 'Tạo OAuth 2.0 credentials (Client ID & Secret)',
                'en' => 'Create OAuth 2.0 credentials (Client ID & Secret)',
            ],
            'google_drive.step_4' => [
                'vi' => 'Sử dụng OAuth Playground để lấy Refresh Token',
                'en' => 'Use OAuth Playground to get Refresh Token',
            ],
            'google_drive.test_connection' => [
                'vi' => 'Kiểm tra kết nối',
                'en' => 'Test Connection',
            ],
            'google_drive.connection_success' => [
                'vi' => 'Kết nối thành công!',
                'en' => 'Connection Successful!',
            ],
            'google_drive.connection_failed' => [
                'vi' => 'Kết nối thất bại',
                'en' => 'Connection Failed',
            ],
            'google_drive.save_settings' => [
                'vi' => 'Lưu cài đặt',
                'en' => 'Save Settings',
            ],

            // File Management
            'google_drive.files' => [
                'vi' => 'Tệp tin',
                'en' => 'Files',
            ],
            'google_drive.folders' => [
                'vi' => 'Thư mục',
                'en' => 'Folders',
            ],
            'google_drive.upload' => [
                'vi' => 'Tải lên',
                'en' => 'Upload',
            ],
            'google_drive.new_folder' => [
                'vi' => 'Thư mục mới',
                'en' => 'New Folder',
            ],
            'google_drive.sync' => [
                'vi' => 'Đồng bộ',
                'en' => 'Sync',
            ],
            'google_drive.syncing' => [
                'vi' => 'Đang đồng bộ...',
                'en' => 'Syncing...',
            ],
            'google_drive.sync_success' => [
                'vi' => 'Đồng bộ thành công',
                'en' => 'Sync Successful',
            ],
            'google_drive.file_name' => [
                'vi' => 'Tên tệp',
                'en' => 'File Name',
            ],
            'google_drive.folder_name_input' => [
                'vi' => 'Tên thư mục',
                'en' => 'Folder Name',
            ],
            'google_drive.size' => [
                'vi' => 'Kích thước',
                'en' => 'Size',
            ],
            'google_drive.modified' => [
                'vi' => 'Sửa đổi',
                'en' => 'Modified',
            ],
            'google_drive.type' => [
                'vi' => 'Loại',
                'en' => 'Type',
            ],
            'google_drive.actions' => [
                'vi' => 'Hành động',
                'en' => 'Actions',
            ],
            'google_drive.rename' => [
                'vi' => 'Đổi tên',
                'en' => 'Rename',
            ],
            'google_drive.move' => [
                'vi' => 'Di chuyển',
                'en' => 'Move',
            ],
            'google_drive.download' => [
                'vi' => 'Tải xuống',
                'en' => 'Download',
            ],
            'google_drive.view' => [
                'vi' => 'Xem',
                'en' => 'View',
            ],
            'google_drive.open_in_drive' => [
                'vi' => 'Mở trong Google Drive',
                'en' => 'Open in Google Drive',
            ],
            'google_drive.no_files' => [
                'vi' => 'Không có tệp tin nào',
                'en' => 'No files found',
            ],
            'google_drive.empty_folder' => [
                'vi' => 'Thư mục trống',
                'en' => 'Empty folder',
            ],

            // Actions
            'google_drive.create_folder' => [
                'vi' => 'Tạo thư mục',
                'en' => 'Create Folder',
            ],
            'google_drive.upload_file' => [
                'vi' => 'Tải lên tệp',
                'en' => 'Upload File',
            ],
            'google_drive.uploading' => [
                'vi' => 'Đang tải lên...',
                'en' => 'Uploading...',
            ],
            'google_drive.upload_success' => [
                'vi' => 'Tải lên thành công',
                'en' => 'Upload Successful',
            ],
            'google_drive.folder_created' => [
                'vi' => 'Thư mục đã được tạo',
                'en' => 'Folder Created',
            ],
            'google_drive.file_deleted' => [
                'vi' => 'Tệp đã được xóa',
                'en' => 'File Deleted',
            ],
            'google_drive.file_renamed' => [
                'vi' => 'Tệp đã được đổi tên',
                'en' => 'File Renamed',
            ],
            'google_drive.file_moved' => [
                'vi' => 'Tệp đã được di chuyển',
                'en' => 'File Moved',
            ],
            'google_drive.confirm_delete' => [
                'vi' => 'Bạn có chắc muốn xóa?',
                'en' => 'Are you sure you want to delete?',
            ],
            
            // Sharing & Permissions
            'google_drive.share' => [
                'vi' => 'Chia sẻ',
                'en' => 'Share',
            ],
            'google_drive.current_permissions' => [
                'vi' => 'Quyền truy cập hiện tại',
                'en' => 'Current Permissions',
            ],
            'google_drive.no_permissions' => [
                'vi' => 'Chưa chia sẻ với ai',
                'en' => 'Not shared with anyone',
            ],
            'google_drive.share_link' => [
                'vi' => 'Liên kết chia sẻ',
                'en' => 'Share Link',
            ],
            'google_drive.copy_link' => [
                'vi' => 'Sao chép liên kết',
                'en' => 'Copy Link',
            ],
            'google_drive.link_copied' => [
                'vi' => 'Đã sao chép liên kết',
                'en' => 'Link Copied',
            ],
            'google_drive.enter_folder_name' => [
                'vi' => 'Nhập tên thư mục',
                'en' => 'Enter folder name',
            ],
            'google_drive.enter_new_name' => [
                'vi' => 'Nhập tên mới',
                'en' => 'Enter new name',
            ],
            'google_drive.select_file' => [
                'vi' => 'Chọn tệp',
                'en' => 'Select File',
            ],
            'google_drive.drag_drop' => [
                'vi' => 'Kéo thả tệp vào đây',
                'en' => 'Drag and drop files here',
            ],
            'google_drive.or' => [
                'vi' => 'hoặc',
                'en' => 'or',
            ],
            'google_drive.browse' => [
                'vi' => 'Duyệt',
                'en' => 'Browse',
            ],
            'google_drive.max_file_size' => [
                'vi' => 'Kích thước tối đa: 100MB',
                'en' => 'Maximum size: 100MB',
            ],

            // Status & Info
            'google_drive.last_synced' => [
                'vi' => 'Đồng bộ lần cuối',
                'en' => 'Last Synced',
            ],
            'google_drive.never_synced' => [
                'vi' => 'Chưa đồng bộ',
                'en' => 'Never Synced',
            ],
            'google_drive.active' => [
                'vi' => 'Hoạt động',
                'en' => 'Active',
            ],
            'google_drive.inactive' => [
                'vi' => 'Không hoạt động',
                'en' => 'Inactive',
            ],
            'google_drive.configure_first' => [
                'vi' => 'Vui lòng cấu hình Google Drive API trước',
                'en' => 'Please configure Google Drive API first',
            ],
            'google_drive.go_to_settings' => [
                'vi' => 'Đi đến cài đặt',
                'en' => 'Go to Settings',
            ],
            
            // Sync statistics
            'google_drive.files_synced' => [
                'vi' => 'Files đã đồng bộ',
                'en' => 'Files Synced',
            ],
            'google_drive.permissions_synced' => [
                'vi' => 'Quyền truy cập đã đồng bộ',
                'en' => 'Permissions Synced',
            ],
            'google_drive.folders_processed' => [
                'vi' => 'Folders đã xử lý',
                'en' => 'Folders Processed',
            ],
        ];

        foreach ($translations as $key => $values) {
            list($group, $keyName) = explode('.', $key, 2);

            foreach ($values as $langCode => $value) {
                if (isset($languages[$langCode])) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $languages[$langCode]->id,
                            'group' => $group,
                            'key' => $keyName,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Google Drive translations seeded successfully!');
    }
}
