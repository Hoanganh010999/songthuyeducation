<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SalesSecondaryMenuTranslations extends Seeder
{
    public function run(): void
    {
        $langs = Language::all();
        
        $translations = [
            'sales' => [
                'description' => ['en' => 'Customer & Campaign Management', 'vi' => 'Quản lý khách hàng và chiến dịch'],
                'settings_description' => ['en' => 'Interactions & Sources', 'vi' => 'Tương tác & nguồn KH'],
            ],
            'enrollments' => [
                'description' => ['en' => 'Paid & pending verification', 'vi' => 'Đã đóng tiền chờ verify'],
            ],
            'campaigns' => [
                'description' => ['en' => 'Discounts, gifts...', 'vi' => 'Giảm giá, tặng quà...'],
            ],
            'vouchers' => [
                'description' => ['en' => 'Discount codes', 'vi' => 'Mã giảm giá'],
            ],
        ];

        foreach ($translations as $group => $keys) {
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

        $this->command->info("\n✅ Sales secondary menu translations added!");
    }
}

