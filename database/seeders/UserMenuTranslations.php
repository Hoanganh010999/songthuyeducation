<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class UserMenuTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            'auth.logout' => [
                'vi' => 'Đăng xuất',
                'en' => 'Logout',
            ],
            'wallets.balance' => [
                'vi' => 'Số dư',
                'en' => 'Balance',
            ],
            'wallets.children_balances' => [
                'vi' => 'Số dư của các con',
                'en' => 'Children Balances',
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

        echo "\n✅ User menu translations seeded successfully!\n";
    }
}

