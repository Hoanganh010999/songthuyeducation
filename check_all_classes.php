<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLessonSession;

echo "╔═══════════════════════════════════════════════════════════════\n";
echo "║ KIỂM TRA TẤT CẢ CÁC LỚP HỌC\n";
echo "╠═══════════════════════════════════════════════════════════════\n\n";

// Get all active classes
$classes = ClassModel::where('status', 'active')
    ->orWhere('status', 'draft')
    ->orderBy('name')
    ->get();

echo "Tổng số lớp: {$classes->count()}\n\n";

$issueClasses = [];
$totalIssues = 0;

foreach ($classes as $class) {
    $totalSessions = ClassLessonSession::where('class_id', $class->id)->count();
    $completedSessions = ClassLessonSession::where('class_id', $class->id)
        ->where('status', 'completed')->count();
    $scheduledSessions = ClassLessonSession::where('class_id', $class->id)
        ->where('status', 'scheduled')->count();
    $cancelledSessions = ClassLessonSession::where('class_id', $class->id)
        ->where('status', 'cancelled')->count();
    
    $validSessions = $totalSessions - $cancelledSessions;
    $syllabusSessions = $class->total_sessions;
    
    // Check if there's a problem
    $hasIssue = false;
    $issueDetails = [];
    
    // Issue 1: Thừa sessions
    if ($totalSessions > $syllabusSessions) {
        $hasIssue = true;
        $excess = $totalSessions - $syllabusSessions;
        $issueDetails[] = "⚠️ THỪA {$excess} buổi (DB: {$totalSessions}, Syllabus: {$syllabusSessions})";
    }
    
    // Issue 2: Thiếu sessions (đã học xong nhưng chưa đủ)
    if ($completedSessions >= $syllabusSessions && $validSessions < $syllabusSessions) {
        $hasIssue = true;
        $missing = $syllabusSessions - $validSessions;
        $issueDetails[] = "⚠️ THIẾU {$missing} buổi (Valid: {$validSessions}, Cần: {$syllabusSessions})";
    }
    
    if ($hasIssue) {
        $issueClasses[] = [
            'class' => $class,
            'total' => $totalSessions,
            'valid' => $validSessions,
            'completed' => $completedSessions,
            'scheduled' => $scheduledSessions,
            'cancelled' => $cancelledSessions,
            'syllabus' => $syllabusSessions,
            'issues' => $issueDetails,
        ];
        $totalIssues++;
    }
}

if ($totalIssues > 0) {
    echo "╔═══════════════════════════════════════════════════════════════\n";
    echo "║ ⚠️ TÌM THẤY {$totalIssues} LỚP CÓ VẤN ĐỀ:\n";
    echo "╠═══════════════════════════════════════════════════════════════\n\n";
    
    foreach ($issueClasses as $item) {
        $class = $item['class'];
        echo "📚 {$class->name} (ID: {$class->id}, Code: {$class->code})\n";
        echo "   Status: {$class->status}\n";
        echo "   Syllabus: {$item['syllabus']} buổi\n";
        echo "   Database: Total={$item['total']}, Valid={$item['valid']}, Completed={$item['completed']}, Scheduled={$item['scheduled']}, Cancelled={$item['cancelled']}\n";
        
        foreach ($item['issues'] as $issue) {
            echo "   {$issue}\n";
        }
        
        // List excess sessions if any
        if ($item['total'] > $item['syllabus']) {
            echo "   \n   📋 Các buổi thừa:\n";
            $excessSessions = ClassLessonSession::where('class_id', $class->id)
                ->where('session_number', '>', $item['syllabus'])
                ->orderBy('session_number')
                ->get();
            
            foreach ($excessSessions as $session) {
                $date = \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
                echo "      - Buổi {$session->session_number}: {$date} - {$session->status} (ID: {$session->id})\n";
            }
        }
        
        echo "\n";
    }
} else {
    echo "✅ TẤT CẢ CÁC LỚP ĐỀU ỔN!\n";
}

echo "╚═══════════════════════════════════════════════════════════════\n";

