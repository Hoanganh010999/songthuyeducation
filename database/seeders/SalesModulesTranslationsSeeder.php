<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SalesModulesTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $vietnamese = Language::where('code', 'vi')->first();
        $english = Language::where('code', 'en')->first();

        if (!$vietnamese || !$english) {
            $this->command->error('Languages not found! Run LanguagesSeeder first.');
            return;
        }

        $translations = [
            // ==============================================
            // PRODUCTS MODULE
            // ==============================================
            'products.title' => [
                'vi' => 'Sản Phẩm',
                'en' => 'Products',
            ],
            'products.list' => [
                'vi' => 'Danh Sách Sản Phẩm',
                'en' => 'Product List',
            ],
            'products.create' => [
                'vi' => 'Tạo Sản Phẩm',
                'en' => 'Create Product',
            ],
            'products.edit' => [
                'vi' => 'Sửa Sản Phẩm',
                'en' => 'Edit Product',
            ],
            'products.code' => [
                'vi' => 'Mã Sản Phẩm',
                'en' => 'Product Code',
            ],
            'products.name' => [
                'vi' => 'Tên Sản Phẩm',
                'en' => 'Product Name',
            ],
            'products.type' => [
                'vi' => 'Loại',
                'en' => 'Type',
            ],
            'products.price' => [
                'vi' => 'Giá Gốc',
                'en' => 'Original Price',
            ],
            'products.sale_price' => [
                'vi' => 'Giá Khuyến Mãi',
                'en' => 'Sale Price',
            ],
            'products.current_price' => [
                'vi' => 'Giá Hiện Tại',
                'en' => 'Current Price',
            ],
            'products.category' => [
                'vi' => 'Danh Mục',
                'en' => 'Category',
            ],
            'products.duration' => [
                'vi' => 'Thời Hạn',
                'en' => 'Duration',
            ],
            'products.total_sessions' => [
                'vi' => 'Tổng Số Buổi',
                'en' => 'Total Sessions',
            ],
            'products.price_per_session' => [
                'vi' => 'Giá/Buổi',
                'en' => 'Price per Session',
            ],
            'products.featured' => [
                'vi' => 'Nổi Bật',
                'en' => 'Featured',
            ],
            'products.active' => [
                'vi' => 'Đang Hoạt Động',
                'en' => 'Active',
            ],

            // ==============================================
            // VOUCHERS MODULE
            // ==============================================
            'vouchers.title' => [
                'vi' => 'Voucher',
                'en' => 'Vouchers',
            ],
            'vouchers.list' => [
                'vi' => 'Danh Sách Voucher',
                'en' => 'Voucher List',
            ],
            'vouchers.code' => [
                'vi' => 'Mã Voucher',
                'en' => 'Voucher Code',
            ],
            'vouchers.enter_code' => [
                'vi' => 'Nhập mã voucher',
                'en' => 'Enter voucher code',
            ],
            'vouchers.apply' => [
                'vi' => 'Áp Dụng',
                'en' => 'Apply',
            ],
            'vouchers.discount_type' => [
                'vi' => 'Loại Giảm Giá',
                'en' => 'Discount Type',
            ],
            'vouchers.discount_value' => [
                'vi' => 'Giá Trị Giảm',
                'en' => 'Discount Value',
            ],
            'vouchers.min_order' => [
                'vi' => 'Đơn Tối Thiểu',
                'en' => 'Minimum Order',
            ],
            'vouchers.usage_limit' => [
                'vi' => 'Giới Hạn Sử Dụng',
                'en' => 'Usage Limit',
            ],
            'vouchers.valid_until' => [
                'vi' => 'Có Hiệu Lực Đến',
                'en' => 'Valid Until',
            ],
            'vouchers.select_voucher' => [
                'vi' => 'Chọn Voucher',
                'en' => 'Select Voucher',
            ],
            'vouchers.no_vouchers' => [
                'vi' => 'Không có voucher khả dụng',
                'en' => 'No vouchers available',
            ],

            // ==============================================
            // CAMPAIGNS MODULE
            // ==============================================
            'campaigns.title' => [
                'vi' => 'Chiến Dịch',
                'en' => 'Campaigns',
            ],
            'campaigns.list' => [
                'vi' => 'Danh Sách Chiến Dịch',
                'en' => 'Campaign List',
            ],
            'campaigns.active' => [
                'vi' => 'Đang Diễn Ra',
                'en' => 'Active',
            ],
            'campaigns.upcoming' => [
                'vi' => 'Sắp Diễn Ra',
                'en' => 'Upcoming',
            ],
            'campaigns.auto_applied' => [
                'vi' => 'Tự động áp dụng',
                'en' => 'Auto applied',
            ],

            // ==============================================
            // ENROLLMENTS MODULE
            // ==============================================
            'enrollments.title' => [
                'vi' => 'Đơn Đăng Ký',
                'en' => 'Enrollments',
            ],
            'enrollments.list' => [
                'vi' => 'Danh Sách Đơn',
                'en' => 'Enrollment List',
            ],
            'enrollments.create' => [
                'vi' => 'Chốt Đơn',
                'en' => 'Create Order',
            ],
            'enrollments.code' => [
                'vi' => 'Mã Đơn',
                'en' => 'Order Code',
            ],
            'enrollments.student' => [
                'vi' => 'Học Viên',
                'en' => 'Student',
            ],
            'enrollments.select_student' => [
                'vi' => 'Chọn người học',
                'en' => 'Select student',
            ],
            'enrollments.for_self' => [
                'vi' => 'Cho chính khách hàng',
                'en' => 'For customer',
            ],
            'enrollments.for_child' => [
                'vi' => 'Cho con',
                'en' => 'For child',
            ],
            'enrollments.select_product' => [
                'vi' => 'Chọn sản phẩm',
                'en' => 'Select product',
            ],
            'enrollments.original_price' => [
                'vi' => 'Giá Gốc',
                'en' => 'Original Price',
            ],
            'enrollments.discount' => [
                'vi' => 'Giảm Giá',
                'en' => 'Discount',
            ],
            'enrollments.final_price' => [
                'vi' => 'Thành Tiền',
                'en' => 'Final Price',
            ],
            'enrollments.paid_amount' => [
                'vi' => 'Đã Thanh Toán',
                'en' => 'Paid Amount',
            ],
            'enrollments.remaining_amount' => [
                'vi' => 'Còn Lại',
                'en' => 'Remaining',
            ],
            'enrollments.status_pending' => [
                'vi' => 'Chờ Duyệt',
                'en' => 'Pending Approval',
            ],
            'enrollments.status_approved' => [
                'vi' => 'Đã Duyệt',
                'en' => 'Approved',
            ],
            'enrollments.status_paid' => [
                'vi' => 'Đã Thanh Toán',
                'en' => 'Paid',
            ],
            'enrollments.status_active' => [
                'vi' => 'Đang Học',
                'en' => 'Active',
            ],
            'enrollments.status_completed' => [
                'vi' => 'Hoàn Thành',
                'en' => 'Completed',
            ],
            'enrollments.status_cancelled' => [
                'vi' => 'Đã Hủy',
                'en' => 'Cancelled',
            ],
            'enrollments.confirm_payment' => [
                'vi' => 'Xác Nhận Thanh Toán',
                'en' => 'Confirm Payment',
            ],
            'enrollments.payment_method' => [
                'vi' => 'Phương Thức',
                'en' => 'Payment Method',
            ],
            'enrollments.payment_cash' => [
                'vi' => 'Tiền Mặt',
                'en' => 'Cash',
            ],
            'enrollments.payment_bank' => [
                'vi' => 'Chuyển Khoản',
                'en' => 'Bank Transfer',
            ],
            'enrollments.payment_card' => [
                'vi' => 'Thẻ',
                'en' => 'Card',
            ],
            'enrollments.payment_wallet' => [
                'vi' => 'Ví Điện Tử',
                'en' => 'E-Wallet',
            ],

            // ==============================================
            // WALLETS MODULE
            // ==============================================
            'wallets.title' => [
                'vi' => 'Ví Tiền',
                'en' => 'Wallets',
            ],
            'wallets.balance' => [
                'vi' => 'Số Dư',
                'en' => 'Balance',
            ],
            'wallets.total_deposited' => [
                'vi' => 'Tổng Nạp',
                'en' => 'Total Deposited',
            ],
            'wallets.total_spent' => [
                'vi' => 'Tổng Chi',
                'en' => 'Total Spent',
            ],
            'wallets.transactions' => [
                'vi' => 'Lịch Sử Giao Dịch',
                'en' => 'Transaction History',
            ],
            'wallets.deposit' => [
                'vi' => 'Nạp Tiền',
                'en' => 'Deposit',
            ],
            'wallets.withdraw' => [
                'vi' => 'Rút Tiền',
                'en' => 'Withdraw',
            ],
            'wallets.refund' => [
                'vi' => 'Hoàn Tiền',
                'en' => 'Refund',
            ],
            'wallets.locked' => [
                'vi' => 'Đã Khóa',
                'en' => 'Locked',
            ],

            // ==============================================
            // COMMON
            // ==============================================
            'common.save' => [
                'vi' => 'Lưu',
                'en' => 'Save',
            ],
            'common.cancel' => [
                'vi' => 'Hủy',
                'en' => 'Cancel',
            ],
            'common.close' => [
                'vi' => 'Đóng',
                'en' => 'Close',
            ],
            'common.success' => [
                'vi' => 'Thành công',
                'en' => 'Success',
            ],
            'common.error' => [
                'vi' => 'Lỗi',
                'en' => 'Error',
            ],
            'common.search' => [
                'vi' => 'Tìm kiếm',
                'en' => 'Search',
            ],
            'common.filter' => [
                'vi' => 'Lọc',
                'en' => 'Filter',
            ],
            'common.all' => [
                'vi' => 'Tất cả',
                'en' => 'All',
            ],
            'common.actions' => [
                'vi' => 'Thao Tác',
                'en' => 'Actions',
            ],
            'common.notes' => [
                'vi' => 'Ghi Chú',
                'en' => 'Notes',
            ],
        ];

        foreach ($translations as $key => $values) {
            foreach ($values as $langCode => $value) {
                $language = $langCode === 'vi' ? $vietnamese : $english;

                Translation::updateOrCreate(
                    [
                        'language_id' => $language->id,
                        'group' => explode('.', $key)[0],
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }

            $this->command->info("✓ Translation: {$key}");
        }

        $this->command->info("\n✅ Sales modules translations seeded successfully!");
    }
}

