<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class BranchTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');

        $translations = [
            'branches.title' => [
                'vi' => 'Chi nhánh',
                'en' => 'Branches',
            ],
            'branches.list' => [
                'vi' => 'Danh sách chi nhánh',
                'en' => 'Branch List',
            ],
            'branches.create' => [
                'vi' => 'Tạo chi nhánh',
                'en' => 'Create Branch',
            ],
            'branches.edit' => [
                'vi' => 'Sửa chi nhánh',
                'en' => 'Edit Branch',
            ],
            'branches.delete' => [
                'vi' => 'Xóa chi nhánh',
                'en' => 'Delete Branch',
            ],
            'branches.name' => [
                'vi' => 'Tên chi nhánh',
                'en' => 'Branch Name',
            ],
            'branches.code' => [
                'vi' => 'Mã chi nhánh',
                'en' => 'Branch Code',
            ],
            'branches.address' => [
                'vi' => 'Địa chỉ',
                'en' => 'Address',
            ],
            'branches.phone' => [
                'vi' => 'Số điện thoại',
                'en' => 'Phone',
            ],
            'branches.email' => [
                'vi' => 'Email',
                'en' => 'Email',
            ],
            'branches.manager' => [
                'vi' => 'Quản lý',
                'en' => 'Manager',
            ],
            'branches.status' => [
                'vi' => 'Trạng thái',
                'en' => 'Status',
            ],
            'branches.active' => [
                'vi' => 'Hoạt động',
                'en' => 'Active',
            ],
            'branches.inactive' => [
                'vi' => 'Không hoạt động',
                'en' => 'Inactive',
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

        echo "\n✅ Branch translations seeded successfully!\n";
    }
}
