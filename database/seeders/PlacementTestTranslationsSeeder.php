<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class PlacementTestTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = Language::all();
        
        $translations = [
            // Placement Test - Vietnamese
            'vi' => [
                // Calendar group
                'placement_test' => 'Lịch Test Đầu Vào',
                'schedule_placement_test' => 'Đặt Lịch Test',
                'placement_test_scheduled' => 'Đã Đặt Lịch Test',
                'placement_test_completed' => 'Đã Hoàn Thành Test',
                'test_date' => 'Ngày Test',
                'test_duration' => 'Thời Lượng',
                'test_location' => 'Địa Điểm Test',
                'test_result' => 'Kết Quả Test',
                'test_score' => 'Điểm Số',
                'test_level' => 'Trình Độ',
                'test_recommendations' => 'Đề Xuất',
                'evaluated_by' => 'Người Đánh Giá',
                'evaluated_at' => 'Thời Gian Đánh Giá',
                'update_test_result' => 'Cập Nhật Kết Quả',
                'no_test_result' => 'Chưa Có Kết Quả',
                'has_test_result' => 'Đã Có Kết Quả',
                'schedule_test_for_customer' => 'Đặt Lịch Test Cho Khách Hàng',
                'schedule_test_for_child' => 'Đặt Lịch Test Cho Học Viên',
                'test_scheduled_successfully' => 'Đặt lịch test thành công',
                'test_updated_successfully' => 'Cập nhật lịch test thành công',
                'test_result_updated_successfully' => 'Cập nhật kết quả test thành công',
                'duration_minutes' => 'Thời lượng (phút)',
                'assigned_teacher' => 'Giáo viên phụ trách',
                'schedule_placement_test_entrance' => 'Đặt lịch test đầu vào',
                'schedule_test_for' => 'Đặt lịch test cho {name}',
                'test_date_required' => 'Ngày test *',
                'location' => 'Địa điểm',
                'location_placeholder' => 'Phòng học...',
                'notes' => 'Ghi chú',
                'notes_placeholder' => 'Ghi chú về buổi test...',
                'please_select_test_date' => 'Vui lòng chọn ngày test',
            ],
            
            // Placement Test - English
            'en' => [
                // Calendar group
                'placement_test' => 'Placement Test',
                'schedule_placement_test' => 'Schedule Test',
                'placement_test_scheduled' => 'Test Scheduled',
                'placement_test_completed' => 'Test Completed',
                'test_date' => 'Test Date',
                'test_duration' => 'Duration',
                'test_location' => 'Test Location',
                'test_result' => 'Test Result',
                'test_score' => 'Score',
                'test_level' => 'Level',
                'test_recommendations' => 'Recommendations',
                'evaluated_by' => 'Evaluated By',
                'evaluated_at' => 'Evaluated At',
                'update_test_result' => 'Update Result',
                'no_test_result' => 'No Result Yet',
                'has_test_result' => 'Has Result',
                'schedule_test_for_customer' => 'Schedule Test for Customer',
                'schedule_test_for_child' => 'Schedule Test for Student',
                'test_scheduled_successfully' => 'Test scheduled successfully',
                'test_updated_successfully' => 'Test updated successfully',
                'test_result_updated_successfully' => 'Test result updated successfully',
                'duration_minutes' => 'Duration (minutes)',
                'assigned_teacher' => 'Assigned Teacher',
                'schedule_placement_test_entrance' => 'Schedule Placement Test',
                'schedule_test_for' => 'Schedule test for {name}',
                'test_date_required' => 'Test date *',
                'location' => 'Location',
                'location_placeholder' => 'Classroom...',
                'notes' => 'Notes',
                'notes_placeholder' => 'Notes about the test session...',
                'please_select_test_date' => 'Please select test date',
            ],
        ];

        foreach ($languages as $language) {
            $langCode = $language->code;
            
            if (!isset($translations[$langCode])) {
                continue;
            }

            foreach ($translations[$langCode] as $key => $value) {
                Translation::updateOrCreate(
                    [
                        'language_id' => $language->id,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                        'group' => 'calendar',
                    ]
                );
            }
        }

        $this->command->info('✅ Placement Test translations seeded successfully!');
    }
}
