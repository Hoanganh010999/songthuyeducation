<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use Carbon\Carbon;

$class = ClassModel::find(17);
$schedules = $class->schedules;

echo "=== CLASS SCHEDULES DEBUG ===\n\n";
echo "Total schedules: " . $schedules->count() . "\n\n";

foreach ($schedules as $schedule) {
    echo "Schedule ID: {$schedule->id}\n";
    echo "  day_of_week: '{$schedule->day_of_week}' (type: " . gettype($schedule->day_of_week) . ")\n";
    echo "  start_time: {$schedule->start_time}\n";
    echo "  end_time: {$schedule->end_time}\n";
    echo "  status: '{$schedule->status}'\n";
    echo "\n";
}

echo "=== TESTING DAY MAPPING ===\n\n";

$schedulesByDay = [];
foreach ($schedules as $schedule) {
    $schedulesByDay[$schedule->day_of_week] = $schedule;
}

echo "schedulesByDay keys: " . implode(", ", array_keys($schedulesByDay)) . "\n\n";

// Test với ngày cụ thể
$testDate = Carbon::parse('2025-11-19'); // Buổi 34 (Thứ 4)
$dayNumber = $testDate->dayOfWeek;
echo "Test date: {$testDate->format('Y-m-d')} (Day number: {$dayNumber})\n";

$dayNumberToName = [
    0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
    4 => 'thursday', 5 => 'friday', 6 => 'saturday',
];

$dayName = $dayNumberToName[$dayNumber] ?? null;
echo "Day name: '{$dayName}'\n";

if (isset($schedulesByDay[$dayName])) {
    echo "✅ Schedule found for '{$dayName}'!\n";
    $schedule = $schedulesByDay[$dayName];
    echo "   Start: {$schedule->start_time}, End: {$schedule->end_time}\n";
} else {
    echo "❌ No schedule found for '{$dayName}'\n";
    echo "Available keys: " . implode(", ", array_keys($schedulesByDay)) . "\n";
}

echo "\n=== TESTING FIND NEXT DATE ===\n\n";

$currentDate = Carbon::parse('2025-11-19'); // Ngày buổi 34 bị hủy
echo "Current date (cancelled): {$currentDate->format('Y-m-d')}\n";

$maxAttempts = 14;
$attemptDate = $currentDate->copy()->addDay();
$found = false;

for ($i = 0; $i < $maxAttempts; $i++) {
    $dayNumber = $attemptDate->dayOfWeek;
    $dayName = $dayNumberToName[$dayNumber] ?? null;
    
    echo "  Attempt #{$i}: {$attemptDate->format('Y-m-d')} (Day: {$dayName})";
    
    if ($dayName && isset($schedulesByDay[$dayName])) {
        echo " ✅ FOUND!\n";
        $found = true;
        break;
    } else {
        echo " ❌\n";
    }
    
    $attemptDate->addDay();
}

if (!$found) {
    echo "\n❌ No next date found within {$maxAttempts} days!\n";
}

