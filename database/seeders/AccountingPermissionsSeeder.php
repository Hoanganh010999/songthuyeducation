<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccountingPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Module Access
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'accounting.view',
                'display_name' => 'Xem Kế toán',
                'description' => 'Xem module Kế toán',
                'sort_order' => 1,
            ],
            [
                'module' => 'accounting',
                'action' => 'manage',
                'name' => 'accounting.manage',
                'display_name' => 'Quản lý Kế toán',
                'description' => 'Quản lý toàn bộ module Kế toán',
                'sort_order' => 2,
            ],
            
            // Account Categories (Danh mục)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'account_categories.view',
                'display_name' => 'Xem Danh mục',
                'description' => 'Xem danh sách danh mục',
                'sort_order' => 5,
            ],
            [
                'module' => 'accounting',
                'action' => 'create',
                'name' => 'account_categories.create',
                'display_name' => 'Tạo Danh mục',
                'description' => 'Tạo danh mục mới',
                'sort_order' => 6,
            ],
            [
                'module' => 'accounting',
                'action' => 'edit',
                'name' => 'account_categories.edit',
                'display_name' => 'Sửa Danh mục',
                'description' => 'Chỉnh sửa danh mục',
                'sort_order' => 7,
            ],
            [
                'module' => 'accounting',
                'action' => 'delete',
                'name' => 'account_categories.delete',
                'display_name' => 'Xóa Danh mục',
                'description' => 'Xóa danh mục',
                'sort_order' => 8,
            ],
            
            // Account Items (Định khoản)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'account_items.view',
                'display_name' => 'Xem Khoản mục',
                'description' => 'Xem danh sách khoản mục',
                'sort_order' => 10,
            ],
            [
                'module' => 'accounting',
                'action' => 'create',
                'name' => 'account_items.create',
                'display_name' => 'Tạo Định khoản',
                'description' => 'Tạo định khoản mới',
                'sort_order' => 11,
            ],
            [
                'module' => 'accounting',
                'action' => 'edit',
                'name' => 'account_items.edit',
                'display_name' => 'Sửa Định khoản',
                'description' => 'Chỉnh sửa định khoản',
                'sort_order' => 12,
            ],
            [
                'module' => 'accounting',
                'action' => 'delete',
                'name' => 'account_items.delete',
                'display_name' => 'Xóa Định khoản',
                'description' => 'Xóa định khoản',
                'sort_order' => 13,
            ],
            
            // Financial Plans (Kế hoạch Thu Chi)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'financial_plans.view',
                'display_name' => 'Xem Kế hoạch',
                'description' => 'Xem kế hoạch thu chi',
                'sort_order' => 20,
            ],
            [
                'module' => 'accounting',
                'action' => 'create',
                'name' => 'financial_plans.create',
                'display_name' => 'Tạo Kế hoạch',
                'description' => 'Tạo kế hoạch thu chi',
                'sort_order' => 21,
            ],
            [
                'module' => 'accounting',
                'action' => 'edit',
                'name' => 'financial_plans.edit',
                'display_name' => 'Sửa Kế hoạch',
                'description' => 'Chỉnh sửa kế hoạch thu chi',
                'sort_order' => 22,
            ],
            [
                'module' => 'accounting',
                'action' => 'approve',
                'name' => 'financial_plans.approve',
                'display_name' => 'Duyệt Kế hoạch',
                'description' => 'Phê duyệt kế hoạch thu chi',
                'sort_order' => 23,
            ],
            [
                'module' => 'accounting',
                'action' => 'delete',
                'name' => 'financial_plans.delete',
                'display_name' => 'Xóa Kế hoạch',
                'description' => 'Xóa kế hoạch thu chi',
                'sort_order' => 24,
            ],
            
            // Expense Proposals (Đề xuất Chi)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'expense_proposals.view',
                'display_name' => 'Xem Đề xuất Chi',
                'description' => 'Xem danh sách đề xuất chi',
                'sort_order' => 30,
            ],
            [
                'module' => 'accounting',
                'action' => 'create',
                'name' => 'expense_proposals.create',
                'display_name' => 'Tạo Đề xuất Chi',
                'description' => 'Tạo đề xuất chi mới',
                'sort_order' => 31,
            ],
            [
                'module' => 'accounting',
                'action' => 'edit',
                'name' => 'expense_proposals.edit',
                'display_name' => 'Sửa Đề xuất Chi',
                'description' => 'Chỉnh sửa đề xuất chi',
                'sort_order' => 32,
            ],
            [
                'module' => 'accounting',
                'action' => 'approve',
                'name' => 'expense_proposals.approve',
                'display_name' => 'Duyệt Đề xuất Chi',
                'description' => 'Phê duyệt đề xuất chi',
                'sort_order' => 33,
            ],
            [
                'module' => 'accounting',
                'action' => 'delete',
                'name' => 'expense_proposals.delete',
                'display_name' => 'Xóa Đề xuất Chi',
                'description' => 'Xóa đề xuất chi',
                'sort_order' => 34,
            ],
            
            // Income Reports (Báo Thu)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'income_reports.view',
                'display_name' => 'Xem Báo Thu',
                'description' => 'Xem danh sách báo thu',
                'sort_order' => 40,
            ],
            [
                'module' => 'accounting',
                'action' => 'create',
                'name' => 'income_reports.create',
                'display_name' => 'Tạo Báo Thu',
                'description' => 'Tạo báo thu mới',
                'sort_order' => 41,
            ],
            [
                'module' => 'accounting',
                'action' => 'edit',
                'name' => 'income_reports.edit',
                'display_name' => 'Sửa Báo Thu',
                'description' => 'Chỉnh sửa báo thu',
                'sort_order' => 42,
            ],
            [
                'module' => 'accounting',
                'action' => 'approve',
                'name' => 'income_reports.approve',
                'display_name' => 'Duyệt Báo Thu',
                'description' => 'Phê duyệt báo thu',
                'sort_order' => 43,
            ],
            [
                'module' => 'accounting',
                'action' => 'delete',
                'name' => 'income_reports.delete',
                'display_name' => 'Xóa Báo Thu',
                'description' => 'Xóa báo thu',
                'sort_order' => 44,
            ],
            
            // Financial Transactions (Giao dịch)
            [
                'module' => 'accounting',
                'action' => 'view',
                'name' => 'financial_transactions.view',
                'display_name' => 'Xem Giao dịch',
                'description' => 'Xem lịch sử giao dịch',
                'sort_order' => 50,
            ],
            [
                'module' => 'accounting',
                'action' => 'export',
                'name' => 'financial_transactions.export',
                'display_name' => 'Xuất Báo cáo',
                'description' => 'Xuất báo cáo giao dịch',
                'sort_order' => 51,
            ],
        ];

        foreach ($permissions as $permissionData) {
            $permissionData['is_active'] = true;
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign all permissions to super-admin role
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('module', 'accounting')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        $this->command->info('✅ Accounting permissions seeded successfully!');
    }
}
