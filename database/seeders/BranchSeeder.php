<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo permissions cho branches module
        $branchPermissions = [
            [
                'module' => 'branches',
                'action' => 'view',
                'name' => 'branches.view',
                'display_name' => 'Xem Chi Nhánh',
                'description' => 'Xem danh sách và chi tiết chi nhánh',
                'sort_order' => 1,
            ],
            [
                'module' => 'branches',
                'action' => 'create',
                'name' => 'branches.create',
                'display_name' => 'Tạo Chi Nhánh',
                'description' => 'Tạo chi nhánh mới',
                'sort_order' => 2,
            ],
            [
                'module' => 'branches',
                'action' => 'edit',
                'name' => 'branches.edit',
                'display_name' => 'Sửa Chi Nhánh',
                'description' => 'Cập nhật thông tin chi nhánh',
                'sort_order' => 3,
            ],
            [
                'module' => 'branches',
                'action' => 'delete',
                'name' => 'branches.delete',
                'display_name' => 'Xóa Chi Nhánh',
                'description' => 'Xóa chi nhánh',
                'sort_order' => 4,
            ],
        ];

        foreach ($branchPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // 2. Gán permissions cho super-admin role (optional vì super-admin tự động có tất cả)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('module', 'branches')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // 3. Tạo sample branches
        $branches = [
            [
                'code' => 'HN01',
                'name' => 'Chi Nhánh Hà Nội',
                'phone' => '0241234567',
                'email' => 'hanoi@school.com',
                'address' => '123 Đường Láng',
                'city' => 'Hà Nội',
                'district' => 'Đống Đa',
                'ward' => 'Láng Thượng',
                'is_active' => true,
                'is_headquarters' => true,
                'description' => 'Trụ sở chính tại Hà Nội',
            ],
            [
                'code' => 'HCM01',
                'name' => 'Chi Nhánh TP.HCM',
                'phone' => '0281234567',
                'email' => 'hcm@school.com',
                'address' => '456 Nguyễn Huệ',
                'city' => 'TP. Hồ Chí Minh',
                'district' => 'Quận 1',
                'ward' => 'Bến Nghé',
                'is_active' => true,
                'is_headquarters' => false,
                'description' => 'Chi nhánh tại TP.HCM',
            ],
            [
                'code' => 'DN01',
                'name' => 'Chi Nhánh Đà Nẵng',
                'phone' => '0236123456',
                'email' => 'danang@school.com',
                'address' => '789 Trần Phú',
                'city' => 'Đà Nẵng',
                'district' => 'Hải Châu',
                'ward' => 'Thạch Thang',
                'is_active' => true,
                'is_headquarters' => false,
                'description' => 'Chi nhánh tại Đà Nẵng',
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                ['code' => $branchData['code']],
                $branchData
            );
        }

        $this->command->info('✅ Branch permissions và sample branches đã được tạo!');
    }
}
