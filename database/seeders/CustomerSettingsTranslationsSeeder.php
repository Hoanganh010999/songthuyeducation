<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class CustomerSettingsTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = Language::all();
        
        $translations = [
            // Settings
            'customers.settings' => [
                'vi' => 'Cài đặt',
                'en' => 'Settings',
            ],
            'customers.interaction_types' => [
                'vi' => 'Loại tương tác',
                'en' => 'Interaction Types',
            ],
            'customers.interaction_type' => [
                'vi' => 'Loại tương tác',
                'en' => 'Interaction Type',
            ],
            'customers.interaction_results' => [
                'vi' => 'Kết quả tương tác',
                'en' => 'Interaction Results',
            ],
            'customers.interaction_result' => [
                'vi' => 'Kết quả tương tác',
                'en' => 'Interaction Result',
            ],
            'customers.sources' => [
                'vi' => 'Nguồn khách hàng',
                'en' => 'Customer Sources',
            ],
            'customers.source' => [
                'vi' => 'Nguồn khách hàng',
                'en' => 'Customer Source',
            ],
            
            // Common fields
            'common.code' => [
                'vi' => 'Mã',
                'en' => 'Code',
            ],
            'common.code_placeholder' => [
                'vi' => 'Nhập mã (tự động nếu để trống)',
                'en' => 'Enter code (auto-generated if empty)',
            ],
            'common.code_hint' => [
                'vi' => 'Để trống để tự động tạo từ tên',
                'en' => 'Leave empty to auto-generate from name',
            ],
            'common.color' => [
                'vi' => 'Màu sắc',
                'en' => 'Color',
            ],
            'common.icon' => [
                'vi' => 'Biểu tượng',
                'en' => 'Icon',
            ],
            'common.select_icon' => [
                'vi' => 'Chọn biểu tượng',
                'en' => 'Select Icon',
            ],
            'common.is_active' => [
                'vi' => 'Đang hoạt động',
                'en' => 'Is Active',
            ],
            'common.sort_order' => [
                'vi' => 'Thứ tự sắp xếp',
                'en' => 'Sort Order',
            ],
        ];

        $count = 0;
        foreach ($translations as $key => $values) {
            list($group, $translationKey) = explode('.', $key, 2);
            
            foreach ($languages as $language) {
                $langCode = $language->code;
                if (isset($values[$langCode])) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'group' => $group,
                            'key' => $translationKey,
                        ],
                        [
                            'value' => $values[$langCode],
                        ]
                    );
                    $count++;
                }
            }
        }

        echo "✅ Customer Settings Translations seeded successfully!\n";
        echo "   - Total: {$count} translations\n";
    }
}
