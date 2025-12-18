<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomersViewAllPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tạo permission 'customers.view_all' để cho phép user xem toàn bộ customers
     * thay vì chỉ customers được assigned cho mình.
     */
    public function run(): void
    {
        // Tạo permission customers.view_all
        $permission = Permission::firstOrCreate(
            ['name' => 'customers.view_all'],
            [
                'module' => 'customers',
                'action' => 'view_all',
                'display_name' => 'Xem Tất Cả Khách Hàng',
                'description' => 'Xem toàn bộ khách hàng trong hệ thống (không giới hạn assigned_to)',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Permission created: {$permission->name}");

        // Gán permission cho roles
        $this->assignPermissionsToRoles($permission);
    }

    private function assignPermissionsToRoles(Permission $permission): void
    {
        // Super Admin: Có quyền view all (nhưng đã có sẵn do is_super_admin check)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching([$permission->id]);
            $this->command->info("✓ Super Admin: customers.view_all");
        }

        // Admin: Có quyền view all customers trong branch
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching([$permission->id]);
            $this->command->info("✓ Admin: customers.view_all");
        }

        // Manager: Có quyền view all customers trong branch (tùy chọn - có thể bỏ nếu muốn giới hạn)
        // Uncomment nếu muốn manager cũng xem tất cả customers
        /*
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $manager->permissions()->syncWithoutDetaching([$permission->id]);
            $this->command->info("✓ Manager: customers.view_all");
        }
        */
    }
}
