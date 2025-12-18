<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translation;

class ClassGoogleDriveTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌐 Seeding Class Google Drive translations...');
        
        $vietnameseLang = Language::where('code', 'vi')->first();
        $englishLang = Language::where('code', 'en')->first();
        
        if (!$vietnameseLang || !$englishLang) {
            $this->command->error('❌ Vietnamese or English language not found!');
            return;
        }
        
        $translations = [
            // Class Google Drive
            [
                'group' => 'classes',
                'key' => 'classes.no_google_drive_folder',
                'vi' => 'Lớp học chưa có folder Google Drive',
                'en' => 'Class does not have a Google Drive folder'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.syllabus_no_folder',
                'vi' => 'Giáo án chưa có folder Google Drive. Vui lòng tạo folder cho giáo án trước.',
                'en' => 'Syllabus does not have a Google Drive folder. Please create a folder for the syllabus first.'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.class_history_not_found',
                'vi' => 'Folder Class History chưa được tạo. Vui lòng liên hệ Admin hoặc Trưởng Bộ Môn để được hỗ trợ.',
                'en' => 'Class History folder has not been created. Please contact Admin or Department Head for support.'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.folder_copy_failed',
                'vi' => 'Không thể sao chép folder giáo án',
                'en' => 'Failed to copy syllabus folder'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.unit_folder_not_found',
                'vi' => 'Không tìm thấy folder Unit',
                'en' => 'Unit folder not found'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.lesson_plans_folder_not_found',
                'vi' => 'Không tìm thấy folder Lesson Plans',
                'en' => 'Lesson Plans folder not found'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.lesson_plan_uploaded',
                'vi' => 'Đã tải lên lesson plan thành công',
                'en' => 'Lesson plan uploaded successfully'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.lesson_plan_upload_failed',
                'vi' => 'Tải lên lesson plan thất bại',
                'en' => 'Failed to upload lesson plan'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.materials_folder',
                'vi' => 'Tài liệu học tập',
                'en' => 'Materials'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.homework_folder',
                'vi' => 'Bài tập về nhà',
                'en' => 'Homework'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.lesson_plans_folder',
                'vi' => 'Giáo án',
                'en' => 'Lesson Plans'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.view_folder',
                'vi' => 'Xem Folder',
                'en' => 'View Folder'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.upload_lesson_plan',
                'vi' => 'Tải lên Lesson Plan',
                'en' => 'Upload Lesson Plan'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.view_lesson_plans',
                'vi' => 'Xem Lesson Plans',
                'en' => 'View Lesson Plans'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.lesson_name',
                'vi' => 'Tên bài học',
                'en' => 'Lesson Name'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.select_file',
                'vi' => 'Chọn file',
                'en' => 'Select File'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.upload',
                'vi' => 'Tải lên',
                'en' => 'Upload'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.uploading',
                'vi' => 'Đang tải lên...',
                'en' => 'Uploading...'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.no_lesson_plans',
                'vi' => 'Chưa có lesson plan nào',
                'en' => 'No lesson plans yet'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.download',
                'vi' => 'Tải xuống',
                'en' => 'Download'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.view_on_drive',
                'vi' => 'Xem trên Drive',
                'en' => 'View on Drive'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.google_drive_integration',
                'vi' => 'Tích hợp Google Drive',
                'en' => 'Google Drive Integration'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.class_folder_created',
                'vi' => 'Đã tạo folder lớp học trên Google Drive',
                'en' => 'Class folder created on Google Drive'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.class_folder_exists',
                'vi' => 'Folder lớp học đã tồn tại trên Google Drive',
                'en' => 'Class folder already exists on Google Drive'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.creating_class_folder',
                'vi' => 'Đang tạo folder lớp học...',
                'en' => 'Creating class folder...'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.folder_copy_warning',
                'vi' => 'Lưu ý: Folder Google Drive sẽ được tạo sau khi lưu lớp học',
                'en' => 'Note: Google Drive folder will be created after saving the class'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.contact_admin_for_class_history',
                'vi' => 'Vui lòng liên hệ Admin để được hỗ trợ tạo folder Class History',
                'en' => 'Please contact Admin for support creating Class History folder'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.unit',
                'vi' => 'Unit',
                'en' => 'Unit'
            ],
            [
                'group' => 'classes',
                'key' => 'classes.folder_copy_warning',
                'vi' => '⚠️ Lưu ý: Folder Google Drive không thể sao chép hoàn toàn do giới hạn thời gian.',
                'en' => '⚠️ Note: Google Drive folder could not be fully copied due to time limit.'
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
        
        $this->command->info('✅ Class Google Drive translations seeded successfully!');
        $this->command->info('   Total: ' . count($translations) . ' keys');
    }
}

