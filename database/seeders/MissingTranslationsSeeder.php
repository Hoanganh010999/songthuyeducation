<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class MissingTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            // Branches
            'branches.title' => [
                'vi' => 'Chi nhánh',
                'en' => 'Branches',
            ],
            
            // Class Detail - Schedule
            'class_detail.click_to_edit' => [
                'vi' => 'Nhấp để sửa',
                'en' => 'Click to edit',
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
                    
                    echo "✓ Translation: {$key} ({$langCode})\n";
                }
            }
        }

        echo "\n✅ Missing translations seeded successfully!\n";
    }
}

