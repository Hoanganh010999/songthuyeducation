<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;

class QualityStudentsParentsTranslations extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');
        
        $translations = [
            'quality.students' => [
                'vi' => 'Học viên',
                'en' => 'Students',
            ],
            'quality.students_description' => [
                'vi' => 'Danh sách học viên',
                'en' => 'Students list',
            ],
            'quality.parents' => [
                'vi' => 'Phụ huynh',
                'en' => 'Parents',
            ],
            'quality.parents_description' => [
                'vi' => 'Danh sách phụ huynh',
                'en' => 'Parents list',
            ],
            'quality.classes' => [
                'vi' => 'Lớp học',
                'en' => 'Classes',
            ],
            'quality.student' => [
                'vi' => 'Học viên',
                'en' => 'Student',
            ],
            'quality.search_by_name_or_code' => [
                'vi' => 'Tìm kiếm theo tên hoặc mã học viên',
                'en' => 'Search by name or student code',
            ],
            'quality.no_class' => [
                'vi' => 'Chưa có lớp',
                'en' => 'No class',
            ],
            'quality.add_to_class' => [
                'vi' => 'Thêm vào lớp',
                'en' => 'Add to Class',
            ],
            'quality.no_classes_available' => [
                'vi' => 'Không có lớp nào',
                'en' => 'No Classes Available',
            ],
            'quality.create_class_first' => [
                'vi' => 'Vui lòng tạo lớp học trước khi thêm học viên',
                'en' => 'Please create a class first before adding students',
            ],
            'quality.add_student_to_class' => [
                'vi' => 'Thêm học viên vào lớp',
                'en' => 'Add Student to Class',
            ],
            'quality.student_code' => [
                'vi' => 'Mã học viên',
                'en' => 'Student Code',
            ],
            'quality.select_class' => [
                'vi' => 'Chọn lớp học',
                'en' => 'Select a class',
            ],
            'quality.please_select_class' => [
                'vi' => 'Vui lòng chọn lớp học',
                'en' => 'Please select a class',
            ],
            'quality.student_info' => [
                'vi' => 'Thông tin học viên',
                'en' => 'Student Information',
            ],
            'quality.current_classes' => [
                'vi' => 'Lớp đang học',
                'en' => 'Current Classes',
            ],
            'quality.no_active_classes' => [
                'vi' => 'Chưa có lớp đang học',
                'en' => 'No active classes',
            ],
            'quality.student_added_to_class' => [
                'vi' => 'Đã thêm học viên vào lớp thành công',
                'en' => 'Student added to class successfully',
            ],
            'users.username' => [
                'vi' => 'Tên đăng nhập',
                'en' => 'Username',
            ],
            'common.no_data' => [
                'vi' => 'Không có dữ liệu',
                'en' => 'No data',
            ],
            'common.all_status' => [
                'vi' => 'Tất cả trạng thái',
                'en' => 'All Status',
            ],
        ];

        foreach ($translations as $key => $values) {
            list($group, $keyName) = explode('.', $key, 2);
            
            foreach ($values as $langCode => $value) {
                if (isset($languages[$langCode])) {
                    Translation::updateOrCreate(
                        [
                            'language_id' => $languages[$langCode]->id,
                            'group' => $group,
                            'key' => $keyName,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                    
                    echo "✓ Translation: {$key} ({$langCode})\n";
                }
            }
        }

        echo "\n✅ Quality Students/Parents translations seeded successfully!\n";
    }
}

