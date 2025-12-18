<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== KIỂM TRA TẤT CẢ HỌC VIÊN ===\n\n";

// Lấy tất cả students và check branch
$allStudents = \DB::table('students')
    ->join('users', 'students.user_id', '=', 'users.id')
    ->leftJoin('branches', 'students.branch_id', '=', 'branches.id')
    ->where('students.is_active', true)
    ->select(
        'students.id as student_id',
        'students.user_id',
        'students.branch_id',
        'users.name',
        'users.email',
        'branches.name as branch_name'
    )
    ->orderBy('students.branch_id')
    ->orderBy('users.name')
    ->get();

echo "Tổng số học viên active: " . $allStudents->count() . "\n\n";

// Nhóm theo branch
$byBranch = [];
foreach ($allStudents as $student) {
    $branchId = $student->branch_id ?? 0;
    if (!isset($byBranch[$branchId])) {
        $byBranch[$branchId] = [];
    }
    $byBranch[$branchId][] = $student;
}

echo "=== THỐNG KÊ THEO BRANCH ===\n";
foreach ($byBranch as $branchId => $students) {
    $branchName = $students[0]->branch_name ?? 'NULL';
    echo "Branch {$branchId} ({$branchName}): " . count($students) . " học viên\n";
}

// Chi tiết học viên của từng branch
echo "\n=== CHI TIẾT HỌC VIÊN ===\n\n";

foreach ([1, 2] as $branchId) {
    if (isset($byBranch[$branchId])) {
        $branchName = $byBranch[$branchId][0]->branch_name;
        echo "=== BRANCH {$branchId}: {$branchName} ===\n";
        
        foreach ($byBranch[$branchId] as $student) {
            // Check enrollments
            $enrollments = \DB::table('class_students')
                ->join('classes', 'class_students.class_id', '=', 'classes.id')
                ->where('class_students.student_id', $student->student_id)
                ->select('classes.name', 'classes.branch_id', 'class_students.status')
                ->get();
            
            $classInfo = '';
            if ($enrollments->count() > 0) {
                $classNames = $enrollments->pluck('name')->toArray();
                $classInfo = ' | Lớp: ' . implode(', ', $classNames);
            } else {
                $classInfo = ' | ⚠️  Không có lớp';
            }
            
            echo "  Student {$student->student_id}: {$student->name} ({$student->email}){$classInfo}\n";
        }
        echo "\n";
    }
}

// Tìm học viên user00129 cụ thể
echo "\n=== TÌM USER user00129@songthuy.edu.vn ===\n";
$user00129 = \DB::table('students')
    ->join('users', 'students.user_id', '=', 'users.id')
    ->where('users.email', 'user00129@songthuy.edu.vn')
    ->select('students.*', 'users.name', 'users.email')
    ->first();

if ($user00129) {
    echo "Student ID: {$user00129->id}\n";
    echo "User ID: {$user00129->user_id}\n";
    echo "Name: {$user00129->name}\n";
    echo "Email: {$user00129->email}\n";
    echo "Branch ID: {$user00129->branch_id}\n";
    
    $enrollments = \DB::table('class_students')
        ->join('classes', 'class_students.class_id', '=', 'classes.id')
        ->where('class_students.student_id', $user00129->id)
        ->select('classes.id', 'classes.name', 'classes.branch_id', 'class_students.status')
        ->get();
    
    echo "\nEnrollments (" . $enrollments->count() . "):\n";
    foreach ($enrollments as $enrollment) {
        echo "  - Class {$enrollment->id}: {$enrollment->name} (Branch {$enrollment->branch_id}) - Status: {$enrollment->status}\n";
    }
}
