<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloGroupNotificationService
{
    /**
     * Send session cancellation notification to class group
     */
    public function sendSessionCancellationNotification(
        ClassLessonSession $session,
        string $cancellationReason
    ): bool {
        try {
            $class = $session->class;

            if (!$class || !$class->zalo_group_id) {
                Log::warning('[ZaloGroupNotification] Class has no Zalo group', [
                    'class_id' => $class?->id,
                ]);
                return false;
            }

            // Get Zalo account for this class
            $account = $this->getClassZaloAccount($class);
            if (!$account) {
                Log::warning('[ZaloGroupNotification] No Zalo account for class', [
                    'class_id' => $class->id,
                ]);
                return false;
            }

            // Format cancellation message
            $message = $this->formatSessionCancellationMessage($session, $cancellationReason);

            // Send message to group
            return $this->sendZaloGroupMessage(
                $account,
                $class->zalo_group_id,
                $class->zalo_group_name ?? $class->name,
                $message
            );
        } catch (\Exception $e) {
            Log::error('[ZaloGroupNotification] Failed to send cancellation notification', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send teacher change notification to class group
     */
    public function sendTeacherChangeNotification(
        ClassLessonSession $session,
        \App\Models\User $oldTeacher,
        \App\Models\User $newTeacher
    ): bool {
        try {
            $class = $session->class;

            if (!$class || !$class->zalo_group_id) {
                Log::warning('[ZaloGroupNotification] Class has no Zalo group', [
                    'class_id' => $class?->id,
                ]);
                return false;
            }

            // Get Zalo account for this class
            $account = $this->getClassZaloAccount($class);
            if (!$account) {
                Log::warning('[ZaloGroupNotification] No Zalo account for class', [
                    'class_id' => $class->id,
                ]);
                return false;
            }

            // Format teacher change message
            $message = $this->formatTeacherChangeMessage($session, $oldTeacher, $newTeacher);

            // Send message to group
            return $this->sendZaloGroupMessage(
                $account,
                $class->zalo_group_id,
                $class->zalo_group_name ?? $class->name,
                $message
            );
        } catch (\Exception $e) {
            Log::error('[ZaloGroupNotification] Failed to send teacher change notification', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format session cancellation message
     */
    protected function formatSessionCancellationMessage(
        ClassLessonSession $session,
        string $cancellationReason
    ): string {
        $timezone = $this->getConfiguredTimezone();
        $sessionDate = \Carbon\Carbon::parse($session->scheduled_date)->timezone($timezone);

        $message = "📢 THÔNG BÁO NGHỈ HỌC\n\n";
        $message .= "Lớp: {$session->class->name}\n\n";
        $message .= "🚫 Buổi {$session->session_number} bị hủy\n";
        $message .= "📅 Ngày: {$sessionDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ: {$session->start_time->format('H:i')} - {$session->end_time->format('H:i')}\n";

        if ($session->lesson_title) {
            $message .= "📖 Nội dung: {$session->lesson_title}\n";
        }

        $message .= "\n❗ Lý do:\n{$cancellationReason}\n";

        $message .= "\n💡 Lịch học sẽ được điều chỉnh lại. Vui lòng theo dõi lịch học mới trên hệ thống.";

        return $message;
    }

    /**
     * Format teacher change message
     */
    protected function formatTeacherChangeMessage(
        ClassLessonSession $session,
        \App\Models\User $oldTeacher,
        \App\Models\User $newTeacher
    ): string {
        $timezone = $this->getConfiguredTimezone();
        $sessionDate = \Carbon\Carbon::parse($session->scheduled_date)->timezone($timezone);

        $message = "📢 THÔNG BÁO THAY ĐỔI GIÁO VIÊN\n\n";
        $message .= "Lớp: {$session->class->name}\n\n";
        $message .= "📝 Buổi {$session->session_number}\n";
        $message .= "📅 Ngày: {$sessionDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ: {$session->start_time->format('H:i')} - {$session->end_time->format('H:i')}\n";

        if ($session->lesson_title) {
            $message .= "📖 Nội dung: {$session->lesson_title}\n";
        }

        $message .= "\n👨‍🏫 Giáo viên cũ: {$oldTeacher->name}\n";
        $message .= "👨‍🏫 Giáo viên mới: {$newTeacher->name}\n";

        $message .= "\n💡 Vui lòng chuẩn bị cho buổi học với giáo viên mới!";

        return $message;
    }

    /**
     * Get Zalo account for class
     */
    protected function getClassZaloAccount(ClassModel $class): ?ZaloAccount
    {
        // Try class-specific account first
        if ($class->zalo_account_id) {
            $account = ZaloAccount::find($class->zalo_account_id);
            if ($account && $account->is_active) {
                return $account;
            }
        }

        // Fallback to primary account
        return ZaloAccount::where('is_active', true)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Send Zalo message to group
     */
    protected function sendZaloGroupMessage(
        ZaloAccount $account,
        string $groupId,
        string $groupName,
        string $message
    ): bool {
        try {
            Log::info('[ZaloGroupNotification] Sending message to group', [
                'account_id' => $account->id,
                'group_id' => $groupId,
            ]);

            // Send message via Zalo service
            $sendResponse = Http::timeout(30)->withHeaders([
                'X-API-Key' => config('services.zalo.api_key'),
            ])->post(config('services.zalo.base_url') . '/api/message/send', [
                'accountId' => $account->id,
                'to' => $groupId,
                'message' => $message,
                'type' => 'group',
            ]);

            if (!$sendResponse->successful()) {
                Log::error('[ZaloGroupNotification] Failed to send message', [
                    'status' => $sendResponse->status(),
                    'error' => $sendResponse->json('message', 'Unknown error'),
                    'body' => $sendResponse->body(),
                ]);
                return false;
            }

            $sendResult = $sendResponse->json('data');

            // Save message to database
            try {
                // Find or create conversation
                $conversation = ZaloConversation::where('zalo_account_id', $account->id)
                    ->where('recipient_id', $groupId)
                    ->where('recipient_type', 'group')
                    ->first();

                if (!$conversation) {
                    $conversation = ZaloConversation::create([
                        'zalo_account_id' => $account->id,
                        'recipient_id' => $groupId,
                        'recipient_type' => 'group',
                        'recipient_name' => $groupName,
                        'last_message_preview' => $message,
                        'last_message_at' => now(),
                        'unread_count' => 0,
                    ]);
                } else {
                    $conversation->update([
                        'last_message_preview' => $message,
                        'last_message_at' => now(),
                    ]);
                }

                // Save message
                ZaloMessage::create([
                    'zalo_account_id' => $account->id,
                    'zalo_conversation_id' => $conversation->id,
                    'recipient_id' => $groupId,
                    'recipient_type' => 'group',
                    'message_id' => $sendResult['messageId'] ?? null,
                    'type' => 'sent',
                    'content' => $message,
                    'sent_at' => now(),
                    'status' => 'sent',
                ]);
            } catch (\Exception $e) {
                Log::warning('[ZaloGroupNotification] Failed to save message to DB', [
                    'error' => $e->getMessage(),
                ]);
                // Don't fail if DB save fails, message was sent successfully
            }

            Log::info('[ZaloGroupNotification] Message sent successfully', [
                'group_id' => $groupId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[ZaloGroupNotification] Exception during send', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $timezone = \DB::table('settings')->where('key', 'timezone')->value('value');
            return $timezone ?? 'Asia/Ho_Chi_Minh';
        } catch (\Exception $e) {
            return 'Asia/Ho_Chi_Minh';
        }
    }
}
