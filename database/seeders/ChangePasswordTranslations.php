<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class ChangePasswordTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            // Change password modal
            'auth.change_password' => [
                'vi' => 'Đổi mật khẩu',
                'en' => 'Change Password',
            ],
            'auth.current_password' => [
                'vi' => 'Mật khẩu hiện tại',
                'en' => 'Current Password',
            ],
            'auth.new_password' => [
                'vi' => 'Mật khẩu mới',
                'en' => 'New Password',
            ],
            'auth.confirm_password' => [
                'vi' => 'Xác nhận mật khẩu',
                'en' => 'Confirm Password',
            ],
            'auth.password_not_match' => [
                'vi' => 'Mật khẩu xác nhận không khớp',
                'en' => 'Password confirmation does not match',
            ],
            'auth.password_match' => [
                'vi' => 'Mật khẩu khớp',
                'en' => 'Password matches',
            ],
            'auth.password_changed' => [
                'vi' => 'Đã đổi mật khẩu thành công',
                'en' => 'Password changed successfully',
            ],
            'auth.current_password_incorrect' => [
                'vi' => 'Mật khẩu hiện tại không đúng',
                'en' => 'Current password is incorrect',
            ],
            'auth.password_requirements' => [
                'vi' => 'Yêu cầu mật khẩu',
                'en' => 'Password Requirements',
            ],
            'auth.min_6_characters' => [
                'vi' => 'Mật khẩu phải có ít nhất 6 ký tự',
                'en' => 'Password must be at least 6 characters',
            ],
            'auth.use_mix_characters' => [
                'vi' => 'Nên sử dụng kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt',
                'en' => 'Use a mix of uppercase, lowercase, numbers and special characters',
            ],
            'auth.do_not_share_password' => [
                'vi' => 'Không chia sẻ mật khẩu với bất kỳ ai',
                'en' => 'Do not share your password with anyone',
            ],
            'auth.password_strength_weak' => [
                'vi' => 'Yếu',
                'en' => 'Weak',
            ],
            'auth.password_strength_medium' => [
                'vi' => 'Trung bình',
                'en' => 'Medium',
            ],
            'auth.password_strength_strong' => [
                'vi' => 'Mạnh',
                'en' => 'Strong',
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

        echo "\n✅ Change password translations seeded successfully!\n";
    }
}

