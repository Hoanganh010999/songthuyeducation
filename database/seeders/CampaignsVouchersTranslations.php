<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class CampaignsVouchersTranslations extends Seeder
{
    public function run(): void
    {
        $langs = Language::all();
        
        $translations = [
            // Campaigns additional
            'campaigns' => [
                'code' => ['en' => 'Campaign Code', 'vi' => 'Mã chiến dịch'],
                'discount_type' => ['en' => 'Discount Type', 'vi' => 'Loại giảm giá'],
                'discount' => ['en' => 'Discount', 'vi' => 'Giảm giá'],
                'discount_percentage' => ['en' => 'Discount Percentage (%)', 'vi' => 'Phần trăm giảm (%)'],
                'discount_amount' => ['en' => 'Discount Amount', 'vi' => 'Số tiền giảm'],
                'type_percentage' => ['en' => 'Percentage', 'vi' => 'Phần trăm'],
                'type_fixed' => ['en' => 'Fixed Amount', 'vi' => 'Số tiền cố định'],
                'period' => ['en' => 'Period', 'vi' => 'Thời gian'],
                'start_date' => ['en' => 'Start Date', 'vi' => 'Ngày bắt đầu'],
                'end_date' => ['en' => 'End Date', 'vi' => 'Ngày kết thúc'],
                'max_discount' => ['en' => 'Max Discount Amount', 'vi' => 'Giảm tối đa'],
                'max_discount_hint' => ['en' => 'Maximum discount for percentage type', 'vi' => 'Giảm tối đa cho loại %'],
                'min_order' => ['en' => 'Min Order Amount', 'vi' => 'Đơn tối thiểu'],
                'min_order_hint' => ['en' => 'Minimum order to apply', 'vi' => 'Đơn hàng tối thiểu để áp dụng'],
                'confirm_delete' => ['en' => 'Delete this campaign?', 'vi' => 'Xóa chiến dịch này?'],
                'created_success' => ['en' => 'Campaign created', 'vi' => 'Tạo chiến dịch thành công'],
                'updated_success' => ['en' => 'Campaign updated', 'vi' => 'Cập nhật chiến dịch thành công'],
                'deleted_success' => ['en' => 'Campaign deleted', 'vi' => 'Xóa chiến dịch thành công'],
            ],
            
            // Vouchers additional
            'vouchers' => [
                'discount_type' => ['en' => 'Discount Type', 'vi' => 'Loại giảm giá'],
                'discount' => ['en' => 'Discount', 'vi' => 'Giảm giá'],
                'discount_percentage' => ['en' => 'Discount Percentage (%)', 'vi' => 'Phần trăm giảm (%)'],
                'discount_amount' => ['en' => 'Discount Amount', 'vi' => 'Số tiền giảm'],
                'type_percentage' => ['en' => 'Percentage', 'vi' => 'Phần trăm'],
                'type_fixed' => ['en' => 'Fixed Amount', 'vi' => 'Số tiền cố định'],
                'max_discount' => ['en' => 'Max Discount Amount', 'vi' => 'Giảm tối đa'],
                'min_order' => ['en' => 'Min Order Amount', 'vi' => 'Đơn tối thiểu'],
                'usage_type' => ['en' => 'Usage Type', 'vi' => 'Loại sử dụng'],
                'usage' => ['en' => 'Usage', 'vi' => 'Sử dụng'],
                'usage_single' => ['en' => 'Single Use', 'vi' => 'Dùng 1 lần'],
                'usage_multiple' => ['en' => 'Multiple Use', 'vi' => 'Dùng nhiều lần'],
                'usage_limit' => ['en' => 'Usage Limit', 'vi' => 'Giới hạn sử dụng'],
                'usage_limit_hint' => ['en' => 'Total times can be used', 'vi' => 'Tổng số lần có thể dùng'],
                'user_usage_limit' => ['en' => 'User Usage Limit', 'vi' => 'Giới hạn/người'],
                'user_limit_hint' => ['en' => 'Times per user', 'vi' => 'Số lần mỗi người dùng'],
                'per_customer' => ['en' => 'per customer', 'vi' => 'mỗi khách'],
                'start_date' => ['en' => 'Start Date', 'vi' => 'Ngày bắt đầu'],
                'end_date' => ['en' => 'End Date', 'vi' => 'Ngày kết thúc'],
                'expiry' => ['en' => 'Expiry', 'vi' => 'Hết hạn'],
                'expiry_date' => ['en' => 'Expiry Date', 'vi' => 'Ngày hết hạn'],
                'expiry_hint' => ['en' => 'Leave empty for no expiry', 'vi' => 'Để trống nếu không hết hạn'],
                'no_expiry' => ['en' => 'No expiry', 'vi' => 'Không hết hạn'],
                'confirm_delete' => ['en' => 'Delete this voucher?', 'vi' => 'Xóa voucher này?'],
                'created_success' => ['en' => 'Voucher created', 'vi' => 'Tạo voucher thành công'],
                'updated_success' => ['en' => 'Voucher updated', 'vi' => 'Cập nhật voucher thành công'],
                'deleted_success' => ['en' => 'Voucher deleted', 'vi' => 'Xóa voucher thành công'],
            ],
            
            // Common additional
            'common' => [
                'active' => ['en' => 'Active', 'vi' => 'Hoạt động'],
                'notes' => ['en' => 'Notes', 'vi' => 'Ghi chú'],
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

        $this->command->info("\n✅ Campaigns & Vouchers translations added!");
    }
}

