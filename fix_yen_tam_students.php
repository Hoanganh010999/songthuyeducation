<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$yenTamBranchId = 1;

echo "=== KIỂM TRA VÀ SỬA HỌC VIÊN YÊN TÂM ===\n\n";

// Bước 1: Lấy tất cả học viên trong các lớp Yên Tâm
$studentsToFix = \DB::table('class_students')
    ->join('classes', 'class_students.class_id', '=', 'classes.id')
    ->join('students', 'class_students.student_id', '=', 'students.id')
    ->join('users', 'students.user_id', '=', 'users.id')
    ->where('classes.branch_id', $yenTamBranchId)
    ->where('class_students.status', 'active')
    ->where('students.branch_id', '!=', $yenTamBranchId)
    ->select(
        'students.id as student_id',
        'students.user_id',
        'students.branch_id as current_branch_id',
        'users.name',
        'users.email',
        'classes.name as class_name'
    )
    ->distinct()
    ->get();

echo "Số học viên cần sửa: " . $studentsToFix->count() . "\n\n";

if ($studentsToFix->count() == 0) {
    echo "✅ Không có học viên nào cần sửa!\n";
    exit(0);
}

// Hiển thị danh sách
echo "=== DANH SÁCH HỌC VIÊN CẦN SỬA ===\n";
foreach ($studentsToFix as $student) {
    $currentBranch = \DB::table('branches')->find($student->current_branch_id);
    echo sprintf(
        "Student ID: %d | %s (%s) | Class: %s | Branch hiện tại: %s (ID: %d)\n",
        $student->student_id,
        $student->name,
        $student->email,
        $student->class_name,
        $currentBranch->name ?? 'N/A',
        $student->current_branch_id
    );
}

echo "\n=== BẮT ĐẦU SỬA ===\n";

// Bước 2: Update students.branch_id
$studentIds = $studentsToFix->pluck('student_id')->toArray();
$affected = \DB::table('students')
    ->whereIn('id', $studentIds)
    ->update(['branch_id' => $yenTamBranchId]);

echo "✅ Đã cập nhật branch_id cho {$affected} học viên\n";

// Bước 3: Xóa branch_user records (nếu có)
$userIds = $studentsToFix->pluck('user_id')->unique()->toArray();
$deletedBranchUser = \DB::table('branch_user')
    ->whereIn('user_id', $userIds)
    ->where('branch_id', '!=', $yenTamBranchId)
    ->delete();

if ($deletedBranchUser > 0) {
    echo "✅ Đã xóa {$deletedBranchUser} branch_user records sai\n";
}

// Bước 4: Verify kết quả
echo "\n=== KIỂM TRA LẠI ===\n";
$remaining = \DB::table('class_students')
    ->join('classes', 'class_students.class_id', '=', 'classes.id')
    ->join('students', 'class_students.student_id', '=', 'students.id')
    ->where('classes.branch_id', $yenTamBranchId)
    ->where('class_students.status', 'active')
    ->where('students.branch_id', '!=', $yenTamBranchId)
    ->count();

if ($remaining == 0) {
    echo "✅ HOÀN THÀNH! Tất cả học viên Yên Tâm đã có branch_id đúng.\n";
} else {
    echo "⚠️  Còn {$remaining} học viên chưa được sửa\n";
}

echo "\n=== THỐNG KÊ ===\n";
$totalYenTamStudents = \DB::table('students')
    ->where('branch_id', $yenTamBranchId)
    ->where('is_active', true)
    ->count();
echo "Tổng số học viên Yên Tâm: {$totalYenTamStudents}\n";
