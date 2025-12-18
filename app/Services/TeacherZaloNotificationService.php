<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\User;
use App\Models\CalendarEvent;
use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeacherZaloNotificationService
{
    /**
     * Send teacher assignment notification for placement test
     */
    public function sendTeacherAssignmentNotification(CalendarEvent $event, User $teacher): bool
    {
        try {
            // Get primary active Zalo account
            $account = $this->getPrimaryZaloAccount();
            if (!$account) {
                Log::warning('[TeacherZaloNotification] No active Zalo account found');
                return false;
            }

            // Check if teacher has phone number
            if (empty($teacher->phone)) {
                Log::warning('[TeacherZaloNotification] Teacher has no phone number', [
                    'teacher_id' => $teacher->id,
                ]);
                return false;
            }

            // Format assignment message
            $message = $this->formatTeacherAssignmentMessage($event, $teacher);

            // Send message
            return $this->sendZaloMessage($account, $teacher->phone, $message, $teacher->id);
        } catch (\Exception $e) {
            Log::error('[TeacherZaloNotification] Failed to send teacher assignment notification', [
                'event_id' => $event->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format teacher assignment message
     */
    protected function formatTeacherAssignmentMessage(CalendarEvent $event, User $teacher): string
    {
        $timezone = $this->getConfiguredTimezone();
        // 🔥 FIX: Database stores datetime in server timezone, use event's start_date directly
        $testDate = $event->start_date->copy()->setTimezone($timezone);

        $message = "📋 THÔNG BÁO PHÂN CÔNG CÔNG VIỆC\n\n";

        $message .= "👋 Xin chào {$teacher->name},\n\n";

        $message .= "Bạn đã được phân công chấm test đầu vào:\n\n";

        // Event details
        $message .= "📝 Nội dung: {$event->title}\n";
        $message .= "📅 Ngày test: {$testDate->format('d/m/Y')}\n";
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
            $message .= "\n📄 Mô tả:\n{$description}\n";
        }

        $message .= "\n💡 Lưu ý:\n";
        $message .= "• Vui lòng chuẩn bị đề thi và tài liệu liên quan\n";
        $message .= "• Đến đúng giờ trước 15 phút\n";
        $message .= "• Sau khi chấm xong, vui lòng cập nhật kết quả vào hệ thống\n";

        $message .= "\nChúc bạn làm việc hiệu quả! 💪";

        return $message;
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
     * Get primary active Zalo account
     */
    public function getPrimaryZaloAccount(): ?ZaloAccount
    {
        return ZaloAccount::where('is_active', true)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Send Zalo message to teacher
     */
    public function sendZaloMessage(
        ZaloAccount $account,
        string $phone,
        string $message,
        ?int $userId = null
    ): bool {
        try {
            // Step 1: Search for user by phone
            Log::info('[TeacherZaloNotification] Searching user by phone', [
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
                $userData = $searchResponse->json('data.user') ?? $searchResponse->json('data');
                $zaloUserId = $userData['id'] ?? $userData['userId'] ?? null;
                $isFriend = $userData['isFriend'] ?? false;
                Log::info('[TeacherZaloNotification] User found via search', [
                    'zalo_user_id' => $zaloUserId,
                    'is_friend' => $isFriend,
                ]);
            } else {
                // 🔥 FALLBACK: If search fails (user blocked search), try to find in friends list
                Log::info('[TeacherZaloNotification] Search failed, trying to find in friends list', [
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
                    Log::info('[TeacherZaloNotification] User found in friends list', [
                        'zalo_user_id' => $zaloUserId,
                        'friend_id' => $friend->id,
                        'name' => $friend->name,
                    ]);
                } else {
                    Log::warning('[TeacherZaloNotification] User not found in friends list either', [
                        'phone' => $phone,
                        'normalized_phones' => $normalizedPhones,
                    ]);
                    return false;
                }
            }

            if (!$zaloUserId) {
                Log::error('[TeacherZaloNotification] No Zalo user ID found', [
                    'phone' => $phone,
                ]);
                return false;
            }

            // Step 2: Send friend request if not already friends
            if (isset($userData['isFriend']) && !$userData['isFriend']) {
                Log::info('[TeacherZaloNotification] Sending friend request', [
                    'zalo_user_id' => $zaloUserId,
                ]);

                $friendRequestResponse = Http::timeout(30)->withHeaders([
                    'X-API-Key' => config('services.zalo.api_key'),
                ])->post(config('services.zalo.base_url') . '/api/friend/request', [
                    'accountId' => $account->id,
                    'userId' => $zaloUserId,
                ]);

                if (!$friendRequestResponse->successful()) {
                    Log::warning('[TeacherZaloNotification] Friend request failed, continuing anyway', [
                        'error' => $friendRequestResponse->json('message', 'Unknown error'),
                    ]);
                }
            }

            // Step 3: Send message via Zalo service
            Log::info('[TeacherZaloNotification] Sending message via Zalo service', [
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
                Log::error('[TeacherZaloNotification] Failed to send message', [
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
                        'recipient_name' => $userData['display_name'] ?? $userData['displayName'] ?? 'Teacher',
                        'recipient_avatar_url' => $userData['avatar'] ?? null,
                        'last_message_preview' => $message,
                        'last_message_at' => now(),
                        'unread_count' => 0,
                    ]);
                } else {
                    // If conversation was soft deleted, restore it
                    if ($conversation->trashed()) {
                        $conversation->restore();
                        Log::info('[TeacherZaloNotification] Restored soft deleted conversation', [
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
                Log::warning('[TeacherZaloNotification] Failed to update conversation', [
                    'error' => $e->getMessage(),
                ]);
                // Don't fail if DB save fails, message was sent successfully
            }

            Log::info('[TeacherZaloNotification] Message sent successfully', [
                'phone' => $phone,
                'user_id' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[TeacherZaloNotification] Exception during send', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
