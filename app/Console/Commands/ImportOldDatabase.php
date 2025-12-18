<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Models\ClassStudent;
use App\Models\Attendance;
use App\Models\ClassLessonSession;
use App\Models\Role;
use App\Models\Customer;
use Carbon\Carbon;

class ImportOldDatabase extends Command
{
    protected $signature = 'import:old-database 
                            {path : Path to old_database folder}
                            {--dry-run : Run without saving to database}
                            {--branch=YT01 : Branch code}';

    protected $description = 'Import data from old CSV files';

    private $branch;
    private $isDryRun;
    private $stats = [
        'teachers' => 0,
        'parents' => 0,
        'students' => 0,
        'classes' => 0,
        'enrollments' => 0,
        'attendances' => 0,
    ];
    
    private $teacherMap = [];
    private $parentMap = [];
    private $studentMap = [];
    private $classMap = [];
    private $defaultProduct;
    private $adminId;
    private $dummyCustomer;

    public function handle()
    {
        $this->isDryRun = $this->option('dry-run');
        $path = $this->argument('path');

        if ($this->isDryRun) {
            $this->warn('🔍 DRY-RUN MODE - No data will be saved');
        }

        $this->info('📥 Starting import from: ' . $path);
        $this->newLine();

        // Get admin ID
        $admin = User::where('email', 'admin@songthuy.edu.vn')->first();
        $this->adminId = $admin->id ?? 1;

        DB::beginTransaction();

        try {
            // Step 1: Create branch
            $this->step1_CreateBranch();
            
            // Step 2: Create subjects
            $this->step2_CreateSubjects();
            
            // Step 3: Create teachers
            $this->step3_CreateTeachers();
            
            // Step 4: Import each CSV file
            $csvFiles = glob($path . '/*.csv');
            
            foreach ($csvFiles as $csvFile) {
                $filename = basename($csvFile);
                
                // Skip schedule file
                if (str_contains($filename, 'THỜI KHOÁ BIỂU')) {
                    continue;
                }
                
                $this->newLine();
                $this->info("📄 Processing: $filename");
                $this->importCsvFile($csvFile);
            }

            // Step 5: Show summary
            $this->showSummary();

            if ($this->isDryRun) {
                DB::rollBack();
                $this->warn('🔄 Rolled back (dry-run mode)');
            } else {
                DB::commit();
                $this->info('✅ Import completed successfully!');
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function step1_CreateBranch()
    {
        $this->info('Step 1: Creating branch...');
        
        $branchCode = $this->option('branch');
        
        if (!$this->isDryRun) {
            $this->branch = Branch::firstOrCreate(
                ['code' => $branchCode],
                [
                    'name' => 'SongThuy - Yên Tâm',
                    'is_active' => true,
                    'is_headquarters' => true,
                    'address' => 'Yên Định',
                    'city' => 'Thanh Hóa',
                    'district' => 'Yên Định',
                    'description' => 'Cơ sở Yên Tâm - Yên Định - Thanh Hóa',
                ]
            );
        } else {
            $this->branch = (object)['id' => 1, 'code' => $branchCode];
        }
        
        $this->info("✓ Branch: {$this->branch->name} ({$this->branch->code})");
    }

    private function step2_CreateSubjects()
    {
        $this->info('Step 2: Creating subjects...');
        
        $subjects = [
            ['code' => 'PRE_IELTS', 'name' => 'Pre IELTS', 'level' => 'high'],
            ['code' => 'ISS', 'name' => 'ISS', 'level' => 'middle'],
            ['code' => 'KINDY', 'name' => 'YT Kindy', 'level' => 'elementary'],
        ];

        if (!$this->isDryRun) {
            foreach ($subjects as $subjectData) {
                Subject::firstOrCreate(
                    ['code' => $subjectData['code']],
                    [
                        'name' => $subjectData['name'],
                        'level' => $subjectData['level'],
                        'branch_id' => $this->branch->id,
                    ]
                );
            }
            
            // Create default product for old enrollments
            $this->defaultProduct = \App\Models\Product::firstOrCreate(
                ['name' => 'Khóa học cũ (Legacy)'],
                [
                    'type' => 'course',
                    'price' => 0,
                    'is_active' => true,
                ]
            );
            
            // Create dummy customer for students without parent
            $this->dummyCustomer = Customer::firstOrCreate(
                ['phone' => '0000000000'],
                [
                    'code' => 'CUST_UNKNOWN',
                    'name' => 'Chưa có thông tin phụ huynh',
                    'email' => 'unknown@songthuy.edu.vn',
                    'branch_id' => $this->branch->id,
                    'stage' => Customer::STAGE_LEAD,
                    'stage_order' => 1,
                    'source' => 'legacy_import',
                ]
            );
        }
        
        $this->info('✓ Created 3 subjects');
    }

    private function step3_CreateTeachers()
    {
        $this->info('Step 3: Creating teachers...');
        
        $teachers = [
            ['name' => 'Mr. Mike', 'email' => 'mike@songthuy.edu.vn'],
            ['name' => 'Mrs. Phượng', 'email' => 'phuong@songthuy.edu.vn'],
            ['name' => 'Ms. Linh', 'email' => 'linh@songthuy.edu.vn'],
            ['name' => 'Mrs. Thùy', 'email' => 'thuy@songthuy.edu.vn'],
        ];

        $teacherRole = null;
        if (!$this->isDryRun) {
            $teacherRole = Role::firstOrCreate(
                ['name' => 'teacher'],
                ['description' => 'Teacher']
            );
        }

        foreach ($teachers as $teacherData) {
            if (!$this->isDryRun) {
                $teacher = User::firstOrCreate(
                    ['email' => $teacherData['email']],
                    [
                        'name' => $teacherData['name'],
                        'password' => Hash::make('123456'),
                    ]
                );
                
                // Assign role
                if (!$teacher->roles()->where('role_id', $teacherRole->id)->exists()) {
                    $teacher->roles()->attach($teacherRole->id);
                }
                
                // Link to branch
                if (!$teacher->branches()->where('branch_id', $this->branch->id)->exists()) {
                    $teacher->branches()->attach($this->branch->id);
                }
                
                $this->teacherMap[$teacherData['name']] = $teacher;
            }
            
            $this->stats['teachers']++;
        }
        
        $this->info("✓ Created {$this->stats['teachers']} teachers");
    }

    private function importCsvFile($csvFile)
    {
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            $this->error("Cannot open file: $csvFile");
            return;
        }

        // Read first 3 rows to understand structure
        $row1 = fgetcsv($handle); // Title row
        $row2 = fgetcsv($handle); // Could be teachers or empty
        $row3 = fgetcsv($handle); // Headers
        
        $headers = $row3;
        
        // Extract class name from first row
        $className = $this->extractClassName($row1[0] ?? '');
        
        if (!$className) {
            $this->warn("Cannot extract class name from file");
            fclose($handle);
            return;
        }

        // Create class
        $class = $this->createClass($className);
        
        $studentCount = 0;
        $attendanceCount = 0;
        
        // Read student rows
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            $studentData = $this->parseStudentRow($headers, $row);
            
            if (!isset($studentData['name']) || empty($studentData['name'])) {
                continue;
            }
            
            // Import student
            $result = $this->importStudent($studentData, $class);
            
            if ($result) {
                $studentCount++;
                $attendanceCount += $result['attendance_count'];
            }
        }

        fclose($handle);
        
        $this->info("  ✓ Imported $studentCount students");
        $this->info("  ✓ Created $attendanceCount attendance records");
    }

    private function extractClassName($titleRow)
    {
        // Examples: "DANH SÁCH LỚP Pre IE.K1", "DANH SÁCH LỚP KINDY"
        if (preg_match('/LỚP\s+(.+?)(?:,|$)/u', $titleRow, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/Pre IE\.K(\d)/u', $titleRow, $matches)) {
            return 'Pre IELTS K' . $matches[1];
        }
        
        if (preg_match('/KINDY/ui', $titleRow)) {
            return 'YT Kindy';
        }
        
        if (preg_match('/Pre STARTERS/ui', $titleRow)) {
            return 'ISS 1';
        }
        
        return null;
    }

    private function createClass($className)
    {
        // Check if already created
        if (isset($this->classMap[$className])) {
            return $this->classMap[$className];
        }

        // Determine teacher and subject
        $teacherName = 'Mrs. Phượng'; // Default
        $subjectCode = 'KINDY'; // Default
        
        if (str_contains($className, 'IELTS K1')) {
            $teacherName = 'Mr. Mike';
            $subjectCode = 'PRE_IELTS';
        } elseif (str_contains($className, 'IELTS K2')) {
            $teacherName = 'Ms. Linh';
            $subjectCode = 'PRE_IELTS';
        } elseif (str_contains($className, 'ISS')) {
            $teacherName = 'Mrs. Phượng';
            $subjectCode = 'ISS';
        }

        if (!$this->isDryRun) {
            $teacher = $this->teacherMap[$teacherName] ?? null;
            $subject = Subject::where('code', $subjectCode)->first();
            
            $class = ClassModel::firstOrCreate(
                ['code' => $this->generateClassCode($className)],
                [
                    'name' => $className,
                    'branch_id' => $this->branch->id,
                    'homeroom_teacher_id' => $teacher?->id,
                    'subject_id' => $subject?->id,
                    'academic_year' => '2024-2025',
                    'level' => 'high',
                    'capacity' => 20,
                    'status' => 'active',
                ]
            );
        } else {
            $class = (object)[
                'id' => count($this->classMap) + 1,
                'name' => $className,
                'code' => $this->generateClassCode($className),
            ];
        }

        $this->classMap[$className] = $class;
        $this->stats['classes']++;
        
        return $class;
    }

    private function generateClassCode($className)
    {
        $code = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $className));
        return substr($code, 0, 50) . '_2024';
    }

    private function parseStudentRow($headers, $row)
    {
        $data = [];
        
        foreach ($headers as $index => $header) {
            $value = $row[$index] ?? '';
            $data[$header] = $value;
        }
        
        // Find standard fields
        $studentName = $data['Họ tên HV'] ?? $data['Họ tên hv'] ?? '';
        $englishName = $data['Tên tiếng anh'] ?? '';
        $parentName = $data['Phụ huynh'] ?? '';
        $parentPhone = $data['SĐT'] ?? $data['Số điện thoại'] ?? '';
        
        // Financial data
        $totalHours = intval($data['Lộ trình'] ?? 0);
        $completedHours = intval($data['Số buổi đã học'] ?? $data['Số giờ đã học'] ?? 0);
        $remainingHours = intval($data['Còn'] ?? 0);
        
        // Status
        $status = $data['Tình trạng'] ?? '';
        
        // Extract attendance dates (columns after financial data)
        $attendanceDates = [];
        $datePattern = '/^\d{1,2}\/\d{1,2}$/'; // Format: 26/06, 9/7, etc.
        
        foreach ($headers as $index => $header) {
            if (preg_match($datePattern, trim($header))) {
                $value = $row[$index] ?? '';
                if (!empty($value)) {
                    $attendanceDates[trim($header)] = trim($value);
                }
            }
        }
        
        return [
            'name' => trim($studentName),
            'english_name' => trim($englishName),
            'parent_name' => trim($parentName),
            'parent_phone' => $this->cleanPhone($parentPhone),
            'total_hours' => $totalHours,
            'completed_hours' => $completedHours,
            'remaining_hours' => $remainingHours,
            'status' => $status,
            'attendance_dates' => $attendanceDates,
        ];
    }

    private function cleanPhone($phone)
    {
        $phone = trim($phone);
        
        // Handle special cases
        if (in_array(strtoupper($phone), ['ZALO', 'FB', '']) || strlen($phone) < 9) {
            return '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        }
        
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        return $phone;
    }

    private function importStudent($studentData, $class)
    {
        if (empty($studentData['name'])) {
            return false;
        }

        // Create or get parent
        $parent = $this->createParent($studentData['parent_name'], $studentData['parent_phone']);
        
        // Create student
        $student = $this->createStudent($studentData, $parent);
        
        if (!$student) {
            return false;
        }

        // Create enrollment
        $this->createEnrollment($student, $parent, $class, $studentData);
        
        // Link to class
        $this->linkStudentToClass($student, $class, $studentData['status']);
        
        // Create attendance records
        $attendanceCount = $this->createAttendanceRecords($student, $class, $studentData['attendance_dates']);
        
        return [
            'student' => $student,
            'attendance_count' => $attendanceCount,
        ];
    }

    private function createParent($name, $phone)
    {
        if (empty($name) || empty($phone)) {
            return null;
        }

        // Check if already created
        $key = $phone;
        if (isset($this->parentMap[$key])) {
            return $this->parentMap[$key];
        }

        if (!$this->isDryRun) {
            // Generate codes FIRST using stats counter  
            $parentCode = 'PH' . str_pad($this->stats['parents'] + 1, 5, '0', STR_PAD_LEFT);
            $customerCode = 'CUST' . str_pad($this->stats['parents'] + 1, 5, '0', STR_PAD_LEFT);
            
            // Create user with code-based email
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => ucwords(strtolower($name)),
                    'email' => strtolower($parentCode) . '@songthuy.edu.vn',
                    'password' => Hash::make('123456'),
                ]
            );
            
            // Create customer (for enrollment)
            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                [
                    'code' => $customerCode,
                    'name' => ucwords(strtolower($name)),
                    'email' => strtolower($customerCode) . '@songthuy.edu.vn',
                    'branch_id' => $this->branch->id,
                    'stage' => Customer::STAGE_CLOSED_WON,
                    'stage_order' => 6,
                    'source' => 'legacy_import',
                ]
            );
            
            // Create parent
            $parent = ParentModel::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'parent_code' => $parentCode,
                    'branch_id' => $this->branch->id,
                ]
            );
            
            // Store customer ID in parent object for enrollment
            $parent->customer_id = $customer->id;
            
            // Link to branch
            if (!$user->branches()->where('branch_id', $this->branch->id)->exists()) {
                $user->branches()->attach($this->branch->id);
            }
        } else {
            $parent = (object)['id' => count($this->parentMap) + 1, 'name' => $name, 'customer_id' => 1];
        }

        $this->parentMap[$key] = $parent;
        $this->stats['parents']++;
        
        return $parent;
    }

    private function createStudent($studentData, $parent)
    {
        $name = $studentData['name'];
        
        // Check if already created (by name + parent)
        $key = $name . '_' . ($parent->id ?? 'noparent');
        if (isset($this->studentMap[$key])) {
            return $this->studentMap[$key];
        }

        if (!$this->isDryRun) {
            // Generate student code FIRST using stats counter
            $studentCode = 'STD' . date('Y') . str_pad($this->stats['students'] + 1, 5, '0', STR_PAD_LEFT);
            
            // Create user with code-based email
            $user = User::create([
                'name' => ucwords(strtolower($name)),
                'email' => strtolower($studentCode) . '@songthuy.edu.vn',
                'password' => Hash::make('123456'),
                'metadata' => json_encode([
                    'english_name' => $studentData['english_name'],
                ]),
            ]);
            
            // Create student
            $student = Student::create([
                'user_id' => $user->id,
                'student_code' => $studentCode,
                'branch_id' => $this->branch->id,
                'enrollment_date' => now(),
                'is_active' => !str_contains(strtolower($studentData['status']), 'dừng'),
            ]);
            
            // Link to branch
            if (!$user->branches()->where('branch_id', $this->branch->id)->exists()) {
                $user->branches()->attach($this->branch->id);
            }
            
            // Link to parent
            if ($parent) {
                $student->parents()->syncWithoutDetaching([
                    $parent->id => [
                        'relationship' => 'parent',
                        'is_primary' => true,
                    ]
                ]);
            }
        } else {
            $student = (object)[
                'id' => count($this->studentMap) + 1,
                'user_id' => count($this->studentMap) + 100,
                'name' => $name,
            ];
        }

        $this->studentMap[$key] = $student;
        $this->stats['students']++;
        
        return $student;
    }

    private function createEnrollment($student, $parent, $class, $studentData)
    {
        if ($this->isDryRun) {
            $this->stats['enrollments']++;
            return;
        }

        Enrollment::create([
            'code' => 'ENR' . date('Ymd') . str_pad($this->stats['enrollments'] + 1, 4, '0', STR_PAD_LEFT),
            'customer_id' => $parent->customer_id ?? $this->dummyCustomer->id, // Use customer from parent or dummy
            'student_type' => Student::class,
            'student_id' => $student->id,
            'product_id' => $this->defaultProduct->id,
            'branch_id' => $this->branch->id,
            'total_sessions' => $studentData['total_hours'],
            'attended_sessions' => $studentData['completed_hours'],
            'remaining_sessions' => $studentData['remaining_hours'],
            'original_price' => $studentData['total_hours'] * 100000, // Estimate
            'final_price' => $studentData['total_hours'] * 100000,
            'status' => $this->mapEnrollmentStatus($studentData['status']),
            'start_date' => now()->subMonths(6),
        ]);
        
        $this->stats['enrollments']++;
    }

    private function linkStudentToClass($student, $class, $status)
    {
        if ($this->isDryRun) {
            return;
        }

        ClassStudent::firstOrCreate([
            'class_id' => $class->id,
            'student_id' => $student->user_id,
        ], [
            'enrollment_date' => now()->subMonths(6),
            'status' => $this->mapClassStudentStatus($status),
        ]);
    }

    private function createAttendanceRecords($student, $class, $attendanceDates)
    {
        $count = 0;
        
        foreach ($attendanceDates as $dateStr => $value) {
            if (empty($value) || $this->isDryRun) {
                continue;
            }
            
            // Parse date
            $date = $this->parseDate($dateStr);
            if (!$date) {
                continue;
            }
            
            // Create or get session
            $session = ClassLessonSession::firstOrCreate([
                'class_id' => $class->id,
                'scheduled_date' => $date,
            ], [
                'session_number' => ClassLessonSession::where('class_id', $class->id)->count() + 1,
                'status' => 'completed',
            ]);
            
            // Map attendance status
            $status = $this->mapAttendanceStatus($value);
            if (!$status) {
                continue;
            }
            
            // Create attendance
            Attendance::firstOrCreate([
                'session_id' => $session->id,
                'student_id' => $student->user_id,
            ], [
                'status' => $status,
                'marked_by' => $this->adminId,
            ]);
            
            $count++;
        }
        
        $this->stats['attendances'] += $count;
        return $count;
    }

    private function parseDate($dateStr)
    {
        // Format: 26/06, 9/7, etc.
        if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $dateStr, $matches)) {
            $day = intval($matches[1]);
            $month = intval($matches[2]);
            $year = 2024; // Assume 2024
            
            try {
                return Carbon::create($year, $month, $day);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    private function mapAttendanceStatus($value)
    {
        $value = strtoupper(trim($value));
        
        if (in_array($value, ['2', '2,5', '1'])) return 'present';
        if ($value === '0') return 'absent';
        if (in_array($value, ['OFF', 'NGHỈ'])) return 'excused';
        if (in_array($value, ['DỪNG HỌC'])) return null;
        
        return 'present'; // Default
    }

    private function mapEnrollmentStatus($status)
    {
        $status = strtolower(trim($status));
        
        if (str_contains($status, 'dừng')) return 'cancelled';
        if (str_contains($status, 'nghỉ')) return 'dropped';
        if (str_contains($status, 'đăng ký')) return 'active';
        
        return 'active';
    }

    private function mapClassStudentStatus($status)
    {
        $status = strtolower(trim($status));
        
        if (str_contains($status, 'dừng')) return 'dropped';
        if (str_contains($status, 'nghỉ')) return 'dropped';
        if (str_contains($status, 'đăng ký')) return 'active';
        
        return 'active';
    }


    private function showSummary()
    {
        $this->newLine(2);
        $this->info('📊 IMPORT SUMMARY:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Teachers', $this->stats['teachers']],
                ['Parents', $this->stats['parents']],
                ['Students', $this->stats['students']],
                ['Classes', $this->stats['classes']],
                ['Enrollments', $this->stats['enrollments']],
                ['Attendances', $this->stats['attendances']],
            ]
        );
    }
}

