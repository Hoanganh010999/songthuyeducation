<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Translation;

class SubjectsGoogleDriveTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌐 Seeding Subjects Google Drive translations...');
        
        $vietnameseLang = Language::where('code', 'vi')->first();
        $englishLang = Language::where('code', 'en')->first();
        
        if (!$vietnameseLang || !$englishLang) {
            $this->command->error('❌ Vietnamese or English language not found!');
            return;
        }
        
        $translations = [
            [
                'group' => 'subjects',
                'key' => 'subjects.how_to_fix',
                'vi' => 'Cách khắc phục',
                'en' => 'How to fix'
            ],
            [
                'group' => 'subjects',
                'key' => 'subjects.go_to_users_management',
                'vi' => 'Vào quản lý Users',
                'en' => 'Go to Users Management'
            ],
            [
                'group' => 'subjects',
                'key' => 'subjects.click_assign_google_email',
                'vi' => 'Click nút gán Google email cho giáo viên',
                'en' => 'Click the assign Google email button for the teacher'
            ],
            [
                'group' => 'subjects',
                'key' => 'subjects.then_add_teacher_to_subject',
                'vi' => 'Sau đó quay lại thêm giáo viên vào môn học',
                'en' => 'Then come back and add teacher to subject'
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
        
        $this->command->info('✅ Subjects Google Drive translations seeded successfully!');
        $this->command->info('   Total: ' . count($translations) . ' keys');
    }
}

