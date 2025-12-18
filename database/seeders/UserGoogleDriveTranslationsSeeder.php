<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class UserGoogleDriveTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌐 Seeding user Google Drive UI translations...');
        
        $languages = Language::all();
        
        if ($languages->isEmpty()) {
            $this->command->warn('⚠️ No languages found. Please seed languages first.');
            return;
        }
        
        $englishLang = $languages->where('code', 'en')->first();
        $vietnameseLang = $languages->where('code', 'vi')->first();
        
        // UI translations
        $translations = [
            [
                'group' => 'users',
                'key' => 'users.user',
                'vi' => 'Người dùng',
                'en' => 'User'
            ],
            [
                'group' => 'users',
                'key' => 'users.phone',
                'vi' => 'Số điện thoại',
                'en' => 'Phone'
            ],
            [
                'group' => 'users',
                'key' => 'users.assign_google_email',
                'vi' => 'Gán Google Email',
                'en' => 'Assign Google Email'
            ],
            [
                'group' => 'users',
                'key' => 'users.update_google_email',
                'vi' => 'Cập Nhật Google Email',
                'en' => 'Update Google Email'
            ],
            [
                'group' => 'users',
                'key' => 'users.google_email',
                'vi' => 'Google Email',
                'en' => 'Google Email'
            ],
            [
                'group' => 'users',
                'key' => 'users.current_google_email',
                'vi' => 'Email Google hiện tại',
                'en' => 'Current Google Email'
            ],
            [
                'group' => 'users',
                'key' => 'users.enter_google_email',
                'vi' => 'Nhập địa chỉ Google email',
                'en' => 'Enter Google email address'
            ],
            [
                'group' => 'users',
                'key' => 'users.folder_already_created',
                'vi' => 'Folder đã được tạo',
                'en' => 'Folder already created'
            ],
            [
                'group' => 'users',
                'key' => 'users.phone_required_warning',
                'vi' => 'Số điện thoại là bắt buộc để tạo folder Google Drive',
                'en' => 'Phone number is required to create Google Drive folder'
            ],
            [
                'group' => 'users',
                'key' => 'users.confirm_remove_google_email',
                'vi' => 'Bạn có chắc muốn xóa Google email này? Quyền truy cập folder sẽ bị thu hồi.',
                'en' => 'Are you sure you want to remove this Google email? Folder access will be revoked.'
            ],
            [
                'group' => 'common',
                'key' => 'common.assign',
                'vi' => 'Gán',
                'en' => 'Assign'
            ],
            [
                'group' => 'common',
                'key' => 'common.yes_remove',
                'vi' => 'Có, xóa',
                'en' => 'Yes, remove'
            ],
            [
                'group' => 'common',
                'key' => 'common.not_set',
                'vi' => 'Chưa thiết lập',
                'en' => 'Not set'
            ],
            [
                'group' => 'common',
                'key' => 'common.warning',
                'vi' => 'Cảnh báo',
                'en' => 'Warning'
            ],
            [
                'group' => 'common',
                'key' => 'common.confirm',
                'vi' => 'Xác nhận',
                'en' => 'Confirm'
            ],
            [
                'group' => 'common',
                'key' => 'common.use_existing',
                'vi' => 'Sử dụng folder cũ',
                'en' => 'Use Existing Folder'
            ],
            [
                'group' => 'common',
                'key' => 'common.create_new',
                'vi' => 'Tạo folder mới',
                'en' => 'Create New Folder'
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
        
        $this->command->info('✅ User Google Drive UI translations seeded successfully!');
        $this->command->info('   Total: ' . count($translations) . ' keys');
    }
}
