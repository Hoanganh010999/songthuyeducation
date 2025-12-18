<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SuccessMessagesTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌐 Seeding success message translations...');
        
        $languages = Language::all();
        
        if ($languages->isEmpty()) {
            $this->command->warn('⚠️ No languages found. Please seed languages first.');
            return;
        }
        
        $englishLang = $languages->where('code', 'en')->first();
        $vietnameseLang = $languages->where('code', 'vi')->first();
        
        // Success messages translations
        $successMessages = [
            // Google Drive related success messages
            [
                'group' => 'messages',
                'key' => 'messages.google_email_assigned_successfully',
                'vi' => 'Đã gán Google email và tạo folder thành công',
                'en' => 'Google email assigned and folder created successfully'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.google_email_updated_successfully',
                'vi' => 'Đã cập nhật Google email thành công',
                'en' => 'Google email updated successfully'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.google_email_removed_successfully',
                'vi' => 'Đã xóa Google email thành công',
                'en' => 'Google email removed successfully'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.permissions_synced_successfully',
                'vi' => 'Đồng bộ quyền truy cập thành công',
                'en' => 'Permissions synced successfully'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.no_accessible_folders',
                'vi' => 'Bạn chưa có quyền truy cập folder nào',
                'en' => 'You have no accessible folders'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.sync_completed_successfully',
                'vi' => 'Đồng bộ hoàn tất thành công',
                'en' => 'Sync completed successfully'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.use_existing_or_create_new',
                'vi' => 'Bạn muốn sử dụng folder hiện có hay tạo folder mới?',
                'en' => 'Do you want to use the existing folder or create a new one?'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.use_existing_or_create_new_syllabus',
                'vi' => 'Folder giáo án này đã tồn tại. Bạn muốn sử dụng folder hiện có hay tạo folder mới?',
                'en' => 'This syllabus folder already exists. Do you want to use the existing folder or create a new one?'
            ],
            [
                'group' => 'messages',
                'key' => 'messages.syllabus_folder_created_successfully',
                'vi' => 'Tạo thư mục giáo án thành công',
                'en' => 'Syllabus folder created successfully'
            ],
        ];
        
        foreach ($successMessages as $message) {
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
        
        $this->command->info('✅ Success message translations seeded successfully!');
        $this->command->info('   Total: ' . count($successMessages) . ' keys');
    }
}
