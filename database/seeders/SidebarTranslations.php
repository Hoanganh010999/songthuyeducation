<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class SidebarTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            // Common
            'common.dashboard' => [
                'vi' => 'Trang chủ',
                'en' => 'Dashboard',
            ],
            
            // Users
            'users.title' => [
                'vi' => 'Người dùng',
                'en' => 'Users',
            ],
            
            // HR
            'hr.title' => [
                'vi' => 'Nhân sự',
                'en' => 'Human Resources',
            ],
            
            // Quality
            'quality.title' => [
                'vi' => 'Quản lý Chất lượng',
                'en' => 'Quality Management',
            ],
            
            // Settings
            'settings.title' => [
                'vi' => 'Cài đặt hệ thống',
                'en' => 'System Settings',
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

        echo "\n✅ Sidebar translations seeded successfully!\n";
    }
}

