<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "═══════════════════════════════════════════════════════════════\n";
echo " TEST DIRECT CANCEL WITH REAL CONTROLLER LOGIC\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Rollback first
$session = ClassLessonSession::where('class_id', 17)->where('session_number', 34)->first();
if ($session && $session->status == 'cancelled') {
    $session->status = 'scheduled';
    $session->cancellation_reason = null;
    $session->save();
    echo "✅ Restored session 34 to scheduled\n\n";
}

// Reload session
$session = ClassLessonSession::where('class_id', 17)
    ->where('session_number', 34)
    ->with(['class'])
    ->first();

echo "Before cancel:\n";
echo "  Session {$session->session_number}: {$session->scheduled_date} - {$session->status}\n";

$beforeTotal = $session->class->lessonSessions()->count();
$beforeValid = $session->class->lessonSessions()->where('status', '!=', 'cancelled')->count();
echo "  Total sessions: {$beforeTotal}, Valid: {$beforeValid}\n\n";

// Simulate cancel
DB::beginTransaction();
try {
    // Update session
    $session->update([
        'status' => 'cancelled',
        'cancellation_reason' => 'TEST - Direct cancel',
    ]);
    
    echo "Step 1: ✅ Marked session {$session->session_number} as cancelled\n\n";
    
    // Reschedule future sessions
    $class = $session->class;
    
    $futureSessions = ClassLessonSession::where('class_id', $class->id)
        ->where('session_number', '>', $session->session_number)
        ->where('status', 'scheduled')
        ->whereDoesntHave('attendances')
        ->orderBy('session_number')
        ->get();
    
    echo "Step 2: Found {$futureSessions->count()} future sessions to reschedule\n";
    
    // Get schedules
    $schedules = $class->schedules()->orderBy('day_of_week')->get();
    
    echo "Step 3: Found {$schedules->count()} schedules\n";
    foreach ($schedules as $sch) {
        echo "  - {$sch->day_of_week}: {$sch->start_time} - {$sch->end_time}\n";
    }
    echo "\n";
    
    if ($schedules->isEmpty()) {
        echo "❌ NO SCHEDULES FOUND! Cannot reschedule.\n";
    } else {
        // Map schedules by day
        $schedulesByDay = [];
        foreach ($schedules as $schedule) {
            $schedulesByDay[$schedule->day_of_week] = $schedule;
        }
        
        echo "Step 4: Mapped schedules by day: " . implode(", ", array_keys($schedulesByDay)) . "\n\n";
        
        // Day mapping
        $dayNumberToName = [
            0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
            4 => 'thursday', 5 => 'friday', 6 => 'saturday',
        ];
        
        $currentDate = \Carbon\Carbon::parse($session->scheduled_date);
        $rescheduledCount = 0;
        
        echo "Step 5: Rescheduling future sessions:\n";
        foreach ($futureSessions as $futureSession) {
            // Find next date
            $maxAttempts = 14;
            $attemptDate = $currentDate->copy()->addDay();
            $newDate = null;
            
            for ($i = 0; $i < $maxAttempts; $i++) {
                $dayNumber = $attemptDate->dayOfWeek;
                $dayName = $dayNumberToName[$dayNumber] ?? null;
                
                if ($dayName && isset($schedulesByDay[$dayName])) {
                    $newDate = $attemptDate;
                    break;
                }
                
                $attemptDate->addDay();
            }
            
            if ($newDate) {
                $dayNumber = $newDate->dayOfWeek;
                $dayName = $dayNumberToName[$dayNumber] ?? null;
                $schedule = $dayName ? ($schedulesByDay[$dayName] ?? null) : null;
                
                if ($schedule) {
                    $oldDate = \Carbon\Carbon::parse($futureSession->scheduled_date)->format('d/m/Y');
                    $newDateStr = $newDate->format('d/m/Y');
                    
                    $futureSession->update([
                        'scheduled_date' => $newDate->toDateString(),
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'class_schedule_id' => $schedule->id,
                    ]);
                    
                    echo "  ✅ Session {$futureSession->session_number}: {$oldDate} → {$newDateStr}\n";
                    
                    $currentDate = $newDate;
                    $rescheduledCount++;
                }
            }
        }
        
        echo "\nStep 6: Rescheduled {$rescheduledCount} sessions\n\n";
    }
    
    // Check if need to add sessions
    $currentValidCount = $class->lessonSessions()->where('status', '!=', 'cancelled')->count();
    $sessionsToAdd = $class->total_sessions - $currentValidCount;
    
    echo "Step 7: Check if need to add replacement sessions\n";
    echo "  Valid sessions: {$currentValidCount}\n";
    echo "  Total needed: {$class->total_sessions}\n";
    echo "  To add: {$sessionsToAdd}\n\n";
    
    if ($sessionsToAdd > 0) {
        // Reload schedules
        $schedules = $class->schedules()->orderBy('day_of_week')->get();
        
        if ($schedules->isEmpty()) {
            echo "❌ No schedules found for adding sessions\n";
        } else {
        // Get last valid session
        $lastSession = $class->lessonSessions()
            ->where('status', '!=', 'cancelled')
            ->orderBy('session_number', 'desc')
            ->orderBy('scheduled_date', 'desc')
            ->first();
        
        $schedulesByDay = [];
        foreach ($schedules as $schedule) {
            $schedulesByDay[$schedule->day_of_week] = $schedule;
        }
        
        $dayNumberToName = [
            0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
            4 => 'thursday', 5 => 'friday', 6 => 'saturday',
        ];
        
        $currentDate = \Carbon\Carbon::parse($lastSession->scheduled_date);
        $startSessionNumber = $lastSession->session_number + 1;
        $addedCount = 0;
        
        echo "Step 8: Adding replacement sessions starting from session {$startSessionNumber}\n";
        
        for ($i = 0; $i < $sessionsToAdd; $i++) {
            $sessionNumber = $startSessionNumber + $i;
            
            // Find next date
            $maxAttempts = 14;
            $attemptDate = $currentDate->copy()->addDay();
            $newDate = null;
            
            for ($j = 0; $j < $maxAttempts; $j++) {
                $dayNumber = $attemptDate->dayOfWeek;
                $dayName = $dayNumberToName[$dayNumber] ?? null;
                
                if ($dayName && isset($schedulesByDay[$dayName])) {
                    $newDate = $attemptDate;
                    break;
                }
                
                $attemptDate->addDay();
            }
            
            if ($newDate) {
                $dayNumber = $newDate->dayOfWeek;
                $dayName = $dayNumberToName[$dayNumber] ?? null;
                $schedule = $dayName ? ($schedulesByDay[$dayName] ?? null) : null;
                
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
                    
                    echo "  ✅ Created session {$sessionNumber}: {$newDate->format('d/m/Y')}\n";
                    
                    $currentDate = $newDate;
                    $addedCount++;
                }
            }
        }
        
        echo "\nStep 9: Added {$addedCount} replacement sessions\n\n";
        }
    }
    
    DB::commit();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ TEST COMPLETED!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Check final state
$afterTotal = $class->lessonSessions()->count();
$afterValid = $class->lessonSessions()->where('status', '!=', 'cancelled')->count();
$afterCompleted = $class->lessonSessions()->where('status', 'completed')->count();
$afterScheduled = $class->lessonSessions()->where('status', 'scheduled')->count();
$afterCancelled = $class->lessonSessions()->where('status', 'cancelled')->count();

echo "After cancel:\n";
echo "  Total: {$afterTotal}, Valid: {$afterValid}, Completed: {$afterCompleted}, Scheduled: {$afterScheduled}, Cancelled: {$afterCancelled}\n\n";

// List last sessions
$lastSessions = ClassLessonSession::where('class_id', 17)
    ->orderBy('session_number', 'desc')
    ->take(5)
    ->get()
    ->reverse();

echo "Last 5 sessions:\n";
foreach ($lastSessions as $s) {
    $date = \Carbon\Carbon::parse($s->scheduled_date)->format('d/m/Y');
    echo "  - Session {$s->session_number}: {$date} - {$s->status}\n";
}

