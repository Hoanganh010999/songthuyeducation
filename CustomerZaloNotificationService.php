<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\Customer;
use App\Models\CustomerChild;
use App\Models\CalendarEvent;
use App\Models\TrialStudent;
use App\Models\ClassLessonSession;
use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerZaloNotificationService
{
    /**
     * Send placement test notification to customer via Zalo
     */
    public function sendPlacementTestNotification(CalendarEvent $event, Customer $customer): bool
    {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            try {
                $message = $this->formatPlacementTestMessage($event, $customer, null);
            } catch (\Exception $e) {
                Log::error('[CustomerZaloNotification] Failed to format placement test message', [
                    'customer_id' => $customer->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return false;
            }

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test notification', [
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send placement test notification for customer child
     */
    public function sendPlacementTestNotificationForChild(CalendarEvent $event, CustomerChild $child): bool
    {
        try {
            $customer = $child->customer;
            if (!$customer) {
                Log::warning('[CustomerZaloNotification] Child has no customer', ['child_id' => $child->id]);
                return false;
            }

            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatPlacementTestMessage($event, $customer, $child);

            // Send message to parent's phone
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test notification for child', [
                'child_id' => $child->id,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send trial class registration notification
     */
    public function sendTrialClassNotification(
        TrialStudent $trial,
        Customer $customer,
        array $sessions
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatTrialClassMessage($trial, $customer, $sessions);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send trial class notification', [
                'trial_id' => $trial->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send placement test reminder notification (1 hour before)
     */
    public function sendPlacementTestReminderNotification(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format reminder message
            $message = $this->formatPlacementTestReminderMessage($event, $customer, $child);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test reminder', [
                'event_id' => $event->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send trial class reminder notification (1 hour before)
     */
    public function sendTrialClassReminderNotification(
        TrialStudent $trial,
        Customer $customer,
        array $sessions
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format reminder message
            $message = $this->formatTrialClassReminderMessage($trial, $customer, $sessions);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send trial class reminder', [
                'trial_id' => $trial->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send placement test result notification
     */
    public function sendPlacementTestResultNotification(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format result message
            $message = $this->formatPlacementTestResultMessage($event, $customer, $child);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test result', [
                'event_id' => $event->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🔥 NEW: Send placement test updated notification
     */
    public function sendPlacementTestUpdatedNotification(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null,
        $oldStartDate = null,
        $oldLocation = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatPlacementTestUpdatedMessage($event, $customer, $child, $oldStartDate, $oldLocation);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test updated notification', [
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🔥 NEW: Send placement test cancelled notification
     */
    public function sendPlacementTestCancelledNotification(
        array $eventData,
        Customer $customer,
        ?CustomerChild $child = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatPlacementTestCancelledMessage($eventData, $customer, $child);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test cancelled notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send trial class feedback notification
     */
    public function sendTrialClassFeedbackNotification(
        TrialStudent $trial,
        Customer $customer
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format feedback message
            $message = $this->formatTrialClassFeedbackMessage($trial, $customer);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send trial class feedback', [
                'trial_id' => $trial->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get configured timezone from system settings
     */
    protected function getConfiguredTimezone(): string
    {
        try {
            $timezone = \DB::table('settings')->where('key', 'timezone')->value('value');
            return $timezone ?? 'Asia/Ho_Chi_Minh';
        } catch (\Exception $e) {
            return 'Asia/Ho_Chi_Minh';
        }
    }

    /**
     * Format placement test notification message
     */
    protected function formatPlacementTestMessage(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null
    ): string {
        $timezone = $this->getConfiguredTimezone();
        // 🔥 FIX: Database stores datetime in server timezone (Asia/Ho_Chi_Minh), not UTC
        // So we should parse it as server timezone, then convert to target timezone if needed
        try {
            // Use event's start_date directly (already in server timezone from Eloquent cast)
            $testDate = $event->start_date->copy()->setTimezone($timezone);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to parse start_date', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            // Fallback: parse raw value as server timezone
            $rawStartDate = \DB::table('calendar_events')->where('id', $event->id)->value('start_date');
            $testDate = \Carbon\Carbon::parse($rawStartDate)->setTimezone($timezone);
        }

        $message = "📝 LỊCH HẸN TEST ĐẦU VÀO\n\n";

        if ($child) {
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        } else {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        }

        $message .= "📞 Số điện thoại: {$customer->phone}\n\n";

        $message .= "📅 Thời gian: {$testDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ: {$testDate->format('H:i')}\n";

        if ($event->location) {
            $message .= "📍 Địa điểm: {$event->location}\n";
        }

        if ($event->description) {
            $message .= "\n📌 Ghi chú: {$event->description}\n";
        }

        $message .= "\nVui lòng đến đúng giờ để được phục vụ tốt nhất! 🎓\n";
        $message .= "Nếu có thay đổi, vui lòng liên hệ lại cho chúng tôi.";

        return $message;
    }

    /**
     * Format trial class notification message
     */
    protected function formatTrialClassMessage(
        TrialStudent $trial,
        Customer $customer,
        array $sessions
    ): string {
        $timezone = $this->getConfiguredTimezone();
        $class = $trial->class;

        $message = "🎓 ĐĂNG KÝ HỌC THỬ THÀNH CÔNG\n\n";

        // Determine if customer or child
        if ($trial->trialable_type === Customer::class) {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        } else {
            $child = $trial->trialable;
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        }

        $message .= "📞 Số điện thoại: {$customer->phone}\n\n";

        $message .= "📚 Lớp học: {$class->name} ({$class->class_code})\n";

        if ($class->homeroomTeacher) {
            $message .= "👨‍🏫 Giáo viên: {$class->homeroomTeacher->name}\n";
        }

        $message .= "\n📅 Các buổi học thử:\n\n";

        // List all registered sessions
        foreach ($sessions as $index => $session) {
            $sessionDate = \Carbon\Carbon::parse($session->scheduled_date)->timezone($timezone);
            $message .= "   " . ($index + 1) . ". Buổi {$session->session_number}\n";
            $message .= "      📖 {$session->lesson_title}\n";
            $message .= "      🗓️ {$sessionDate->format('d/m/Y')} - {$sessionDate->format('H:i')}\n";

            if ($index < count($sessions) - 1) {
                $message .= "\n";
            }
        }

        if ($trial->notes) {
            $message .= "\n📌 Ghi chú: {$trial->notes}\n";
        }

        $message .= "\nChúc bạn có trải nghiệm học tập tuyệt vời! 🌟\n";
        $message .= "Nếu có thắc mắc, vui lòng liên hệ lại cho chúng tôi.";

        return $message;
    }

    /**
     * Format placement test reminder message (1 hour before)
     */
    protected function formatPlacementTestReminderMessage(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null
    ): string {
        $timezone = $this->getConfiguredTimezone();
        // 🔥 FIX: Database stores datetime in server timezone, use event's start_date directly
        $testDate = $event->start_date->copy()->setTimezone($timezone);

        $message = "⏰ NHẮC NHỞ - LỊCH TEST ĐẦU VÀO\n\n";

        $message .= "🔔 Lịch test của bạn sẽ bắt đầu sau 1 giờ nữa!\n\n";

        if ($child) {
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        } else {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        }

        $message .= "\n📅 Thời gian: {$testDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ: {$testDate->format('H:i')}\n";

        if ($event->location) {
            $message .= "📍 Địa điểm: {$event->location}\n";
        }

        $message .= "\n💡 Lưu ý:\n";
        $message .= "• Vui lòng đến đúng giờ\n";
        $message .= "• Mang theo giấy tờ tùy thân (nếu có)\n";
        $message .= "• Chuẩn bị tinh thần thoải mái nhất\n";

        $message .= "\nChúc bạn làm bài test thật tốt! 💪";

        return $message;
    }

    /**
     * Format trial class reminder message (1 hour before)
     */
    protected function formatTrialClassReminderMessage(
        TrialStudent $trial,
        Customer $customer,
        array $sessions
    ): string {
        $timezone = $this->getConfiguredTimezone();
        $class = $trial->class;

        $message = "⏰ NHẮC NHỞ - BUỔI HỌC THỬ\n\n";

        $message .= "🔔 Buổi học thử của bạn sẽ bắt đầu sau 1 giờ nữa!\n\n";

        // Determine if customer or child
        if ($trial->trialable_type === Customer::class) {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        } else {
            $child = $trial->trialable;
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        }

        $message .= "\n📚 Lớp học: {$class->name} ({$class->class_code})\n";

        if ($class->homeroomTeacher) {
            $message .= "👨‍🏫 Giáo viên: {$class->homeroomTeacher->name}\n";
        }

        $message .= "\n📅 Các buổi học hôm nay:\n\n";

        // List all sessions
        foreach ($sessions as $index => $session) {
            // 🔥 FIX: Parse with explicit UTC timezone first, then convert to target timezone
            $sessionDate = \Carbon\Carbon::parse($session->scheduled_date, 'UTC')->timezone($timezone);
            $message .= "   " . ($index + 1) . ". Buổi {$session->session_number}\n";
            $message .= "      📖 {$session->lesson_title}\n";
            $message .= "      🕐 {$sessionDate->format('H:i')}\n";

            if ($index < count($sessions) - 1) {
                $message .= "\n";
            }
        }

        $message .= "\n💡 Lưu ý:\n";
        $message .= "• Vui lòng đến đúng giờ\n";
        $message .= "• Chuẩn bị dụng cụ học tập\n";
        $message .= "• Tham gia tích cực vào buổi học\n";

        $message .= "\nChúc bạn có buổi học thử thật vui vẻ! 🎓✨";

        return $message;
    }

    /**
     * 🔥 NEW: Send placement test updated notification
     */
    public function sendPlacementTestUpdatedNotification(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null,
        $oldStartDate = null,
        $oldLocation = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatPlacementTestUpdatedMessage($event, $customer, $child, $oldStartDate, $oldLocation);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test updated notification', [
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🔥 NEW: Send placement test cancelled notification
     */
    public function sendPlacementTestCancelledNotification(
        array $eventData,
        Customer $customer,
        ?CustomerChild $child = null
    ): bool {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[CustomerZaloNotification] No active Zalo account found');
                return false;
            }

            // Format notification message
            $message = $this->formatPlacementTestCancelledMessage($eventData, $customer, $child);

            // Send message
            return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id);
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Failed to send placement test cancelled notification', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format placement test result message
     */
    protected function formatPlacementTestResultMessage(
        CalendarEvent $event,
        Customer $customer,
        ?CustomerChild $child = null
    ): string {
        $timezone = $this->getConfiguredTimezone();
        // 🔥 FIX: Database stores datetime in server timezone, use event's start_date directly
        $testDate = $event->start_date->copy()->setTimezone($timezone);
        $testResult = $event->test_result;

        $message = "📊 KẾT QUẢ TEST ĐẦU VÀO\n\n";

        if ($child) {
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        } else {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        }

        $message .= "\n📅 Ngày thi: {$testDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ thi: {$testDate->format('H:i')}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Score
        $score = $testResult['score'] ?? 0;
        $message .= "🎯 Điểm số: {$score}/100\n\n";

        // Level
        if (isset($testResult['level'])) {
            $message .= "📚 Trình độ: {$testResult['level']}\n\n";
        }

        // Notes (strip HTML tags)
        if (isset($testResult['notes'])) {
            $notes = strip_tags($testResult['notes']);
            $message .= "📝 Nhận xét:\n{$notes}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Evaluator info
        if (isset($testResult['evaluated_by_name'])) {
            $message .= "👨‍🏫 Giáo viên chấm: {$testResult['evaluated_by_name']}\n";
        }

        $message .= "\n💡 Bước tiếp theo:\n";
        $message .= "• Liên hệ với chúng tôi để tư vấn lộ trình học phù hợp\n";
        $message .= "• Đăng ký học chính thức với ưu đãi đặc biệt\n";

        $message .= "\nCảm ơn bạn đã tin tưởng! 🌟";

        return $message;
    }

    /**
     * Format trial class feedback message
     */
    protected function formatTrialClassFeedbackMessage(
        TrialStudent $trial,
        Customer $customer
    ): string {
        $timezone = $this->getConfiguredTimezone();
        $session = $trial->session;
        $class = $trial->class;
        $sessionDate = \Carbon\Carbon::parse($session->scheduled_date)->timezone($timezone);

        $message = "⭐ ĐÁNH GIÁ HỌC THỬ\n\n";

        // Customer/Child info
        if ($trial->trialable_type === Customer::class) {
            $message .= "👤 Khách hàng: {$customer->name}\n";
        } else {
            $child = $trial->trialable;
            $message .= "👨‍👩‍👧 Phụ huynh: {$customer->name}\n";
            $message .= "👶 Học viên: {$child->name}\n";
        }

        $message .= "\n📚 Lớp học: {$class->name} ({$class->class_code})\n";
        $message .= "📅 Ngày học: {$sessionDate->format('d/m/Y')}\n";
        $message .= "📖 Bài học: {$session->lesson_title}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Rating
        if ($trial->rating) {
            $stars = str_repeat('⭐', $trial->rating) . str_repeat('☆', 5 - $trial->rating);
            $message .= "🎯 Đánh giá: {$stars} ({$trial->rating}/5)\n\n";
        }

        // Feedback (strip HTML tags)
        if ($trial->feedback) {
            $feedback = strip_tags($trial->feedback);
            $message .= "📝 Nhận xét của giáo viên:\n{$feedback}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Teacher info
        if ($class->homeroomTeacher) {
            $message .= "👨‍🏫 Giáo viên: {$class->homeroomTeacher->name}\n";
        }

        if ($trial->feedbackBy) {
            $message .= "✍️ Người đánh giá: {$trial->feedbackBy->name}\n";
        }

        $message .= "\n💡 Bước tiếp theo:\n";
        $message .= "• Liên hệ với chúng tôi để đăng ký học chính thức\n";
        $message .= "• Nhận tư vấn lộ trình học phù hợp\n";
        $message .= "• Ưu đãi đặc biệt dành cho học viên đăng ký ngay\n";

        $message .= "\nCảm ơn bạn đã tham gia buổi học thử! 🎓✨";

        return $message;
    }

    /**
     * Get primary active Zalo account
     */
    public function getPrimaryZaloAccount(): ?ZaloAccount
    {
        return ZaloAccount::where('is_active', true)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Send Zalo message to customer
     */
    public function sendZaloMessage(
        ZaloAccount $account,
        string $phone,
        string $message,
        ?int $customerId = null
    ): bool {
        try {
            // Step 1: Search for user by phone
            Log::info('[CustomerZaloNotification] Searching user by phone', [
                'phone' => $phone,
                'account_id' => $account->id,
            ]);

            $searchResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/user/search', [
                'accountId' => $account->id,
                'phoneNumber' => $phone,
            ]);

            $zaloUserId = null;
            $isFriend = false;

            // If search successful, use the result
            if ($searchResponse->successful()) {
                $userData = $searchResponse->json('data');
                $zaloUserId = $userData['id'] ?? null;
                $isFriend = $userData['isFriend'] ?? false;
                Log::info('[CustomerZaloNotification] User found via search', [
                    'zalo_user_id' => $zaloUserId,
                    'is_friend' => $isFriend,
                ]);
            } else {
                // 🔥 FALLBACK: If search fails (user blocked search), try to find in friends list
                Log::info('[CustomerZaloNotification] Search failed, trying to find in friends list', [
                    'phone' => $phone,
                    'status' => $searchResponse->status(),
                    'error' => $searchResponse->json('message', 'Unknown error'),
                ]);

                // Normalize phone number (handle both 0xxx and 84xxx formats)
                $normalizedPhones = [$phone];
                if (substr($phone, 0, 1) === '0') {
                    $normalizedPhones[] = '84' . substr($phone, 1);
                } elseif (substr($phone, 0, 2) === '84') {
                    $normalizedPhones[] = '0' . substr($phone, 2);
                }

                // Search in friends list
                $friend = \App\Models\ZaloFriend::where('zalo_account_id', $account->id)
                    ->whereIn('phone', $normalizedPhones)
                    ->first();

                if ($friend && $friend->zalo_user_id) {
                    $zaloUserId = $friend->zalo_user_id;
                    $isFriend = true; // If in friends list, they are friends
                    Log::info('[CustomerZaloNotification] User found in friends list', [
                        'zalo_user_id' => $zaloUserId,
                        'friend_id' => $friend->id,
                        'name' => $friend->name,
                    ]);
                } else {
                    Log::warning('[CustomerZaloNotification] User not found in friends list either', [
                        'phone' => $phone,
                        'normalized_phones' => $normalizedPhones,
                    ]);
                    return false;
                }
            }

            if (!$zaloUserId) {
                Log::error('[CustomerZaloNotification] No Zalo user ID found', [
                    'phone' => $phone,
                ]);
                return false;
            }

            // Step 2: If not friends, send friend request
            if (!$isFriend) {
                Log::info('[CustomerZaloNotification] Not friends, sending friend request', [
                    'zalo_user_id' => $zaloUserId,
                ]);

                $friendRequestResponse = Http::timeout(30)->withHeaders([
                    'X-API-Key' => config('services.zalo.api_key'),
                ])->post(config('services.zalo.base_url') . '/api/friend/send-request', [
                    'accountId' => $account->id,
                    'userId' => $zaloUserId,
                    'message' => 'Xin chào! Tôi muốn kết bạn với bạn.',
                ]);

                if (!$friendRequestResponse->successful()) {
                    Log::warning('[CustomerZaloNotification] Friend request failed, continuing anyway', [
                        'error' => $friendRequestResponse->json('message', 'Unknown error'),
                    ]);
                }
            }

            // Step 3: Send message via Zalo service
            Log::info('[CustomerZaloNotification] Sending message via Zalo service', [
                'zalo_user_id' => $zaloUserId,
                'account_id' => $account->id,
            ]);

            $sendResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/message/send', [
                'accountId' => $account->id,
                'to' => $zaloUserId,
                'message' => $message,
            ]);

            if (!$sendResponse->successful()) {
                Log::error('[CustomerZaloNotification] Failed to send message', [
                    'status' => $sendResponse->status(),
                    'error' => $sendResponse->json('message', 'Unknown error'),
                    'body' => $sendResponse->body(),
                ]);
                return false;
            }

            $sendResult = $sendResponse->json('data');

            // Step 4: Save message to database
            try {
                // Find conversation (including soft deleted ones)
                $conversation = ZaloConversation::withTrashed()
                    ->where('zalo_account_id', $account->id)
                    ->where('recipient_id', $zaloUserId)
                    ->where('recipient_type', 'user')
                    ->first();

                if (!$conversation) {
                    // Create new conversation
                    $conversation = ZaloConversation::create([
                        'zalo_account_id' => $account->id,
                        'recipient_id' => $zaloUserId,
                        'recipient_type' => 'user',
                        'recipient_name' => $userData['display_name'] ?? $userData['displayName'] ?? 'Customer',
                        'recipient_avatar_url' => $userData['avatar'] ?? null,
                        'last_message_preview' => $message,
                        'last_message_at' => now(),
                        'unread_count' => 0,
                        'customer_id' => $customerId,
                    ]);
                } else {
                    // If conversation was soft deleted, restore it
                    if ($conversation->trashed()) {
                        $conversation->restore();
                        Log::info('[CustomerZaloNotification] Restored soft deleted conversation', [
                            'conversation_id' => $conversation->id,
                            'recipient_id' => $zaloUserId,
                        ]);
                    }
                    
                    // Update conversation
                    $conversation->update([
                        'last_message_preview' => $message,
                        'last_message_at' => now(),
                    ]);
                }

                // NOTE: Message is NOT saved here to avoid duplicates
                // The WebSocket listener (selfListen: true) will catch the echo and save
                // the message via /api/zalo/messages/receive endpoint with proper message_id
            } catch (\Exception $e) {
                Log::warning('[CustomerZaloNotification] Failed to update conversation', [
                    'error' => $e->getMessage(),
                ]);
                // Don't fail if DB save fails, message was sent successfully
            }

            Log::info('[CustomerZaloNotification] Message sent successfully', [
                'phone' => $phone,
                'customer_id' => $customerId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[CustomerZaloNotification] Exception during send', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
