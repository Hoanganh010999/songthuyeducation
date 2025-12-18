# 📚 Example: Tích hợp Zalo vào Homework System

## 🎯 Tổng quan

Tích hợp Zalo notifications vào các điểm sau:
1. **Tạo bài tập mới** → Thông báo cho students
2. **Nộp bài tập** → Xác nhận với student
3. **Chấm bài** → Thông báo điểm số
4. **Nhắc nhở** → Scheduled job nhắc nộp bài

---

## 1️⃣ Thông báo khi tạo bài tập mới

### File: `app/Http/Controllers/Api/HomeworkAssignmentController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Services\ZaloNotificationService;
use Illuminate\Support\Facades\Log;

class HomeworkAssignmentController extends Controller
{
    public function store(Request $request)
    {
        // ... validation ...
        
        $homework = HomeworkAssignment::create($validated);
        
        // ... existing logic (create post, calendar event, etc.) ...
        
        // 🆕 Send Zalo notification
        $this->sendHomeworkNotification($homework, $request->user());
        
        return response()->json([
            'message' => __('homework.created_successfully'),
            'homework' => $homework->load('class', 'session'),
        ], 201);
    }
    
    /**
     * Send Zalo notification when homework is created
     */
    protected function sendHomeworkNotification($homework, $creator)
    {
        try {
            $zalo = new ZaloNotificationService();
            
            // Check if Zalo service is available
            if (!$zalo->isReady()) {
                Log::warning('[Zalo] Service not ready, skipping notification');
                return;
            }
            
            // Get students in the class
            $class = $homework->class;
            $students = $class->students;
            
            if ($students->isEmpty()) {
                return;
            }
            
            // Build message
            $deadline = $homework->deadline 
                ? $homework->deadline->format('d/m/Y H:i')
                : 'Chưa có';
            
            $message = "📚 BÀI TẬP MỚI\n\n" .
                       "Lớp: {$class->name}\n" .
                       "Tiêu đề: {$homework->title}\n" .
                       "📅 Hạn nộp: {$deadline}\n\n";
            
            if ($homework->description) {
                $plainDescription = strip_tags($homework->description);
                $shortDescription = mb_substr($plainDescription, 0, 100);
                $message .= "📝 {$shortDescription}" . 
                           (mb_strlen($plainDescription) > 100 ? '...' : '') . "\n\n";
            }
            
            $message .= "👉 Vào hệ thống để xem chi tiết và nộp bài";
            
            // Send to all students
            $result = $zalo->notifyStudents($students, $message);
            
            if ($result['success'] ?? false) {
                $successCount = count($result['results'] ?? []);
                $errorCount = count($result['errors'] ?? []);
                
                Log::info('[Zalo] Homework notification sent', [
                    'homework_id' => $homework->id,
                    'success' => $successCount,
                    'failed' => $errorCount,
                ]);
            }
            
        } catch (\Exception $e) {
            // Don't break the homework creation if Zalo fails
            Log::error('[Zalo] Failed to send homework notification', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

## 2️⃣ Xác nhận khi student nộp bài

### File: `app/Http/Controllers/Api/HomeworkSubmissionController.php`

```php
public function submit(Request $request, $homeworkId)
{
    // ... existing submission logic ...
    
    $submission = HomeworkSubmission::updateOrCreate(
        [
            'homework_assignment_id' => $homeworkId,
            'student_id' => $user->id,
        ],
        [
            'session_id' => $classLessonSession->id,
            'status' => 'submitted',
            'submitted_at' => now(),
            'submission_link' => $uploadedFile->web_view_link,
        ]
    );
    
    // 🆕 Send confirmation to student
    $this->sendSubmissionConfirmation($homework, $user);
    
    return response()->json([
        'message' => __('homework.submitted_successfully'),
        'submission' => $submission,
    ]);
}

/**
 * Send confirmation when student submits homework
 */
protected function sendSubmissionConfirmation($homework, $student)
{
    try {
        $zalo = new ZaloNotificationService();
        
        if (!$zalo->isReady()) {
            return;
        }
        
        $submittedAt = now()->format('d/m/Y H:i');
        
        $message = "✅ ĐÃ NHẬN BÀI TẬP\n\n" .
                   "Bài tập: {$homework->title}\n" .
                   "Lớp: {$homework->class->name}\n" .
                   "⏰ Nộp lúc: {$submittedAt}\n\n" .
                   "Giáo viên sẽ chấm bài và thông báo điểm cho bạn sớm nhất.";
        
        $zalo->notifyStudent($student, $message);
        
    } catch (\Exception $e) {
        Log::error('[Zalo] Failed to send submission confirmation', [
            'student_id' => $student->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## 3️⃣ Thông báo điểm số khi giáo viên chấm bài

### File: `app/Http/Controllers/Api/ClassDetailController.php`

Trong method `markAttendance()`:

```php
public function markAttendance(Request $request, $classId, $sessionId)
{
    // ... existing attendance marking logic ...
    
    foreach ($validated['students'] as $studentData) {
        // ... update attendance ...
        
        // Update homework score if provided
        if (isset($studentData['homework_score']) && $studentData['homework_score'] !== null) {
            $submission = HomeworkSubmission::where('session_id', $sessionId)
                ->where('student_id', $studentData['student_id'])
                ->first();
            
            if ($submission) {
                $oldStatus = $submission->status;
                
                $submission->update([
                    'status' => 'graded',
                    'score' => $studentData['homework_score'],
                ]);
                
                // 🆕 Send score notification if newly graded
                if ($oldStatus !== 'graded') {
                    $this->sendScoreNotification($submission);
                }
            }
        }
    }
    
    // ... rest of the method ...
}

/**
 * Send notification when homework is graded
 */
protected function sendScoreNotification($submission)
{
    try {
        $zalo = new ZaloNotificationService();
        
        if (!$zalo->isReady()) {
            return;
        }
        
        $student = \App\Models\User::find($submission->student_id);
        $homework = $submission->homeworkAssignment;
        
        if (!$student || !$homework) {
            return;
        }
        
        $score = $submission->score;
        $emoji = $score >= 8 ? '🎉' : ($score >= 5 ? '👍' : '📚');
        
        $message = "{$emoji} ĐIỂM BÀI TẬP\n\n" .
                   "Bài tập: {$homework->title}\n" .
                   "Lớp: {$homework->class->name}\n" .
                   "📊 Điểm: {$score}/10\n\n";
        
        if ($score >= 8) {
            $message .= "Xuất sắc! Tiếp tục phát huy! 🌟";
        } elseif ($score >= 5) {
            $message .= "Tốt! Hãy cố gắng hơn nữa nhé! 💪";
        } else {
            $message .= "Cần ôn tập thêm. Hãy xem lại bài giảng nhé! 📖";
        }
        
        $zalo->notifyStudent($student, $message);
        
        Log::info('[Zalo] Score notification sent', [
            'submission_id' => $submission->id,
            'student_id' => $student->id,
            'score' => $score,
        ]);
        
    } catch (\Exception $e) {
        Log::error('[Zalo] Failed to send score notification', [
            'submission_id' => $submission->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## 4️⃣ Nhắc nhở nộp bài tự động (Scheduled Job)

### Tạo Command:

```bash
php artisan make:command SendHomeworkReminders
```

### File: `app/Console/Commands/SendHomeworkReminders.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HomeworkAssignment;
use App\Services\ZaloNotificationService;
use Illuminate\Support\Facades\Log;

class SendHomeworkReminders extends Command
{
    protected $signature = 'homework:remind';
    protected $description = 'Send Zalo reminders for pending homework';

    public function handle()
    {
        $this->info('🔔 Checking for homework to remind...');
        
        $zalo = new ZaloNotificationService();
        
        if (!$zalo->isReady()) {
            $this->error('❌ Zalo service not ready');
            return 1;
        }
        
        // Get homeworks due within 24 hours
        $dueHomeworks = HomeworkAssignment::whereBetween('deadline', [
            now(),
            now()->addHours(24)
        ])
        ->with('class.students')
        ->get();
        
        $totalReminders = 0;
        
        foreach ($dueHomeworks as $homework) {
            // Find students who haven't submitted
            $notSubmitted = $homework->class->students->filter(function ($student) use ($homework) {
                return !$homework->submissions()
                    ->where('student_id', $student->id)
                    ->exists();
            });
            
            if ($notSubmitted->isEmpty()) {
                continue;
            }
            
            $hoursLeft = now()->diffInHours($homework->deadline);
            
            $message = "⏰ NHẮC NỘP BÀI TẬP\n\n" .
                       "Bài tập: {$homework->title}\n" .
                       "Lớp: {$homework->class->name}\n" .
                       "⏳ Còn {$hoursLeft} giờ nữa hết hạn!\n" .
                       "📅 Hạn nộp: " . $homework->deadline->format('d/m/Y H:i') . "\n\n" .
                       "👉 Hãy nộp bài ngay để không bị trừ điểm nhé!";
            
            $result = $zalo->notifyStudents($notSubmitted, $message);
            
            $successCount = count($result['results'] ?? []);
            $totalReminders += $successCount;
            
            $this->info("Sent {$successCount} reminders for: {$homework->title}");
            
            Log::info('[Zalo] Homework reminder sent', [
                'homework_id' => $homework->id,
                'recipients' => $successCount,
            ]);
        }
        
        $this->info("✅ Total reminders sent: {$totalReminders}");
        
        return 0;
    }
}
```

### Đăng ký Schedule:

File: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Send reminders every day at 8 AM and 6 PM
    $schedule->command('homework:remind')
             ->twiceDaily(8, 18);
    
    // Or send every hour during working hours
    // $schedule->command('homework:remind')
    //          ->hourly()
    //          ->between('8:00', '20:00');
}
```

---

## 🧪 Test từng phần

### Test 1: Tạo homework mới
```bash
# Tạo homework qua UI hoặc API
# Check logs
tail -f storage/logs/laravel.log | grep Zalo
```

### Test 2: Nộp bài
```bash
# Student nộp bài qua UI
# Check logs
```

### Test 3: Chấm bài
```bash
# Teacher chấm bài qua attendance modal
# Check logs
```

### Test 4: Reminder
```bash
# Run manually
php artisan homework:remind

# Check scheduled tasks
php artisan schedule:list
```

---

## 📊 Monitoring & Logs

### Check Zalo service status:
```php
$zalo = new ZaloNotificationService();
$isReady = $zalo->isReady(); // true/false
```

### Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "\[Zalo\]"
```

### Zalo service logs:
```bash
cd zalo-service
# Check console output for request logs
```

---

## ⚠️ Best Practices

1. **Always wrap Zalo calls in try-catch** - không để lỗi Zalo làm crash app
2. **Check `isReady()` trước khi gửi** - tránh lỗi khi service offline
3. **Log tất cả Zalo activities** - dễ debug và monitor
4. **Không block main flow** - Zalo notification là "nice to have", không phải "must have"
5. **Rate limiting** - không gửi quá nhiều tin trong thời gian ngắn

---

## 🎯 Kết quả mong đợi

### User Experience:
- ✅ Students nhận thông báo Zalo khi có bài tập mới
- ✅ Students nhận xác nhận khi nộp bài thành công
- ✅ Students nhận thông báo điểm số khi được chấm
- ✅ Students được nhắc nhở khi sắp quá hạn nộp bài

### System Performance:
- ✅ Không ảnh hưởng tốc độ tạo homework
- ✅ Không crash khi Zalo service offline
- ✅ Logs đầy đủ để monitor và debug

---

🎉 **Integration hoàn tất! Students sẽ không bao giờ bỏ lỡ deadline nữa!** 📱

