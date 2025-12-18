<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class CustomersTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $en = Language::where('code', 'en')->first();
        $vi = Language::where('code', 'vi')->first();

        if (!$en || !$vi) {
            $this->command->error('Languages not found. Please run LanguageSeeder first.');
            return;
        }

        // Customers translations
        $customersEn = [
            'title' => 'Customers',
            'list' => 'Customer List',
            'create' => 'Create Customer',
            'edit' => 'Edit Customer',
            'view_detail' => 'View Detail',
            'kanban' => 'Sales Pipeline',
            'list_view' => 'List View',
            'kanban_view' => 'Kanban View',
            
            // Form fields
            'code' => 'Customer Code',
            'name' => 'Full Name',
            'name_placeholder' => 'Enter customer name',
            'phone' => 'Phone Number',
            'phone_placeholder' => 'Enter phone number',
            'email' => 'Email',
            'email_placeholder' => 'Enter email address',
            'date_of_birth' => 'Date of Birth',
            'gender' => 'Gender',
            'gender_male' => 'Male',
            'gender_female' => 'Female',
            'gender_other' => 'Other',
            
            // Address
            'address' => 'Address',
            'address_placeholder' => 'Enter address',
            'city' => 'City',
            'city_placeholder' => 'Select city',
            'district' => 'District',
            'district_placeholder' => 'Enter district',
            'ward' => 'Ward',
            'ward_placeholder' => 'Enter ward',
            
            // Sales info
            'source' => 'Source',
            'source_placeholder' => 'e.g., Facebook, Google, Referral',
            'branch' => 'Branch',
            'branch_placeholder' => 'Select branch',
            'branch_auto' => 'Auto-assigned to your branch',
            'assigned_to' => 'Assigned To',
            'assigned_to_placeholder' => 'Select staff',
            'notes' => 'Notes',
            'notes_placeholder' => 'Enter notes about customer',
            'estimated_value' => 'Estimated Value',
            'estimated_value_placeholder' => 'Enter estimated value',
            'expected_close_date' => 'Expected Close Date',
            'closed_at' => 'Closed Date',
            
            // Stages
            'stage' => 'Stage',
            'stage_lead' => 'Lead',
            'stage_contacted' => 'Contacted',
            'stage_qualified' => 'Qualified',
            'stage_proposal' => 'Proposal Sent',
            'stage_negotiation' => 'Negotiation',
            'stage_closed_won' => 'Closed Won',
            'stage_closed_lost' => 'Closed Lost',
            
            // Actions
            'move_to_stage' => 'Move to Stage',
            'change_stage' => 'Change Stage',
            
            // Statistics
            'total_customers' => 'Total Customers',
            'total_value' => 'Total Value',
            'won_value' => 'Won Value',
            'conversion_rate' => 'Conversion Rate',
            
            // Messages
            'created_success' => 'Customer created successfully',
            'updated_success' => 'Customer updated successfully',
            'deleted_success' => 'Customer deleted successfully',
            'stage_moved_success' => 'Stage changed successfully',
            'no_branch_error' => 'You are not assigned to any branch',
            'confirm_delete' => 'Are you sure you want to delete this customer?',
            
            // Filters
            'filter_by_stage' => 'Filter by Stage',
            'filter_by_branch' => 'Filter by Branch',
            'filter_by_assigned' => 'Filter by Assigned',
            'all_stages' => 'All Stages',
            'all_branches' => 'All Branches',
            'all_staff' => 'All Staff',
        ];

        $customersVi = [
            'title' => 'Khách Hàng',
            'list' => 'Danh Sách Khách Hàng',
            'create' => 'Tạo Khách Hàng',
            'edit' => 'Chỉnh Sửa Khách Hàng',
            'view_detail' => 'Xem Chi Tiết',
            'kanban' => 'Quy Trình Bán Hàng',
            'list_view' => 'Dạng Danh Sách',
            'kanban_view' => 'Dạng Kanban',
            
            // Form fields
            'code' => 'Mã Khách Hàng',
            'name' => 'Họ Tên',
            'name_placeholder' => 'Nhập tên khách hàng',
            'phone' => 'Số Điện Thoại',
            'phone_placeholder' => 'Nhập số điện thoại',
            'email' => 'Email',
            'email_placeholder' => 'Nhập địa chỉ email',
            'date_of_birth' => 'Ngày Sinh',
            'gender' => 'Giới Tính',
            'gender_male' => 'Nam',
            'gender_female' => 'Nữ',
            'gender_other' => 'Khác',
            
            // Address
            'address' => 'Địa Chỉ',
            'address_placeholder' => 'Nhập địa chỉ',
            'city' => 'Thành Phố',
            'city_placeholder' => 'Chọn thành phố',
            'district' => 'Quận/Huyện',
            'district_placeholder' => 'Nhập quận/huyện',
            'ward' => 'Phường/Xã',
            'ward_placeholder' => 'Nhập phường/xã',
            
            // Sales info
            'source' => 'Nguồn',
            'source_placeholder' => 'VD: Facebook, Google, Giới thiệu',
            'branch' => 'Chi Nhánh',
            'branch_placeholder' => 'Chọn chi nhánh',
            'branch_auto' => 'Tự động gán vào chi nhánh của bạn',
            'assigned_to' => 'Người Phụ Trách',
            'assigned_to_placeholder' => 'Chọn nhân viên',
            'notes' => 'Ghi Chú',
            'notes_placeholder' => 'Nhập ghi chú về khách hàng',
            'estimated_value' => 'Giá Trị Dự Kiến',
            'estimated_value_placeholder' => 'Nhập giá trị dự kiến',
            'expected_close_date' => 'Ngày Dự Kiến Chốt',
            'closed_at' => 'Ngày Chốt',
            
            // Stages
            'stage' => 'Giai Đoạn',
            'stage_lead' => 'Khách Tiềm Năng',
            'stage_contacted' => 'Đã Liên Hệ',
            'stage_qualified' => 'Đủ Điều Kiện',
            'stage_proposal' => 'Đã Gửi Đề Xuất',
            'stage_negotiation' => 'Đang Đàm Phán',
            'stage_closed_won' => 'Chốt Thành Công',
            'stage_closed_lost' => 'Mất Khách',
            
            // Actions
            'move_to_stage' => 'Chuyển Giai Đoạn',
            'change_stage' => 'Đổi Giai Đoạn',
            
            // Statistics
            'total_customers' => 'Tổng Khách Hàng',
            'total_value' => 'Tổng Giá Trị',
            'won_value' => 'Giá Trị Chốt',
            'conversion_rate' => 'Tỷ Lệ Chuyển Đổi',
            
            // Messages
            'created_success' => 'Tạo khách hàng thành công',
            'updated_success' => 'Cập nhật khách hàng thành công',
            'deleted_success' => 'Xóa khách hàng thành công',
            'stage_moved_success' => 'Chuyển giai đoạn thành công',
            'no_branch_error' => 'Bạn chưa được gán vào chi nhánh nào',
            'confirm_delete' => 'Bạn có chắc chắn muốn xóa khách hàng này?',
            
            // Filters
            'filter_by_stage' => 'Lọc theo Giai Đoạn',
            'filter_by_branch' => 'Lọc theo Chi Nhánh',
            'filter_by_assigned' => 'Lọc theo Người Phụ Trách',
            'all_stages' => 'Tất Cả Giai Đoạn',
            'all_branches' => 'Tất Cả Chi Nhánh',
            'all_staff' => 'Tất Cả Nhân Viên',
        ];

        foreach ($customersEn as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $en->id, 'group' => 'customers', 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($customersVi as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $vi->id, 'group' => 'customers', 'key' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('✅ Customers translations added successfully!');
    }
}
