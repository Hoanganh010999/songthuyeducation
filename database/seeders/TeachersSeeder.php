<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeachersSeeder extends Seeder
{
    /**
     * Seed teachers for branches 1, 2, 3
     */
    public function run(): void
    {
        // Danh sách giáo viên mẫu
        $teachersData = [
            // Branch 1 - Hà Nội
            [
                'branch_id' => 1,
                'teachers' => [
                    [
                        'name' => 'Nguyễn Thị Hoa',
                        'phone' => '0901234567',
                        'email' => 'nguyen.hoa@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Toán - Lý'
                    ],
                    [
                        'name' => 'Trần Văn Nam',
                        'phone' => '0901234568',
                        'email' => 'tran.nam@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Văn - Sử'
                    ],
                    [
                        'name' => 'Lê Thị Mai',
                        'phone' => '0901234569',
                        'email' => 'le.mai@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Ngoại ngữ'
                    ],
                    [
                        'name' => 'Phạm Minh Tuấn',
                        'phone' => '0901234570',
                        'email' => 'pham.tuan@school.edu.vn',
                        'position_code' => 'GVCN',
                        'department_name' => 'Khoa Toán - Lý'
                    ],
                    [
                        'name' => 'Hoàng Thị Lan',
                        'phone' => '0901234571',
                        'email' => 'hoang.lan@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Văn - Sử'
                    ],
                    [
                        'name' => 'Đặng Văn Hùng',
                        'phone' => '0901234572',
                        'email' => 'dang.hung@school.edu.vn',
                        'position_code' => 'GV03',
                        'department_name' => 'Khoa Ngoại ngữ'
                    ],
                    [
                        'name' => 'Vũ Thị Thu',
                        'phone' => '0901234573',
                        'email' => 'vu.thu@school.edu.vn',
                        'position_code' => 'GVTT',
                        'department_name' => 'Khoa Toán - Lý'
                    ],
                ]
            ],
            // Branch 2 - Hồ Chí Minh
            [
                'branch_id' => 2,
                'teachers' => [
                    [
                        'name' => 'Ngô Minh Châu',
                        'phone' => '0902234567',
                        'email' => 'ngo.chau@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Khoa học Tự nhiên'
                    ],
                    [
                        'name' => 'Bùi Văn Đức',
                        'phone' => '0902234568',
                        'email' => 'bui.duc@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Xã hội'
                    ],
                    [
                        'name' => 'Trương Thị Hằng',
                        'phone' => '0902234569',
                        'email' => 'truong.hang@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Ngoại ngữ'
                    ],
                    [
                        'name' => 'Lý Minh Khánh',
                        'phone' => '0902234570',
                        'email' => 'ly.khanh@school.edu.vn',
                        'position_code' => 'GVCN',
                        'department_name' => 'Khoa Khoa học Tự nhiên'
                    ],
                    [
                        'name' => 'Phan Thị Ngọc',
                        'phone' => '0902234571',
                        'email' => 'phan.ngoc@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Xã hội'
                    ],
                    [
                        'name' => 'Đinh Văn Phong',
                        'phone' => '0902234572',
                        'email' => 'dinh.phong@school.edu.vn',
                        'position_code' => 'GV03',
                        'department_name' => 'Khoa Ngoại ngữ'
                    ],
                ]
            ],
            // Branch 3 - Đà Nẵng
            [
                'branch_id' => 3,
                'teachers' => [
                    [
                        'name' => 'Võ Thị Quỳnh',
                        'phone' => '0903234567',
                        'email' => 'vo.quynh@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Chính'
                    ],
                    [
                        'name' => 'Dương Văn Sơn',
                        'phone' => '0903234568',
                        'email' => 'duong.son@school.edu.vn',
                        'position_code' => 'GV01',
                        'department_name' => 'Khoa Phụ'
                    ],
                    [
                        'name' => 'Mai Thị Tâm',
                        'phone' => '0903234569',
                        'email' => 'mai.tam@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Chính'
                    ],
                    [
                        'name' => 'Hồ Minh Vũ',
                        'phone' => '0903234570',
                        'email' => 'ho.vu@school.edu.vn',
                        'position_code' => 'GVCN',
                        'department_name' => 'Khoa Phụ'
                    ],
                    [
                        'name' => 'Lâm Thị Yến',
                        'phone' => '0903234571',
                        'email' => 'lam.yen@school.edu.vn',
                        'position_code' => 'GV02',
                        'department_name' => 'Khoa Chính'
                    ],
                ]
            ],
        ];

        // Get teacher role
        $teacherRole = Role::where('name', 'teacher')->first();
        
        if (!$teacherRole) {
            $this->command->error('Teacher role not found! Please run TeacherPositionsSeeder first.');
            return;
        }

        foreach ($teachersData as $branchData) {
            $branchId = $branchData['branch_id'];
            $branch = Branch::find($branchId);
            
            if (!$branch) {
                $this->command->warn("Branch {$branchId} not found, skipping...");
                continue;
            }

            $this->command->info("Seeding teachers for branch: {$branch->name}");

            foreach ($branchData['teachers'] as $teacherData) {
                // Create or get user
                $user = User::where('phone', $teacherData['phone'])->first();
                
                if (!$user) {
                    $user = User::create([
                        'name' => $teacherData['name'],
                        'phone' => $teacherData['phone'],
                        'email' => $teacherData['email'],
                        'password' => Hash::make('password123'),
                        'employment_status' => 'active',
                        'email_verified_at' => now(),
                    ]);
                    
                    $this->command->info("  Created user: {$user->name}");
                }

                // Attach to branch if not already
                if (!$user->branches()->where('branch_id', $branchId)->exists()) {
                    $user->branches()->attach($branchId, [
                        'is_primary' => true,
                    ]);
                    
                    $this->command->info("  Attached to branch: {$branch->name}");
                }

                // Get or create department
                $department = Department::where('branch_id', $branchId)
                    ->where('name', $teacherData['department_name'])
                    ->first();
                
                if (!$department) {
                    // Get position for default_position_id
                    $position = Position::where('code', $teacherData['position_code'])->first();
                    
                    $department = Department::create([
                        'branch_id' => $branchId,
                        'name' => $teacherData['department_name'],
                        'code' => 'DEPT_B' . $branchId . '_' . strtoupper(Str::slug($teacherData['department_name'])),
                        'description' => 'Phòng ban ' . $teacherData['department_name'],
                        'default_position_id' => $position?->id,
                        'is_active' => true,
                        'sort_order' => 0,
                    ]);
                    
                    $this->command->info("  Created department: {$department->name}");
                }

                // Get position by code
                $position = Position::where('code', $teacherData['position_code'])->first();
                
                if (!$position) {
                    $this->command->warn("  Position {$teacherData['position_code']} not found, skipping assignment");
                    continue;
                }

                // Attach user to department with position
                if (!$user->departments()->where('department_id', $department->id)->exists()) {
                    $user->departments()->attach($department->id, [
                        'position_id' => $position->id,
                        'is_head' => false,
                        'is_deputy' => false,
                        'start_date' => now()->subMonths(rand(1, 12)),
                    ]);
                    
                    $this->command->info("  Assigned to department: {$department->name} with position: {$position->name}");
                }

                // Attach teacher role if not already
                if (!$user->roles()->where('role_id', $teacherRole->id)->exists()) {
                    $user->roles()->attach($teacherRole->id);
                }
            }
        }

        $this->command->info('Teachers seeded successfully!');
        $this->command->info('Default password for all teachers: password123');
    }
}

