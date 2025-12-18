<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SalesTranslationsAdditional extends Seeder
{
    public function run(): void
    {
        $langs = Language::all();
        
        $additionalTranslations = [
            'products' => [
                'type_course' => ['en' => 'Course', 'vi' => 'Khóa học'],
                'type_package' => ['en' => 'Package', 'vi' => 'Gói combo'],
                'type_material' => ['en' => 'Material', 'vi' => 'Tài liệu'],
                'type_service' => ['en' => 'Service', 'vi' => 'Dịch vụ'],
            ],
            'common' => [
                'inactive' => ['en' => 'Inactive', 'vi' => 'Không hoạt động'],
                'status' => ['en' => 'Status', 'vi' => 'Trạng thái'],
                'value' => ['en' => 'Value', 'vi' => 'Giá trị'],
                'amount' => ['en' => 'Amount', 'vi' => 'Số tiền'],
            ],
        ];

        foreach ($additionalTranslations as $group => $keys) {
            foreach ($keys as $key => $values) {
                foreach ($langs as $lang) {
                    $value = $values[$lang->code] ?? $values['en'];
                    Translation::updateOrCreate(
                        [
                            'language_id' => $lang->id,
                            'group' => $group,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
                $this->command->info("✓ {$group}.{$key}");
            }
        }

        $this->command->info("\n✅ Additional translations added!");
    }
}

