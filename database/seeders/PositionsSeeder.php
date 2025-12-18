<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionsSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Giám đốc', 'code' => 'director', 'level' => 1, 'sort_order' => 1],
            ['name' => 'Phó giám đốc', 'code' => 'deputy_director', 'level' => 2, 'sort_order' => 2],
            ['name' => 'Trưởng phòng', 'code' => 'head', 'level' => 3, 'sort_order' => 3],
            ['name' => 'Phó phòng', 'code' => 'deputy_head', 'level' => 4, 'sort_order' => 4],
            ['name' => 'Trưởng nhóm', 'code' => 'team_lead', 'level' => 5, 'sort_order' => 5],
            ['name' => 'Nhân viên chính', 'code' => 'senior_staff', 'level' => 6, 'sort_order' => 6],
            ['name' => 'Nhân viên', 'code' => 'staff', 'level' => 7, 'sort_order' => 7],
            ['name' => 'Thực tập sinh', 'code' => 'intern', 'level' => 8, 'sort_order' => 8],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
