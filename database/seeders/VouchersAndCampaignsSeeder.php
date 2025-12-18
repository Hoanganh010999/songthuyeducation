<?php

namespace Database\Seeders;

use App\Models\Voucher;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class VouchersAndCampaignsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $createdBy = $admin?->id ?? 1;

        // ========================================
        // VOUCHERS
        // ========================================
        $vouchers = [
            [
                'code' => 'WELCOME2025',
                'name' => 'Voucher Chào Mừng 2025',
                'description' => 'Giảm 15% cho khách hàng mới đăng ký lần đầu',
                'type' => 'percentage',
                'value' => 15,
                'max_discount_amount' => 1000000,
                'min_order_amount' => 3000000,
                'usage_limit' => 100,
                'usage_per_customer' => 1,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'is_auto_apply' => false,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'SUMMER500K',
                'name' => 'Giảm 500K Mùa Hè',
                'description' => 'Giảm cố định 500,000đ cho đơn hàng từ 5 triệu',
                'type' => 'fixed_amount',
                'value' => 500000,
                'min_order_amount' => 5000000,
                'usage_limit' => 50,
                'usage_per_customer' => 1,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addMonth(),
                'is_active' => true,
                'is_auto_apply' => false,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'VIP20',
                'name' => 'Voucher VIP 20%',
                'description' => 'Dành riêng cho khách hàng VIP, giảm 20% không giới hạn',
                'type' => 'percentage',
                'value' => 20,
                'usage_limit' => null,
                'usage_per_customer' => 5,
                'start_date' => Carbon::now()->subMonth(),
                'end_date' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'is_auto_apply' => false,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'TRIAL10',
                'name' => 'Giảm 10% Sau Học Thử',
                'description' => 'Dành cho khách hàng đăng ký sau khi học thử',
                'type' => 'percentage',
                'value' => 10,
                'max_discount_amount' => 500000,
                'min_order_amount' => 2000000,
                'usage_limit' => 200,
                'usage_per_customer' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'is_active' => true,
                'is_auto_apply' => false,
                'created_by' => $createdBy,
            ],
        ];

        foreach ($vouchers as $voucherData) {
            Voucher::create($voucherData);
            $this->command->info("✓ Voucher: {$voucherData['code']}");
        }

        // ========================================
        // CAMPAIGNS
        // ========================================
        $campaigns = [
            [
                'code' => 'BLACKFRIDAY2025',
                'name' => 'Black Friday 2025',
                'description' => 'Khuyến mãi lớn Black Friday - Giảm 30% cho tất cả khóa học',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'max_discount_amount' => 2000000,
                'min_order_amount' => 4000000,
                'start_date' => Carbon::parse('2025-11-20'),
                'end_date' => Carbon::parse('2025-11-30'),
                'priority' => 10,
                'is_active' => true,
                'is_auto_apply' => true,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'NEWYEAR2026',
                'name' => 'Chào Năm Mới 2026',
                'description' => 'Giảm giá chào đón năm mới - 25% cho tất cả sản phẩm',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'max_discount_amount' => 1500000,
                'start_date' => Carbon::parse('2025-12-25'),
                'end_date' => Carbon::parse('2026-01-10'),
                'priority' => 9,
                'is_active' => true,
                'is_auto_apply' => true,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'FLASHSALE',
                'name' => 'Flash Sale Cuối Tuần',
                'description' => 'Flash sale cuối tuần - Giảm cố định 1 triệu cho đơn từ 10 triệu',
                'discount_type' => 'fixed_amount',
                'discount_value' => 1000000,
                'min_order_amount' => 10000000,
                'start_date' => Carbon::now()->next('Saturday'),
                'end_date' => Carbon::now()->next('Sunday')->endOfDay(),
                'priority' => 8,
                'total_usage_limit' => 30,
                'is_active' => true,
                'is_auto_apply' => true,
                'created_by' => $createdBy,
            ],
            [
                'code' => 'EARLYBIRD',
                'name' => 'Ưu Đãi Đăng Ký Sớm',
                'description' => 'Giảm 20% cho khách hàng đăng ký trước 1/12',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'max_discount_amount' => 1200000,
                'min_order_amount' => 3000000,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::parse('2025-12-01'),
                'priority' => 7,
                'is_active' => true,
                'is_auto_apply' => true,
                'created_by' => $createdBy,
            ],
        ];

        foreach ($campaigns as $campaignData) {
            Campaign::create($campaignData);
            $this->command->info("✓ Campaign: {$campaignData['code']}");
        }

        $this->command->info("\n✅ Vouchers & Campaigns seeded successfully!");
    }
}

