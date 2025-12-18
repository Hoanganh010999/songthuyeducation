<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translation;

class SyllabusGoogleDriveTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌐 Seeding Syllabus Google Drive translations...');
        
        $vietnameseLang = Language::where('code', 'vi')->first();
        $englishLang = Language::where('code', 'en')->first();
        
        if (!$vietnameseLang || !$englishLang) {
            $this->command->error('❌ Vietnamese or English language not found!');
            return;
        }
        
        $translations = [
            [
                'group' => 'syllabus',
                'key' => 'syllabus.contact_admin_for_google_email',
                'vi' => 'Vui lòng liên hệ Admin để được cấp tài khoản Google Drive',
                'en' => 'Please contact Admin to be assigned a Google Drive account'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.contact_admin_for_syllabus_folder',
                'vi' => 'Vui lòng liên hệ Admin để tạo thư mục Syllabus',
                'en' => 'Please contact Admin to create Syllabus folder'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.contact_admin_for_permission',
                'vi' => 'Vui lòng liên hệ Admin để được cấp quyền truy cập folder này',
                'en' => 'Please contact Admin to be granted permission to this folder'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.no_permission_warning',
                'vi' => '⚠️ Bạn chưa có quyền truy cập folder này. Nếu chọn sử dụng folder cũ, bạn sẽ cần liên hệ Admin để được cấp quyền.',
                'en' => '⚠️ You do not have permission to this folder. If you choose to use the old folder, you will need to contact Admin for permission.'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.created_without_folder',
                'vi' => 'Giáo án đã được tạo nhưng không thể tạo thư mục Google Drive. Vui lòng liên hệ Admin.',
                'en' => 'Syllabus was created but Google Drive folder could not be created. Please contact Admin.'
            ],
            [
                'group' => 'common',
                'key' => 'common.folder',
                'vi' => 'Thư mục',
                'en' => 'Folder'
            ],
            // Status descriptions
            [
                'group' => 'syllabus',
                'key' => 'syllabus.status_draft_desc',
                'vi' => 'Giáo án đang được soạn thảo, chưa sẵn sàng sử dụng',
                'en' => 'Syllabus is being drafted, not ready for use'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.status_approved_desc',
                'vi' => 'Giáo án đã được phê duyệt và sẵn sàng sử dụng',
                'en' => 'Syllabus has been approved and ready for use'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.status_in_use_desc',
                'vi' => 'Giáo án đang được sử dụng trong các lớp học',
                'en' => 'Syllabus is currently being used in classes'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.status_archived_desc',
                'vi' => 'Giáo án đã được lưu trữ, không còn sử dụng',
                'en' => 'Syllabus has been archived, no longer in use'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.creating_folder',
                'vi' => 'Đang tạo Giáo Án',
                'en' => 'Creating Syllabus'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.please_wait',
                'vi' => 'Vui lòng đợi... Đang tạo folder trên Google Drive',
                'en' => 'Please wait... Creating folder on Google Drive'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.folder_creation_failed',
                'vi' => 'Không thể tạo folder Google Drive',
                'en' => 'Failed to create Google Drive folder'
            ],
            [
                'group' => 'syllabus',
                'key' => 'syllabus.creation_cancelled',
                'vi' => 'Đã hủy tạo giáo án',
                'en' => 'Syllabus creation cancelled'
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
        
        $this->command->info('✅ Syllabus Google Drive translations seeded successfully!');
        $this->command->info('   Total: ' . count($translations) . ' keys');
    }
}

