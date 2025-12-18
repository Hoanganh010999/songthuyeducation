<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Hash;

class StudentsSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        
        if (!$branch) {
            $this->command->error('No branch found! Please create a branch first.');
            return;
        }

        $students = [
            ['name' => 'Nguyễn Văn An', 'email' => 'student1@school.edu.vn', 'code' => 'SV001', 'phone' => '0901234001', 'gender' => 'male', 'dob' => '2005-01-15'],
            ['name' => 'Trần Thị Bích', 'email' => 'student2@school.edu.vn', 'code' => 'SV002', 'phone' => '0901234002', 'gender' => 'female', 'dob' => '2005-03-22'],
            ['name' => 'Lê Minh Châu', 'email' => 'student3@school.edu.vn', 'code' => 'SV003', 'phone' => '0901234003', 'gender' => 'female', 'dob' => '2005-05-10'],
            ['name' => 'Phạm Quốc Duy', 'email' => 'student4@school.edu.vn', 'code' => 'SV004', 'phone' => '0901234004', 'gender' => 'male', 'dob' => '2005-07-08'],
            ['name' => 'Hoàng Thị Hương', 'email' => 'student5@school.edu.vn', 'code' => 'SV005', 'phone' => '0901234005', 'gender' => 'female', 'dob' => '2005-02-14'],
            ['name' => 'Đỗ Văn Khoa', 'email' => 'student6@school.edu.vn', 'code' => 'SV006', 'phone' => '0901234006', 'gender' => 'male', 'dob' => '2005-09-25'],
            ['name' => 'Vũ Thị Linh', 'email' => 'student7@school.edu.vn', 'code' => 'SV007', 'phone' => '0901234007', 'gender' => 'female', 'dob' => '2005-11-30'],
            ['name' => 'Bùi Minh Nam', 'email' => 'student8@school.edu.vn', 'code' => 'SV008', 'phone' => '0901234008', 'gender' => 'male', 'dob' => '2005-04-18'],
            ['name' => 'Đinh Thị Oanh', 'email' => 'student9@school.edu.vn', 'code' => 'SV009', 'phone' => '0901234009', 'gender' => 'female', 'dob' => '2005-06-12'],
            ['name' => 'Trương Văn Phong', 'email' => 'student10@school.edu.vn', 'code' => 'SV010', 'phone' => '0901234010', 'gender' => 'male', 'dob' => '2005-08-05'],
        ];

        $createdStudents = [];
        
        foreach ($students as $studentData) {
            $user = User::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'employee_code' => $studentData['code'],
                'password' => Hash::make('password123'),
                'phone' => $studentData['phone'],
                'gender' => $studentData['gender'],
                'date_of_birth' => $studentData['dob'],
            ]);
            
            // Verify email
            $user->email_verified_at = now();
            $user->save();
            
            // Assign student role (assuming role_id 3 is student)
            $user->roles()->attach(3); // You may need to adjust this based on your roles table
            
            $createdStudents[] = $user;
            
            $this->command->info("✓ Created student: {$user->name} ({$user->email})");
        }

        // Add all students to IELTS 5.0 class (Class ID 4)
        $class = ClassModel::find(4);
        
        if ($class) {
            $this->command->info("\nAdding students to class: {$class->name}");
            
            foreach ($createdStudents as $student) {
                $class->students()->create([
                    'student_id' => $student->id,
                    'enrollment_date' => now(),
                    'status' => 'active',
                ]);
                
                $this->command->info("  ✓ Added {$student->name} to class");
            }
        } else {
            $this->command->warn("\nClass ID 4 not found. Students created but not enrolled.");
        }

        $this->command->info("\n✅ Successfully created " . count($createdStudents) . " students!");
        $this->command->info("📧 Default password for all students: password123");
    }
}
