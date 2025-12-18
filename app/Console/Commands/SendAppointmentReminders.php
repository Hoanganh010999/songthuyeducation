<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalendarEvent;
use App\Models\TrialStudent;
use App\Models\ClassLessonSession;
use App\Services\CustomerZaloNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send Zalo reminders for appointments starting in 1 hour';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Checking for appointments starting in 1 hour...');

        $sentCount = 0;

        // 1. Send reminders for placement tests
        $sentCount += $this->sendPlacementTestReminders();

        // 2. Send reminders for trial class sessions
        $sentCount += $this->sendTrialClassReminders();

        $this->info("✅ Sent {$sentCount} reminder(s)");

        return 0;
    }

    /**
     * Send reminders for placement tests (CalendarEvent)
     */
    protected function sendPlacementTestReminders(): int
    {
        $count = 0;

        // Get timezone from settings
        $timezone = \DB::table('settings')->where('key', 'timezone')->value('value') ?? 'Asia/Ho_Chi_Minh';

        // Calculate time window: 55-65 minutes from now
        $now = Carbon::now($timezone);
        $reminderStart = $now->copy()->addMinutes(55);
        $reminderEnd = $now->copy()->addMinutes(65);

        // Find placement tests starting in ~1 hour that haven't sent reminders
        $events = CalendarEvent::where('category', 'placement_test')
            ->where('status', 'pending')
            ->where('has_reminder', true)
            ->where('reminder_sent', false)
            ->whereBetween('start_date', [$reminderStart, $reminderEnd])
            ->with(['eventable'])
            ->get();

        $this->info("Found {$events->count()} placement test(s) starting in 1 hour");

        foreach ($events as $event) {
            try {
                $sent = $this->sendPlacementTestReminder($event);
                if ($sent) {
                    $event->update(['reminder_sent' => true]);
                    $count++;
                    $this->info("  ✓ Sent reminder for: {$event->title}");
                }
            } catch (\Exception $e) {
                Log::error('[AppointmentReminder] Failed to send placement test reminder', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Failed for: {$event->title}");
            }
        }

        return $count;
    }

    /**
     * Send reminders for trial class sessions
     */
    protected function sendTrialClassReminders(): int
    {
        $count = 0;

        // Get timezone from settings
        $timezone = \DB::table('settings')->where('key', 'timezone')->value('value') ?? 'Asia/Ho_Chi_Minh';

        // Calculate time window: 55-65 minutes from now
        $now = Carbon::now($timezone);
        $reminderStart = $now->copy()->addMinutes(55);
        $reminderEnd = $now->copy()->addMinutes(65);

        // Find trial students for sessions starting in ~1 hour
        $trialStudents = TrialStudent::where('status', 'registered')
            ->where('reminder_sent', false)
            ->whereHas('session', function ($query) use ($reminderStart, $reminderEnd) {
                $query->where('status', 'scheduled')
                    ->whereBetween('scheduled_date', [$reminderStart, $reminderEnd]);
            })
            ->with(['session', 'class', 'trialable'])
            ->get();

        $this->info("Found {$trialStudents->count()} trial class session(s) starting in 1 hour");

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
                $sent = $this->sendTrialClassReminder($trials);
                if ($sent) {
                    // Mark all trials as reminded
                    foreach ($trials as $trial) {
                        $trial->update(['reminder_sent' => true]);
                    }
                    $count++;
                    $this->info("  ✓ Sent reminder for {$trials->count()} trial session(s)");
                }
            } catch (\Exception $e) {
                Log::error('[AppointmentReminder] Failed to send trial class reminder', [
                    'customer_key' => $key,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Failed for customer: {$key}");
            }
        }

        return $count;
    }

    /**
     * Send placement test reminder
     */
    protected function sendPlacementTestReminder(CalendarEvent $event): bool
    {
        $notificationService = app(CustomerZaloNotificationService::class);

        // Check if it's a customer or child event
        if ($event->eventable_type === \App\Models\Customer::class) {
            $customer = $event->eventable;
            return $notificationService->sendPlacementTestReminderNotification($event, $customer, null);
        } else if ($event->eventable_type === \App\Models\CustomerChild::class) {
            $child = $event->eventable;
            $customer = $child->customer;
            return $notificationService->sendPlacementTestReminderNotification($event, $customer, $child);
        }

        return false;
    }

    /**
     * Send trial class reminder
     */
    protected function sendTrialClassReminder($trials): bool
    {
        $notificationService = app(CustomerZaloNotificationService::class);

        // Get customer
        $firstTrial = $trials->first();
        if ($firstTrial->trialable_type === \App\Models\Customer::class) {
            $customer = $firstTrial->trialable;
        } else {
            $customer = $firstTrial->trialable->customer;
        }

        // Get all sessions
        $sessions = $trials->map(fn($t) => $t->session)->unique('id');

        return $notificationService->sendTrialClassReminderNotification(
            $firstTrial,
            $customer,
            $sessions->all()
        );
    }
}
