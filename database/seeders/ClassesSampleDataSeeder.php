<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\StudyPeriod;
use App\Models\Room;
use App\Models\Holiday;
use App\Models\LessonPlan;
use App\Models\LessonPlanSession;
use App\Models\Subject;
use Carbon\Carbon;

class ClassesSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding sample data for Classes module...');
        
        // Branch IDs to seed for
        $branchIds = [1, 2, 3];
        
        foreach ($branchIds as $branchId) {
            $this->command->info("\nSeeding for Branch {$branchId}...");
            
            // 1. Academic Year
            $academicYear = AcademicYear::updateOrCreate(
                ['branch_id' => $branchId, 'code' => "AY_2024_2025_B{$branchId}"],
                [
                    'name' => '2024-2025',
                    'start_date' => '2024-09-01',
                    'end_date' => '2025-06-30',
                    'is_current' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                    'description' => 'Năm học 2024-2025'
                ]
            );
            $this->command->info("Created Academic Year: {$academicYear->name}");
            
            // 2. Semesters
            $semester1 = Semester::updateOrCreate(
                ['academic_year_id' => $academicYear->id, 'code' => 'SEM_1'],
                [
                    'name' => 'Học kỳ 1',
                    'start_date' => '2024-09-01',
                    'end_date' => '2025-01-15',
                    'total_weeks' => 18,
                    'is_current' => true,
                    'is_active' => true,
                    'sort_order' => 1
                ]
            );
            
            $semester2 = Semester::updateOrCreate(
                ['academic_year_id' => $academicYear->id, 'code' => 'SEM_2'],
                [
                    'name' => 'Học kỳ 2',
                    'start_date' => '2025-01-20',
                    'end_date' => '2025-06-30',
                    'total_weeks' => 18,
                    'is_current' => false,
                    'is_active' => true,
                    'sort_order' => 2
                ]
            );
            $this->command->info("Created Semesters: HK1, HK2");
            
            // 3. Study Periods
            $studyPeriods = [
                [
                    'name' => 'Ca sáng',
                    'code' => 'MORNING',
                    'duration_minutes' => 240,
                    'lesson_duration' => 45,
                    'break_duration' => 10,
                    'sort_order' => 1
                ],
                [
                    'name' => 'Ca chiều',
                    'code' => 'AFTERNOON',
                    'duration_minutes' => 240,
                    'lesson_duration' => 45,
                    'break_duration' => 10,
                    'sort_order' => 2
                ],
            ];
            
            foreach ($studyPeriods as $periodData) {
                StudyPeriod::updateOrCreate(
                    ['branch_id' => $branchId, 'code' => $periodData['code']],
                    array_merge($periodData, ['is_active' => true])
                );
            }
            $this->command->info("Created Study Periods: Ca sáng, Ca chiều");
            
            // 4. Rooms
            $rooms = [
                ['name' => 'Phòng 101', 'code' => "ROOM_101_B{$branchId}", 'building' => 'Tòa A', 'floor' => 'Tầng 1', 'capacity' => 40, 'room_type' => 'classroom'],
                ['name' => 'Phòng 102', 'code' => "ROOM_102_B{$branchId}", 'building' => 'Tòa A', 'floor' => 'Tầng 1', 'capacity' => 40, 'room_type' => 'classroom'],
                ['name' => 'Phòng 201', 'code' => "ROOM_201_B{$branchId}", 'building' => 'Tòa A', 'floor' => 'Tầng 2', 'capacity' => 35, 'room_type' => 'classroom'],
                ['name' => 'Phòng Máy 1', 'code' => "LAB_1_B{$branchId}", 'building' => 'Tòa B', 'floor' => 'Tầng 1', 'capacity' => 30, 'room_type' => 'computer_lab'],
            ];
            
            foreach ($rooms as $roomData) {
                Room::updateOrCreate(
                    ['branch_id' => $branchId, 'code' => $roomData['code']],
                    array_merge($roomData, [
                        'is_available' => true,
                        'is_active' => true,
                        'facilities' => ['Máy chiếu', 'Điều hòa', 'Bảng trắng']
                    ])
                );
            }
            $this->command->info("Created Rooms: 101, 102, 201, Lab 1");
            
            // 5. Holidays
            $holidays = [
                [
                    'name' => 'Tết Nguyên Đán 2025',
                    'start_date' => '2025-01-25',
                    'end_date' => '2025-02-05',
                    'total_days' => 12,
                    'type' => 'national',
                    'affects_schedule' => true
                ],
                [
                    'name' => 'Giỗ Tổ Hùng Vương',
                    'start_date' => '2025-04-10',
                    'end_date' => '2025-04-10',
                    'total_days' => 1,
                    'type' => 'national',
                    'affects_schedule' => true
                ],
                [
                    'name' => 'Giải phóng Miền Nam',
                    'start_date' => '2025-04-30',
                    'end_date' => '2025-05-01',
                    'total_days' => 2,
                    'type' => 'national',
                    'affects_schedule' => true
                ],
            ];
            
            foreach ($holidays as $holidayData) {
                Holiday::updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'academic_year_id' => $academicYear->id,
                        'name' => $holidayData['name']
                    ],
                    array_merge($holidayData, ['is_active' => true])
                );
            }
            $this->command->info("Created Holidays: Tết, Hùng Vương, 30/4");
            
            // 6. Lesson Plans (only for Math subject if exists)
            $mathSubject = Subject::where('branch_id', $branchId)->where('code', 'MATH')->first();
            
            if ($mathSubject) {
                $lessonPlan = LessonPlan::updateOrCreate(
                    ['branch_id' => $branchId, 'code' => "LP_MATH_10_B{$branchId}"],
                    [
                        'subject_id' => $mathSubject->id,
                        'created_by' => 1,
                        'name' => 'Giáo án Toán 10 - Học kỳ 1',
                        'description' => 'Giáo án môn Toán lớp 10 theo chương trình mới',
                        'total_sessions' => 40,
                        'level' => 'high',
                        'academic_year' => '2024-2025',
                        'status' => 'approved',
                        'is_active' => true
                    ]
                );
                
                // Create sample sessions
                $sampleSessions = [
                    ['session_number' => 1, 'lesson_title' => 'Mệnh đề - Logic toán học', 'lesson_objectives' => 'Hiểu được khái niệm mệnh đề và các phép toán logic'],
                    ['session_number' => 2, 'lesson_title' => 'Tập hợp và các phép toán tập hợp', 'lesson_objectives' => 'Nắm vững định nghĩa tập hợp và các phép toán'],
                    ['session_number' => 3, 'lesson_title' => 'Các tập hợp số', 'lesson_objectives' => 'Phân biệt được các tập hợp số N, Z, Q, R'],
                    ['session_number' => 4, 'lesson_title' => 'Số gần đúng và sai số', 'lesson_objectives' => 'Biết cách tính toán với số gần đúng'],
                    ['session_number' => 5, 'lesson_title' => 'Hàm số và đồ thị', 'lesson_objectives' => 'Hiểu khái niệm hàm số và cách vẽ đồ thị'],
                ];
                
                foreach ($sampleSessions as $sessionData) {
                    LessonPlanSession::updateOrCreate(
                        ['lesson_plan_id' => $lessonPlan->id, 'session_number' => $sessionData['session_number']],
                        array_merge($sessionData, [
                            'lesson_content' => 'Nội dung chi tiết bài học',
                            'lesson_plan_url' => 'https://drive.google.com/file/sample',
                            'materials_url' => 'https://drive.google.com/file/materials',
                            'homework_url' => 'https://drive.google.com/file/homework',
                            'duration_minutes' => 45,
                            'sort_order' => $sessionData['session_number']
                        ])
                    );
                }
                
                $this->command->info("Created Lesson Plan: Toán 10 with {$lessonPlan->sessions()->count()} sessions");
            }
        }
        
        $this->command->info("\n========================================");
        $this->command->info('✓ Sample data seeded successfully!');
        $this->command->info('========================================');
        $this->command->info('Summary for each branch:');
        $this->command->info('• 1 Academic Year (2024-2025)');
        $this->command->info('• 2 Semesters');
        $this->command->info('• 2 Study Periods (Morning, Afternoon)');
        $this->command->info('• 4 Rooms');
        $this->command->info('• 3 Holidays');
        $this->command->info('• 1 Lesson Plan (Toán 10) with 5 sessions');
    }
}
