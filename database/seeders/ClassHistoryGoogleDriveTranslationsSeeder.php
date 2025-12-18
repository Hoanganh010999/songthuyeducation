<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translation;

class ClassHistoryGoogleDriveTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌐 Seeding Class History Google Drive translations...');
        
        $vietnameseLang = Language::where('code', 'vi')->first();
        $englishLang = Language::where('code', 'en')->first();
        
        if (!$vietnameseLang || !$englishLang) {
            $this->command->error('❌ Vietnamese or English language not found!');
            return;
        }
        
        $translations = [
            [
                'group' => 'google_drive',
                'key' => 'google_drive.title',
                'vi' => 'Google Drive',
                'en' => 'Google Drive'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_folder',
                'vi' => 'Folder Lịch Sử Lớp Học',
                'en' => 'Class History Folder'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_description',
                'vi' => 'Folder này sẽ chứa tất cả tài liệu và lịch sử của các lớp học đã kết thúc',
                'en' => 'This folder will contain all documents and history of completed classes'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.folder_exists',
                'vi' => 'Folder đã tồn tại',
                'en' => 'Folder exists'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.folder_not_exists',
                'vi' => 'Folder chưa được tạo',
                'en' => 'Folder not created yet'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.folder_ready',
                'vi' => 'Đã sẵn sàng',
                'en' => 'Ready'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.create_folder',
                'vi' => 'Tạo Folder',
                'en' => 'Create Folder'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.creating',
                'vi' => 'Đang tạo...',
                'en' => 'Creating...'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_info',
                'vi' => 'Khi lớp học kết thúc, tất cả tài liệu sẽ được di chuyển vào folder này để lưu trữ',
                'en' => 'When a class ends, all documents will be moved to this folder for archival'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_folder_exists',
                'vi' => 'Folder Class History đã tồn tại',
                'en' => 'Class History folder already exists'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_folder_created',
                'vi' => 'Đã tạo folder Class History thành công',
                'en' => 'Class History folder created successfully'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.class_history_folder_creation_failed',
                'vi' => 'Tạo folder Class History thất bại',
                'en' => 'Failed to create Class History folder'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.how_to_fix',
                'vi' => 'Cách khắc phục',
                'en' => 'How to fix'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.contact_admin_for_root_permission',
                'vi' => 'Vui lòng liên hệ Super Admin để được cấp quyền truy cập Root Folder của Google Drive',
                'en' => 'Please contact Super Admin to be granted access to Root Folder of Google Drive'
            ],
            [
                'group' => 'google_drive',
                'key' => 'google_drive.folder_already_exists',
                'vi' => 'Folder đã tồn tại',
                'en' => 'Folder already exists'
            ],
        ];
        
        foreach ($translations as $translation) {
            $this->command->info("  Processing: {$translation['key']}");
            
            // Create or update Vietnamese translation
            if ($vietnameseLang) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $vietnameseLang->id,
                        'key' => $translation['key']
                    ],
                    [
                        'value' => $translation['vi'],
                        'group' => $translation['group']
                    ]
                );
            }
            
            // Create or update English translation
            if ($englishLang) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $englishLang->id,
                        'key' => $translation['key']
                    ],
                    [
                        'value' => $translation['en'],
                        'group' => $translation['group']
                    ]
                );
            }
        }
        
        $this->command->info('✅ Class History Google Drive translations seeded successfully!');
        $this->command->info('   Total: ' . count($translations) . ' keys');
    }
}

