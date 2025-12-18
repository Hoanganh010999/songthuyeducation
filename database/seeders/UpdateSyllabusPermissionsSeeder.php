<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class UpdateSyllabusPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Update display names for lesson_plans permissions (keep permission names same for backward compatibility)
        $permissions = [
            'lesson_plans.view' => [
                'display_name' => 'Xem Syllabus',
                'description' => 'Xem danh sách và nội dung syllabus'
            ],
            'lesson_plans.create' => [
                'display_name' => 'Tạo Syllabus',
                'description' => 'Tạo syllabus mới'
            ],
            'lesson_plans.edit' => [
                'display_name' => 'Sửa Syllabus',
                'description' => 'Cập nhật và chỉnh sửa syllabus'
            ],
            'lesson_plans.delete' => [
                'display_name' => 'Xóa Syllabus',
                'description' => 'Xóa syllabus'
            ],
        ];

        foreach ($permissions as $name => $data) {
            Permission::where('name', $name)->update([
                'display_name' => $data['display_name'],
                'description' => $data['description']
            ]);
        }

        $this->command->info('Syllabus permissions updated successfully!');
    }
}
