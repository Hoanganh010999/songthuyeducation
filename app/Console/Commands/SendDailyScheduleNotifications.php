<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\ClassLessonSession;
use App\Models\TrialStudent;
use App\Services\CustomerZaloNotificationService;
use App\Services\TeacherZaloNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDailyScheduleNotifications extends Command
{
    protected $signature = 'schedule:send-daily-notifications';
    protected $description = 'Send daily schedule notifications to customers and teachers at configured time';

    protected CustomerZaloNotificationService $customerNotificationService;
    protected TeacherZaloNotificationService $teacherNotificationService;

    public function __construct(
        CustomerZaloNotificationService $customerNotificationService,
        TeacherZaloNotificationService $teacherNotificationService
    ) {
        parent::__construct();
        $this->customerNotificationService = $customerNotificationService;
        $this->teacherNotificationService = $teacherNotificationService;
    }

    public function handle()
    {
        // Check if daily notifications are enabled
        $enabled = DB::table('settings')
            ->where('key', 'daily_schedule_notification_enabled')
            ->value('value');

        if (!$enabled || $enabled === '0') {
            $this->info('⚠️ Daily schedule notifications are disabled');
            return 0;
        }

        // Check if notifications were already sent today
        $timezone = $this->getConfiguredTimezone();
        $today = Carbon::now($timezone)->toDateString();
        $lastSentKey = 'daily_schedule_notification_last_sent';
        $lastSent = DB::table('settings')->where('key', $lastSentKey)->value('value');

        if ($lastSent === $today) {
            $this->info('✅ Daily notifications already sent today');
            return 0;
        }

        $todayStart = Carbon::now($timezone)->startOfDay();

        Log::info('[DailySchedule] Starting daily schedule notifications', [
            'date' => $today,
            'timezone' => $timezone,
        ]);

        $this->info("📅 Sending daily schedule notifications for {$today}");

        $sentCount = 0;

        // 1. Send placement test notifications
        $sentCount += $this->sendPlacementTestNotifications($todayStart);

        // 2. Send trial class notifications
        $sentCount += $this->sendTrialClassNotifications($todayStart);

        // 3. Send class session notifications to teachers (with trial student info)
        $sentCount += $this->sendClassSessionNotifications($todayStart);

        $this->info("✅ Sent {$sentCount} daily schedule notification(s)");

        // Mark notifications as sent for today
        $existing = DB::table('settings')->where('key', $lastSentKey)->exists();
        if ($existing) {
            DB::table('settings')->where('key', $lastSentKey)->update([
                'value' => $today,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('settings')->insert([
                'key' => $lastSentKey,
                'value' => $today,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('[DailySchedule] Completed daily schedule notifications', [
            'sent_count' => $sentCount,
        ]);

        return 0;
    }

    /**
     * Send placement test notifications to customers and assigned teachers
     */
    protected function sendPlacementTestNotifications(Carbon $today): int
    {
        $count = 0;

        $events = CalendarEvent::where('category', 'placement_test')
            ->whereDate('start_date', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['eventable'])
            ->get();

        foreach ($events as $event) {
            try {
                // Notify customer
                if ($event->eventable_type === \App\Models\Customer::class) {
                    $customer = $event->eventable;
                    if ($customer && $this->sendPlacementTestToCustomer($event, $customer, null)) {
                        $count++;
                    }
                } elseif ($event->eventable_type === \App\Models\CustomerChild::class) {
                    $child = $event->eventable;
                    $customer = $child?->customer;
                    if ($customer && $this->sendPlacementTestToCustomer($event, $customer, $child)) {
                        $count++;
                    }
                }

                // Notify assigned teacher if any
                if ($event->assigned_teacher_id) {
                    $teacher = \App\Models\User::find($event->assigned_teacher_id);
                    if ($teacher && $this->sendPlacementTestToTeacher($event, $teacher)) {
                        $count++;
                    }
                }
            } catch (\Exception $e) {
                Log::error('[DailySchedule] Failed to send placement test notification', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("  📝 Sent {$count} placement test notification(s)");
        return $count;
    }

    /**
     * Send trial class notifications to customers
     */
    protected function sendTrialClassNotifications(Carbon $today): int
    {
        $count = 0;

        // Get trial students for today's sessions
        $trialStudents = TrialStudent::whereHas('session', function ($query) use ($today) {
            $query->whereDate('scheduled_date', $today)
                ->where('status', 'scheduled');
        })
            ->where('status', 'registered')
            ->with(['trialable', 'session', 'class'])
            ->get();

        // Group by customer to send one message per customer
        $groupedByCustomer = $trialStudents->groupBy(function ($trial) {
            if ($trial->trialable_type === \App\Models\Customer::class) {
                return 'customer_' . $trial->trialable_id;
            } else {
                return 'customer_' . $trial->trialable->customer_id;
            }
        });

        foreach ($groupedByCustomer as $key => $trials) {
            try {
                $firstTrial = $trials->first();

                // Get customer
                if ($firstTrial->trialable_type === \App\Models\Customer::class) {
                    $customer = $firstTrial->trialable;
                } else {
                    $customer = $firstTrial->trialable->customer;
                }

                if ($customer && $this->sendTrialClassToCustomer($trials)) {
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error('[DailySchedule] Failed to send trial class notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("  🎓 Sent {$count} trial class notification(s)");
        return $count;
    }

    /**
     * Send class session notifications to teachers (with trial student info)
     * Groups all sessions by teacher to send ONE consolidated message per teacher
     */
    protected function sendClassSessionNotifications(Carbon $today): int
    {
        $count = 0;

        // 🔥 FIX: Loại bỏ các lớp đã hoàn thành hoặc bị hủy
        $sessions = ClassLessonSession::whereDate('scheduled_date', $today)
            ->where('status', 'scheduled')
            ->whereHas('class', function ($query) {
                $query->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->with(['class.homeroomTeacher', 'class.subject', 'trialStudents.trialable'])
            ->orderBy('start_time')
            ->get();

        // Group sessions by teacher
        $sessionsByTeacher = [];

        foreach ($sessions as $session) {
            // Get teacher for this session
            // Priority: subject teacher from class_subject, then homeroom teacher
            $teacher = null;

            if ($session->class && $session->class->subject_id) {
                // Try to get teacher from class_subject pivot table
                $classSubject = DB::table('class_subject')
                    ->where('class_id', $session->class_id)
                    ->where('subject_id', $session->class->subject_id)
                    ->where('status', 'active')
                    ->first();

                if ($classSubject && $classSubject->teacher_id) {
                    $teacher = \App\Models\User::find($classSubject->teacher_id);
                }
            }

            // Fallback to homeroom teacher
            if (!$teacher && $session->class) {
                $teacher = $session->class->homeroomTeacher;
            }

            if ($teacher) {
                $teacherId = $teacher->id;
                if (!isset($sessionsByTeacher[$teacherId])) {
                    $sessionsByTeacher[$teacherId] = [
                        'teacher' => $teacher,
                        'sessions' => [],
                    ];
                }
                $sessionsByTeacher[$teacherId]['sessions'][] = $session;
            }
        }

        // Send one consolidated message per teacher
        foreach ($sessionsByTeacher as $teacherId => $data) {
            try {
                if ($this->sendConsolidatedClassSessionToTeacher($data['sessions'], $data['teacher'])) {
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error('[DailySchedule] Failed to send class session notification', [
                    'teacher_id' => $teacherId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("  👨‍🏫 Sent {$count} class session notification(s)");
        return $count;
    }

    /**
     * Format and send placement test notification to customer
     */
    protected function sendPlacementTestToCustomer(
        CalendarEvent $event,
        \App\Models\Customer $customer,
        ?\App\Models\CustomerChild $child
    ): bool {
        $timezone = $this->getConfiguredTimezone();
        $testDate = Carbon::parse($event->start_date)->timezone($timezone);

        $message = "📅 LỊCH HẸN HÔM NAY\n\n";
        $message .= "👋 Xin chào {$customer->name},\n\n";
        $message .= "Nhắc nhở lịch test đầu vào hôm nay:\n\n";
        $message .= "📝 Nội dung: {$event->title}\n";
        $message .= "🕐 Giờ: {$testDate->format('H:i')}\n";

        if ($event->location) {
            $message .= "📍 Địa điểm: {$event->location}\n";
        }

        if ($child) {
            $message .= "\n👶 Thí sinh: {$child->name}\n";
        }

        if ($event->description) {
            $description = strip_tags($event->description);
            $message .= "\n📄 Ghi chú:\n{$description}\n";
        }

        $message .= "\n💡 Lưu ý: Vui lòng đến đúng giờ. Chúc bạn test tốt! 💪";

        return $this->sendZaloToCustomer($customer, $message);
    }

    /**
     * Format and send placement test notification to teacher
     */
    protected function sendPlacementTestToTeacher(CalendarEvent $event, \App\Models\User $teacher): bool
    {
        $timezone = $this->getConfiguredTimezone();
        $testDate = Carbon::parse($event->start_date)->timezone($timezone);

        $message = "📅 LỊCH CÔNG VIỆC HÔM NAY\n\n";
        $message .= "👋 Xin chào {$teacher->name},\n\n";
        $message .= "Nhắc nhở lịch chấm test đầu vào hôm nay:\n\n";
        $message .= "📝 Nội dung: {$event->title}\n";
        $message .= "🕐 Giờ: {$testDate->format('H:i')}\n";

        if ($event->location) {
            $message .= "📍 Địa điểm: {$event->location}\n";
        }

        // Customer info
        if ($event->eventable) {
            if ($event->eventable_type === \App\Models\Customer::class) {
                $customer = $event->eventable;
                $message .= "\n👤 Thí sinh: {$customer->name}\n";
                $message .= "📞 Số điện thoại: {$customer->phone}\n";
            } else if ($event->eventable_type === \App\Models\CustomerChild::class) {
                $child = $event->eventable;
                $customer = $child->customer;
                $message .= "\n👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
                $message .= "👶 Thí sinh: {$child->name}\n";
                $message .= "📞 Số điện thoại: {$customer->phone}\n";
            }
        }

        if ($event->description) {
            $description = strip_tags($event->description);
            $message .= "\n📄 Ghi chú:\n{$description}\n";
        }

        $message .= "\n💡 Vui lòng chuẩn bị tài liệu và đến đúng giờ. Chúc bạn làm việc hiệu quả! 💪";

        return $this->sendZaloToTeacher($teacher, $message);
    }

    /**
     * Format and send trial class notification to customer
     */
    protected function sendTrialClassToCustomer($trials): bool
    {
        $firstTrial = $trials->first();

        // Get customer
        if ($firstTrial->trialable_type === \App\Models\Customer::class) {
            $customer = $firstTrial->trialable;
            $studentName = $customer->name;
        } else {
            $customer = $firstTrial->trialable->customer;
            $studentName = $firstTrial->trialable->name;
        }

        $timezone = $this->getConfiguredTimezone();

        $message = "📅 LỊCH HỌC THỬ HÔM NAY\n\n";
        $message .= "👋 Xin chào {$customer->name},\n\n";
        $message .= "Nhắc nhở lịch học thử hôm nay:\n\n";

        foreach ($trials as $trial) {
            $session = $trial->session;
            $class = $trial->class;
            $sessionTime = Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'))
                ->timezone($timezone);

            $message .= "📚 Lớp: {$class->name}\n";
            $message .= "🕐 Giờ: {$sessionTime->format('H:i')}\n";

            if ($class->room_number) {
                $message .= "🚪 Phòng: {$class->room_number}\n";
            }

            if ($session->lesson_title) {
                $message .= "📖 Nội dung: {$session->lesson_title}\n";
            }

            $message .= "\n";
        }

        $message .= "👶 Học viên: {$studentName}\n\n";
        $message .= "💡 Lưu ý: Vui lòng đến đúng giờ. Chúc con học tốt! 💪";

        return $this->sendZaloToCustomer($customer, $message);
    }

    /**
     * Format and send CONSOLIDATED class session notification to teacher
     * All sessions for a teacher are combined into ONE message
     */
    protected function sendConsolidatedClassSessionToTeacher(array $sessions, \App\Models\User $teacher): bool
    {
        $timezone = $this->getConfiguredTimezone();
        $sessionCount = count($sessions);

        $message = "📅 LỊCH DẠY HÔM NAY\n\n";
        $message .= "👋 Xin chào {$teacher->name},\n\n";
        $message .= "Nhắc nhở lịch dạy hôm nay ({$sessionCount} buổi):\n";

        $allTrialStudents = [];

        foreach ($sessions as $index => $session) {
            $sessionTime = Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'))
                ->timezone($timezone);

            $num = $index + 1;
            $message .= "\n━━━━━━━━━━━━━━━━━━\n";
            $message .= "📚 [{$num}] {$session->class->name}\n";
            $message .= "🕐 {$sessionTime->format('H:i')} - {$session->end_time->format('H:i')}\n";

            if ($session->class->room_number) {
                $message .= "🚪 Phòng: {$session->class->room_number}\n";
            }

            if ($session->lesson_title) {
                $message .= "📖 Nội dung: {$session->lesson_title}\n";
            }

            // Collect trial students for this session
            $trialStudents = $session->trialStudents()
                ->where('status', 'registered')
                ->with(['trialable'])
                ->get();

            foreach ($trialStudents as $trial) {
                $allTrialStudents[] = [
                    'trial' => $trial,
                    'class_name' => $session->class->name,
                ];
            }
        }

        // Add trial students section if any
        if (!empty($allTrialStudents)) {
            $message .= "\n━━━━━━━━━━━━━━━━━━\n";
            $message .= "⭐ LƯU Ý: HÔM NAY CÓ HỌC VIÊN HỌC THỬ\n\n";

            foreach ($allTrialStudents as $index => $data) {
                $trial = $data['trial'];
                $num = $index + 1;

                if ($trial->trialable_type === \App\Models\Customer::class) {
                    $student = $trial->trialable;
                    $message .= "{$num}. 👤 {$student->name}\n";
                    $message .= "   📚 Lớp: {$data['class_name']}\n";
                    $message .= "   📞 {$student->phone}\n";
                } else if ($trial->trialable_type === \App\Models\CustomerChild::class) {
                    $child = $trial->trialable;
                    $parent = $child->customer;
                    $message .= "{$num}. 👶 {$child->name}\n";
                    $message .= "   📚 Lớp: {$data['class_name']}\n";
                    $message .= "   👨‍👩‍👧 Phụ huynh: {$parent->name}\n";
                    $message .= "   📞 {$parent->phone}\n";
                }

                if ($trial->notes) {
                    $notes = strip_tags($trial->notes);
                    $message .= "   📝 Ghi chú: {$notes}\n";
                }
            }

            $message .= "\n💡 Vui lòng chú ý theo dõi và đánh giá học viên học thử!\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━\n";
        $message .= "Chúc bạn buổi dạy hiệu quả! 💪";

        return $this->sendZaloToTeacher($teacher, $message);
    }

    /**
     * Send Zalo message to customer
     */
    protected function sendZaloToCustomer(\App\Models\Customer $customer, string $message): bool
    {
        if (empty($customer->phone)) {
            Log::warning('[DailySchedule] Customer has no phone number', [
                'customer_id' => $customer->id,
            ]);
            return false;
        }

        // Use existing method from CustomerZaloNotificationService
        // We'll need to add a generic sendMessage method
        try {
            $account = $this->customerNotificationService->getPrimaryZaloAccount();
            if (!$account) {
                return false;
            }

            return $this->customerNotificationService->sendZaloMessage(
                $account,
                $customer->phone,
                $message,
                $customer->id
            );
        } catch (\Exception $e) {
            Log::error('[DailySchedule] Failed to send message to customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send Zalo message to teacher
     */
    protected function sendZaloToTeacher(\App\Models\User $teacher, string $message): bool
    {
        if (empty($teacher->phone)) {
            Log::warning('[DailySchedule] Teacher has no phone number', [
                'teacher_id' => $teacher->id,
            ]);
            return false;
        }

        try {
            $account = $this->teacherNotificationService->getPrimaryZaloAccount();
            if (!$account) {
                return false;
            }

            return $this->teacherNotificationService->sendZaloMessage(
                $account,
                $teacher->phone,
                $message,
                $teacher->id
            );
        } catch (\Exception $e) {
            Log::error('[DailySchedule] Failed to send message to teacher', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get configured timezone
     */
    protected function getConfiguredTimezone(): string
    {
        try {
            $timezone = DB::table('settings')->where('key', 'timezone')->value('value');
            return $timezone ?? 'Asia/Ho_Chi_Minh';
        } catch (\Exception $e) {
            return 'Asia/Ho_Chi_Minh';
        }
    }
}
