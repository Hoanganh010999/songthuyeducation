<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;

class AttendanceFeeTranslations extends Seeder
{
    public function run(): void
    {
        $translations = [
            // Settings
            'quality.settings' => ['vi' => 'Cài đặt', 'en' => 'Settings'],
            'quality.settings_description' => ['vi' => 'Cấu hình hệ thống', 'en' => 'System Configuration'],
            
            // Attendance Fee Policy
            'attendance_fee.title' => ['vi' => 'Chính sách trừ học phí', 'en' => 'Attendance Fee Policy'],
            'attendance_fee.policies' => ['vi' => 'Các chính sách', 'en' => 'Policies'],
            'attendance_fee.create_policy' => ['vi' => 'Tạo chính sách mới', 'en' => 'Create New Policy'],
            'attendance_fee.edit_policy' => ['vi' => 'Chỉnh sửa chính sách', 'en' => 'Edit Policy'],
            'attendance_fee.policy_name' => ['vi' => 'Tên chính sách', 'en' => 'Policy Name'],
            'attendance_fee.description' => ['vi' => 'Mô tả', 'en' => 'Description'],
            'attendance_fee.is_active' => ['vi' => 'Đang áp dụng', 'en' => 'Active'],
            'attendance_fee.activate' => ['vi' => 'Kích hoạt', 'en' => 'Activate'],
            'attendance_fee.unexcused_absence' => ['vi' => 'Vắng không lý do', 'en' => 'Unexcused Absence'],
            'attendance_fee.absence_unexcused_percent' => ['vi' => '% Trừ khi vắng không lý do', 'en' => '% Deduct for Unexcused Absence'],
            'attendance_fee.absence_consecutive_threshold' => ['vi' => 'Số buổi liên tiếp mới trừ', 'en' => 'Consecutive Threshold'],
            'attendance_fee.excused_absence' => ['vi' => 'Vắng có lý do', 'en' => 'Excused Absence'],
            'attendance_fee.absence_excused_free_limit' => ['vi' => 'Số buổi miễn phí/tháng', 'en' => 'Free Limit per Month'],
            'attendance_fee.absence_excused_percent' => ['vi' => '% Trừ khi vượt giới hạn', 'en' => '% Deduct Over Limit'],
            'attendance_fee.late' => ['vi' => 'Đi trễ', 'en' => 'Late'],
            'attendance_fee.late_deduct_percent' => ['vi' => '% Trừ khi đi trễ', 'en' => '% Deduct for Late'],
            'attendance_fee.late_grace_minutes' => ['vi' => 'Cho phép trễ (phút)', 'en' => 'Grace Minutes'],
            'attendance_fee.policy_created' => ['vi' => 'Tạo chính sách thành công', 'en' => 'Policy created successfully'],
            'attendance_fee.policy_updated' => ['vi' => 'Cập nhật chính sách thành công', 'en' => 'Policy updated successfully'],
            'attendance_fee.policy_deleted' => ['vi' => 'Xóa chính sách thành công', 'en' => 'Policy deleted successfully'],
            'attendance_fee.policy_activated' => ['vi' => 'Kích hoạt chính sách thành công', 'en' => 'Policy activated successfully'],
            'attendance_fee.no_policies' => ['vi' => 'Chưa có chính sách nào', 'en' => 'No policies yet'],
            'attendance_fee.confirm_delete' => ['vi' => 'Bạn có chắc muốn xóa chính sách này?', 'en' => 'Are you sure you want to delete this policy?'],
            'attendance_fee.cannot_delete_active' => ['vi' => 'Không thể xóa chính sách đang áp dụng', 'en' => 'Cannot delete active policy'],
        ];

        foreach ($translations as $key => $values) {
            [$group, $translationKey] = explode('.', $key, 2);
            
            // Vietnamese
            Translation::updateOrCreate(
                ['group' => $group, 'key' => $translationKey, 'locale' => 'vi'],
                ['value' => $values['vi']]
            );
            $this->command->info("✓ {$key} (vi)");
            
            // English
            Translation::updateOrCreate(
                ['group' => $group, 'key' => $translationKey, 'locale' => 'en'],
                ['value' => $values['en']]
            );
            $this->command->info("✓ {$key} (en)");
        }

        $this->command->info("\n✅ Attendance Fee translations seeded successfully!");
    }
}

