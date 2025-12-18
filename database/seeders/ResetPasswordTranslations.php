<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class ResetPasswordTranslations extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $langs = Language::all();

        $translations = [
            'users' => [
                'reset_password' => ['en' => 'Reset Password', 'vi' => 'Reset Mật Khẩu'],
                'reset_type' => ['en' => 'Reset Type', 'vi' => 'Loại Reset'],
                'reset_to_default' => ['en' => 'Reset to default password', 'vi' => 'Reset về mật khẩu mặc định'],
                'set_custom_password' => ['en' => 'Set custom password', 'vi' => 'Đặt mật khẩu tùy chỉnh'],
                'new_password' => ['en' => 'New Password', 'vi' => 'Mật Khẩu Mới'],
                'enter_new_password' => ['en' => 'Enter new password', 'vi' => 'Nhập mật khẩu mới'],
                'password_min_length' => ['en' => 'Password must be at least 6 characters', 'vi' => 'Mật khẩu phải có ít nhất 6 ký tự'],
                'please_enter_password' => ['en' => 'Please enter a password', 'vi' => 'Vui lòng nhập mật khẩu'],
                'default_password_info' => ['en' => 'Default Password Rules', 'vi' => 'Quy Tắc Mật Khẩu Mặc Định'],
                'default_password_rule_1' => ['en' => 'For students: Last 6 characters of student code', 'vi' => 'Với học viên: 6 ký tự cuối của mã học viên'],
                'default_password_rule_2' => ['en' => 'For users with phone: Last 6 digits of phone number', 'vi' => 'Với user có SĐT: 6 số cuối của số điện thoại'],
                'default_password_rule_3' => ['en' => 'Fallback: 123456', 'vi' => 'Mặc định: 123456'],
            ],
            'common' => [
                'processing' => ['en' => 'Processing...', 'vi' => 'Đang xử lý...'],
                'warning' => ['en' => 'Warning', 'vi' => 'Cảnh Báo'],
                'ok' => ['en' => 'OK', 'vi' => 'Đồng Ý'],
                'error_occurred' => ['en' => 'An error occurred', 'vi' => 'Đã xảy ra lỗi'],
            ],
        ];

        foreach ($translations as $group => $keys) {
            foreach ($keys as $key => $values) {
                foreach ($langs as $lang) {
                    Translation::updateOrCreate(
                        ['language_id' => $lang->id, 'group' => $group, 'key' => $key],
                        ['value' => $values[$lang->code] ?? $values['en']]
                    );
                    $this->command->info("✓ Translation: {$group}.{$key} ({$lang->code})");
                }
            }
        }

        $this->command->info("\n✅ Reset Password translations seeded successfully!");
    }
}
