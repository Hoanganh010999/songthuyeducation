<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════════════════════════\n";
echo "║ CLEANUP EXCESS SESSIONS - LỚP FLYERS 1\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Find Flyers 1 class
$class = ClassModel::where('name', 'LIKE', '%Flyers 1%')
    ->orWhere('code', 'LIKE', '%Flyers 1%')
    ->first();

if (!$class) {
    echo "║ ❌ Không tìm thấy lớp Flyers 1\n";
    echo "╚═══════════════════════════════════════════════════════════════\n";
    exit;
}

echo "║ Tên lớp: {$class->name}\n";
echo "║ ID: {$class->id}\n";
echo "║ Tổng số buổi (theo syllabus): {$class->total_sessions}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Count sessions
$totalSessions = ClassLessonSession::where('class_id', $class->id)->count();
$completedCount = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'completed')->count();
$scheduledCount = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'scheduled')->count();
$cancelledCount = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'cancelled')->count();

echo "║ 📊 HIỆN TẠI:\n";
echo "║   - Tổng số buổi: {$totalSessions}\n";
echo "║   - Đã học: {$completedCount}\n";
echo "║   - Đã lên lịch: {$scheduledCount}\n";
echo "║   - Đã hủy: {$cancelledCount}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Calculate what should be deleted
$validSessionCount = $completedCount; // Chỉ tính completed sessions
$maxSessionNumber = $class->total_sessions;

echo "║ 🔍 PHÂN TÍCH:\n";
echo "║   - Số buổi hợp lệ (completed): {$validSessionCount}\n";
echo "║   - Số buổi tối đa (theo syllabus): {$maxSessionNumber}\n";
echo "║   - Còn lại cần tạo: " . ($maxSessionNumber - $validSessionCount) . "\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Find sessions to delete (session_number > total_sessions và status = scheduled/cancelled)
$sessionsToDelete = ClassLessonSession::where('class_id', $class->id)
    ->where('session_number', '>', $maxSessionNumber)
    ->whereIn('status', ['scheduled', 'cancelled'])
    ->get();

if ($sessionsToDelete->isEmpty()) {
    echo "║ ✅ Không có buổi học thừa cần xóa\n";
} else {
    echo "║ ⚠️ TÌM THẤY " . $sessionsToDelete->count() . " BUỔI HỌC THỪA:\n";
    foreach ($sessionsToDelete as $session) {
        $date = \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
        echo "║   - Buổi {$session->session_number}: {$date} - {$session->status} (ID: {$session->id})\n";
    }
    echo "╠═══════════════════════════════════════════════════════════════\n";
    
    // Confirm deletion
    echo "║ ❓ Bạn có muốn XÓA các buổi học này? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim($line);
    fclose($handle);
    
    if (strtolower($confirmation) === 'yes') {
        DB::beginTransaction();
        try {
            // Delete calendar events first
            foreach ($sessionsToDelete as $session) {
                \App\Models\CalendarEvent::where('eventable_type', 'App\\Models\\ClassLessonSession')
                    ->where('eventable_id', $session->id)
                    ->delete();
            }
            
            // Delete sessions
            $deletedCount = ClassLessonSession::where('class_id', $class->id)
                ->where('session_number', '>', $maxSessionNumber)
                ->whereIn('status', ['scheduled', 'cancelled'])
                ->delete();
            
            DB::commit();
            
            echo "║ ✅ Đã xóa {$deletedCount} buổi học thừa!\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "║ ❌ Lỗi khi xóa: " . $e->getMessage() . "\n";
        }
    } else {
        echo "║ ℹ️ Hủy bỏ xóa\n";
    }
}

echo "╠═══════════════════════════════════════════════════════════════\n";

// Check final state
$finalTotal = ClassLessonSession::where('class_id', $class->id)->count();
$finalCompleted = ClassLessonSession::where('class_id', $class->id)->where('status', 'completed')->count();
$finalScheduled = ClassLessonSession::where('class_id', $class->id)->where('status', 'scheduled')->count();
$finalCancelled = ClassLessonSession::where('class_id', $class->id)->where('status', 'cancelled')->count();

echo "║ 📊 SAU KHI XÓA:\n";
echo "║   - Tổng số buổi: {$finalTotal}\n";
echo "║   - Đã học: {$finalCompleted}\n";
echo "║   - Đã lên lịch: {$finalScheduled}\n";
echo "║   - Đã hủy: {$finalCancelled}\n";

echo "╚═══════════════════════════════════════════════════════════════\n";

