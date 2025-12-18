<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class HolidaysPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Holidays Module - Main
            ['module' => 'holidays', 'action' => 'view', 'name' => 'holidays.view', 'display_name' => 'Xem Lịch nghỉ', 'description' => 'Xem module Lịch nghỉ', 'sort_order' => 1, 'is_active' => true],
            ['module' => 'holidays', 'action' => 'create', 'name' => 'holidays.create', 'display_name' => 'Tạo Lịch nghỉ', 'description' => 'Tạo lịch nghỉ mới', 'sort_order' => 2, 'is_active' => true],
            ['module' => 'holidays', 'action' => 'edit', 'name' => 'holidays.edit', 'display_name' => 'Sửa Lịch nghỉ', 'description' => 'Chỉnh sửa lịch nghỉ', 'sort_order' => 3, 'is_active' => true],
            ['module' => 'holidays', 'action' => 'delete', 'name' => 'holidays.delete', 'display_name' => 'Xóa Lịch nghỉ', 'description' => 'Xóa lịch nghỉ', 'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✅ Holidays permissions seeded successfully!');
    }
}
