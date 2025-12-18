<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class EnrollmentsAdditionalTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            // Customer/Enrollment Form
            'enrollments.customer_info' => [
                'vi' => 'Thông tin khách hàng',
                'en' => 'Customer Information',
            ],
            'enrollments.select_student_first' => [
                'vi' => 'Vui lòng chọn sản phẩm trước',
                'en' => 'Please select a product first',
            ],
            'enrollments.price_details' => [
                'vi' => 'Chi tiết giá',
                'en' => 'Price Details',
            ],
            'enrollments.student_child' => [
                'vi' => 'Con',
                'en' => 'Child',
            ],
            'enrollments.student_self' => [
                'vi' => 'Chính họ',
                'en' => 'Self',
            ],
            'enrollments.description' => [
                'vi' => 'Đăng ký học',
                'en' => 'Enrollments',
            ],
            'enrollments.total_orders' => [
                'vi' => 'Tổng đơn',
                'en' => 'Total Orders',
            ],
            'enrollments.confirm_delete_text' => [
                'vi' => 'Bạn có chắc muốn xóa đăng ký này?',
                'en' => 'Are you sure you want to delete this enrollment?',
            ],
            'enrollments.deleted_success' => [
                'vi' => 'Đã xóa đăng ký thành công',
                'en' => 'Enrollment deleted successfully',
            ],
            'enrollments.created_success' => [
                'vi' => 'Đơn đăng ký đã được tạo thành công',
                'en' => 'Enrollment created successfully',
            ],
            'enrollments.create_from_customer' => [
                'vi' => 'Chốt đơn',
                'en' => 'Create Order',
            ],
            
            // Payment Modal
            'enrollments.payment_amount' => [
                'vi' => 'Số tiền thanh toán',
                'en' => 'Payment Amount',
            ],
            'enrollments.pay_full' => [
                'vi' => 'Thanh toán toàn bộ',
                'en' => 'Pay Full',
            ],
            'enrollments.wallet_deposit_notice' => [
                'vi' => 'Hệ thống sẽ tạo/nạp tiền vào ví của học viên',
                'en' => 'System will create/deposit money to student wallet',
            ],
            'enrollments.wallet_balance_after' => [
                'vi' => 'Số dư ví sau khi nạp',
                'en' => 'Wallet balance after deposit',
            ],
            'enrollments.status_change_notice' => [
                'vi' => 'Đơn hàng sẽ chuyển sang trạng thái "Đã thanh toán" → "Đang học"',
                'en' => 'Order status will change to "Paid" → "Active"',
            ],
            'enrollments.payment_success' => [
                'vi' => 'Thanh toán thành công! Tiền đã được nạp vào ví của học viên',
                'en' => 'Payment successful! Money has been deposited to student wallet',
            ],
            
            // Common additions
            'common.name' => [
                'vi' => 'Tên',
                'en' => 'Name',
            ],
            'common.phone' => [
                'vi' => 'Số điện thoại',
                'en' => 'Phone',
            ],
            'common.email' => [
                'vi' => 'Email',
                'en' => 'Email',
            ],
            'common.branch' => [
                'vi' => 'Chi nhánh',
                'en' => 'Branch',
            ],
            'common.years_old' => [
                'vi' => 'tuổi',
                'en' => 'years old',
            ],
            'common.months' => [
                'vi' => 'tháng',
                'en' => 'months',
            ],
            'common.optional' => [
                'vi' => 'tùy chọn',
                'en' => 'optional',
            ],
            'common.no_apply' => [
                'vi' => 'Không áp dụng',
                'en' => 'No Apply',
            ],
            'common.ok' => [
                'vi' => 'OK',
                'en' => 'OK',
            ],
            'common.processing' => [
                'vi' => 'Đang xử lý...',
                'en' => 'Processing...',
            ],
            'common.total' => [
                'vi' => 'Tổng tiền',
                'en' => 'Total',
            ],
            'common.value' => [
                'vi' => 'Giá trị',
                'en' => 'Value',
            ],
            
            // Sales
            'sales.settings_description' => [
                'vi' => 'Cài đặt bán hàng',
                'en' => 'Sales Settings',
            ],
            
            // Vouchers additional
            'vouchers.enter_code' => [
                'vi' => 'Nhập mã voucher...',
                'en' => 'Enter voucher code...',
            ],
            'vouchers.description' => [
                'vi' => 'Quản lý voucher',
                'en' => 'Voucher Management',
            ],
            'vouchers.per_customer' => [
                'vi' => 'Mỗi khách',
                'en' => 'Per Customer',
            ],
            'vouchers.no_expiry' => [
                'vi' => 'Không giới hạn',
                'en' => 'No Expiry',
            ],
            'vouchers.confirm_delete' => [
                'vi' => 'Bạn có chắc muốn xóa voucher này?',
                'en' => 'Are you sure you want to delete this voucher?',
            ],
            'vouchers.deleted_success' => [
                'vi' => 'Đã xóa voucher thành công',
                'en' => 'Voucher deleted successfully',
            ],
            
            // Campaigns additional
            'campaigns.description' => [
                'vi' => 'Chiến dịch khuyến mãi',
                'en' => 'Promotion Campaigns',
            ],
            'campaigns.confirm_delete' => [
                'vi' => 'Bạn có chắc muốn xóa chiến dịch này?',
                'en' => 'Are you sure you want to delete this campaign?',
            ],
            'campaigns.deleted_success' => [
                'vi' => 'Đã xóa chiến dịch thành công',
                'en' => 'Campaign deleted successfully',
            ],
            'campaigns.updated_success' => [
                'vi' => 'Đã cập nhật chiến dịch thành công',
                'en' => 'Campaign updated successfully',
            ],
            'campaigns.created_success' => [
                'vi' => 'Đã tạo chiến dịch thành công',
                'en' => 'Campaign created successfully',
            ],
            
            // Products additional
            'products.confirm_delete' => [
                'vi' => 'Bạn có chắc muốn xóa sản phẩm này?',
                'en' => 'Are you sure you want to delete this product?',
            ],
            'products.deleted_success' => [
                'vi' => 'Đã xóa sản phẩm thành công',
                'en' => 'Product deleted successfully',
            ],
            'products.updated_success' => [
                'vi' => 'Đã cập nhật sản phẩm thành công',
                'en' => 'Product updated successfully',
            ],
            'products.created_success' => [
                'vi' => 'Đã tạo sản phẩm thành công',
                'en' => 'Product created successfully',
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

        echo "\n✅ Additional enrollment translations seeded successfully!\n";
    }
}
