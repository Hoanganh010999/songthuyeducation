<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerInteractionType;
use App\Models\CustomerInteractionResult;
use App\Models\CustomerSource;

class CustomerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Interaction Types
        $interactionTypes = [
            ['name' => 'Gọi điện', 'code' => 'phone_call', 'icon' => 'phone', 'color' => '#3B82F6', 'sort_order' => 1],
            ['name' => 'Email', 'code' => 'email', 'icon' => 'envelope', 'color' => '#8B5CF6', 'sort_order' => 2],
            ['name' => 'SMS', 'code' => 'sms', 'icon' => 'message', 'color' => '#10B981', 'sort_order' => 3],
            ['name' => 'Gặp mặt', 'code' => 'meeting', 'icon' => 'users', 'color' => '#F59E0B', 'sort_order' => 4],
            ['name' => 'Zalo', 'code' => 'zalo', 'icon' => 'comment', 'color' => '#0068FF', 'sort_order' => 5],
            ['name' => 'Facebook', 'code' => 'facebook', 'icon' => 'facebook', 'color' => '#1877F2', 'sort_order' => 6],
            ['name' => 'Tư vấn trực tiếp', 'code' => 'walk_in', 'icon' => 'store', 'color' => '#EF4444', 'sort_order' => 7],
        ];

        foreach ($interactionTypes as $type) {
            CustomerInteractionType::create($type);
        }

        // Interaction Results
        $interactionResults = [
            ['name' => 'Thành công', 'code' => 'success', 'icon' => 'check-circle', 'color' => '#10B981', 'sort_order' => 1],
            ['name' => 'Không liên lạc được', 'code' => 'no_contact', 'icon' => 'phone-slash', 'color' => '#EF4444', 'sort_order' => 2],
            ['name' => 'Hẹn gặp lại', 'code' => 'scheduled', 'icon' => 'calendar', 'color' => '#3B82F6', 'sort_order' => 3],
            ['name' => 'Từ chối', 'code' => 'rejected', 'icon' => 'times-circle', 'color' => '#DC2626', 'sort_order' => 4],
            ['name' => 'Đang cân nhắc', 'code' => 'considering', 'icon' => 'clock', 'color' => '#F59E0B', 'sort_order' => 5],
            ['name' => 'Yêu cầu thông tin thêm', 'code' => 'need_info', 'icon' => 'info-circle', 'color' => '#8B5CF6', 'sort_order' => 6],
            ['name' => 'Không quan tâm', 'code' => 'not_interested', 'icon' => 'ban', 'color' => '#6B7280', 'sort_order' => 7],
        ];

        foreach ($interactionResults as $result) {
            CustomerInteractionResult::create($result);
        }

        // Customer Sources
        $customerSources = [
            ['name' => 'Facebook', 'code' => 'facebook', 'icon' => 'facebook', 'color' => '#1877F2', 'sort_order' => 1],
            ['name' => 'Google', 'code' => 'google', 'icon' => 'google', 'color' => '#EA4335', 'sort_order' => 2],
            ['name' => 'Zalo', 'code' => 'zalo', 'icon' => 'comment', 'color' => '#0068FF', 'sort_order' => 3],
            ['name' => 'Giới thiệu', 'code' => 'referral', 'icon' => 'user-friends', 'color' => '#10B981', 'sort_order' => 4],
            ['name' => 'Walk-in', 'code' => 'walk_in', 'icon' => 'walking', 'color' => '#F59E0B', 'sort_order' => 5],
            ['name' => 'Website', 'code' => 'website', 'icon' => 'globe', 'color' => '#3B82F6', 'sort_order' => 6],
            ['name' => 'Hotline', 'code' => 'hotline', 'icon' => 'phone', 'color' => '#8B5CF6', 'sort_order' => 7],
            ['name' => 'Sự kiện', 'code' => 'event', 'icon' => 'calendar-star', 'color' => '#EC4899', 'sort_order' => 8],
            ['name' => 'Khác', 'code' => 'other', 'icon' => 'ellipsis-h', 'color' => '#6B7280', 'sort_order' => 99],
        ];

        foreach ($customerSources as $source) {
            CustomerSource::create($source);
        }

        echo "✅ Customer Settings seeded successfully!\n";
        echo "   - " . count($interactionTypes) . " Interaction Types\n";
        echo "   - " . count($interactionResults) . " Interaction Results\n";
        echo "   - " . count($customerSources) . " Customer Sources\n";
    }
}
