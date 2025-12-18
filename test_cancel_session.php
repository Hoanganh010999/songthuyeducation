<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "╔═══════════════════════════════════════════════════════════════\n";
echo "║ TEST HỦY BUỔI HỌC VÀ RESCHEDULE - LỚP FLYERS 1\n";
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

echo "║ Tên lớp: {$class->name} (ID: {$class->id})\n";
echo "║ Tổng số buổi: {$class->total_sessions}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Trạng thái TRƯỚC khi hủy
$beforeTotal = $class->lessonSessions()->count();
$beforeCompleted = $class->lessonSessions()->where('status', 'completed')->count();
$beforeScheduled = $class->lessonSessions()->where('status', 'scheduled')->count();
$beforeCancelled = $class->lessonSessions()->where('status', 'cancelled')->count();
$beforeValid = $class->lessonSessions()->where('status', '!=', 'cancelled')->count();

echo "║ 📊 TRẠNG THÁI TRƯỚC KHI HỦY:\n";
echo "║   - Tổng: {$beforeTotal} | Valid: {$beforeValid} | Completed: {$beforeCompleted} | Scheduled: {$beforeScheduled} | Cancelled: {$beforeCancelled}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Tìm buổi 34 để test hủy
$sessionToCancel = ClassLessonSession::where('class_id', $class->id)
    ->where('session_number', 34)
    ->where('status', 'scheduled')
    ->first();

if (!$sessionToCancel) {
    echo "║ ❌ Không tìm thấy buổi 34 để test hủy\n";
    echo "╚═══════════════════════════════════════════════════════════════\n";
    exit;
}

$cancelDate = \Carbon\Carbon::parse($sessionToCancel->scheduled_date)->format('d/m/Y');
echo "║ 🎯 TEST: Hủy buổi {$sessionToCancel->session_number} (ngày {$cancelDate})\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Simulate cancel session (giống như API endpoint)
DB::beginTransaction();
try {
    // Update session to cancelled
    $sessionToCancel->update([
        'status' => 'cancelled',
        'cancellation_reason' => 'TEST - Kiểm tra flow reschedule',
    ]);
    
    echo "║ ✅ Đã đánh dấu buổi {$sessionToCancel->session_number} là 'cancelled'\n";
    echo "╠═══════════════════════════════════════════════════════════════\n";
    
    // Reschedule future sessions
    echo "║ 🔄 Đang reschedule các buổi học sau đó...\n";
    
    $futureSessions = ClassLessonSession::where('class_id', $class->id)
        ->where('session_number', '>', $sessionToCancel->session_number)
        ->where('status', 'scheduled')
        ->whereDoesntHave('attendances')
        ->orderBy('session_number')
        ->get();
    
    echo "║   - Tìm thấy {$futureSessions->count()} buổi học cần reschedule\n";
    
    $schedules = $class->schedules;
    $schedulesByDay = [];
    foreach ($schedules as $schedule) {
        $schedulesByDay[$schedule->day_of_week] = $schedule;
    }
    
    $currentDate = \Carbon\Carbon::parse($sessionToCancel->scheduled_date);
    $rescheduledCount = 0;
    
    foreach ($futureSessions as $session) {
        // Find next schedule date
        $maxAttempts = 14;
        $attemptDate = $currentDate->copy()->addDay();
        $newDate = null;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            $dayOfWeek = $attemptDate->dayOfWeek === 0 ? 7 : $attemptDate->dayOfWeek;
            
            if (isset($schedulesByDay[$dayOfWeek])) {
                $newDate = $attemptDate;
                break;
            }
            
            $attemptDate->addDay();
        }
        
        if ($newDate) {
            $dayOfWeek = $newDate->dayOfWeek === 0 ? 7 : $newDate->dayOfWeek;
            $schedule = $schedulesByDay[$dayOfWeek] ?? null;
            
            if ($schedule) {
                $oldDate = \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
                $newDateStr = $newDate->format('d/m/Y');
                
                $session->update([
                    'scheduled_date' => $newDate->toDateString(),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'class_schedule_id' => $schedule->id,
                ]);
                
                echo "║   - Buổi {$session->session_number}: {$oldDate} → {$newDateStr}\n";
                
                $currentDate = $newDate;
                $rescheduledCount++;
            }
        }
    }
    
    echo "║   ✅ Đã reschedule {$rescheduledCount} buổi học\n";
    echo "╠═══════════════════════════════════════════════════════════════\n";
    
    // Check if need to add replacement sessions
    $currentValidCount = $class->lessonSessions()->where('status', '!=', 'cancelled')->count();
    $sessionsToAdd = $class->total_sessions - $currentValidCount;
    
    echo "║ 🔍 KIỂM TRA TẠO BUỔI MỚI:\n";
    echo "║   - Số buổi hợp lệ hiện tại: {$currentValidCount}\n";
    echo "║   - Tổng số buổi cần có: {$class->total_sessions}\n";
    echo "║   - Cần tạo thêm: {$sessionsToAdd} buổi\n";
    
    if ($sessionsToAdd > 0) {
        // Get last valid session
        $lastSession = $class->lessonSessions()
            ->where('status', '!=', 'cancelled')
            ->orderBy('session_number', 'desc')
            ->orderBy('scheduled_date', 'desc')
            ->first();
        
        $currentDate = \Carbon\Carbon::parse($lastSession->scheduled_date);
        $startSessionNumber = $lastSession->session_number + 1;
        $addedCount = 0;
        
        for ($i = 0; $i < $sessionsToAdd; $i++) {
            $sessionNumber = $startSessionNumber + $i;
            
            // Find next schedule date
            $maxAttempts = 14;
            $attemptDate = $currentDate->copy()->addDay();
            $newDate = null;
            
            for ($j = 0; $j < $maxAttempts; $j++) {
                $dayOfWeek = $attemptDate->dayOfWeek === 0 ? 7 : $attemptDate->dayOfWeek;
                
                if (isset($schedulesByDay[$dayOfWeek])) {
                    $newDate = $attemptDate;
                    break;
                }
                
                $attemptDate->addDay();
            }
            
            if ($newDate) {
                $dayOfWeek = $newDate->dayOfWeek === 0 ? 7 : $newDate->dayOfWeek;
                $schedule = $schedulesByDay[$dayOfWeek] ?? null;
                
                if ($schedule) {
                    ClassLessonSession::create([
                        'class_id' => $class->id,
                        'lesson_plan_id' => $class->lesson_plan_id,
                        'class_schedule_id' => $schedule->id,
                        'session_number' => $sessionNumber,
                        'scheduled_date' => $newDate->toDateString(),
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'status' => 'scheduled',
                    ]);
                    
                    echo "║   ✅ Tạo buổi {$sessionNumber}: {$newDate->format('d/m/Y')}\n";
                    
                    $currentDate = $newDate;
                    $addedCount++;
                }
            }
        }
        
        echo "║   ✅ Đã tạo {$addedCount} buổi học mới\n";
    } else {
        echo "║   ℹ️ Không cần tạo buổi mới\n";
    }
    
    DB::commit();
    echo "╠═══════════════════════════════════════════════════════════════\n";
    echo "║ ✅ HOÀN TẤT TEST!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "║ ❌ LỖI: " . $e->getMessage() . "\n";
}

echo "╠═══════════════════════════════════════════════════════════════\n";

// Trạng thái SAU khi hủy
$afterTotal = $class->lessonSessions()->count();
$afterCompleted = $class->lessonSessions()->where('status', 'completed')->count();
$afterScheduled = $class->lessonSessions()->where('status', 'scheduled')->count();
$afterCancelled = $class->lessonSessions()->where('status', 'cancelled')->count();
$afterValid = $class->lessonSessions()->where('status', '!=', 'cancelled')->count();

echo "║ 📊 TRẠNG THÁI SAU KHI HỦY:\n";
echo "║   - Tổng: {$afterTotal} | Valid: {$afterValid} | Completed: {$afterCompleted} | Scheduled: {$afterScheduled} | Cancelled: {$afterCancelled}\n";

// List các buổi học cuối
echo "╠═══════════════════════════════════════════════════════════════\n";
echo "║ 📝 CÁC BUỔI HỌC CUỐI CÙNG:\n";
$lastSessions = ClassLessonSession::where('class_id', $class->id)
    ->orderBy('session_number', 'desc')
    ->take(5)
    ->get()
    ->reverse();

foreach ($lastSessions as $session) {
    $date = \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
    echo "║   - Buổi {$session->session_number}: {$date} - {$session->status}\n";
}

echo "╚═══════════════════════════════════════════════════════════════\n";

