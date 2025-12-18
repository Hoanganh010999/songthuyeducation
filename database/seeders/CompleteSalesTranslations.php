<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

/**
 * Complete Sales Module Translations
 * Bổ sung các translations còn thiếu cho module Sales
 */
class CompleteSalesTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all();

        $translations = [
            // Sales module - Bổ sung description
            'sales' => [
                'en' => [
                    'title' => 'Sales',
                    'menu' => 'Sales',
                    'description' => 'Manage sales and customer relations',
                    'settings' => 'Sales Settings',
                    'settings_description' => 'System settings',
                    'settings_subtitle' => 'Manage interaction types, results and customer sources',
                    'interaction_types' => 'Interaction Types',
                    'interaction_results' => 'Interaction Results',
                    'customer_sources' => 'Customer Sources',
                ],
                'vi' => [
                    'title' => 'Bán hàng',
                    'menu' => 'Bán hàng',
                    'description' => 'Quản lý bán hàng và quan hệ khách hàng',
                    'settings' => 'Cài đặt Bán hàng',
                    'settings_description' => 'Cấu hình hệ thống',
                    'settings_subtitle' => 'Quản lý loại tương tác, kết quả và nguồn khách hàng',
                    'interaction_types' => 'Loại tương tác',
                    'interaction_results' => 'Kết quả tương tác',
                    'customer_sources' => 'Nguồn khách hàng',
                ],
            ],

            // Products - Bổ sung description
            'products' => [
                'en' => [
                    'title' => 'Products',
                    'list' => 'Product Catalog',
                    'description' => 'Courses & services',
                    'create' => 'Create Product',
                    'name' => 'Product Name',
                    'code' => 'Product Code',
                    'price' => 'Price',
                    'sale_price' => 'Sale Price',
                    'active' => 'Active',
                    'featured' => 'Featured',
                    'duration' => 'Duration',
                    'type' => 'Type',
                    'type_course' => 'Course',
                    'type_package' => 'Package',
                    'type_material' => 'Material',
                    'type_service' => 'Service',
                    'category' => 'Category',
                    'total_sessions' => 'Total Sessions',
                    'confirm_delete' => 'Are you sure you want to delete this product?',
                    'deleted_success' => 'Product deleted successfully',
                ],
                'vi' => [
                    'title' => 'Sản phẩm',
                    'list' => 'Danh mục sản phẩm',
                    'description' => 'Khóa học & dịch vụ',
                    'create' => 'Tạo Sản Phẩm',
                    'name' => 'Tên Sản Phẩm',
                    'code' => 'Mã Sản Phẩm',
                    'price' => 'Giá',
                    'sale_price' => 'Giá khuyến mãi',
                    'active' => 'Đang Hoạt Động',
                    'featured' => 'Nổi bật',
                    'duration' => 'Thời lượng',
                    'type' => 'Loại',
                    'type_course' => 'Khóa học',
                    'type_package' => 'Gói học',
                    'type_material' => 'Tài liệu',
                    'type_service' => 'Dịch vụ',
                    'category' => 'Danh mục',
                    'total_sessions' => 'Số buổi học',
                    'confirm_delete' => 'Bạn có chắc muốn xóa sản phẩm này?',
                    'deleted_success' => 'Xóa sản phẩm thành công',
                ],
            ],

            // Enrollments - Bổ sung description
            'enrollments' => [
                'en' => [
                    'title' => 'Enrollments',
                    'description' => 'Course registrations',
                    'list' => 'Enrollment List',
                    'create' => 'Create Enrollment',
                    'create_from_customer' => 'Create Enrollment',
                    'total_orders' => 'Total Orders',
                    'customer' => 'Customer',
                    'customer_info' => 'Customer Information',
                    'student' => 'Student',
                    'student_child' => 'Child',
                    'student_self' => 'Self',
                    'select_student' => 'Select Student',
                    'for_self' => 'For Self',
                    'for_child' => 'For Child',
                    'product' => 'Product',
                    'select_product' => 'Select Product',
                    'status' => 'Status',
                    'status_pending' => 'Pending',
                    'status_paid' => 'Paid',
                    'status_active' => 'Active',
                    'status_approved' => 'Approved',
                    'status_completed' => 'Completed',
                    'status_cancelled' => 'Cancelled',
                    'price_details' => 'Price Details',
                    'original_price' => 'Original Price',
                    'discount' => 'Discount',
                    'final_price' => 'Final Price',
                    'confirm_delete_text' => 'Are you sure you want to delete this enrollment?',
                    'deleted_success' => 'Enrollment deleted successfully',
                    'created_success' => 'Enrollment created successfully',
                ],
                'vi' => [
                    'title' => 'Đăng Ký Học',
                    'description' => 'Đơn đăng ký khóa học',
                    'list' => 'Danh sách đăng ký',
                    'create' => 'Tạo Đơn Đăng Ký',
                    'create_from_customer' => 'Tạo Đơn Đăng Ký',
                    'total_orders' => 'Tổng đơn hàng',
                    'customer' => 'Khách hàng',
                    'customer_info' => 'Thông tin khách hàng',
                    'student' => 'Học viên',
                    'student_child' => 'Con',
                    'student_self' => 'Chính mình',
                    'select_student' => 'Chọn học viên',
                    'for_self' => 'Cho chính mình',
                    'for_child' => 'Cho con',
                    'product' => 'Sản phẩm',
                    'select_product' => 'Chọn sản phẩm',
                    'status' => 'Trạng thái',
                    'status_pending' => 'Chờ xử lý',
                    'status_paid' => 'Đã thanh toán',
                    'status_active' => 'Đang học',
                    'status_approved' => 'Đã duyệt',
                    'status_completed' => 'Hoàn thành',
                    'status_cancelled' => 'Đã hủy',
                    'price_details' => 'Chi tiết giá',
                    'original_price' => 'Giá gốc',
                    'discount' => 'Giảm giá',
                    'final_price' => 'Giá cuối cùng',
                    'confirm_delete_text' => 'Bạn có chắc muốn xóa đơn đăng ký này?',
                    'deleted_success' => 'Xóa đơn đăng ký thành công',
                    'created_success' => 'Tạo đơn đăng ký thành công',
                ],
            ],

            // Campaigns - Bổ sung description
            'campaigns' => [
                'en' => [
                    'title' => 'Sales Campaigns',
                    'description' => 'Promotions & offers',
                    'create' => 'Create Campaign',
                    'name' => 'Campaign Name',
                    'code' => 'Campaign Code',
                    'discount' => 'Discount',
                    'discount_percentage' => 'Discount Percentage',
                    'discount_type' => 'Discount Type',
                    'type_percentage' => 'Percentage',
                    'type_fixed' => 'Fixed Amount',
                    'period' => 'Period',
                    'start_date' => 'Start Date',
                    'end_date' => 'End Date',
                    'max_discount' => 'Max Discount',
                    'max_discount_hint' => 'Maximum discount amount (for percentage type)',
                    'min_order' => 'Minimum Order',
                    'min_order_hint' => 'Minimum order value required',
                ],
                'vi' => [
                    'title' => 'Chiến Dịch Sales',
                    'description' => 'Khuyến mãi & ưu đãi',
                    'create' => 'Tạo chiến dịch',
                    'name' => 'Tên chiến dịch',
                    'code' => 'Mã chiến dịch',
                    'discount' => 'Giảm giá',
                    'discount_percentage' => 'Phần trăm giảm giá',
                    'discount_type' => 'Loại giảm giá',
                    'type_percentage' => 'Phần trăm',
                    'type_fixed' => 'Số tiền cố định',
                    'period' => 'Thời gian',
                    'start_date' => 'Ngày bắt đầu',
                    'end_date' => 'Ngày kết thúc',
                    'max_discount' => 'Giảm tối đa',
                    'max_discount_hint' => 'Số tiền giảm tối đa (cho loại phần trăm)',
                    'min_order' => 'Đơn hàng tối thiểu',
                    'min_order_hint' => 'Giá trị đơn hàng tối thiểu yêu cầu',
                ],
            ],

            // Vouchers - Bổ sung description
            'vouchers' => [
                'en' => [
                    'title' => 'Vouchers',
                    'description' => 'Discount codes',
                    'create' => 'Create Voucher',
                    'name' => 'Voucher Name',
                    'code' => 'Voucher Code',
                    'discount' => 'Discount',
                    'discount_percentage' => 'Discount Percentage',
                    'discount_type' => 'Discount Type',
                    'type_percentage' => 'Percentage',
                    'type_fixed' => 'Fixed Amount',
                    'usage' => 'Usage Limit',
                    'usage_limit' => 'Usage Limit',
                    'usage_limit_hint' => 'Total usage limit (leave blank for unlimited)',
                    'user_usage_limit' => 'Per User Limit',
                    'user_limit_hint' => 'Usage limit per user',
                    'start_date' => 'Start Date',
                    'end_date' => 'End Date',
                    'expiry' => 'Expiry Date',
                    'expiry_hint' => 'Voucher expiration date',
                    'max_discount' => 'Max Discount',
                    'min_order' => 'Minimum Order',
                    'enter_code' => 'Enter voucher code',
                    'apply' => 'Apply',
                ],
                'vi' => [
                    'title' => 'Vouchers',
                    'description' => 'Mã giảm giá',
                    'create' => 'Tạo Voucher',
                    'name' => 'Tên Voucher',
                    'code' => 'Mã Voucher',
                    'discount' => 'Giảm giá',
                    'discount_percentage' => 'Phần trăm giảm giá',
                    'discount_type' => 'Loại giảm giá',
                    'type_percentage' => 'Phần trăm',
                    'type_fixed' => 'Số tiền cố định',
                    'usage' => 'Giới hạn sử dụng',
                    'usage_limit' => 'Giới hạn sử dụng',
                    'usage_limit_hint' => 'Tổng số lần sử dụng (để trống = không giới hạn)',
                    'user_usage_limit' => 'Giới hạn mỗi người',
                    'user_limit_hint' => 'Số lần sử dụng tối đa mỗi người',
                    'start_date' => 'Ngày bắt đầu',
                    'end_date' => 'Ngày kết thúc',
                    'expiry' => 'Ngày hết hạn',
                    'expiry_hint' => 'Ngày hết hạn của voucher',
                    'max_discount' => 'Giảm tối đa',
                    'min_order' => 'Đơn hàng tối thiểu',
                    'enter_code' => 'Nhập mã voucher',
                    'apply' => 'Áp dụng',
                    'per_customer' => 'Mỗi khách hàng',
                ],
            ],
            
            // Common translations
            'common' => [
                'en' => [
                    'all' => 'All',
                    'optional' => 'Optional',
                    'no_apply' => 'Not Applied',
                    'notes' => 'Notes',
                    'name' => 'Name',
                    'phone' => 'Phone',
                    'email' => 'Email',
                    'branch' => 'Branch',
                    'months' => 'Months',
                    'value' => 'Value',
                    'add_new' => 'Add New',
                ],
                'vi' => [
                    'all' => 'Tất cả',
                    'optional' => 'Tùy chọn',
                    'no_apply' => 'Không áp dụng',
                    'notes' => 'Ghi chú',
                    'name' => 'Tên',
                    'phone' => 'Số điện thoại',
                    'email' => 'Email',
                    'branch' => 'Chi nhánh',
                    'months' => 'Tháng',
                    'value' => 'Giá trị',
                    'add_new' => 'Thêm mới',
                ],
            ],
            
            // HR translations (placeholder để tránh lỗi translation group not found)
            'hr' => [
                'en' => [
                    'title' => 'Human Resources',
                    'menu' => 'HR',
                ],
                'vi' => [
                    'title' => 'Nhân Sự',
                    'menu' => 'Nhân Sự',
                ],
            ],

            // Customers
            'customers' => [
                'en' => [
                    'title' => 'Customers',
                    'list' => 'Customer List',
                ],
                'vi' => [
                    'title' => 'Khách Hàng',
                    'list' => 'Danh sách khách hàng',
                ],
            ],

            // Branches translations
            'branches' => [
                'en' => [
                    'title' => 'Branches',
                    'list' => 'Branch List',
                    'name' => 'Branch Name',
                    'code' => 'Branch Code',
                    'address' => 'Address',
                    'phone' => 'Phone',
                    'manager' => 'Manager',
                    'status' => 'Status',
                ],
                'vi' => [
                    'title' => 'Chi nhánh',
                    'list' => 'Danh sách chi nhánh',
                    'name' => 'Tên chi nhánh',
                    'code' => 'Mã chi nhánh',
                    'address' => 'Địa chỉ',
                    'phone' => 'Số điện thoại',
                    'manager' => 'Quản lý',
                    'status' => 'Trạng thái',
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
                    
                    $this->command->info("✓ {$group}.{$key} ({$langCode})");
                }
            }
        }

        $this->command->info("\n✅ Complete Sales translations seeded successfully!");
    }
}

