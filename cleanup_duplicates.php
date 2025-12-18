<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "  DỌN DẸP DUPLICATE USERS\n";
echo "  ⚠️  CHẠY SAU KHI ĐÃ BACKUP!\n";
echo "========================================\n\n";

$dryRun = true; // Set false để thực sự xóa

if ($dryRun) {
    echo "🔍 CHẾ ĐỘ DRY-RUN (không xóa thật)\n\n";
} else {
    echo "⚠️  CHẾ ĐỘ THỰC THI - SẼ XÓA DỮ LIỆU!\n\n";
    echo "Nhấn Ctrl+C trong 5 giây để hủy...\n";
    sleep(5);
}

$deletedCount = 0;
$keptCount = 0;

// 1. Xóa users có phone fake (260, 80, "Ph? huynh", etc)
echo "📱 1. XÓA USERS CÓ PHONE FAKE:\n";
echo "----------------------------------------\n";

$fakePhones = ['260', '80', '340', '455', 'Ph? huynh', 'Phụ huynh', 'Parent'];

foreach ($fakePhones as $fakePhone) {
    $users = DB::table('users')
        ->where('phone', $fakePhone)
        ->get();
    
    foreach ($users as $user) {
        // Check if user is active in classes
        $asStudent = DB::table('class_students')->where('student_id', $user->id)->count();
        $asTeacher = DB::table('classes')->where('homeroom_teacher_id', $user->id)->count();
        $asSubjectTeacher = DB::table('subject_teacher')->where('user_id', $user->id)->count();
        $asParent = DB::table('parent_student')->where('parent_id', $user->id)->count();
        
        $isActive = ($asStudent > 0 || $asTeacher > 0 || $asSubjectTeacher > 0 || $asParent > 0);
        
        if (!$isActive) {
            echo "  🗑️  Xóa: ID {$user->id} | {$user->name} | {$user->email} | Phone: {$user->phone}\n";
            if (!$dryRun) {
                DB::table('users')->where('id', $user->id)->delete();
            }
            $deletedCount++;
        } else {
            echo "  ⚠️  GIỮ LẠI (active): ID {$user->id} | {$user->name} | Phone: {$user->phone}\n";
            $keptCount++;
        }
    }
}
echo "\n";

// 2. Xóa users có tên placeholder
echo "👤 2. XÓA USERS CÓ TÊN PLACEHOLDER:\n";
echo "----------------------------------------\n";

$placeholderNames = [
    "Student's name",
    "Vietnamese name", 
    "Parent's name",
    "Teacher's name",
    "User's name"
];

foreach ($placeholderNames as $placeholder) {
    $users = DB::table('users')
        ->where('name', $placeholder)
        ->get();
    
    foreach ($users as $user) {
        $asStudent = DB::table('class_students')->where('student_id', $user->id)->count();
        $asTeacher = DB::table('classes')->where('homeroom_teacher_id', $user->id)->count();
        $asSubjectTeacher = DB::table('subject_teacher')->where('user_id', $user->id)->count();
        $asParent = DB::table('parent_student')->where('parent_id', $user->id)->count();
        
        $isActive = ($asStudent > 0 || $asTeacher > 0 || $asSubjectTeacher > 0 || $asParent > 0);
        
        if (!$isActive) {
            echo "  🗑️  Xóa: ID {$user->id} | {$user->name} | {$user->email}\n";
            if (!$dryRun) {
                DB::table('users')->where('id', $user->id)->delete();
            }
            $deletedCount++;
        } else {
            echo "  ⚠️  GIỮ LẠI (active): ID {$user->id} | {$user->name}\n";
            $keptCount++;
        }
    }
}
echo "\n";

// 3. XỬ LÝ DUPLICATE PHONES (GIỮ USER ACTIVE, XÓA INACTIVE)
echo "🔗 3. XỬ LÝ DUPLICATE PHONES:\n";
echo "----------------------------------------\n";

$duplicatePhones = DB::select("
    SELECT phone, COUNT(*) as count, GROUP_CONCAT(id) as user_ids
    FROM users
    WHERE phone IS NOT NULL 
      AND phone != ''
      AND phone NOT IN ('260', '80', '340', '455', 'Ph? huynh', 'Phụ huynh', 'Parent')
    GROUP BY phone
    HAVING count > 1
    ORDER BY count DESC
");

foreach ($duplicatePhones as $dup) {
    echo "Phone: {$dup->phone} | Duplicates: {$dup->count}\n";
    
    $userIds = explode(',', $dup->user_ids);
    $activeUsers = [];
    $inactiveUsers = [];
    
    foreach ($userIds as $userId) {
        $asStudent = DB::table('class_students')->where('student_id', $userId)->count();
        $asTeacher = DB::table('classes')->where('homeroom_teacher_id', $userId)->count();
        $asSubjectTeacher = DB::table('subject_teacher')->where('user_id', $userId)->count();
        $asParent = DB::table('parent_student')->where('parent_id', $userId)->count();
        
        if ($asStudent > 0 || $asTeacher > 0 || $asSubjectTeacher > 0 || $asParent > 0) {
            $activeUsers[] = $userId;
        } else {
            $inactiveUsers[] = $userId;
        }
    }
    
    echo "  - Active: " . count($activeUsers) . " users\n";
    echo "  - Inactive: " . count($inactiveUsers) . " users\n";
    
    // Xóa inactive users
    foreach ($inactiveUsers as $userId) {
        $user = DB::table('users')->where('id', $userId)->first();
        echo "    🗑️  Xóa inactive: ID {$userId} | {$user->name} | {$user->email}\n";
        if (!$dryRun) {
            DB::table('users')->where('id', $userId)->delete();
        }
        $deletedCount++;
    }
    
    // Nếu có nhiều hơn 1 active user với cùng phone -> cần xem xét thủ công
    if (count($activeUsers) > 1) {
        echo "  ⚠️  CẢN BÁO: {$dup->phone} có " . count($activeUsers) . " active users - cần kiểm tra thủ công!\n";
        foreach ($activeUsers as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            echo "      - ID {$userId}: {$user->name} | {$user->email}\n";
        }
    }
    
    echo "\n";
}

// 4. TỔNG KẾT
echo "========================================\n";
echo "  TỔNG KẾT\n";
echo "========================================\n";
echo "Users đã xóa: {$deletedCount}\n";
echo "Users giữ lại: {$keptCount}\n\n";

if ($dryRun) {
    echo "✅ Chạy lại với \$dryRun = false để thực sự xóa\n";
} else {
    echo "✅ Đã hoàn tất dọn dẹp!\n";
}

