<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class AccountingTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            // Module
            'accounting.title' => [
                'vi' => 'Kế Toán',
                'en' => 'Accounting',
            ],
            'accounting.description' => [
                'vi' => 'Quản lý thu chi, kế hoạch tài chính',
                'en' => 'Manage income, expenses, and financial planning',
            ],
            
            // Dashboard
            'accounting.total_income' => [
                'vi' => 'Tổng Thu',
                'en' => 'Total Income',
            ],
            'accounting.total_expense' => [
                'vi' => 'Tổng Chi',
                'en' => 'Total Expense',
            ],
            'accounting.balance' => [
                'vi' => 'Số Dư',
                'en' => 'Balance',
            ],
            
            // Tabs
            'accounting.account_items' => [
                'vi' => 'Định Khoản',
                'en' => 'Account Items',
            ],
            'accounting.financial_plans' => [
                'vi' => 'Kế Hoạch Thu Chi',
                'en' => 'Financial Plans',
            ],
            'accounting.expense_proposals' => [
                'vi' => 'Đề Xuất Chi',
                'en' => 'Expense Proposals',
            ],
            'accounting.income_reports' => [
                'vi' => 'Báo Thu',
                'en' => 'Income Reports',
            ],
            'accounting.transactions' => [
                'vi' => 'Giao Dịch',
                'en' => 'Transactions',
            ],
            
            // Actions
            'accounting.create' => [
                'vi' => 'Tạo mới',
                'en' => 'Create',
            ],
            'accounting.edit' => [
                'vi' => 'Chỉnh sửa',
                'en' => 'Edit',
            ],
            'accounting.delete' => [
                'vi' => 'Xóa',
                'en' => 'Delete',
            ],
            'accounting.approve' => [
                'vi' => 'Duyệt',
                'en' => 'Approve',
            ],
            'accounting.reject' => [
                'vi' => 'Từ chối',
                'en' => 'Reject',
            ],
            
            // Status
            'accounting.status.draft' => [
                'vi' => 'Nháp',
                'en' => 'Draft',
            ],
            'accounting.status.pending' => [
                'vi' => 'Chờ duyệt',
                'en' => 'Pending',
            ],
            'accounting.status.approved' => [
                'vi' => 'Đã duyệt',
                'en' => 'Approved',
            ],
            'accounting.status.rejected' => [
                'vi' => 'Đã từ chối',
                'en' => 'Rejected',
            ],
            
            // Sidebar
            'accounting.dashboard' => [
                'vi' => 'Tổng Quan',
                'en' => 'Dashboard',
            ],
            'accounting.overview' => [
                'vi' => 'Xem tổng quan tài chính',
                'en' => 'View financial overview',
            ],
            'accounting.account_setup' => [
                'vi' => 'Định Khoản',
                'en' => 'Account Setup',
            ],
            'accounting.manage_categories' => [
                'vi' => 'Quản lý danh mục và khoản mục',
                'en' => 'Manage categories and items',
            ],
            'accounting.categories' => [
                'vi' => 'Danh Mục',
                'en' => 'Categories',
            ],
            'accounting.proposals_reports' => [
                'vi' => 'Đề Xuất & Báo Thu',
                'en' => 'Proposals & Reports',
            ],
            'accounting.expense_income' => [
                'vi' => 'Quản lý đề xuất chi và báo thu',
                'en' => 'Manage expense proposals and income reports',
            ],
            'accounting.approve_transactions' => [
                'vi' => 'Duyệt Thu Chi',
                'en' => 'Approve Transactions',
            ],
            'accounting.official_records' => [
                'vi' => 'Ghi nhận chính thức',
                'en' => 'Official records',
            ],
            'accounting.reports' => [
                'vi' => 'Báo Cáo',
                'en' => 'Reports',
            ],
            'accounting.analysis_reports' => [
                'vi' => 'Báo cáo và phân tích',
                'en' => 'Analysis and reports',
            ],
            'accounting.financial_report' => [
                'vi' => 'Báo Cáo Tài Chính',
                'en' => 'Financial Report',
            ],
            'accounting.cash_flow' => [
                'vi' => 'Dòng Tiền',
                'en' => 'Cash Flow',
            ],
            'accounting.quarterly_monthly' => [
                'vi' => 'Kế hoạch theo quý/tháng',
                'en' => 'Quarterly/monthly plans',
            ],
            'accounting.more_coming' => [
                'vi' => 'Thêm tính năng sắp ra mắt',
                'en' => 'More features coming soon',
            ],
            
            // Dashboard
            'accounting.dashboard_subtitle' => [
                'vi' => 'Tổng quan tình hình tài chính',
                'en' => 'Overview of financial status',
            ],
            'accounting.this_month' => [
                'vi' => 'Tháng này',
                'en' => 'This Month',
            ],
            'accounting.net_balance' => [
                'vi' => 'Số dư ròng',
                'en' => 'Net Balance',
            ],
            'accounting.quick_actions' => [
                'vi' => 'Thao Tác Nhanh',
                'en' => 'Quick Actions',
            ],
            'accounting.new_expense' => [
                'vi' => 'Đề xuất chi mới',
                'en' => 'New Expense',
            ],
            'accounting.new_income' => [
                'vi' => 'Báo thu mới',
                'en' => 'New Income',
            ],
            'accounting.new_plan' => [
                'vi' => 'Kế hoạch mới',
                'en' => 'New Plan',
            ],
            'accounting.view_reports' => [
                'vi' => 'Xem báo cáo',
                'en' => 'View Reports',
            ],
            'accounting.recent_activity' => [
                'vi' => 'Hoạt Động Gần Đây',
                'en' => 'Recent Activity',
            ],
            'accounting.no_recent_activity' => [
                'vi' => 'Chưa có hoạt động nào',
                'en' => 'No recent activity',
            ],
            
            // Account Items
            'accounting.items_subtitle' => [
                'vi' => 'Quản lý các khoản mục thu chi',
                'en' => 'Manage income and expense items',
            ],
            'accounting.add_item' => [
                'vi' => 'Thêm Khoản Mục',
                'en' => 'Add Item',
            ],
            'accounting.search' => [
                'vi' => 'Tìm kiếm',
                'en' => 'Search',
            ],
            'accounting.search_placeholder' => [
                'vi' => 'Tìm theo mã hoặc tên...',
                'en' => 'Search by code or name...',
            ],
            'accounting.type' => [
                'vi' => 'Loại',
                'en' => 'Type',
            ],
            'accounting.all_types' => [
                'vi' => 'Tất cả loại',
                'en' => 'All Types',
            ],
            'accounting.income' => [
                'vi' => 'Thu',
                'en' => 'Income',
            ],
            'accounting.expense' => [
                'vi' => 'Chi',
                'en' => 'Expense',
            ],
            'accounting.status' => [
                'vi' => 'Trạng thái',
                'en' => 'Status',
            ],
            'accounting.all_status' => [
                'vi' => 'Tất cả trạng thái',
                'en' => 'All Status',
            ],
            'accounting.active' => [
                'vi' => 'Hoạt động',
                'en' => 'Active',
            ],
            'accounting.inactive' => [
                'vi' => 'Không hoạt động',
                'en' => 'Inactive',
            ],
            'accounting.code' => [
                'vi' => 'Mã',
                'en' => 'Code',
            ],
            'accounting.name' => [
                'vi' => 'Tên',
                'en' => 'Name',
            ],
            'accounting.category' => [
                'vi' => 'Danh mục',
                'en' => 'Category',
            ],
            'accounting.actions' => [
                'vi' => 'Thao tác',
                'en' => 'Actions',
            ],
            'accounting.loading' => [
                'vi' => 'Đang tải...',
                'en' => 'Loading...',
            ],
            'accounting.no_items' => [
                'vi' => 'Chưa có khoản mục nào',
                'en' => 'No items yet',
            ],
            'accounting.confirm_delete' => [
                'vi' => 'Bạn có chắc chắn muốn xóa?',
                'en' => 'Are you sure you want to delete?',
            ],
            'accounting.delete_error' => [
                'vi' => 'Có lỗi xảy ra khi xóa',
                'en' => 'Error occurred while deleting',
            ],
            
            // Descriptions
            'accounting.categories_description' => [
                'vi' => 'Quản lý các danh mục thu chi',
                'en' => 'Manage income and expense categories',
            ],
            'accounting.plans_description' => [
                'vi' => 'Tạo và quản lý kế hoạch thu chi theo quý, tháng',
                'en' => 'Create and manage quarterly/monthly financial plans',
            ],
            'accounting.expense_description' => [
                'vi' => 'Đề xuất các khoản chi cần phê duyệt',
                'en' => 'Propose expenses that need approval',
            ],
            'accounting.income_description' => [
                'vi' => 'Báo cáo các khoản thu',
                'en' => 'Report income receipts',
            ],
            'accounting.transactions_description' => [
                'vi' => 'Duyệt và ghi nhận các giao dịch thu chi chính thức',
                'en' => 'Approve and record official financial transactions',
            ],
            'accounting.report_description' => [
                'vi' => 'Xem báo cáo tài chính tổng hợp',
                'en' => 'View comprehensive financial reports',
            ],
            'accounting.cashflow_description' => [
                'vi' => 'Theo dõi dòng tiền vào ra',
                'en' => 'Track cash inflows and outflows',
            ],
            
            // Categories
            'accounting.add_category' => [
                'vi' => 'Thêm Danh Mục',
                'en' => 'Add Category',
            ],
            'accounting.edit_category' => [
                'vi' => 'Chỉnh Sửa Danh Mục',
                'en' => 'Edit Category',
            ],
            'accounting.parent' => [
                'vi' => 'Danh mục cha',
                'en' => 'Parent',
            ],
            'accounting.parent_category' => [
                'vi' => 'Danh mục cha',
                'en' => 'Parent Category',
            ],
            'accounting.no_parent' => [
                'vi' => 'Không có danh mục cha',
                'en' => 'No parent category',
            ],
            'accounting.code_placeholder' => [
                'vi' => 'VD: THU001',
                'en' => 'E.g: INC001',
            ],
            'accounting.name_placeholder' => [
                'vi' => 'Nhập tên',
                'en' => 'Enter name',
            ],
            'accounting.description_placeholder' => [
                'vi' => 'Nhập mô tả (tùy chọn)',
                'en' => 'Enter description (optional)',
            ],
            'accounting.no_categories' => [
                'vi' => 'Chưa có danh mục nào',
                'en' => 'No categories yet',
            ],
            'accounting.all_categories' => [
                'vi' => 'Tất cả danh mục',
                'en' => 'All Categories',
            ],
            
            // Items
            'accounting.edit_item' => [
                'vi' => 'Chỉnh Sửa Khoản Mục',
                'en' => 'Edit Item',
            ],
            'accounting.select_category' => [
                'vi' => 'Chọn danh mục',
                'en' => 'Select category',
            ],
            
            // Plans
            'accounting.add_plan' => [
                'vi' => 'Thêm Kế Hoạch',
                'en' => 'Add Plan',
            ],
            'accounting.plans_subtitle' => [
                'vi' => 'Quản lý kế hoạch thu chi theo quý/tháng',
                'en' => 'Manage quarterly/monthly financial plans',
            ],
            'accounting.plan_type' => [
                'vi' => 'Loại kế hoạch',
                'en' => 'Plan Type',
            ],
            'accounting.quarterly' => [
                'vi' => 'Theo quý',
                'en' => 'Quarterly',
            ],
            'accounting.monthly' => [
                'vi' => 'Theo tháng',
                'en' => 'Monthly',
            ],
            'accounting.draft' => [
                'vi' => 'Nháp',
                'en' => 'Draft',
            ],
            'accounting.pending' => [
                'vi' => 'Chờ duyệt',
                'en' => 'Pending',
            ],
            'accounting.approved' => [
                'vi' => 'Đã duyệt',
                'en' => 'Approved',
            ],
            'accounting.rejected' => [
                'vi' => 'Đã từ chối',
                'en' => 'Rejected',
            ],
            'accounting.closed' => [
                'vi' => 'Đã đóng',
                'en' => 'Closed',
            ],
            'accounting.year' => [
                'vi' => 'Năm',
                'en' => 'Year',
            ],
            'accounting.period' => [
                'vi' => 'Kỳ',
                'en' => 'Period',
            ],
            'accounting.no_plans' => [
                'vi' => 'Chưa có kế hoạch nào',
                'en' => 'No plans yet',
            ],
            'accounting.view' => [
                'vi' => 'Xem',
                'en' => 'View',
            ],
            'accounting.confirm_approve' => [
                'vi' => 'Bạn có chắc chắn muốn duyệt?',
                'en' => 'Are you sure you want to approve?',
            ],
            'accounting.approve_error' => [
                'vi' => 'Có lỗi xảy ra khi duyệt',
                'en' => 'Error occurred while approving',
            ],
            
            // Proposals
            'accounting.add_expense' => [
                'vi' => 'Thêm Đề Xuất Chi',
                'en' => 'Add Expense',
            ],
            'accounting.expense_proposals_subtitle' => [
                'vi' => 'Quản lý đề xuất chi cần phê duyệt',
                'en' => 'Manage expense proposals requiring approval',
            ],
            'accounting.from_date' => [
                'vi' => 'Từ ngày',
                'en' => 'From Date',
            ],
            'accounting.to_date' => [
                'vi' => 'Đến ngày',
                'en' => 'To Date',
            ],
            'accounting.proposal_title' => [
                'vi' => 'Tiêu đề',
                'en' => 'Title',
            ],
            'accounting.account_item' => [
                'vi' => 'Khoản mục',
                'en' => 'Account Item',
            ],
            'accounting.amount' => [
                'vi' => 'Số tiền',
                'en' => 'Amount',
            ],
            'accounting.requested_date' => [
                'vi' => 'Ngày đề xuất',
                'en' => 'Requested Date',
            ],
            'accounting.no_proposals' => [
                'vi' => 'Chưa có đề xuất nào',
                'en' => 'No proposals yet',
            ],
            'accounting.paid' => [
                'vi' => 'Đã thanh toán',
                'en' => 'Paid',
            ],
            'accounting.reject_reason' => [
                'vi' => 'Nhập lý do từ chối:',
                'en' => 'Enter rejection reason:',
            ],
            'accounting.reject_error' => [
                'vi' => 'Có lỗi xảy ra khi từ chối',
                'en' => 'Error occurred while rejecting',
            ],
            
            // Income Reports
            'accounting.add_income' => [
                'vi' => 'Thêm Báo Thu',
                'en' => 'Add Income',
            ],
            'accounting.income_reports_subtitle' => [
                'vi' => 'Quản lý báo cáo thu',
                'en' => 'Manage income reports',
            ],
            'accounting.received_date' => [
                'vi' => 'Ngày thu',
                'en' => 'Received Date',
            ],
            'accounting.payer' => [
                'vi' => 'Người nộp',
                'en' => 'Payer',
            ],
            'accounting.no_reports' => [
                'vi' => 'Chưa có báo thu nào',
                'en' => 'No reports yet',
            ],
            
            // Transactions
            'accounting.transactions_subtitle' => [
                'vi' => 'Xem và quản lý giao dịch đã ghi nhận',
                'en' => 'View and manage recorded transactions',
            ],
            'accounting.transaction_type' => [
                'vi' => 'Loại giao dịch',
                'en' => 'Transaction Type',
            ],
            'accounting.transaction_date' => [
                'vi' => 'Ngày giao dịch',
                'en' => 'Transaction Date',
            ],
            'accounting.payment_method' => [
                'vi' => 'Phương thức',
                'en' => 'Payment Method',
            ],
            'accounting.select_payment_method' => [
                'vi' => 'Chọn phương thức thanh toán',
                'en' => 'Select Payment Method',
            ],
            'accounting.cash' => [
                'vi' => 'Tiền mặt',
                'en' => 'Cash',
            ],
            'accounting.bank_transfer' => [
                'vi' => 'Chuyển khoản',
                'en' => 'Bank Transfer',
            ],
            'accounting.check' => [
                'vi' => 'Séc',
                'en' => 'Check',
            ],
            'accounting.credit_card' => [
                'vi' => 'Thẻ tín dụng',
                'en' => 'Credit Card',
            ],
            'accounting.other' => [
                'vi' => 'Khác',
                'en' => 'Other',
            ],
            'accounting.no_transactions' => [
                'vi' => 'Chưa có giao dịch nào',
                'en' => 'No transactions yet',
            ],
            'accounting.export' => [
                'vi' => 'Xuất Excel',
                'en' => 'Export',
            ],
            'accounting.export_error' => [
                'vi' => 'Lỗi khi xuất dữ liệu',
                'en' => 'Error exporting data',
            ],
            'accounting.monthly_trend' => [
                'vi' => 'Xu Hướng Theo Tháng',
                'en' => 'Monthly Trend',
            ],
            'accounting.category_breakdown' => [
                'vi' => 'Phân Tích Theo Danh Mục',
                'en' => 'Category Breakdown',
            ],
            'accounting.uncategorized' => [
                'vi' => 'Không phân loại',
                'en' => 'Uncategorized',
            ],
            'accounting.all_types' => [
                'vi' => 'Tất cả loại',
                'en' => 'All Types',
            ],
            'accounting.from_date' => [
                'vi' => 'Từ ngày',
                'en' => 'From Date',
            ],
            'accounting.to_date' => [
                'vi' => 'Đến ngày',
                'en' => 'To Date',
            ],
            'accounting.view' => [
                'vi' => 'Xem',
                'en' => 'View',
            ],
            
            // Status
            'accounting.draft' => ['vi' => 'Nháp', 'en' => 'Draft'],
            'accounting.pending' => ['vi' => 'Chờ duyệt', 'en' => 'Pending'],
            'accounting.approved' => ['vi' => 'Đã duyệt', 'en' => 'Approved'],
            'accounting.active' => ['vi' => 'Đang hoạt động', 'en' => 'Active'],
            'accounting.closed' => ['vi' => 'Đã đóng', 'en' => 'Closed'],
            'accounting.all_status' => ['vi' => 'Tất cả trạng thái', 'en' => 'All Status'],
            
            // Common
            'accounting.cancel' => [
                'vi' => 'Hủy',
                'en' => 'Cancel',
            ],
            'accounting.save' => [
                'vi' => 'Lưu',
                'en' => 'Save',
            ],
            'accounting.saving' => [
                'vi' => 'Đang lưu...',
                'en' => 'Saving...',
            ],
            'accounting.save_error' => [
                'vi' => 'Có lỗi xảy ra khi lưu',
                'en' => 'Error occurred while saving',
            ],
            'accounting.description' => [
                'vi' => 'Mô tả',
                'en' => 'Description',
            ],
            
            // Months
            'accounting.month_1' => ['vi' => 'Tháng 1', 'en' => 'January'],
            'accounting.month_2' => ['vi' => 'Tháng 2', 'en' => 'February'],
            'accounting.month_3' => ['vi' => 'Tháng 3', 'en' => 'March'],
            'accounting.month_4' => ['vi' => 'Tháng 4', 'en' => 'April'],
            'accounting.month_5' => ['vi' => 'Tháng 5', 'en' => 'May'],
            'accounting.month_6' => ['vi' => 'Tháng 6', 'en' => 'June'],
            'accounting.month_7' => ['vi' => 'Tháng 7', 'en' => 'July'],
            'accounting.month_8' => ['vi' => 'Tháng 8', 'en' => 'August'],
            'accounting.month_9' => ['vi' => 'Tháng 9', 'en' => 'September'],
            'accounting.month_10' => ['vi' => 'Tháng 10', 'en' => 'October'],
            'accounting.month_11' => ['vi' => 'Tháng 11', 'en' => 'November'],
            'accounting.month_12' => ['vi' => 'Tháng 12', 'en' => 'December'],
            
            // Plan & Items
            'accounting.quarter' => ['vi' => 'Quý', 'en' => 'Quarter'],
            'accounting.month' => ['vi' => 'Tháng', 'en' => 'Month'],
            'accounting.plan_items' => ['vi' => 'Các Khoản Mục Kế Hoạch', 'en' => 'Plan Items'],
            'accounting.planned_amount' => ['vi' => 'Số tiền kế hoạch', 'en' => 'Planned Amount'],
            'accounting.total_income_planned' => ['vi' => 'Tổng Thu Kế Hoạch', 'en' => 'Total Income Planned'],
            'accounting.total_expense_planned' => ['vi' => 'Tổng Chi Kế Hoạch', 'en' => 'Total Expense Planned'],
            'accounting.create_plan' => ['vi' => 'Tạo Kế Hoạch', 'en' => 'Create Plan'],
            'accounting.edit_plan' => ['vi' => 'Chỉnh Sửa Kế Hoạch', 'en' => 'Edit Plan'],
            'accounting.view_plan' => ['vi' => 'Xem Kế Hoạch', 'en' => 'View Plan'],
            'accounting.close' => ['vi' => 'Đóng', 'en' => 'Close'],
            'accounting.submit_for_approval' => ['vi' => 'Gửi duyệt', 'en' => 'Submit for Approval'],
            'accounting.confirm_submit' => ['vi' => 'Bạn có chắc muốn gửi kế hoạch này để duyệt?', 'en' => 'Are you sure you want to submit this plan for approval?'],
            'accounting.submit' => ['vi' => 'Gửi', 'en' => 'Submit'],
            'accounting.submit_error' => ['vi' => 'Lỗi khi gửi kế hoạch', 'en' => 'Error submitting plan'],
            'accounting.plan_submitted' => ['vi' => 'Đã gửi kế hoạch để duyệt thành công!', 'en' => 'Plan submitted for approval successfully!'],
            'accounting.approve_error' => ['vi' => 'Lỗi khi duyệt kế hoạch', 'en' => 'Error approving plan'],
            'accounting.plan_approved' => ['vi' => 'Đã duyệt kế hoạch thành công!', 'en' => 'Plan approved successfully!'],
            'accounting.plan_deleted' => ['vi' => 'Đã xóa kế hoạch thành công!', 'en' => 'Plan deleted successfully!'],
            'accounting.delete_warning' => ['vi' => 'Hành động này không thể hoàn tác!', 'en' => 'This action cannot be undone!'],
            'accounting.success' => ['vi' => 'Thành công', 'en' => 'Success'],
            'accounting.error' => ['vi' => 'Lỗi', 'en' => 'Error'],
            'accounting.total' => ['vi' => 'Tổng cộng', 'en' => 'Total'],
            'accounting.approved_info' => ['vi' => 'Thông Tin Duyệt', 'en' => 'Approval Information'],
            'accounting.approved_by' => ['vi' => 'Người duyệt', 'en' => 'Approved By'],
            'accounting.approved_at' => ['vi' => 'Ngày duyệt', 'en' => 'Approved At'],
            'accounting.period' => ['vi' => 'Kỳ', 'en' => 'Period'],
            'accounting.plan_name_placeholder' => ['vi' => 'VD: Kế hoạch Q1/2025', 'en' => 'E.g: Q1/2025 Plan'],
            'accounting.no_plan_items' => ['vi' => 'Chưa có khoản mục nào. Click "Thêm Khoản Mục" để bắt đầu.', 'en' => 'No items yet. Click "Add Item" to start.'],
            'accounting.select_item' => ['vi' => 'Chọn khoản mục', 'en' => 'Select item'],
            'accounting.select_plan_item' => ['vi' => 'Chọn khoản mục kế hoạch', 'en' => 'Select plan item'],
            'accounting.select_plan' => ['vi' => 'Chọn kế hoạch tài chính', 'en' => 'Select financial plan'],
            'accounting.financial_plan' => ['vi' => 'Kế hoạch tài chính', 'en' => 'Financial Plan'],
            'accounting.unplanned' => ['vi' => 'Ngoài kế hoạch', 'en' => 'Unplanned'],
            'accounting.plan_optional_hint' => ['vi' => 'Để trống nếu không thuộc kế hoạch nào', 'en' => 'Leave empty if not part of any plan'],
            'accounting.expense_plan_required_hint' => ['vi' => 'Đề xuất chi bắt buộc phải thuộc một kế hoạch tài chính', 'en' => 'Expense proposals must belong to a financial plan'],
            'accounting.showing_plan_items' => ['vi' => 'Chỉ hiển thị các khoản mục trong kế hoạch đã chọn', 'en' => 'Showing only items from selected plan'],
            'accounting.expense_item' => ['vi' => 'Hạng Mục Chi', 'en' => 'Expense Item'],
            'accounting.expense_from_plan_hint' => ['vi' => 'Chọn hạng mục từ kế hoạch đã duyệt', 'en' => 'Select item from approved plan'],
            'accounting.cash_account' => ['vi' => 'Tài Khoản Chi', 'en' => 'Cash Account'],
            'accounting.select_cash_account' => ['vi' => 'Chọn tài khoản', 'en' => 'Select account'],
            'accounting.cash_account_hint' => ['vi' => 'Tài khoản nào sẽ chi tiền này', 'en' => 'Which account will pay this expense'],
            'accounting.budget_status' => ['vi' => 'Tình Trạng Ngân Sách', 'en' => 'Budget Status'],
            'accounting.planned' => ['vi' => 'Kế hoạch', 'en' => 'Planned'],
            'accounting.remaining' => ['vi' => 'Còn lại', 'en' => 'Remaining'],
            'accounting.over_budget' => ['vi' => 'Vượt ngân sách', 'en' => 'Over Budget'],
            'accounting.select_plan_first' => ['vi' => 'Chọn kế hoạch trước', 'en' => 'Select plan first'],
            'accounting.items_available' => ['vi' => 'hạng mục có sẵn', 'en' => 'items available'],
            'accounting.no_expense_items_in_plan' => ['vi' => 'Kế hoạch này không có hạng mục chi', 'en' => 'This plan has no expense items'],
            'accounting.no_cash_accounts' => ['vi' => 'Chưa có tài khoản nào.', 'en' => 'No cash accounts yet.'],
            'accounting.create_now' => ['vi' => 'Tạo ngay', 'en' => 'Create now'],
            'accounting.create_cash_account' => ['vi' => 'Tạo Tài Khoản', 'en' => 'Create Cash Account'],
            'accounting.edit_cash_account' => ['vi' => 'Sửa Tài Khoản', 'en' => 'Edit Cash Account'],
            'accounting.name_placeholder' => ['vi' => 'Ví dụ: Quỹ tiền mặt VP', 'en' => 'e.g. Main Cash Fund'],
            'accounting.account_number' => ['vi' => 'Số tài khoản', 'en' => 'Account Number'],
            'accounting.account_number_placeholder' => ['vi' => 'Ví dụ: 0123456789', 'en' => 'e.g. 0123456789'],
            'accounting.bank_name' => ['vi' => 'Tên ngân hàng', 'en' => 'Bank Name'],
            'accounting.bank_name_placeholder' => ['vi' => 'Ví dụ: Vietcombank', 'en' => 'e.g. Vietcombank'],
            'accounting.initial_balance' => ['vi' => 'Số dư ban đầu', 'en' => 'Initial Balance'],
            'accounting.amount_placeholder' => ['vi' => 'Nhập số tiền', 'en' => 'Enter amount'],
            'accounting.proposal_saved' => ['vi' => 'Lưu đề xuất thành công', 'en' => 'Proposal saved successfully'],
            'accounting.cash_account_saved' => ['vi' => 'Lưu tài khoản thành công', 'en' => 'Cash account saved successfully'],
            'accounting.save_error' => ['vi' => 'Lỗi khi lưu', 'en' => 'Failed to save'],
            'accounting.delete_confirm_text' => ['vi' => 'Xóa', 'en' => 'Delete'],
            'accounting.confirm' => ['vi' => 'Xác nhận', 'en' => 'Confirm'],
            'accounting.expense_proposal_details' => ['vi' => 'Chi Tiết Đề Xuất Chi', 'en' => 'Expense Proposal Details'],
            'accounting.approve_proposal' => ['vi' => 'Duyệt đề xuất', 'en' => 'Approve proposal'],
            'accounting.reject_proposal' => ['vi' => 'Từ chối đề xuất', 'en' => 'Reject proposal'],
            'accounting.proposal_approved' => ['vi' => 'Đã duyệt đề xuất', 'en' => 'Proposal approved'],
            'accounting.proposal_rejected' => ['vi' => 'Đã từ chối đề xuất', 'en' => 'Proposal rejected'],
            'accounting.approve_error' => ['vi' => 'Lỗi khi duyệt', 'en' => 'Failed to approve'],
            'accounting.reject_error' => ['vi' => 'Lỗi khi từ chối', 'en' => 'Failed to reject'],
            'accounting.reject_reason_placeholder' => ['vi' => 'Nhập lý do từ chối...', 'en' => 'Enter rejection reason...'],
            'accounting.reject_reason_required' => ['vi' => 'Vui lòng nhập lý do', 'en' => 'Please enter a reason'],
            'accounting.rejected_by' => ['vi' => 'Người từ chối', 'en' => 'Rejected by'],
            'accounting.rejected_at' => ['vi' => 'Ngày từ chối', 'en' => 'Rejected at'],
            'accounting.payment_info' => ['vi' => 'Thông Tin Thanh Toán', 'en' => 'Payment Information'],
            'accounting.paid' => ['vi' => 'Đã thanh toán', 'en' => 'Paid'],
            'accounting.verified' => ['vi' => 'Đã xác minh', 'en' => 'Verified'],
            'accounting.verify' => ['vi' => 'Xác minh', 'en' => 'Verify'],
            'accounting.income_report_details' => ['vi' => 'Chi Tiết Báo Thu', 'en' => 'Income Report Details'],
            'accounting.approve_income' => ['vi' => 'Duyệt báo thu', 'en' => 'Approve income report'],
            'accounting.reject_income' => ['vi' => 'Từ chối báo thu', 'en' => 'Reject income report'],
            'accounting.verify_income' => ['vi' => 'Xác minh báo thu', 'en' => 'Verify income report'],
            'accounting.income_approved' => ['vi' => 'Đã duyệt báo thu. Chờ thủ quỹ xác minh', 'en' => 'Income report approved. Waiting for cashier verification'],
            'accounting.income_verified' => ['vi' => 'Đã xác minh và hoàn tất giao dịch', 'en' => 'Verified and transaction completed'],
            'accounting.income_rejected' => ['vi' => 'Đã từ chối báo thu', 'en' => 'Income report rejected'],
            'accounting.verify_error' => ['vi' => 'Lỗi khi xác minh', 'en' => 'Failed to verify'],
            'accounting.verified_by' => ['vi' => 'Người xác minh', 'en' => 'Verified by'],
            'accounting.verified_at' => ['vi' => 'Ngày xác minh', 'en' => 'Verified at'],
            'accounting.select_receiving_account' => ['vi' => 'Chọn tài khoản nhận tiền', 'en' => 'Select receiving account'],
            'accounting.receiving_account_required' => ['vi' => 'Vui lòng chọn tài khoản', 'en' => 'Please select an account'],
            'accounting.payer_name' => ['vi' => 'Người nộp tiền', 'en' => 'Payer Name'],
            'accounting.payer_phone' => ['vi' => 'SĐT người nộp', 'en' => 'Payer Phone'],
            'accounting.received_date' => ['vi' => 'Ngày thu', 'en' => 'Received Date'],
            'accounting.reported_by' => ['vi' => 'Người báo thu', 'en' => 'Reported by'],
            'accounting.verification_info' => ['vi' => 'Thông Tin Xác Minh', 'en' => 'Verification Information'],
            'accounting.unplanned' => ['vi' => 'Ngoài kế hoạch', 'en' => 'Unplanned'],
            'accounting.payer' => ['vi' => 'Người nộp', 'en' => 'Payer'],
            'accounting.plan_item' => ['vi' => 'Khoản mục kế hoạch', 'en' => 'Plan Item'],
            
            // Transactions workflow
            'accounting.approve_transaction' => ['vi' => 'Duyệt Giao Dịch', 'en' => 'Approve Transaction'],
            'accounting.transaction_approved' => ['vi' => 'Đã duyệt giao dịch', 'en' => 'Transaction approved'],
            'accounting.transaction_rejected' => ['vi' => 'Đã từ chối giao dịch', 'en' => 'Transaction rejected'],
            'accounting.select_payment_account' => ['vi' => 'Chọn tài khoản thanh toán', 'en' => 'Select payment account'],
            'accounting.select_account' => ['vi' => 'Chọn tài khoản', 'en' => 'Select account'],
            'accounting.transaction_details' => ['vi' => 'Chi Tiết Giao Dịch', 'en' => 'Transaction Details'],
            'accounting.date' => ['vi' => 'Ngày', 'en' => 'Date'],
            'accounting.cash_account_already_selected' => ['vi' => 'Tài khoản đã được chọn từ khi tạo phiếu', 'en' => 'Cash account was already selected when creating the proposal'],
            'accounting.can_only_approve_pending' => ['vi' => 'Chỉ có thể duyệt giao dịch đang chờ', 'en' => 'Can only approve pending transactions'],
            
            // Proposals & Reports
            'accounting.create_expense_proposal' => ['vi' => 'Tạo Đề Xuất Chi', 'en' => 'Create Expense Proposal'],
            'accounting.edit_expense_proposal' => ['vi' => 'Chỉnh Sửa Đề Xuất Chi', 'en' => 'Edit Expense Proposal'],
            'accounting.create_income_report' => ['vi' => 'Tạo Báo Thu', 'en' => 'Create Income Report'],
            'accounting.edit_income_report' => ['vi' => 'Chỉnh Sửa Báo Thu', 'en' => 'Edit Income Report'],
            'accounting.title_placeholder' => ['vi' => 'VD: Tiền điện tháng 11', 'en' => 'E.g: Electricity bill November'],
            
            // Payment
            'accounting.payment_info' => ['vi' => 'Thông Tin Thanh Toán', 'en' => 'Payment Information'],
            'accounting.payment_date' => ['vi' => 'Ngày thanh toán', 'en' => 'Payment Date'],
            'accounting.select_method' => ['vi' => 'Chọn phương thức', 'en' => 'Select method'],
            'accounting.cash' => ['vi' => 'Tiền mặt', 'en' => 'Cash'],
            'accounting.bank_transfer' => ['vi' => 'Chuyển khoản', 'en' => 'Bank Transfer'],
            'accounting.card' => ['vi' => 'Thẻ', 'en' => 'Card'],
            'accounting.payment_ref' => ['vi' => 'Mã tham chiếu', 'en' => 'Reference'],
            'accounting.payment_ref_placeholder' => ['vi' => 'Mã GD, số hóa đơn...', 'en' => 'Transaction ID, invoice number...'],
            
            // Payer
            'accounting.payer_info' => ['vi' => 'Thông Tin Người Nộp', 'en' => 'Payer Information'],
            'accounting.payer_name' => ['vi' => 'Tên người nộp', 'en' => 'Payer Name'],
            'accounting.payer_phone' => ['vi' => 'Số điện thoại', 'en' => 'Phone Number'],
            'accounting.payer_name_placeholder' => ['vi' => 'Họ và tên', 'en' => 'Full name'],
            'accounting.payer_phone_placeholder' => ['vi' => '0912345678', 'en' => '0912345678'],
            'accounting.payer_additional_info' => ['vi' => 'Thông tin bổ sung', 'en' => 'Additional Info'],
            'accounting.payer_additional_info_placeholder' => ['vi' => 'Email, địa chỉ, ghi chú...', 'en' => 'Email, address, notes...'],
            
            // Notes
            'accounting.notes' => ['vi' => 'Ghi chú', 'en' => 'Notes'],
            'accounting.notes_placeholder' => ['vi' => 'Ghi chú thêm (tùy chọn)', 'en' => 'Additional notes (optional)'],
            
            // Cost Types
            'accounting.cost_type' => ['vi' => 'Phân loại chi phí', 'en' => 'Cost Type'],
            'accounting.fixed' => ['vi' => 'Định phí', 'en' => 'Fixed Cost'],
            'accounting.variable' => ['vi' => 'Biến phí', 'en' => 'Variable Cost'],
            'accounting.infrastructure' => ['vi' => 'Đầu tư CSVC', 'en' => 'Infrastructure'],
            'accounting.all_cost_types' => ['vi' => 'Tất cả loại chi phí', 'en' => 'All Cost Types'],
            'accounting.select_cost_type' => ['vi' => 'Chọn loại chi phí', 'en' => 'Select cost type'],
            
            // Additional keys
            'accounting.budget_planning' => ['vi' => 'Lập kế hoạch ngân sách', 'en' => 'Budget Planning'],
        ];

        foreach ($translations as $fullKey => $values) {
            // Remove 'accounting.' prefix from key for database storage
            $key = str_replace('accounting.', '', $fullKey);
            
            foreach ($values as $locale => $value) {
                $language = Language::where('code', $locale)->first();
                
                if ($language) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'group' => 'accounting',
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Accounting translations created successfully!');
    }
}
