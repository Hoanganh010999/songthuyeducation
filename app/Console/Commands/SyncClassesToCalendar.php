<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use App\Services\CalendarEventService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncClassesToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-classes
                            {--class_id= : Sync specific class by ID}
                            {--branch_id= : Sync all classes in a branch}
                            {--force : Force re-sync even if calendar event exists}
                            {--create-sessions : Create sessions from syllabus if missing}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ lịch học của lớp lên calendar module. Tự động tạo sessions nếu thiếu.';

    protected $calendarService;

    public function __construct(CalendarEventService $calendarService)
    {
        parent::__construct();
        $this->calendarService = $calendarService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎓 Bắt đầu đồng bộ lịch học lên calendar...');

        $classId = $this->option('class_id');
        $branchId = $this->option('branch_id');
        $force = $this->option('force');
        $createSessions = $this->option('create-sessions');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 CHẾ ĐỘ DRY-RUN: Chỉ hiển thị, không thay đổi dữ liệu');
        }

        // Build query
        $query = ClassModel::with(['lessonSessions', 'schedules', 'lessonPlan.sessions']);

        if ($classId) {
            $query->where('id', $classId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $classes = $query->get();

        if ($classes->isEmpty()) {
            $this->error('❌ Không tìm thấy lớp học nào!');
            return 1;
        }

        $this->info("📚 Tìm thấy {$classes->count()} lớp học");

        $stats = [
            'sessions_created' => 0,
            'events_synced' => 0,
            'events_skipped' => 0,
            'errors' => 0,
            'classes_missing_data' => 0,
        ];

        $progressBar = $this->output->createProgressBar($classes->count());
        $progressBar->start();

        foreach ($classes as $class) {
            $this->newLine();
            $this->processClass($class, $force, $createSessions, $dryRun, $stats);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->printSummary($stats);

        return 0;
    }

    /**
     * Process a single class
     */
    private function processClass($class, $force, $createSessions, $dryRun, &$stats)
    {
        $this->line("📖 Lớp: {$class->name} ({$class->code})");

        // Check if class has sessions
        $hasExistingSessions = $class->lessonSessions->count() > 0;
        $hasSchedules = $class->schedules->count() > 0;
        $hasSyllabus = $class->lesson_plan_id && $class->lessonPlan;
        $hasStartDate = $class->start_date;

        // Case 1: Class has no sessions but has schedules + syllabus + start_date
        if (!$hasExistingSessions && $hasSchedules && $hasSyllabus && $hasStartDate) {
            if ($createSessions) {
                $this->warn("   ⚠️ Lớp chưa có sessions, đang tạo từ syllabus...");

                if (!$dryRun) {
                    $sessionsCreated = $this->createSessionsFromSyllabus($class);
                    $stats['sessions_created'] += $sessionsCreated;
                    $this->info("   ✅ Đã tạo {$sessionsCreated} sessions");

                    // Reload sessions
                    $class->load('lessonSessions');
                } else {
                    $syllabusSessionCount = $class->lessonPlan->sessions->count();
                    $this->info("   [DRY-RUN] Sẽ tạo {$syllabusSessionCount} sessions");
                }
            } else {
                $this->warn("   ⚠️ Lớp chưa có sessions. Chạy với --create-sessions để tạo.");
                return;
            }
        }

        // Case 2: Class missing required data
        if (!$hasExistingSessions) {
            $missing = [];
            if (!$hasSchedules) $missing[] = 'schedules';
            if (!$hasSyllabus) $missing[] = 'syllabus';
            if (!$hasStartDate) $missing[] = 'start_date';

            if (!empty($missing)) {
                $this->warn("   ⏭️ Bỏ qua - thiếu: " . implode(', ', $missing));
                $stats['classes_missing_data']++;
                return;
            }
        }

        // Sync sessions to calendar
        foreach ($class->lessonSessions as $session) {
            try {
                // Skip if already has calendar event and not forcing
                if (!$force && $session->calendarEvent) {
                    $stats['events_skipped']++;
                    continue;
                }

                // Skip if no scheduled_date
                if (!$session->scheduled_date) {
                    $this->warn("   ⚠️ Session #{$session->session_number} không có scheduled_date");
                    continue;
                }

                if (!$dryRun) {
                    $this->calendarService->syncClassSessionToCalendar($session);
                    $stats['events_synced']++;
                } else {
                    $this->line("   [DRY-RUN] Sẽ sync session #{$session->session_number}");
                    $stats['events_synced']++;
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Lỗi session #{$session->session_number}: " . $e->getMessage());
                $stats['errors']++;
                Log::error("Calendar sync error for session {$session->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Create lesson sessions from syllabus
     * (Copied logic from ClassManagementController)
     */
    private function createSessionsFromSyllabus($class)
    {
        $lessonPlan = $class->lessonPlan;
        $schedules = $class->schedules;

        if (!$lessonPlan || $schedules->isEmpty()) {
            return 0;
        }

        // Load syllabus sessions
        $lessonPlan->load('sessions');

        if ($lessonPlan->sessions->isEmpty()) {
            $this->warn("   ⚠️ Syllabus không có sessions");
            return 0;
        }

        // Calculate scheduled dates based on class schedules
        $startDate = Carbon::parse($class->start_date);

        // Map day names to Carbon day numbers
        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];

        $scheduledDays = $schedules->map(fn($s) => $dayMap[$s->day_of_week] ?? null)->filter()->unique()->toArray();

        if (empty($scheduledDays)) {
            $this->warn("   ⚠️ Không tìm thấy ngày học hợp lệ");
            return 0;
        }

        $currentDate = $startDate->copy();
        $sessionsCreated = 0;
        $maxIterations = 1000;
        $iterationCount = 0;

        foreach ($lessonPlan->sessions as $syllabusSession) {
            if ($iterationCount >= $maxIterations) {
                Log::error("[SyncClassesToCalendar] Max iterations reached", ['class_id' => $class->id]);
                break;
            }

            // Find next scheduled date
            $daySearchCount = 0;
            while (!in_array($currentDate->dayOfWeek, $scheduledDays)) {
                $currentDate->addDay();
                $daySearchCount++;
                $iterationCount++;

                if ($daySearchCount > 14) {
                    Log::error("[SyncClassesToCalendar] Cannot find scheduled day", ['class_id' => $class->id]);
                    break 2;
                }

                if ($currentDate->diffInDays($startDate) > 365) {
                    break 2;
                }
            }

            // Find matching schedule for this day
            $schedule = $schedules->first(fn($s) => ($dayMap[$s->day_of_week] ?? null) === $currentDate->dayOfWeek);

            // Create session (this triggers the booted() hook which creates calendar event)
            ClassLessonSession::create([
                'class_id' => $class->id,
                'lesson_plan_id' => $lessonPlan->id,
                'lesson_plan_session_id' => $syllabusSession->id,
                'class_schedule_id' => $schedule?->id,
                'session_number' => $syllabusSession->session_number,
                'scheduled_date' => $currentDate->format('Y-m-d'),
                'start_time' => $schedule?->start_time,
                'end_time' => $schedule?->end_time,
                'status' => 'scheduled',
                'lesson_title' => $syllabusSession->lesson_title,
                'lesson_objectives' => $syllabusSession->lesson_objectives,
                'lesson_content' => $syllabusSession->lesson_content,
                'lesson_plan_url' => $syllabusSession->lesson_plan_url,
                'materials_url' => $syllabusSession->materials_url,
                'valuation_form_id' => $syllabusSession->valuation_form_id,
                'homework_url' => $syllabusSession->homework_url,
                'duration_minutes' => $syllabusSession->duration_minutes ?? 45,
                'lesson_plans_folder_id' => $syllabusSession->lesson_plans_folder_id,
                'materials_folder_id' => $syllabusSession->materials_folder_id,
                'homework_folder_id' => $syllabusSession->homework_folder_id,
            ]);

            $currentDate->addDay();
            $sessionsCreated++;
            $iterationCount++;
        }

        Log::info("[SyncClassesToCalendar] Created sessions", [
            'class_id' => $class->id,
            'sessions_created' => $sessionsCreated,
        ]);

        return $sessionsCreated;
    }

    /**
     * Print summary of sync operation
     */
    private function printSummary($stats)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ KẾT QUẢ ĐỒNG BỘ:');

        if ($stats['sessions_created'] > 0) {
            $this->info("   • Sessions đã tạo: {$stats['sessions_created']}");
        }

        $this->info("   • Calendar events đồng bộ: {$stats['events_synced']}");
        $this->info("   • Bỏ qua (đã có event): {$stats['events_skipped']}");

        if ($stats['classes_missing_data'] > 0) {
            $this->warn("   • Lớp thiếu dữ liệu: {$stats['classes_missing_data']}");
        }

        if ($stats['errors'] > 0) {
            $this->error("   • Lỗi: {$stats['errors']}");
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Hints
        $this->newLine();
        $this->line('💡 Gợi ý:');
        $this->line('   • Dùng --force để sync lại tất cả (kể cả đã có event)');
        $this->line('   • Dùng --create-sessions để tạo sessions từ syllabus');
        $this->line('   • Dùng --dry-run để xem trước mà không thay đổi dữ liệu');
        $this->line('   • Dùng --class_id=123 để sync một lớp cụ thể');
        $this->line('   • Dùng --branch_id=1 để sync tất cả lớp trong chi nhánh');
    }
}
