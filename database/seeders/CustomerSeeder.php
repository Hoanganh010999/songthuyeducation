<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo permissions cho customers module
        $customerPermissions = [
            [
                'module' => 'customers',
                'action' => 'view',
                'name' => 'customers.view',
                'display_name' => 'Xem Khách Hàng',
                'description' => 'Xem danh sách và chi tiết khách hàng',
                'sort_order' => 1,
            ],
            [
                'module' => 'customers',
                'action' => 'create',
                'name' => 'customers.create',
                'display_name' => 'Tạo Khách Hàng',
                'description' => 'Tạo khách hàng mới',
                'sort_order' => 2,
            ],
            [
                'module' => 'customers',
                'action' => 'edit',
                'name' => 'customers.edit',
                'display_name' => 'Sửa Khách Hàng',
                'description' => 'Cập nhật thông tin khách hàng',
                'sort_order' => 3,
            ],
            [
                'module' => 'customers',
                'action' => 'delete',
                'name' => 'customers.delete',
                'display_name' => 'Xóa Khách Hàng',
                'description' => 'Xóa khách hàng',
                'sort_order' => 4,
            ],
        ];

        foreach ($customerPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // 2. Gán permissions cho roles
        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();

        if ($superAdmin) {
            $permissionIds = Permission::where('module', 'customers')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($admin) {
            $permissionIds = Permission::where('module', 'customers')->pluck('id');
            $admin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($manager) {
            $permissionIds = Permission::where('module', 'customers')
                ->whereIn('action', ['view', 'create', 'edit'])
                ->pluck('id');
            $manager->permissions()->syncWithoutDetaching($permissionIds);
        }

        // 3. Tạo sample customers
        $branches = Branch::all();
        $users = User::all();

        if ($branches->isEmpty() || $users->isEmpty()) {
            $this->command->warn('⚠️ Không có branches hoặc users để tạo sample customers');
            return;
        }

        $stages = array_keys(Customer::getStages());
        $sources = ['Facebook', 'Google', 'Referral', 'Website', 'Walk-in', 'Phone Call'];
        $cities = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ'];

        // Tạo 20 customers mẫu
        for ($i = 1; $i <= 20; $i++) {
            $stage = $stages[array_rand($stages)];
            $branch = $branches->random();
            $assignedUser = $users->random();

            Customer::create([
                'name' => 'Khách Hàng ' . $i,
                'phone' => '09' . rand(10000000, 99999999),
                'email' => 'customer' . $i . '@example.com',
                'date_of_birth' => now()->subYears(rand(20, 50))->format('Y-m-d'),
                'gender' => ['male', 'female'][array_rand(['male', 'female'])],
                'address' => rand(1, 999) . ' Đường ' . chr(rand(65, 90)),
                'city' => $cities[array_rand($cities)],
                'district' => 'Quận ' . rand(1, 12),
                'stage' => $stage,
                'stage_order' => $i,
                'source' => $sources[array_rand($sources)],
                'branch_id' => $branch->id,
                'assigned_to' => $assignedUser->id,
                'notes' => 'Khách hàng tiềm năng, cần follow up',
                'estimated_value' => rand(1000000, 50000000),
                'expected_close_date' => now()->addDays(rand(7, 90))->format('Y-m-d'),
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Customer permissions và 20 sample customers đã được tạo!');
    }
}
