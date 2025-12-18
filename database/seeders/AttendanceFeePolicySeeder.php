<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceFeePolicy;

class AttendanceFeePolicySeeder extends Seeder
{
    public function run(): void
    {
        AttendanceFeePolicy::create([
            'name' => 'Chính sách mặc định',
            'branch_id' => null, // Apply to all branches
            'is_active' => true,
            'absence_unexcused_percent' => 100.00,
            'absence_consecutive_threshold' => 1,
            'absence_excused_free_limit' => 2,
            'absence_excused_percent' => 50.00,
            'late_deduct_percent' => 30.00,
            'late_grace_minutes' => 15,
            'description' => 'Chính sách trừ học phí mặc định cho hệ thống. Vắng không lý do trừ 100%, vắng có lý do quá 2 buổi/tháng trừ 50%, đi trễ quá 15 phút trừ 30%.',
        ]);
    }
}

