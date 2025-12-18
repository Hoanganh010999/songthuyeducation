<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SalesTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::all();

        $translations = [
            // Products translations
            'products' => [
                'en' => [
                    'title' => 'Products',
                    'list' => 'Product List',
                    'create' => 'Create Product',
                    'edit' => 'Edit Product',
                    'delete' => 'Delete Product',
                    'code' => 'Code',
                    'name' => 'Product Name',
                    'type' => 'Type',
                    'category' => 'Category',
                    'price' => 'Price',
                    'sale_price' => 'Sale Price',
                    'duration' => 'Duration',
                    'total_sessions' => 'Total Sessions',
                    'description' => 'Description',
                    'active' => 'Active',
                    'featured' => 'Featured',
                    'confirm_delete' => 'Are you sure you want to delete this product?',
                    'created_success' => 'Product created successfully',
                    'updated_success' => 'Product updated successfully',
                    'deleted_success' => 'Product deleted successfully',
                ],
                'vi' => [
                    'title' => 'Sản phẩm',
                    'list' => 'Danh sách sản phẩm',
                    'create' => 'Tạo sản phẩm',
                    'edit' => 'Sửa sản phẩm',
                    'delete' => 'Xóa sản phẩm',
                    'code' => 'Mã',
                    'name' => 'Tên sản phẩm',
                    'type' => 'Loại',
                    'category' => 'Danh mục',
                    'price' => 'Giá',
                    'sale_price' => 'Giá khuyến mãi',
                    'duration' => 'Thời gian',
                    'total_sessions' => 'Tổng số buổi',
                    'description' => 'Mô tả',
                    'active' => 'Hoạt động',
                    'featured' => 'Nổi bật',
                    'confirm_delete' => 'Bạn có chắc muốn xóa sản phẩm này?',
                    'created_success' => 'Tạo sản phẩm thành công',
                    'updated_success' => 'Cập nhật sản phẩm thành công',
                    'deleted_success' => 'Xóa sản phẩm thành công',
                ],
            ],

            // Enrollments translations
            'enrollments' => [
                'en' => [
                    'title' => 'Enrollments',
                    'list' => 'Enrollment List',
                    'create' => 'Create Enrollment',
                    'create_from_customer' => 'Create Enrollment',
                    'edit' => 'Edit Enrollment',
                    'delete' => 'Cancel Enrollment',
                    'detail' => 'Enrollment Details',
                    'code' => 'Code',
                    'customer' => 'Customer',
                    'student' => 'Student',
                    'product' => 'Product',
                    'status' => 'Status',
                    'status_pending' => 'Pending Payment',
                    'status_paid' => 'Paid',
                    'status_active' => 'Active',
                    'status_completed' => 'Completed',
                    'status_cancelled' => 'Cancelled',
                    'original_price' => 'Original Price',
                    'discount' => 'Discount',
                    'final_price' => 'Final Price',
                    'paid_amount' => 'Paid Amount',
                    'remaining_amount' => 'Remaining Amount',
                    'payment_method' => 'Payment Method',
                    'payment_cash' => 'Cash',
                    'payment_bank' => 'Bank Transfer',
                    'payment_card' => 'Card',
                    'payment_wallet' => 'E-Wallet',
                    'confirm_payment' => 'Confirm Payment',
                    'select_student' => 'Select Student',
                    'select_product' => 'Select Product',
                    'apply_voucher' => 'Apply Voucher',
                    'apply_campaign' => 'Apply Campaign',
                    'voucher_code' => 'Voucher Code',
                    'campaign' => 'Campaign',
                    'price_summary' => 'Price Summary',
                    'student_self' => 'Self',
                    'student_child' => 'Child',
                    'total_orders' => 'Total Orders',
                    'statistics' => 'Statistics',
                    'created_success' => 'Enrollment created successfully',
                    'payment_success' => 'Payment confirmed successfully',
                    'cancelled_success' => 'Enrollment cancelled successfully',
                ],
                'vi' => [
                    'title' => 'Đăng ký học',
                    'list' => 'Danh sách đăng ký',
                    'create' => 'Tạo đơn đăng ký',
                    'create_from_customer' => 'Chốt đơn',
                    'edit' => 'Sửa đơn đăng ký',
                    'delete' => 'Hủy đơn',
                    'detail' => 'Chi tiết đăng ký',
                    'code' => 'Mã đơn',
                    'customer' => 'Khách hàng',
                    'student' => 'Học viên',
                    'product' => 'Sản phẩm',
                    'status' => 'Trạng thái',
                    'status_pending' => 'Chờ thanh toán',
                    'status_paid' => 'Đã thanh toán',
                    'status_active' => 'Đang học',
                    'status_completed' => 'Hoàn thành',
                    'status_cancelled' => 'Đã hủy',
                    'original_price' => 'Giá gốc',
                    'discount' => 'Giảm giá',
                    'final_price' => 'Thành tiền',
                    'paid_amount' => 'Đã thanh toán',
                    'remaining_amount' => 'Còn lại',
                    'payment_method' => 'Phương thức thanh toán',
                    'payment_cash' => 'Tiền mặt',
                    'payment_bank' => 'Chuyển khoản',
                    'payment_card' => 'Thẻ',
                    'payment_wallet' => 'Ví điện tử',
                    'confirm_payment' => 'Xác nhận thanh toán',
                    'select_student' => 'Chọn học viên',
                    'select_product' => 'Chọn sản phẩm',
                    'apply_voucher' => 'Áp dụng voucher',
                    'apply_campaign' => 'Áp dụng chiến dịch',
                    'voucher_code' => 'Mã giảm giá',
                    'campaign' => 'Chiến dịch',
                    'price_summary' => 'Chi tiết giá',
                    'student_self' => 'Chính họ',
                    'student_child' => 'Con',
                    'total_orders' => 'Tổng đơn',
                    'statistics' => 'Thống kê',
                    'created_success' => 'Tạo đơn đăng ký thành công',
                    'payment_success' => 'Xác nhận thanh toán thành công',
                    'cancelled_success' => 'Đã hủy đơn',
                ],
            ],

            // Vouchers translations
            'vouchers' => [
                'en' => [
                    'title' => 'Vouchers',
                    'list' => 'Voucher List',
                    'create' => 'Create Voucher',
                    'code' => 'Code',
                    'name' => 'Name',
                    'type' => 'Type',
                    'value' => 'Value',
                    'apply' => 'Apply',
                    'invalid' => 'Invalid voucher',
                    'applied_success' => 'Voucher applied successfully',
                ],
                'vi' => [
                    'title' => 'Mã giảm giá',
                    'list' => 'Danh sách voucher',
                    'create' => 'Tạo voucher',
                    'code' => 'Mã',
                    'name' => 'Tên',
                    'type' => 'Loại',
                    'value' => 'Giá trị',
                    'apply' => 'Áp dụng',
                    'invalid' => 'Mã không hợp lệ',
                    'applied_success' => 'Áp dụng voucher thành công',
                ],
            ],

            // Campaigns translations
            'campaigns' => [
                'en' => [
                    'title' => 'Campaigns',
                    'list' => 'Campaign List',
                    'create' => 'Create Campaign',
                    'name' => 'Name',
                    'type' => 'Type',
                    'value' => 'Value',
                ],
                'vi' => [
                    'title' => 'Chiến dịch KM',
                    'list' => 'Danh sách chiến dịch',
                    'create' => 'Tạo chiến dịch',
                    'name' => 'Tên',
                    'type' => 'Loại',
                    'value' => 'Giá trị',
                ],
            ],

            // Wallets translations
            'wallets' => [
                'en' => [
                    'title' => 'Wallets',
                    'balance' => 'Balance',
                    'deposit' => 'Deposit',
                    'withdraw' => 'Withdraw',
                    'transactions' => 'Transactions',
                    'balance_after' => 'Balance after deposit',
                ],
                'vi' => [
                    'title' => 'Ví',
                    'balance' => 'Số dư',
                    'deposit' => 'Nạp tiền',
                    'withdraw' => 'Rút tiền',
                    'transactions' => 'Giao dịch',
                    'balance_after' => 'Số dư sau khi nạp',
                ],
            ],

            // Sales menu
            'sales' => [
                'en' => [
                    'title' => 'Sales',
                    'menu' => 'Sales',
                ],
                'vi' => [
                    'title' => 'Bán hàng',
                    'menu' => 'Bán hàng',
                ],
            ],
        ];

        foreach ($translations as $group => $languageTranslations) {
            foreach ($languages as $language) {
                $langCode = $language->code;
                
                if (!isset($languageTranslations[$langCode])) {
                    continue;
                }

                foreach ($languageTranslations[$langCode] as $key => $value) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'group' => $group,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }

                $this->command->info("✓ {$group} translations for {$langCode}: " . count($languageTranslations[$langCode]) . " keys");
            }
        }

        $this->command->info("\n✅ Sales translations seeded successfully!");
    }
}

