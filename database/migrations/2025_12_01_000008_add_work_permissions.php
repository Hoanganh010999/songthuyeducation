<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Work Items
            ["name" => "work_items.view_all", "module" => "work_management", "action" => "view_all", "description" => "Xem tất cả công việc"],
            ["name" => "work_items.view_own", "module" => "work_management", "action" => "view_own", "description" => "Xem công việc của mình"],
            ["name" => "work_items.view_department", "module" => "work_management", "action" => "view_department", "description" => "Xem công việc phòng ban"],
            ["name" => "work_items.create", "module" => "work_management", "action" => "create", "description" => "Tạo công việc"],
            ["name" => "work_items.edit", "module" => "work_management", "action" => "edit", "description" => "Sửa công việc"],
            ["name" => "work_items.delete", "module" => "work_management", "action" => "delete", "description" => "Xóa công việc"],
            ["name" => "work_items.assign", "module" => "work_management", "action" => "assign", "description" => "Phân công công việc"],
            ["name" => "work_items.submit", "module" => "work_management", "action" => "submit", "description" => "Nộp sản phẩm"],
            ["name" => "work_items.review", "module" => "work_management", "action" => "review", "description" => "Đánh giá sản phẩm"],
            ["name" => "work_items.approve", "module" => "work_management", "action" => "approve", "description" => "Phê duyệt sản phẩm"],

            // Work Management
            ["name" => "work_management.dashboard", "module" => "work_management", "action" => "dashboard", "description" => "Xem dashboard công việc"],
            ["name" => "work_management.statistics", "module" => "work_management", "action" => "statistics", "description" => "Xem thống kê công việc"],
            ["name" => "work_management.calendar", "module" => "work_management", "action" => "calendar", "description" => "Xem lịch công việc"],
            ["name" => "work_management.reports", "module" => "work_management", "action" => "reports", "description" => "Xem báo cáo công việc"],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ["name" => $permission["name"]],
                $permission
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            "work_items.view_all",
            "work_items.view_own",
            "work_items.view_department",
            "work_items.create",
            "work_items.edit",
            "work_items.delete",
            "work_items.assign",
            "work_items.submit",
            "work_items.review",
            "work_items.approve",
            "work_management.dashboard",
            "work_management.statistics",
            "work_management.calendar",
            "work_management.reports",
        ];

        Permission::whereIn("name", $permissions)->delete();
    }
};
