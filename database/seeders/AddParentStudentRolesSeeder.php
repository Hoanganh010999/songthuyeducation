<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class AddParentStudentRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Thêm role Parent nếu chưa có
        if (!Role::where('name', 'parent')->exists()) {
            Role::create([
                'name' => 'parent',
                'display_name' => 'Parent',
                'description' => 'Phụ huynh - xem kết quả học tập của con',
                'is_active' => true,
            ]);
            $this->command->info('✅ Đã tạo role: Parent');
        } else {
            $this->command->info('ℹ️ Role Parent đã tồn tại');
        }

        // Thêm role Student nếu chưa có
        if (!Role::where('name', 'student')->exists()) {
            Role::create([
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Học viên - truy cập lớp học và nội dung học tập',
                'is_active' => true,
            ]);
            $this->command->info('✅ Đã tạo role: Student');
        } else {
            $this->command->info('ℹ️ Role Student đã tồn tại');
        }

        $this->command->info('✅ Hoàn tất! Tổng số roles: ' . Role::count());
    }
}

