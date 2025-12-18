<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassLessonSession;
use App\Services\CalendarEventService;
use Illuminate\Support\Facades\Log;

class SyncCalendarTeachers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'calendar:sync-teachers {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     */
    protected $description = 'Sync assigned_teacher_id from class sessions to calendar events using getEffectiveTeacher()';

    /**
     * Execute the console command.
     */
    public function handle(CalendarEventService $calendarService): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Starting calendar teacher synchronization...');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Get all class lesson sessions
        $sessions = ClassLessonSession::with([
            'class.homeroomTeacher',
            'classSchedule.teacher',
            'teacher',
            'calendarEvent'
        ])->get();

        $this->info("📊 Found {$sessions->count()} class sessions");

        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($sessions->count());
        $progressBar->start();

        foreach ($sessions as $session) {
            try {
                $effectiveTeacher = $session->getEffectiveTeacher();
                $calendarEvent = $session->calendarEvent;

                if (!$calendarEvent) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                $currentTeacherId = $calendarEvent->assigned_teacher_id;
                $newTeacherId = $effectiveTeacher?->id;

                // Check if update is needed
                if ($currentTeacherId == $newTeacherId) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Show what will change
                $oldName = $currentTeacherId ? \App\Models\User::find($currentTeacherId)?->name ?? "ID: $currentTeacherId" : 'null';
                $newName = $effectiveTeacher?->name ?? 'null';

                $this->newLine();
                $this->line("  Session #{$session->id} ({$session->class->name} - Buổi {$session->session_number}):");
                $this->line("    Current: {$oldName}");
                $this->line("    New: {$newName}");

                if (!$dryRun) {
                    // Update calendar event
                    $calendarEvent->update([
                        'assigned_teacher_id' => $newTeacherId,
                        'user_id' => $newTeacherId ?? 1,
                    ]);

                    // Update metadata
                    $metadata = $calendarEvent->metadata ?? [];
                    $metadata['session_id'] = $session->id; // Add session ID
                    $metadata['teacher_id'] = $newTeacherId;
                    $metadata['teacher_name'] = $effectiveTeacher?->full_name ?? $effectiveTeacher?->name;

                    $calendarEvent->update(['metadata' => $metadata]);
                }

                $syncedCount++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("  ❌ Error syncing session #{$session->id}: {$e->getMessage()}");
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Synchronization complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Synced', $syncedCount],
                ['Skipped (already correct)', $skippedCount],
                ['Errors', $errorCount],
                ['Total', $sessions->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        Log::info('[SyncCalendarTeachers] Completed', [
            'synced' => $syncedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
        ]);

        return 0;
    }
}
