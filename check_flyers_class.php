<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use App\Models\CalendarEvent;
use Carbon\Carbon;

echo "╔═══════════════════════════════════════════════════════════════\n";
echo "║ KIỂM TRA LỚP FLYERS 1\n";
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
echo "║ Mã lớp: {$class->code}\n";
echo "║ ID: {$class->id}\n";
echo "║ Tổng số buổi (theo syllabus): {$class->total_sessions}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Count sessions in database
$totalSessionsInDB = ClassLessonSession::where('class_id', $class->id)->count();
$completedSessions = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'completed')->count();
$scheduledSessions = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'scheduled')->count();
$cancelledSessions = ClassLessonSession::where('class_id', $class->id)
    ->where('status', 'cancelled')->count();

echo "║ 📊 THỐNG KÊ BUỔI HỌC TRONG DATABASE (class_lesson_sessions):\n";
echo "║   - Tổng số buổi: {$totalSessionsInDB}\n";
echo "║   - Đã học (completed): {$completedSessions}\n";
echo "║   - Đã lên lịch (scheduled): {$scheduledSessions}\n";
echo "║   - Đã hủy (cancelled): {$cancelledSessions}\n";
echo "╠═══════════════════════════════════════════════════════════════\n";

// Skip calendar events check for now
echo "╠═══════════════════════════════════════════════════════════════\n";

// List all sessions
echo "║ 📝 DANH SÁCH TẤT CẢ CÁC BUỔI HỌC (sắp xếp theo session_number):\n";
$allSessions = ClassLessonSession::where('class_id', $class->id)
    ->orderBy('session_number')
    ->orderBy('scheduled_date')
    ->get();

$sessionGroups = [];
foreach ($allSessions as $session) {
    $sessionGroups[$session->session_number][] = $session;
}

foreach ($sessionGroups as $sessionNum => $sessions) {
    if (count($sessions) > 1) {
        echo "║   ⚠️ BUỔI {$sessionNum} CÓ " . count($sessions) . " BẢN GHI:\n";
        foreach ($sessions as $session) {
            $date = Carbon::parse($session->scheduled_date)->format('d/m/Y');
            echo "║      - ID {$session->id}: {$date} - {$session->status}\n";
        }
    } else {
        $session = $sessions[0];
        $date = Carbon::parse($session->scheduled_date)->format('d/m/Y');
        echo "║   - Buổi {$sessionNum}: {$date} - {$session->status}\n";
    }
}

echo "╠═══════════════════════════════════════════════════════════════\n";

// Check cancelled session on 2025-12-08
echo "║ 🔍 KIỂM TRA BUỔI HỌC NGÀY 08/12/2025:\n";
$sessionOnDec08 = ClassLessonSession::where('class_id', $class->id)
    ->whereDate('scheduled_date', '2025-12-08')
    ->first();

if ($sessionOnDec08) {
    echo "║   - Tìm thấy buổi học số: {$sessionOnDec08->session_number}\n";
    echo "║   - Trạng thái: {$sessionOnDec08->status}\n";
    echo "║   - Lý do hủy: " . ($sessionOnDec08->cancellation_reason ?? 'N/A') . "\n";
} else {
    echo "║   - ❌ Không tìm thấy buổi học nào ngày 08/12/2025\n";
}

echo "╠═══════════════════════════════════════════════════════════════\n";

// Check if there's a session on 2025-08-15
echo "║ 🔍 KIỂM TRA BUỔI HỌC NGÀY 15/08/2025:\n";
$sessionOnAug15 = ClassLessonSession::where('class_id', $class->id)
    ->whereDate('scheduled_date', '2025-08-15')
    ->first();

if ($sessionOnAug15) {
    echo "║   - Tìm thấy buổi học số: {$sessionOnAug15->session_number}\n";
    echo "║   - Trạng thái: {$sessionOnAug15->status}\n";
} else {
    echo "║   - ❌ Không tìm thấy buổi học nào ngày 15/08/2025\n";
}

echo "╠═══════════════════════════════════════════════════════════════\n";

// Check class schedules
echo "║ 📅 LỊCH HỌC CỐ ĐỊNH (class_schedules):\n";
$schedules = \App\Models\ClassSchedule::where('class_id', $class->id)->get();
if ($schedules->count() > 0) {
    foreach ($schedules as $schedule) {
        $dayNames = [
            1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5',
            5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'
        ];
        $dayName = $dayNames[$schedule->day_of_week] ?? $schedule->day_of_week;
        echo "║   - {$dayName}: {$schedule->start_time} - {$schedule->end_time} (Status: {$schedule->status})\n";
    }
} else {
    echo "║   - ❌ Không có lịch học cố định\n";
}

echo "╚═══════════════════════════════════════════════════════════════\n";

