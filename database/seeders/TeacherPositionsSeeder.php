<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Role;

class TeacherPositionsSeeder extends Seeder
{
    /**
     * Seed teacher positions with codes
     */
    public function run(): void
    {
        // Các vị trí giáo viên với mã code
        $teacherPositions = [
            [
                'name' => 'Giáo viên Hạng I',
                'code' => 'GV01',
                'level' => 1,
                'sort_order' => 1,
                'description' => 'Giáo viên hạng I - Trình độ cao nhất',
                'is_active' => true
            ],
            [
                'name' => 'Giáo viên Hạng II',
                'code' => 'GV02',
                'level' => 2,
                'sort_order' => 2,
                'description' => 'Giáo viên hạng II - Trình độ trung cấp',
                'is_active' => true
            ],
            [
                'name' => 'Giáo viên Hạng III',
                'code' => 'GV03',
                'level' => 3,
                'sort_order' => 3,
                'description' => 'Giáo viên hạng III - Giáo viên mới',
                'is_active' => true
            ],
            [
                'name' => 'Giáo viên Thực tập',
                'code' => 'GVTT',
                'level' => 4,
                'sort_order' => 4,
                'description' => 'Giáo viên đang trong thời gian thực tập',
                'is_active' => true
            ],
            [
                'name' => 'Giáo viên Chủ nhiệm',
                'code' => 'GVCN',
                'level' => 1,
                'sort_order' => 5,
                'description' => 'Giáo viên làm chủ nhiệm lớp',
                'is_active' => true
            ],
        ];

        // Find teacher role to assign to these positions
        $teacherRole = Role::where('name', 'teacher')->first();
        
        if (!$teacherRole) {
            // Create teacher role if not exists
            $teacherRole = Role::create([
                'name' => 'teacher',
                'description' => 'Giáo viên - Quyền cơ bản cho giáo viên'
            ]);
            
            $this->command->info('Created teacher role');
        }

        foreach ($teacherPositions as $positionData) {
            $position = Position::updateOrCreate(
                ['code' => $positionData['code']],
                $positionData
            );

            // Attach teacher role to position if not already attached
            if (!$position->roles()->where('role_id', $teacherRole->id)->exists()) {
                $position->roles()->attach($teacherRole->id);
                $this->command->info("Assigned teacher role to position: {$position->name}");
            }
        }

        $this->command->info('Teacher positions seeded successfully!');
    }
}

