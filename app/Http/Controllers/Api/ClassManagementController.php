<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\ClassLessonSession;
use App\Models\Holiday;
use App\Models\LessonPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassManagementController extends Controller
{
    // ==========================================
    // CLASS CRUD
    // ==========================================
    
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        $semesterId = $request->input('semester_id');
        $status = $request->input('status'); // 🔥 FIX: Add status filter

        $query = ClassModel::with([
            'homeroomTeacher',
            'subject',
            'semester',
            'lessonPlan',
            'schedules.teacher',
            'schedules.subject',
            'schedules.room'
        ])->where('branch_id', $branchId);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        // 🔥 FIX: Filter by status if provided
        if ($status) {
            $query->where('status', $status);
        }

        $classes = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:classes,code',
            'subject_id' => 'required|exists:subjects,id',
            'homeroom_teacher_id' => 'required|exists:users,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'lesson_plan_id' => 'nullable|exists:lesson_plans,id',
            'total_sessions' => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'room_number' => 'nullable|string',
            'status' => 'nullable|in:draft,active,paused,completed,cancelled',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Get total sessions from lesson plan if provided, otherwise from request
            $totalSessions = $request->total_sessions;
            if ($request->lesson_plan_id) {
                $lessonPlan = \App\Models\LessonPlan::find($request->lesson_plan_id);
                $totalSessions = $lessonPlan->total_sessions ?? $totalSessions;
            }
            
            // Generate code if not provided
            $code = $request->code;
            if (!$code) {
                $code = 'CLASS_' . strtoupper(uniqid());
            }
            
            // Get academic year from semester if provided
            $academicYear = null;
            if ($request->semester_id) {
                $semester = \App\Models\Semester::find($request->semester_id);
                if ($semester && $semester->academicYear) {
                    $academicYear = $semester->academicYear->name;
                }
            }
            // Default to current year if no semester
            if (!$academicYear) {
                $currentYear = date('Y');
                $nextYear = $currentYear + 1;
                $academicYear = "{$currentYear}-{$nextYear}";
            }
            
            $class = ClassModel::create([
                'branch_id' => $request->branch_id,
                'name' => $request->name,
                'code' => $code,
                'academic_year' => $academicYear,
                'subject_id' => $request->subject_id,
                'homeroom_teacher_id' => $request->homeroom_teacher_id,
                'semester_id' => $request->semester_id,
                'lesson_plan_id' => $request->lesson_plan_id,
                'total_sessions' => $totalSessions,
                'completed_sessions' => 0,
                'capacity' => $request->capacity ?? 40,
                'room_number' => $request->room_number,
                'status' => $request->status ?? 'draft',
                'is_active' => true,
                'start_date' => $request->start_date ?? now()->toDateString(),
            ]);
            
            // Create schedules if provided
            if ($request->schedules && is_array($request->schedules)) {
                $dayMap = [
                    '2' => 'monday',
                    '3' => 'tuesday',
                    '4' => 'wednesday',
                    '5' => 'thursday',
                    '6' => 'friday',
                    '7' => 'saturday',
                    '8' => 'sunday'
                ];
                
                foreach ($request->schedules as $schedule) {
                    $dayOfWeek = $dayMap[$schedule['day_of_week']] ?? 'monday';
                    
                    $class->schedules()->create([
                        'subject_id' => $request->subject_id, // Use class subject
                        'day_of_week' => $dayOfWeek,
                        'study_period_id' => $schedule['study_period_id'],
                        'lesson_number' => 1, // Default to 1st lesson
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'] ?? $schedule['start_time'], // Default to start_time if not provided
                        'teacher_id' => $schedule['teacher_id'],
                        'room_id' => $schedule['room_id'] ?? null,
                    ]);
                }
            }
            
            // Auto-create lesson sessions from syllabus if lesson_plan_id is provided
            if ($request->lesson_plan_id) {
                $lessonPlan = LessonPlan::with('sessions')->find($request->lesson_plan_id);
                if ($lessonPlan && $lessonPlan->sessions) {
                    // Reload schedules to ensure we have fresh data
                    $class->load('schedules');
                    $schedules = $class->schedules;
                    
                    // Check if we have schedules and start_date
                    if ($schedules->isEmpty()) {
                        Log::warning('[ClassManagement] Cannot create sessions: No schedules defined', [
                            'class_id' => $class->id,
                        ]);
                    } elseif (!$class->start_date) {
                        Log::warning('[ClassManagement] Cannot create sessions: No start_date', [
                            'class_id' => $class->id,
                        ]);
                    } else {
                        // Use helper method to create sessions
                        $this->createSessionsFromSyllabus($class, $lessonPlan, $schedules);
                    }
                }
            }
            
            DB::commit();
            
            // Reload class with all relationships including schedules with teacher
            $class->refresh();
            $class->load([
                'homeroomTeacher:id,name,email', 
                'subject:id,name', 
                'semester:id,name', 
                'lessonPlan:id,name', 
                'schedules.teacher:id,name,email',
                'schedules.room:id,name',
                'schedules.subject:id,name',
                'schedules.studyPeriod:id,name,duration_minutes'
            ]);
            
            // Ensure schedules are properly loaded with relationships
            $class->schedules->each(function($schedule) {
                $schedule->load(['teacher:id,name,email', 'room:id,name', 'subject:id,name']);
            });
            
            // Create empty class folder in Class History (OUTSIDE transaction)
            $folderCreationStatus = null;
            try {
                $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
                if ($setting) {
                    $service = new \App\Services\GoogleDriveService($setting);
                    $result = $service->createEmptyClassFolder(
                        $class->name,
                        $class->code,
                        $class->id,
                        $class->branch_id
                    );
                    
                    // Update class with folder ID
                    $class->update([
                        'google_drive_folder_id' => $result['folder_id'],
                        'google_drive_folder_name' => $result['folder_name'],
                    ]);
                    
                    $folderCreationStatus = [
                        'success' => true,
                        'folder_id' => $result['folder_id'],
                        'folder_name' => $result['folder_name'],
                    ];
                    
                    Log::info('[ClassManagement] Created class folder', [
                        'class_id' => $class->id,
                        'folder_id' => $result['folder_id'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[ClassManagement] Error creating class folder', [
                    'class_id' => $class->id,
                    'error' => $e->getMessage(),
                ]);
                $folderCreationStatus = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo lớp học thành công',
                'data' => $class,
                'folder_creation_status' => $folderCreationStatus,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo lớp học: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function show(Request $request, $id)
    {
        // Default relationships to load
        $with = [
            'homeroomTeacher',
            'semester.academicYear',
            'lessonPlan.sessions',
            'schedules.teacher',
            'schedules.subject',
            'schedules.room',
            'schedules.studyPeriod',
            'lessonSessions.lessonPlanSession'
        ];

        // Support dynamic includes from request (e.g., for permission checks)
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $with = array_merge($with, $includes);
        }

        $class = ClassModel::with($with)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $class
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $class = ClassModel::with('schedules', 'lessonSessions.attendances')->findOrFail($id);
        
        // Check permission: Only homeroom teacher or admin can update
        $user = auth()->user();
        if ($class->homeroom_teacher_id !== $user->id && !$user->hasPermission('classes.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật lớp học này'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:classes,code,' . $id,
            'subject_id' => 'required|exists:subjects,id',
            'homeroom_teacher_id' => 'required|exists:users,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'lesson_plan_id' => 'nullable|exists:lesson_plans,id',
            'total_sessions' => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:draft,active,paused,completed,cancelled',
            'start_date' => 'required|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|string',
            'schedules.*.study_period_id' => 'required|exists:study_periods,id',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
            'schedules.*.teacher_id' => 'required|exists:users,id',
            'schedules.*.room_id' => 'nullable|exists:rooms,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Check if start_date changed
            $oldStartDate = $class->start_date;
            $newStartDate = $request->input('start_date', $oldStartDate);
            $startDateChanged = $oldStartDate !== $newStartDate;
            
            // Check if schedules changed
            $schedulesChanged = false;
            $oldSchedules = $class->schedules->map(function($s) {
                return [
                    'day_of_week' => $s->day_of_week,
                    'start_time' => $s->start_time,
                    'study_period_id' => $s->study_period_id
                ];
            })->toArray();
            
            $newSchedules = collect($request->schedules ?? [])->map(function($s) {
                $dayMap = [
                    '2' => 'monday', '3' => 'tuesday', '4' => 'wednesday', '5' => 'thursday',
                    '6' => 'friday', '7' => 'saturday', '8' => 'sunday'
                ];
                return [
                    'day_of_week' => $dayMap[$s['day_of_week']] ?? $s['day_of_week'],
                    'start_time' => $s['start_time'],
                    'study_period_id' => $s['study_period_id']
                ];
            })->toArray();
            
            // Simple comparison
            if (count($oldSchedules) !== count($newSchedules)) {
                $schedulesChanged = true;
            } else {
                foreach ($oldSchedules as $index => $oldSchedule) {
                    if (!isset($newSchedules[$index]) || 
                        $oldSchedule['day_of_week'] !== $newSchedules[$index]['day_of_week'] ||
                        $oldSchedule['start_time'] !== $newSchedules[$index]['start_time'] ||
                        $oldSchedule['study_period_id'] !== $newSchedules[$index]['study_period_id']) {
                        $schedulesChanged = true;
                        break;
                    }
                }
            }
            
            // Update class basic info
            $updateData = $request->except('schedules');

            // Auto-generate code if not provided
            if (empty($updateData['code'])) {
                $updateData['code'] = 'CLS-' . strtoupper(substr(md5(uniqid()), 0, 8));
            }

            // Auto-generate academic_year if not provided
            if (empty($updateData['academic_year'])) {
                $updateData['academic_year'] = date('Y');
            }

            // Check if homeroom teacher changed
            $oldHomeroomTeacherId = $class->homeroom_teacher_id;
            $newHomeroomTeacherId = $request->homeroom_teacher_id;
            $homeroomTeacherChanged = ($oldHomeroomTeacherId != $newHomeroomTeacherId);

            $class->update($updateData);

            // Update future sessions when homeroom teacher changes
            // Only update sessions that don't have a specific teacher_id override
            if ($homeroomTeacherChanged) {
                Log::info('[ClassManagement] Homeroom teacher changed, syncing sessions', [
                    'class_id' => $class->id,
                    'old_teacher_id' => $oldHomeroomTeacherId,
                    'new_teacher_id' => $newHomeroomTeacherId,
                ]);

                // Note: We DON'T set teacher_id on sessions
                // Sessions will automatically use getEffectiveTeacher() which checks:
                // 1. session.teacher_id (if set) 2. classSchedule.teacher 3. class.homeroomTeacher
                // So changing homeroom teacher will automatically affect sessions without teacher_id
                // We just need to trigger calendar sync by touching the sessions
                $futureSessions = $class->lessonSessions()
                    ->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', 'scheduled')
                    ->whereNull('teacher_id') // Only sessions without specific teacher override
                    ->get();

                foreach ($futureSessions as $session) {
                    // Touch to trigger calendar sync via model hooks
                    $session->touch();
                }

                Log::info('[ClassManagement] Updated sessions for homeroom teacher change', [
                    'class_id' => $class->id,
                    'updated_count' => $futureSessions->count(),
                ]);
            }
            
            // Update schedules if provided
            if ($request->has('schedules') && is_array($request->schedules)) {
                $dayMap = [
                    '2' => 'monday', '3' => 'tuesday', '4' => 'wednesday', '5' => 'thursday',
                    '6' => 'friday', '7' => 'saturday', '8' => 'sunday'
                ];
                
                $existingSchedules = $class->schedules->keyBy('day_of_week');
                $processedDays = [];
                
                foreach ($request->schedules as $schedule) {
                    $dayOfWeek = $dayMap[$schedule['day_of_week']] ?? $schedule['day_of_week'];
                    $processedDays[] = $dayOfWeek;
                    
                    $scheduleData = [
                        'subject_id' => $request->subject_id,
                        'day_of_week' => $dayOfWeek,
                        'study_period_id' => $schedule['study_period_id'],
                        'lesson_number' => 1,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'] ?? $schedule['start_time'],
                        'teacher_id' => $schedule['teacher_id'],
                        'room_id' => $schedule['room_id'] ?? null,
                    ];
                    
                    // Update existing schedule or create new one
                    if (isset($existingSchedules[$dayOfWeek])) {
                        $existingSchedules[$dayOfWeek]->update($scheduleData);
                    } else {
                        $class->schedules()->create($scheduleData);
                    }
                }
                
                // Delete schedules for days that are no longer in the request
                $class->schedules()
                    ->whereNotIn('day_of_week', $processedDays)
                    ->delete();
                
                // Reload schedules to get fresh data with relationships
                $class->load('schedules.teacher', 'schedules.room', 'schedules.subject');
            }
            
            // Handle lesson sessions when start_date or schedules changed
            if ($class->lessonSessions()->count() > 0) {
                if ($startDateChanged) {
                    // Check if there are sessions with attendance
                    $sessionsWithAttendance = $class->lessonSessions()
                        ->whereHas('attendances')
                        ->count();
                    
                    if ($sessionsWithAttendance > 0) {
                        // Cannot regenerate from new start_date if there are attended sessions
                        // Just log warning and skip regeneration
                        Log::warning('[ClassManagement] Cannot regenerate from new start_date: sessions with attendance exist', [
                            'class_id' => $class->id,
                            'old_start_date' => $oldStartDate,
                            'new_start_date' => $newStartDate,
                            'sessions_with_attendance' => $sessionsWithAttendance,
                        ]);
                        
                        // Only update schedules for existing sessions
                        if ($schedulesChanged) {
                            $this->updateLessonSessionsSchedules($class);
                        }
                    } else {
                        // No attended sessions, safe to delete and regenerate
                        Log::info('[ClassManagement] Start date changed, regenerating sessions', [
                            'class_id' => $class->id,
                            'old_start_date' => $oldStartDate,
                            'new_start_date' => $newStartDate,
                        ]);
                        
                        // Delete sessions without attendance
                        $deletedCount = $class->lessonSessions()
                            ->whereDoesntHave('attendances')
                            ->delete();
                        
                        // Delete orphan calendar events
                        DB::table('calendar_events')
                            ->where('eventable_type', 'App\\Models\\ClassLessonSession')
                            ->whereNotIn('eventable_id', function($query) use ($class) {
                                $query->select('id')
                                    ->from('class_lesson_sessions')
                                    ->where('class_id', $class->id);
                            })
                            ->delete();
                        
                        Log::info('[ClassManagement] Deleted sessions without attendance', [
                            'class_id' => $class->id,
                            'deleted_count' => $deletedCount,
                        ]);
                        
                        // Recalculate completed_sessions count after deletion
                        $actualCompleted = $class->lessonSessions()
                            ->where('status', 'completed')
                            ->count();
                        $class->update(['completed_sessions' => $actualCompleted]);
                        
                        // Regenerate sessions from new start_date
                        $class->refresh();
                        $class->load('schedules');
                        $this->regenerateLessonSessions($class);
                    }
                    
                } elseif ($schedulesChanged) {
                    // If only schedules changed (not start_date), update existing sessions
                    $this->updateLessonSessionsSchedules($class);
                }
            }
            
            // Map existing sessions to schedules if they don't have class_schedule_id yet
            if ($request->has('schedules') && $class->lessonSessions()->count() > 0) {
                $orphanedCount = $class->lessonSessions()->whereNull('class_schedule_id')->whereDoesntHave('attendances')->count();
                if ($orphanedCount > 0) {
                    Log::info('[ClassManagement] Found orphaned sessions, mapping...', [
                        'class_id' => $class->id,
                        'orphaned_count' => $orphanedCount,
                    ]);
                    $this->mapSessionsToSchedules($class);
                }
            }
            
            // Check if lesson_plan_id changed or was newly assigned
            $oldLessonPlanId = $class->getOriginal('lesson_plan_id');
            $newLessonPlanId = $request->lesson_plan_id;
            $lessonPlanChanged = ($oldLessonPlanId != $newLessonPlanId);
            
            // Auto-create or sync lesson sessions from syllabus if lesson_plan_id is provided
            if ($newLessonPlanId) {
                // Reload class to get fresh data after update
                $class->refresh();
                $hasExistingSessions = $class->lessonSessions()->count() > 0;
                
                $lessonPlan = LessonPlan::with('sessions')->find($newLessonPlanId);
                if ($lessonPlan && $lessonPlan->sessions) {
                    // Reload schedules to ensure we have fresh data with relationships
                    $class->load('schedules.teacher', 'schedules.room', 'schedules.subject');
                    $schedules = $class->schedules;
                    
                    // Check if we have schedules and start_date
                    if ($schedules->isEmpty()) {
                        Log::warning('[ClassManagement] Cannot create/sync sessions: No schedules defined', [
                            'class_id' => $class->id,
                        ]);
                    } elseif (!$class->start_date) {
                        Log::warning('[ClassManagement] Cannot create/sync sessions: No start_date', [
                            'class_id' => $class->id,
                        ]);
                    } else {
                        if (!$hasExistingSessions) {
                            // Create new sessions from syllabus (similar to store method)
                            $this->createSessionsFromSyllabus($class, $lessonPlan, $schedules);
                        } else {
                            // Sync existing sessions with syllabus content
                            $this->syncExistingSessionsWithSyllabus($class, $lessonPlan);
                        }
                    }
                }
            }
            
            DB::commit();
            
            // Reload class with all relationships including schedules with teacher
            $class->refresh();
            $class->load([
                'homeroomTeacher:id,name,email', 
                'subject:id,name', 
                'semester:id,name', 
                'lessonPlan:id,name', 
                'schedules.teacher:id,name,email',
                'schedules.room:id,name',
                'schedules.subject:id,name',
                'schedules.studyPeriod:id,name,duration_minutes'
            ]);
            
            // Ensure schedules are properly loaded with relationships
            $class->schedules->each(function($schedule) {
                $schedule->load(['teacher:id,name,email', 'room:id,name', 'subject:id,name']);
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật lớp học thành công',
                'data' => $class,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật lớp học: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update lesson sessions schedules (time/day) without deleting
     * Only updates sessions that haven't been attended
     */
    private function updateLessonSessionsSchedules($class)
    {
        $schedules = $class->schedules;
        if ($schedules->isEmpty()) {
            return;
        }
        
        // Map day names to Carbon day numbers
        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];
        
        // Get scheduled days and create a map
        $scheduledDays = $schedules->mapWithKeys(function($schedule) use ($dayMap) {
            return [$dayMap[$schedule->day_of_week] => $schedule];
        });
        
        // Get sessions without attendance
        $sessions = $class->lessonSessions()
            ->whereDoesntHave('attendances')
            ->orderBy('session_number')
            ->get();
        
        if ($sessions->isEmpty()) {
            return;
        }
        
        // Start from the first unattended session's scheduled date or class start date
        $firstSession = $sessions->first();
        $currentDate = \Carbon\Carbon::parse($firstSession->scheduled_date);
        
        // Recalculate dates for unattended sessions
        foreach ($sessions as $session) {
            // Find next valid day based on new schedules
            $found = false;
            $attempts = 0;
            
            while (!$found && $attempts < 365) {
                $currentDay = $currentDate->dayOfWeek;
                
                if (isset($scheduledDays[$currentDay])) {
                    $daySchedule = $scheduledDays[$currentDay];
                    
                    // Update session with new schedule info
                    $session->update([
                        'class_schedule_id' => $daySchedule->id,
                        'scheduled_date' => $currentDate->format('Y-m-d'),
                        'start_time' => $daySchedule->start_time,
                        'end_time' => $daySchedule->end_time,
                    ]);
                    
                    $found = true;
                    $currentDate->addDay(); // Move to next day for next session
                } else {
                    $currentDate->addDay();
                }
                
                $attempts++;
            }
        }
    }
    
    /**
     * Regenerate lesson sessions after schedule change
     */
    private function regenerateLessonSessions($class)
    {
        $schedules = $class->schedules;
        if ($schedules->isEmpty()) {
            return;
        }
        
        // Get last session by scheduled_date (regardless of attendance status)
        $lastSession = $class->lessonSessions()
            ->where('session_number', '>', 0)
            ->orderBy('scheduled_date', 'desc')
            ->first();
        
        $startDate = $lastSession 
            ? \Carbon\Carbon::parse($lastSession->scheduled_date)->addDay() 
            : \Carbon\Carbon::parse($class->start_date);
        
        // Get current session count (KHÔNG tính buổi đã hủy)
        $currentSessionCount = $class->lessonSessions()
            ->where('status', '!=', 'cancelled')
            ->count();
        $remainingSessions = $class->total_sessions - $currentSessionCount;
        
        if ($remainingSessions <= 0) {
            return; // Already completed
        }
        
        // Load lesson plan sessions if exists
        $lessonPlanSessions = [];
        if ($class->lesson_plan_id) {
            $lessonPlanSessions = \App\Models\LessonPlanSession::where('lesson_plan_id', $class->lesson_plan_id)
                ->orderBy('session_number')
                ->get()
                ->keyBy('session_number');
        }
        
        // Map day names to Carbon day numbers
        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];
        
        // Get scheduled days
        $scheduledDays = $schedules->map(fn($s) => $dayMap[$s->day_of_week])->toArray();
        
        $currentDate = $startDate;
        $sessionCount = 0;
        
        // 🔥 FIX: Tìm session_number THIẾU đầu tiên (fill gaps)
        $existingSessionNumbers = $class->lessonSessions()
            ->where('session_number', '>', 0)
            ->pluck('session_number')
            ->toArray();
        sort($existingSessionNumbers);

        // Tìm số buổi thiếu đầu tiên trong chuỗi
        $nextMissingSessionNumber = 1;
        foreach ($existingSessionNumbers as $num) {
            if ($num === $nextMissingSessionNumber) {
                $nextMissingSessionNumber++;
            } else {
                break; // Tìm thấy gap, dừng lại
            }
        }
        $sessionNumber = $nextMissingSessionNumber;
        
        // Generate remaining sessions
        while ($sessionCount < $remainingSessions) {
            $currentDay = $currentDate->dayOfWeek;
            
            if (in_array($currentDay, $scheduledDays)) {
                // Find schedule for this day
                $dayName = array_search($currentDay, $dayMap);
                $daySchedule = $schedules->where('day_of_week', $dayName)->first();
                
                if ($daySchedule) {
                    // 🔥 VALIDATION: Đảm bảo không tạo quá số buổi (double check)
                    $currentValidCount = $class->lessonSessions()
                        ->where('status', '!=', 'cancelled')
                        ->count();
                    
                    if ($currentValidCount >= $class->total_sessions) {
                        Log::warning('[ClassManagement] Reached total_sessions limit, stopping generation', [
                            'class_id' => $class->id,
                            'current_valid' => $currentValidCount,
                            'total_sessions' => $class->total_sessions,
                        ]);
                        break; // Dừng tạo sessions
                    }
                    
                    // Get corresponding lesson plan session if exists
                    $lessonPlanSession = $lessonPlanSessions[$sessionNumber] ?? null;
                    
                    \App\Models\ClassLessonSession::create([
                        'class_id' => $class->id,
                        'lesson_plan_id' => $class->lesson_plan_id,
                        'class_schedule_id' => $daySchedule->id,
                        'lesson_plan_session_id' => $lessonPlanSession?->id,
                        'session_number' => $sessionNumber,
                        'scheduled_date' => $currentDate->format('Y-m-d'),
                        'start_time' => $daySchedule->start_time,
                        'end_time' => $daySchedule->end_time,
                        'status' => 'scheduled',
                        // Copy content from lesson plan if available
                        'lesson_title' => $lessonPlanSession?->lesson_title,
                        'lesson_objectives' => $lessonPlanSession?->lesson_objectives,
                        'lesson_content' => $lessonPlanSession?->lesson_content,
                        'lesson_plan_url' => $lessonPlanSession?->lesson_plan_url,
                        'materials_url' => $lessonPlanSession?->materials_url,
                        'homework_url' => $lessonPlanSession?->homework_url,
                        'duration_minutes' => $lessonPlanSession?->duration_minutes,
                    ]);
                    
                    $sessionCount++;
                    $sessionNumber++;
                }
            }
            
            $currentDate->addDay();
            
            // Safety limit
            if ($currentDate->diffInDays($startDate) > 365) {
                Log::warning('[ClassManagement] Safety limit reached (365 days), stopping generation', [
                    'class_id' => $class->id,
                ]);
                break;
            }
        }
        
        // After generating, renumber all sessions by scheduled_date to fix any ordering issues
        $this->renumberSessionsByDate($class);
    }
    
    /**
     * Renumber all sessions by scheduled_date to ensure correct ordering
     */
    private function renumberSessionsByDate($class)
    {
        $sessions = $class->lessonSessions()
            ->where('session_number', '>', 0)
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();
        
        $newNumber = 1;
        foreach ($sessions as $session) {
            if ($session->session_number != $newNumber) {
                $session->session_number = $newNumber;
                $session->save();
                
                // Update calendar event title
                DB::table('calendar_events')
                    ->where('eventable_type', 'App\\Models\\ClassLessonSession')
                    ->where('eventable_id', $session->id)
                    ->update([
                        'title' => "{$class->name} - Buổi {$newNumber}: {$session->lesson_title}"
                    ]);
            }
            $newNumber++;
        }
        
        Log::info('[ClassManagement] Renumbered sessions by date', [
            'class_id' => $class->id,
            'total_sessions' => $sessions->count(),
        ]);
    }
    
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $class = ClassModel::findOrFail($id);
            
            // Check if class has students enrolled
            $studentCount = $class->students()->count();
            if ($studentCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa lớp học đang có {$studentCount} học viên. Vui lòng chuyển học viên sang lớp khác trước."
                ], 422);
            }
            
            // Delete related records (cascade)
            $class->schedules()->delete();
            $class->lessonSessions()->delete();
            
            // Delete the class
            $class->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa lớp học và các dữ liệu liên quan thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa lớp học: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // ==========================================
    // CLASS SCHEDULES
    // ==========================================
    
    public function getSchedules(Request $request, $classId)
    {
        $class = ClassModel::findOrFail($classId);
        
        $schedules = $class->schedules()
            ->with(['teacher', 'subject', 'room', 'studyPeriod'])
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('study_period_id')
            ->orderBy('lesson_number')
            ->get();
        
        // Group by day of week
        $groupedSchedules = $schedules->groupBy('day_of_week');
        
        return response()->json([
            'success' => true,
            'data' => $groupedSchedules
        ]);
    }
    
    public function createSchedule(Request $request, $classId)
    {
        $class = ClassModel::findOrFail($classId);
        
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'study_period_id' => 'required|exists:study_periods,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'lesson_number' => 'required|integer|min:1',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check for teacher conflict (unless they are homeroom teacher)
        $teacherConflict = ClassSchedule::hasTeacherConflict(
            $request->teacher_id,
            $request->day_of_week,
            $request->study_period_id,
            $request->lesson_number
        );
        
        if ($teacherConflict && $class->homeroom_teacher_id !== $request->teacher_id) {
            return response()->json([
                'success' => false,
                'message' => 'Giáo viên đã có lịch dạy vào thời gian này',
                'conflict' => true
            ], 422);
        }
        
        // Check for room conflict if room provided
        if ($request->room_id) {
            $room = \App\Models\Room::find($request->room_id);
            if (!$room->isAvailableAt($request->day_of_week, $request->study_period_id, $request->lesson_number)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phòng học đã được sử dụng vào thời gian này',
                    'conflict' => true
                ], 422);
            }
        }
        
        $schedule = ClassSchedule::create(array_merge($request->all(), [
            'class_id' => $classId
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo lịch học thành công',
            'data' => $schedule->load(['teacher', 'subject', 'room', 'studyPeriod'])
        ], 201);
    }
    
    public function updateSchedule(Request $request, $classId, $scheduleId)
    {
        $class = ClassModel::findOrFail($classId);
        $schedule = ClassSchedule::where('class_id', $classId)->findOrFail($scheduleId);
        
        // Check permission: Only homeroom teacher or assigned teacher can update
        $user = auth()->user();
        $canUpdate = $class->homeroom_teacher_id === $user->id || 
                     $schedule->teacher_id === $user->id ||
                     $user->hasPermission('classes.manage');
        
        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật lịch học này'
            ], 403);
        }
        
        // If not homeroom teacher, can only update notes
        if ($class->homeroom_teacher_id !== $user->id && !$user->hasPermission('classes.manage')) {
            $schedule->update(['notes' => $request->notes]);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật ghi chú thành công',
                'data' => $schedule
            ]);
        }
        
        // Validate only the fields being updated
        $validator = Validator::make($request->all(), [
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'study_period_id' => 'nullable|exists:study_periods,id',
            'lesson_number' => 'nullable|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check for teacher conflict only if teacher_id is being updated
        if ($request->has('teacher_id') && $request->has('day_of_week') && $request->has('study_period_id') && $request->has('lesson_number')) {
            $teacherConflict = ClassSchedule::hasTeacherConflict(
                $request->teacher_id,
                $request->day_of_week,
                $request->study_period_id,
                $request->lesson_number,
                $scheduleId
            );
            
            if ($teacherConflict && $class->homeroom_teacher_id !== $request->teacher_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giáo viên đã có lịch dạy vào thời gian này',
                    'conflict' => true
                ], 422);
            }
        }
        
        // Track if critical fields changed (to update lesson sessions)
        $criticalFieldsChanged = false;
        $oldScheduleData = [
            'teacher_id' => $schedule->teacher_id,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
        ];
        
        // Only update fields that are provided
        $updateData = $request->only(['day_of_week', 'start_time', 'end_time', 'subject_id', 'teacher_id', 'room_id', 'study_period_id', 'lesson_number']);
        $schedule->update($updateData);
        
        // Check if critical fields changed
        if ($request->has('teacher_id') && $oldScheduleData['teacher_id'] != $schedule->teacher_id) {
            $criticalFieldsChanged = true;
        }
        if ($request->has('start_time') && $oldScheduleData['start_time'] != $schedule->start_time) {
            $criticalFieldsChanged = true;
        }
        if ($request->has('end_time') && $oldScheduleData['end_time'] != $schedule->end_time) {
            $criticalFieldsChanged = true;
        }
        
        // If critical fields changed, update related lesson sessions (only those without attendance)
        if ($criticalFieldsChanged) {
            $this->updateLessonSessionsFromScheduleChange($schedule);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật lịch học thành công',
            'data' => $schedule->load(['teacher', 'subject', 'room', 'studyPeriod'])
        ]);
    }
    
    /**
     * Update lesson sessions when schedule changes
     * Only updates sessions without attendance
     */
    private function updateLessonSessionsFromScheduleChange($schedule)
    {
        try {
            // Get all lesson sessions using this schedule (without attendance)
            $sessions = ClassLessonSession::where('class_schedule_id', $schedule->id)
                ->whereDoesntHave('attendances')
                ->get();
            
            if ($sessions->isEmpty()) {
                return;
            }
            
            Log::info('[ClassManagement] Updating lesson sessions after schedule change', [
                'schedule_id' => $schedule->id,
                'session_count' => $sessions->count(),
            ]);
            
            // Update each session
            foreach ($sessions as $session) {
                $session->update([
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ]);

                // Force touch to ensure calendar sync even if only teacher changed
                // This triggers updated() event which syncs getEffectiveTeacher() to calendar
                $session->touch();
            }
            
            Log::info('[ClassManagement] Updated lesson sessions successfully', [
                'schedule_id' => $schedule->id,
                'updated_count' => $sessions->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Error updating lesson sessions', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Map existing sessions to schedules based on day of week
     * This is used when schedules are added to a class that already has sessions
     */
    private function mapSessionsToSchedules($class)
    {
        try {
            // Get all sessions without class_schedule_id (orphaned sessions)
            $orphanedSessions = ClassLessonSession::where('class_id', $class->id)
                ->whereNull('class_schedule_id')
                ->whereDoesntHave('attendances')
                ->orderBy('scheduled_date')
                ->get();
            
            if ($orphanedSessions->isEmpty()) {
                Log::info('[ClassManagement] No orphaned sessions to map', [
                    'class_id' => $class->id,
                ]);
                return;
            }
            
            Log::info('[ClassManagement] Mapping orphaned sessions to schedules', [
                'class_id' => $class->id,
                'session_count' => $orphanedSessions->count(),
            ]);
            
            // Load schedules with relationships
            $schedules = $class->schedules()->with('teacher', 'room')->get();
            
            if ($schedules->isEmpty()) {
                Log::warning('[ClassManagement] No schedules available to map', [
                    'class_id' => $class->id,
                ]);
                return;
            }
            
            // Map day names to Carbon day numbers
            $dayMap = [
                'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                'thursday' => 4, 'friday' => 5, 'saturday' => 6
            ];
            
            // Create a map of schedules by day of week
            $schedulesByDay = $schedules->keyBy(function($schedule) use ($dayMap) {
                return $dayMap[$schedule->day_of_week] ?? null;
            });
            
            $mappedCount = 0;
            
            // Map each orphaned session to appropriate schedule
            foreach ($orphanedSessions as $session) {
                if (!$session->scheduled_date) {
                    continue;
                }
                
                $sessionDate = \Carbon\Carbon::parse($session->scheduled_date);
                $dayOfWeek = $sessionDate->dayOfWeek;
                
                // Find matching schedule for this day
                if (isset($schedulesByDay[$dayOfWeek])) {
                    $schedule = $schedulesByDay[$dayOfWeek];
                    
                    // Update session with schedule info
                    $session->update([
                        'class_schedule_id' => $schedule->id,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ]);
                    
                    $mappedCount++;
                    
                    // The update() will trigger the ClassLessonSession::updated() hook
                    // which automatically syncs to calendar with teacher info
                }
            }
            
            Log::info('[ClassManagement] Successfully mapped sessions to schedules', [
                'class_id' => $class->id,
                'mapped_count' => $mappedCount,
                'total_orphaned' => $orphanedSessions->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Error mapping sessions to schedules', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function deleteSchedule($classId, $scheduleId)
    {
        $schedule = ClassSchedule::where('class_id', $classId)->findOrFail($scheduleId);
        $schedule->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lịch học thành công'
        ]);
    }
    
    // ==========================================
    // LESSON SESSIONS
    // ==========================================
    
    public function generateLessonSessions(Request $request, $classId)
    {
        $class = ClassModel::with(['semester', 'lessonPlan.sessions', 'schedules'])->findOrFail($classId);
        
        if (!$class->lesson_plan_id) {
            return response()->json([
                'success' => false,
                'message' => 'Lớp học chưa có giáo án'
            ], 422);
        }
        
        if ($class->schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Lớp học chưa có lịch học'
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $lessonPlanSessions = $class->lessonPlan->sessions;
            $schedules = $class->schedules;
            $semester = $class->semester;
            
            // Get holidays in semester
            $holidays = Holiday::getHolidaysInRange(
                $class->branch_id,
                $semester->start_date,
                $semester->end_date
            );
            
            $currentDate = Carbon::parse($semester->start_date);
            $endDate = Carbon::parse($semester->end_date);
            $sessionIndex = 0;
            $generatedSessions = [];
            
            while ($currentDate <= $endDate && $sessionIndex < $lessonPlanSessions->count()) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                
                // Check if it's a holiday
                $isHoliday = false;
                foreach ($holidays as $holiday) {
                    if ($holiday->includesDate($currentDate)) {
                        $isHoliday = true;
                        break;
                    }
                }
                
                if ($isHoliday) {
                    $currentDate->addDay();
                    continue;
                }
                
                // Find schedule for this day
                $daySchedule = $schedules->where('day_of_week', $dayOfWeek)->first();
                
                if ($daySchedule) {
                    $lessonPlanSession = $lessonPlanSessions[$sessionIndex];
                    
                    $session = ClassLessonSession::create([
                        'class_id' => $classId,
                        'lesson_plan_id' => $class->lesson_plan_id,
                        'lesson_plan_session_id' => $lessonPlanSession->id,
                        'class_schedule_id' => $daySchedule->id,
                        'scheduled_date' => $currentDate->toDateString(),
                        'start_time' => $daySchedule->start_time,
                        'end_time' => $daySchedule->end_time,
                        'status' => 'scheduled'
                    ]);
                    
                    $generatedSessions[] = $session;
                    $sessionIndex++;
                }
                
                $currentDate->addDay();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo lịch buổi học thành công',
                'data' => [
                    'total_generated' => count($generatedSessions),
                    'sessions' => $generatedSessions
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo lịch buổi học: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getLessonSessions(Request $request, $classId)
    {
        $status = $request->input('status');
        $month = $request->input('month');
        
        $query = ClassLessonSession::where('class_id', $classId)
            ->with([
                'lessonPlanSession',
                'classSchedule.teacher',
                'classSchedule.subject',
                'teacher' // Load session-specific teacher
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($month) {
            $query->whereMonth('scheduled_date', $month);
        }

        $sessions = $query->orderBy('scheduled_date')->get();
        
        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get detail of a specific lesson session
     */
    public function getSessionDetail($classId, $sessionId)
    {
        $session = ClassLessonSession::where('class_id', $classId)
            ->with([
                'class.subject.activeTeachers',
                'teacher',
                'classSchedule.teacher',
                'classSchedule.subject.activeTeachers',
                'lessonPlanSession',
            ])
            ->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    public function updateLessonSession(Request $request, $classId, $sessionId)
    {
        $session = ClassLessonSession::where('class_id', $classId)->findOrFail($sessionId);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,completed,cancelled,rescheduled,holiday',
            'actual_date' => 'nullable|date',
            'cancellation_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $session->update($request->all());
        
        // Update completed sessions count
        if ($request->status === 'completed') {
            $class = ClassModel::find($classId);
            $completedCount = ClassLessonSession::where('class_id', $classId)
                ->where('status', 'completed')
                ->count();
            $class->update(['completed_sessions' => $completedCount]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật buổi học thành công',
            'data' => $session
        ]);
    }

    /**
     * Sync lesson sessions from syllabus
     * Only updates sessions without attendance records
     */
    public function syncFromSyllabus($classId)
    {
        $class = ClassModel::with('lessonPlan.sessions')->findOrFail($classId);
        
        if (!$class->lesson_plan_id) {
            return response()->json([
                'success' => false,
                'message' => 'Lớp học chưa được gán giáo án'
            ], 400);
        }

        $syllabusSessions = $class->lessonPlan->sessions()->orderBy('session_number')->get();
        
        if ($syllabusSessions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Giáo án chưa có buổi học nào'
            ], 400);
        }

        // Get all class sessions
        $classSessions = ClassLessonSession::where('class_id', $classId)
            ->orderBy('session_number')
            ->get();

        // If no sessions exist, create them from syllabus
        if ($classSessions->isEmpty()) {
            // Reload schedules to ensure we have fresh data
            $class->load('schedules');
            $schedules = $class->schedules;
            
            if ($schedules->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lớp học chưa có lịch học. Vui lòng cập nhật lịch học trước.'
                ], 400);
            }
            
            if (!$class->start_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lớp học chưa có ngày bắt đầu. Vui lòng cập nhật ngày bắt đầu trước.'
                ], 400);
            }
            
            // Create sessions from syllabus
            $this->createSessionsFromSyllabus($class, $class->lessonPlan, $schedules);
            
            // Reload sessions after creation
            $classSessions = ClassLessonSession::where('class_id', $classId)
                ->orderBy('session_number')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => "Đã tạo {$classSessions->count()} buổi học từ giáo án.",
                'data' => [
                    'created' => $classSessions->count(),
                    'updated' => 0,
                    'skipped' => 0,
                    'total' => $classSessions->count()
                ]
            ]);
        }

        // If sessions exist, sync them with syllabus content
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($classSessions as $classSession) {
            // Check if session has attendance records
            $hasAttendance = DB::table('attendances')
                ->where('session_id', $classSession->id)
                ->exists();

            if ($hasAttendance) {
                $skippedCount++;
                continue; // Skip sessions with attendance
            }

            // Find matching syllabus session by session number
            $syllabusSession = $syllabusSessions->firstWhere('session_number', $classSession->session_number);

            if ($syllabusSession) {
                $classSession->update([
                    'lesson_title' => $syllabusSession->lesson_title,
                    'lesson_objectives' => $syllabusSession->lesson_objectives,
                    'lesson_content' => $syllabusSession->lesson_content,
                    'lesson_plan_url' => $syllabusSession->lesson_plan_url,
                    'materials_url' => $syllabusSession->materials_url,
                    'homework_url' => $syllabusSession->homework_url,
                    'duration_minutes' => $syllabusSession->duration_minutes,
                    'valuation_form_id' => $syllabusSession->valuation_form_id,
                    // Sync folder IDs from lesson plan session
                    'lesson_plans_folder_id' => $syllabusSession->lesson_plans_folder_id,
                    'materials_folder_id' => $syllabusSession->materials_folder_id,
                    'homework_folder_id' => $syllabusSession->homework_folder_id,
                ]);
                $updatedCount++;
            }
        }
        
        // After syncing, regenerate missing sessions if any
        $currentValidCount = $class->lessonSessions()
            ->where('session_number', '>', 0)
            ->count();
        
        $createdCount = 0;
        if ($currentValidCount < $class->total_sessions) {
            Log::info('[ClassManagement] Regenerating missing sessions after sync', [
                'class_id' => $class->id,
                'current_count' => $currentValidCount,
                'total_sessions' => $class->total_sessions,
                'missing' => $class->total_sessions - $currentValidCount,
            ]);
            
            $class->load('schedules');
            $this->regenerateLessonSessions($class);
            
            $createdCount = $class->lessonSessions()
                ->where('session_number', '>', 0)
                ->count() - $currentValidCount;
        } else {
            // Even if no missing sessions, renumber to fix any ordering issues
            $this->renumberSessionsByDate($class);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$updatedCount} buổi học từ giáo án. Bỏ qua {$skippedCount} buổi đã điểm danh." . ($createdCount > 0 ? " Đã tạo thêm {$createdCount} buổi thiếu." : ""),
            'data' => [
                'created' => $createdCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'total' => $class->lessonSessions()->where('session_number', '>', 0)->count()
            ]
        ]);
    }

    /**
     * Đồng bộ lại toàn bộ lịch học của lớp lên calendar
     * (Dùng khi có lỗi hoặc cần rebuild)
     */
    public function syncClassToCalendar($classId)
    {
        $class = ClassModel::with('lessonSessions.classSchedule.teacher')->findOrFail($classId);
        
        // Check permission
        // $this->authorize('update', $class);
        
        // First, try to map orphaned sessions if any
        if ($class->schedules()->count() > 0) {
            $this->mapSessionsToSchedules($class);
        }
        
        $calendarService = app(\App\Services\CalendarEventService::class);
        $synced = 0;
        $skipped = 0;
        
        // Reload sessions after mapping
        $class->load('lessonSessions.classSchedule.teacher');
        
        foreach ($class->lessonSessions as $session) {
            try {
                // Reload session with fresh relationships
                $session->refresh();
                $session->load('class.homeroomTeacher', 'classSchedule.teacher');
                
                $calendarService->syncClassSessionToCalendar($session);
                $synced++;
            } catch (\Exception $e) {
                \Log::error("Failed to sync session {$session->id} to calendar: " . $e->getMessage());
                $skipped++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Đã đồng bộ {$synced} buổi học lên calendar. Bỏ qua {$skipped} buổi lỗi.",
            'data' => [
                'synced' => $synced,
                'skipped' => $skipped,
                'total' => $class->lessonSessions->count()
            ]
        ]);
    }


    /**
     * Sync folder IDs from lesson plan sessions to class lesson sessions
     */
    public function syncFolderIds($classId)
    {
        try {
            $class = ClassModel::with('lessonPlan.sessions')->findOrFail($classId);
            
            if (!$class->lesson_plan_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lớp học chưa được gán giáo án'
                ], 400);
            }

            $syllabusSessions = $class->lessonPlan->sessions()->orderBy('session_number')->get();
            $classSessions = ClassLessonSession::where('class_id', $classId)
                ->orderBy('session_number')
                ->get();

            $syncedCount = 0;

            foreach ($classSessions as $classSession) {
                // Find matching syllabus session by session number
                $syllabusSession = $syllabusSessions->firstWhere('session_number', $classSession->session_number);

                if ($syllabusSession) {
                    $classSession->update([
                        'lesson_plans_folder_id' => $syllabusSession->lesson_plans_folder_id,
                        'materials_folder_id' => $syllabusSession->materials_folder_id,
                        'homework_folder_id' => $syllabusSession->homework_folder_id,
                    ]);
                    $syncedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã đồng bộ folder IDs cho {$syncedCount} buổi học",
                'data' => [
                    'synced' => $syncedCount,
                    'total' => $classSessions->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[Class Google Drive] Error syncing folder IDs', [
                'error' => $e->getMessage(),
                'class_id' => $classId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể đồng bộ folder IDs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create lesson sessions from syllabus (used in store and update)
     */
    private function createSessionsFromSyllabus($class, $lessonPlan, $schedules)
    {
        // Calculate scheduled dates based on class schedules
        $startDate = \Carbon\Carbon::parse($class->start_date);
        
        // Map day names to Carbon day numbers
        $dayMap = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];
        
        $scheduledDays = $schedules->map(fn($s) => $dayMap[$s->day_of_week] ?? null)->filter()->unique()->toArray();
        
        // Safety check: if no scheduled days, skip session creation
        if (empty($scheduledDays)) {
            Log::warning('[ClassManagement] No valid scheduled days found', [
                'class_id' => $class->id,
                'schedules' => $schedules->pluck('day_of_week')->toArray(),
            ]);
            return;
        }
        
        $currentDate = $startDate->copy();
        $sessionIndex = 0;
        $maxIterations = 1000; // Safety limit for total iterations
        $iterationCount = 0;
        
        foreach ($lessonPlan->sessions as $syllabusSession) {
            // Safety check: prevent infinite loop
            if ($iterationCount >= $maxIterations) {
                Log::error('[ClassManagement] Max iterations reached while creating sessions', [
                    'class_id' => $class->id,
                    'sessions_created' => $sessionIndex,
                    'total_sessions' => $lessonPlan->sessions->count(),
                ]);
                break;
            }
            
            // Find next scheduled date with safety limit
            $daySearchCount = 0;
            while (!in_array($currentDate->dayOfWeek, $scheduledDays)) {
                $currentDate->addDay();
                $daySearchCount++;
                $iterationCount++;
                
                // Safety limit: max 14 days search (2 weeks)
                if ($daySearchCount > 14) {
                    Log::error('[ClassManagement] Cannot find scheduled day within 14 days', [
                        'class_id' => $class->id,
                        'current_date' => $currentDate->format('Y-m-d'),
                        'scheduled_days' => $scheduledDays,
                    ]);
                    break 2; // Break out of both loops
                }
                
                // Additional safety: max 365 days from start
                if ($currentDate->diffInDays($startDate) > 365) {
                    Log::error('[ClassManagement] Exceeded 365 days limit', [
                        'class_id' => $class->id,
                    ]);
                    break 2;
                }
            }
            
            // Find matching schedule for this day
            $schedule = $schedules->first(fn($s) => ($dayMap[$s->day_of_week] ?? null) === $currentDate->dayOfWeek);
            
            // COPY all content from syllabus to class session (independent)
            $class->lessonSessions()->create([
                'class_id' => $class->id,
                'lesson_plan_id' => $lessonPlan->id,
                'lesson_plan_session_id' => $syllabusSession->id,
                'class_schedule_id' => $schedule?->id,
                'session_number' => $syllabusSession->session_number,
                'scheduled_date' => $currentDate->format('Y-m-d'),
                'start_time' => $schedule?->start_time,
                'end_time' => $schedule?->end_time,
                'status' => 'scheduled',
                // COPY lesson content from syllabus
                'lesson_title' => $syllabusSession->lesson_title,
                'lesson_objectives' => $syllabusSession->lesson_objectives,
                'lesson_content' => $syllabusSession->lesson_content,
                'lesson_plan_url' => $syllabusSession->lesson_plan_url,
                'materials_url' => $syllabusSession->materials_url,
                'valuation_form_id' => $syllabusSession->valuation_form_id,
                'homework_url' => $syllabusSession->homework_url,
                'duration_minutes' => $syllabusSession->duration_minutes ?? 45,
                // Copy folder IDs directly from syllabus session
                'lesson_plans_folder_id' => $syllabusSession->lesson_plans_folder_id,
                'materials_folder_id' => $syllabusSession->materials_folder_id,
                'homework_folder_id' => $syllabusSession->homework_folder_id,
            ]);
            
            $currentDate->addDay();
            $sessionIndex++;
            $iterationCount++;
        }
        
        Log::info('[ClassManagement] Created sessions from syllabus', [
            'class_id' => $class->id,
            'sessions_created' => $sessionIndex,
            'total_sessions' => $lessonPlan->sessions->count(),
        ]);
    }

    /**
     * Sync existing class sessions with syllabus content
     */
    private function syncExistingSessionsWithSyllabus($class, $lessonPlan)
    {
        $syllabusSessions = $lessonPlan->sessions()->orderBy('session_number')->get();
        
        if ($syllabusSessions->isEmpty()) {
            Log::warning('[ClassManagement] Cannot sync: Syllabus has no sessions', [
                'class_id' => $class->id,
                'lesson_plan_id' => $lessonPlan->id,
            ]);
            return;
        }

        $updatedCount = 0;
        $skippedCount = 0;

        // Get all class sessions
        $classSessions = ClassLessonSession::where('class_id', $class->id)
            ->orderBy('session_number')
            ->get();

        foreach ($classSessions as $classSession) {
            // Check if session has attendance records
            $hasAttendance = DB::table('attendances')
                ->where('session_id', $classSession->id)
                ->exists();

            if ($hasAttendance) {
                $skippedCount++;
                continue; // Skip sessions with attendance
            }

            // Find matching syllabus session by session number
            $syllabusSession = $syllabusSessions->firstWhere('session_number', $classSession->session_number);

            if ($syllabusSession) {
                $classSession->update([
                    'lesson_plan_id' => $lessonPlan->id,
                    'lesson_plan_session_id' => $syllabusSession->id,
                    'lesson_title' => $syllabusSession->lesson_title,
                    'lesson_objectives' => $syllabusSession->lesson_objectives,
                    'lesson_content' => $syllabusSession->lesson_content,
                    'lesson_plan_url' => $syllabusSession->lesson_plan_url,
                    'materials_url' => $syllabusSession->materials_url,
                    'homework_url' => $syllabusSession->homework_url,
                    'duration_minutes' => $syllabusSession->duration_minutes,
                    'valuation_form_id' => $syllabusSession->valuation_form_id,
                    // Sync folder IDs from lesson plan session
                    'lesson_plans_folder_id' => $syllabusSession->lesson_plans_folder_id,
                    'materials_folder_id' => $syllabusSession->materials_folder_id,
                    'homework_folder_id' => $syllabusSession->homework_folder_id,
                ]);
                $updatedCount++;
            }
        }

        Log::info('[ClassManagement] Synced existing sessions with syllabus', [
            'class_id' => $class->id,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'total' => $classSessions->count(),
        ]);
        
        // After syncing, regenerate missing sessions if any
        $currentValidCount = $class->lessonSessions()
            ->where('session_number', '>', 0)
            ->count();
        
        if ($currentValidCount < $class->total_sessions) {
            Log::info('[ClassManagement] Regenerating missing sessions after sync', [
                'class_id' => $class->id,
                'current_count' => $currentValidCount,
                'total_sessions' => $class->total_sessions,
                'missing' => $class->total_sessions - $currentValidCount,
            ]);
            
            $this->regenerateLessonSessions($class);
        } else {
            // Even if no missing sessions, renumber to fix any ordering issues
            $this->renumberSessionsByDate($class);
        }
    }

    /**
     * Update Zalo group information for a class
     */
    public function updateZaloGroup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'zalo_account_id' => 'nullable|exists:zalo_accounts,id',
            'zalo_group_id' => 'nullable|string',
            'zalo_group_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $class = ClassModel::findOrFail($id);

            $class->update([
                'zalo_account_id' => $request->zalo_account_id,
                'zalo_group_id' => $request->zalo_group_id,
                'zalo_group_name' => $request->zalo_group_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zalo group updated successfully',
                'data' => $class
            ]);
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to update Zalo group', [
                'class_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Zalo group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Zalo contacts for class students and parents
     * GET /api/classes/{id}/zalo-contacts
     */
    public function getZaloContacts(Request $request, $id)
    {
        try {
            $class = ClassModel::findOrFail($id);
            $zaloAccountId = $request->input('zalo_account_id');

            if (!$zaloAccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo account ID is required',
                ], 400);
            }

            // Get all students in the class with their user data
            $students = \App\Models\Student::whereHas('classes', function ($query) use ($id) {
                $query->where('classes.id', $id);
            })->with(['user', 'parents.user'])->get();

            $contacts = [];
            $zaloAccount = \App\Models\ZaloAccount::find($zaloAccountId);

            // Process students
            foreach ($students as $student) {
                if (!$student->user || !$student->user->phone) continue;

                // Check friend status via Zalo API (like CustomerZaloChatModal)
                $isFriend = false;
                $zaloUserId = null;
                $zaloName = null;
                $avatarUrl = null;

                try {
                    $response = \Http::timeout(10)->withHeaders([
                        'X-API-Key' => config('services.zalo.api_key'),
                    ])->post(config('services.zalo.base_url') . '/api/user/search', [
                        'accountId' => $zaloAccount->id,
                        'phoneNumber' => $student->user->phone,
                    ]);

                    if ($response->successful()) {
                        $userData = $response->json('data');
                        $isFriend = $userData['isFriend'] ?? false;
                        $zaloUserId = $userData['id'] ?? null;
                        $zaloName = $userData['display_name'] ?? null;
                        $avatarUrl = $userData['avatar'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::warning('[ClassManagement] Failed to check student friend status', [
                        'phone' => $student->user->phone,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Add student to contacts
                $contacts[] = [
                    'type' => 'student',
                    'user_id' => $student->user->id,
                    'name' => $student->user->name,
                    'phone' => $student->user->phone,
                    'zalo_user_id' => $zaloUserId,
                    'zalo_name' => $zaloName,
                    'avatar_url' => $avatarUrl,
                    'relationship' => 'Học viên',
                    'student_code' => $student->student_code,
                    'has_zalo_friend' => $isFriend,
                    'needs_friend_request' => !$isFriend,
                ];

                // Process parents of this student
                foreach ($student->parents as $parent) {
                    if (!$parent->user || !$parent->user->phone) continue;

                    // Check parent friend status via Zalo API
                    $parentIsFriend = false;
                    $parentZaloUserId = null;
                    $parentZaloName = null;
                    $parentAvatarUrl = null;

                    try {
                        $parentResponse = \Http::timeout(10)->withHeaders([
                            'X-API-Key' => config('services.zalo.api_key'),
                        ])->post(config('services.zalo.base_url') . '/api/user/search', [
                            'accountId' => $zaloAccount->id,
                            'phoneNumber' => $parent->user->phone,
                        ]);

                        if ($parentResponse->successful()) {
                            $parentUserData = $parentResponse->json('data');
                            $parentIsFriend = $parentUserData['isFriend'] ?? false;
                            $parentZaloUserId = $parentUserData['id'] ?? null;
                            $parentZaloName = $parentUserData['display_name'] ?? null;
                            $parentAvatarUrl = $parentUserData['avatar'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::warning('[ClassManagement] Failed to check parent friend status', [
                            'phone' => $parent->user->phone,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $relationship = $parent->pivot->relationship ?? 'Phụ huynh';

                    $contacts[] = [
                        'type' => 'parent',
                        'user_id' => $parent->user->id,
                        'name' => $parent->user->name,
                        'phone' => $parent->user->phone,
                        'zalo_user_id' => $parentZaloUserId,
                        'zalo_name' => $parentZaloName,
                        'avatar_url' => $parentAvatarUrl,
                        'relationship' => $relationship,
                        'student_name' => $student->user->name,
                        'student_code' => $student->student_code,
                        'has_zalo_friend' => $parentIsFriend,
                        'needs_friend_request' => !$parentIsFriend,
                    ];
                }
            }

            // Remove duplicates (in case same parent has multiple children)
            // Use phone number for deduplication since zalo_user_id might be null
            $uniqueContacts = collect($contacts)->unique(function ($contact) {
                return $contact['type'] . '_' . $contact['phone'];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $uniqueContacts,
                'stats' => [
                    'total_contacts' => $uniqueContacts->count(),
                    'students' => $uniqueContacts->where('type', 'student')->count(),
                    'parents' => $uniqueContacts->where('type', 'parent')->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to get Zalo contacts', [
                'class_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get Zalo contacts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalize phone number for matching
     */
    private function normalizePhone($phone)
    {
        if (!$phone) return null;

        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Handle Vietnam country code
        if (substr($phone, 0, 2) === '84') {
            $phone = substr($phone, 2);
        }

        // Add leading zero
        return '0' . $phone;
    }

    /**
     * Change teacher for a specific session
     */
    public function changeSessionTeacher(Request $request, $classId, $sessionId)
    {
        try {
            $session = ClassLessonSession::where('class_id', $classId)
                ->with(['class', 'teacher'])
                ->findOrFail($sessionId);

            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newTeacherId = $request->teacher_id;
            $newTeacher = \App\Models\User::findOrFail($newTeacherId);

            // Get old teacher for notification
            $oldTeacher = $session->getEffectiveTeacher();

            // Update session teacher
            $session->update(['teacher_id' => $newTeacherId]);

            // Send Zalo notification to new teacher
            try {
                $teacherNotificationService = app(\App\Services\TeacherZaloNotificationService::class);
                $message = $this->formatTeacherAssignmentMessage($session->fresh(), $newTeacher);

                $account = $teacherNotificationService->getPrimaryZaloAccount();
                if ($account && $newTeacher->phone) {
                    $teacherNotificationService->sendZaloMessage(
                        $account,
                        $newTeacher->phone,
                        $message,
                        $newTeacher->id
                    );
                }
            } catch (\Exception $e) {
                Log::warning('[ClassManagement] Failed to send teacher notification', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Send Zalo notification to class group (optional)
            if ($oldTeacher && $oldTeacher->id !== $newTeacherId) {
                try {
                    $groupNotificationService = app(\App\Services\ZaloGroupNotificationService::class);
                    $groupNotificationService->sendTeacherChangeNotification(
                        $session->fresh(),
                        $oldTeacher,
                        $newTeacher
                    );
                } catch (\Exception $e) {
                    Log::warning('[ClassManagement] Failed to send group notification', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã thay đổi giáo viên thành công',
                'data' => $session->fresh()->load('teacher'),
            ]);
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to change session teacher', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change teacher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a session and optionally reschedule future sessions
     */
    public function cancelSession(Request $request, $classId, $sessionId)
    {
        try {
            $session = ClassLessonSession::where('class_id', $classId)
                ->with(['class', 'attendances'])
                ->findOrFail($sessionId);

            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string',
                'reschedule_future_sessions' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if session has attendance records
            if ($session->attendances()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy buổi học đã có điểm danh',
                ], 400);
            }

            $cancellationReason = $request->cancellation_reason;
            $rescheduleFuture = $request->input('reschedule_future_sessions', true);

            // Update session status
            // 🔥 FIX: Đổi session_number = 0 và XÓA NỘI DUNG để không gắn với syllabus
            $originalSessionNumber = $session->session_number;
            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => $cancellationReason,
                'session_number' => 0, // Không còn tính vào syllabus
                // 🔥 XÓA NỘI DUNG để không bị trùng với buổi mới
                'lesson_title' => null,
                'lesson_objectives' => null,
                'lesson_content' => null,
                'lesson_plan_url' => null,
                'materials_url' => null,
                'homework_url' => null,
                'lesson_plan_session_id' => null,
            ]);

            // Send Zalo notification to class group
            try {
                $groupNotificationService = app(\App\Services\ZaloGroupNotificationService::class);
                $groupNotificationService->sendSessionCancellationNotification(
                    $session,
                    $cancellationReason
                );
            } catch (\Exception $e) {
                Log::warning('[ClassManagement] Failed to send cancellation notification', [
                    'error' => $e->getMessage(),
                ]);
            }

            // 🔥 LOGIC MỚI: Shift tất cả buổi sau về sau, tạo buổi mới ở cuối
            // 1. Buổi hủy: session_number = 0
            // 2. Shift tất cả buổi sau về sau 1 slot
            // 3. Tạo buổi mới ở cuối
            $shiftedCount = 0;
            $newSession = null;
            if ($rescheduleFuture) {
                // Get class
                $class = $session->class;
                
                // Shift tất cả các buổi từ session_number gốc trở đi
                $shiftedCount = $this->shiftFutureSessions($originalSessionNumber, $class);
                
                // Tạo buổi mới ở cuối để đủ số
                $newSession = $this->createNewSessionAtEnd($originalSessionNumber, $class);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy buổi học thành công',
                'data' => [
                    'session' => $session->fresh(),
                    'shifted_count' => $shiftedCount,
                    'new_session' => $newSession,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to cancel session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel session: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reschedule future sessions after a cancellation
     */
    private function rescheduleFutureSessions(ClassLessonSession $cancelledSession): int
    {
        try {
            $class = $cancelledSession->class;

            // Get all future sessions after this one
            $futureSessions = ClassLessonSession::where('class_id', $class->id)
                ->where('session_number', '>', $cancelledSession->session_number)
                ->where('status', 'scheduled')
                ->whereDoesntHave('attendances') // Only reschedule sessions without attendance
                ->orderBy('session_number')
                ->get();

            if ($futureSessions->isEmpty()) {
                return 0;
            }

            // Get class schedules
            $schedules = $class->schedules()->orderBy('day_of_week')->get();

            if ($schedules->isEmpty()) {
                Log::warning('[ClassManagement] No active schedules for rescheduling', [
                    'class_id' => $class->id,
                ]);
                return 0;
            }

            // Start rescheduling from the cancelled session's date
            $currentDate = \Carbon\Carbon::parse($cancelledSession->scheduled_date);

            // Map schedules by day of week
            $schedulesByDay = [];
            foreach ($schedules as $schedule) {
                $schedulesByDay[$schedule->day_of_week] = $schedule;
            }

            $rescheduledCount = 0;

            // 🔥 FIX: Day number to name mapping
            $dayNumberToName = [
                0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
                4 => 'thursday', 5 => 'friday', 6 => 'saturday',
            ];
            
            foreach ($futureSessions as $session) {
                // Find next available date based on class schedules
                $newDate = $this->findNextScheduleDate($currentDate, $schedulesByDay, $class);

                if ($newDate) {
                    // Get the schedule for this day
                    $dayNumber = $newDate->dayOfWeek; // 0-6
                    $dayName = $dayNumberToName[$dayNumber] ?? null;
                    $schedule = $dayName ? ($schedulesByDay[$dayName] ?? null) : null;

                    if ($schedule) {
                        $session->update([
                            'scheduled_date' => $newDate->toDateString(),
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'class_schedule_id' => $schedule->id,
                        ]);

                        $currentDate = $newDate;
                        $rescheduledCount++;
                    }
                }
            }

            Log::info('[ClassManagement] Rescheduled future sessions', [
                'class_id' => $class->id,
                'cancelled_session_id' => $cancelledSession->id,
                'rescheduled_count' => $rescheduledCount,
            ]);

            return $rescheduledCount;
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to reschedule future sessions', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 🔥 NEW: Pull up - Kéo tất cả các buổi sau lên (giảm session_number đi 1)
     * Không thay đổi ngày, chỉ thay đổi session_number
     */
    private function shiftFutureSessions(int $startSessionNumber, ClassModel $class): int
    {
        try {
            // Lấy tất cả các buổi từ startSessionNumber trở đi (không tính cancelled)
            $futureSessions = ClassLessonSession::where('class_id', $class->id)
                ->where('session_number', '>', $startSessionNumber)
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('attendances')
                ->orderBy('session_number')
                ->get();
            
            if ($futureSessions->isEmpty()) {
                return 0;
            }
            
            // Load lesson plan sessions để update nội dung
            $lessonPlanSessions = [];
            if ($class->lesson_plan_id) {
                $lessonPlanSessions = \App\Models\LessonPlanSession::where('lesson_plan_id', $class->lesson_plan_id)
                    ->orderBy('session_number')
                    ->get()
                    ->keyBy('session_number');
            }
            
            $shiftedCount = 0;
            
            // PULL UP: Giảm session_number đi 1 và UPDATE NỘI DUNG
            foreach ($futureSessions as $session) {
                $oldNumber = $session->session_number;
                $newNumber = $oldNumber - 1;
                
                // Skip nếu newNumber < startSessionNumber (không nên xảy ra)
                if ($newNumber < $startSessionNumber) {
                    continue;
                }
                
                // 🔥 Lấy nội dung lesson plan cho session_number MỚI
                $lessonPlan = $lessonPlanSessions[$newNumber] ?? null;
                
                $newTitle = $lessonPlan?->lesson_title ?? "Unit {$newNumber}";
                
                $session->update([
                    'session_number' => $newNumber,
                    // 🔥 UPDATE NỘI DUNG để khớp với session_number mới
                    'lesson_title' => $newTitle,
                    'lesson_objectives' => $lessonPlan?->lesson_objectives,
                    'lesson_content' => $lessonPlan?->lesson_content,
                    'lesson_plan_url' => $lessonPlan?->lesson_plan_url,
                    'materials_url' => $lessonPlan?->materials_url,
                    'homework_url' => $lessonPlan?->homework_url,
                    'lesson_plan_session_id' => $lessonPlan?->id,
                ]);
                
                // 🔥 UPDATE CALENDAR EVENT TITLE
                \Illuminate\Support\Facades\DB::table('calendar_events')
                    ->where('eventable_type', 'App\\Models\\ClassLessonSession')
                    ->where('eventable_id', $session->id)
                    ->update([
                        'title' => "{$class->name} - Buổi {$newNumber}: {$newTitle}",
                    ]);
                
                Log::debug('[ClassManagement] Pulled up session', [
                    'session_id' => $session->id,
                    'old_number' => $oldNumber,
                    'new_number' => $newNumber,
                    'new_title' => $lessonPlan?->lesson_title ?? "Unit {$newNumber}",
                    'date' => $session->scheduled_date,
                ]);
                
                $shiftedCount++;
            }
            
            Log::info('[ClassManagement] Pulled up future sessions', [
                'class_id' => $class->id,
                'start_session_number' => $startSessionNumber,
                'shifted_count' => $shiftedCount,
            ]);
            
            return $shiftedCount;
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to shift future sessions', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
    
    /**
     * 🔥 NEW: Tạo buổi mới ở cuối lịch với lesson từ buổi bị hủy
     * Session number = highest session number + 1
     */
    private function createNewSessionAtEnd(int $cancelledSessionNumber, ClassModel $class): ?ClassLessonSession
    {
        try {
            // Lấy buổi học cuối cùng (không tính cancelled)
            $lastValidSession = $class->lessonSessions()
                ->where('status', '!=', 'cancelled')
                ->orderBy('session_number', 'desc')
                ->first();
            
            if (!$lastValidSession) {
                Log::warning('[ClassManagement] No last valid session found');
                return null;
            }
            
            // Lấy lịch học
            $schedules = $class->schedules()->orderBy('day_of_week')->get();
            
            if ($schedules->isEmpty()) {
                Log::warning('[ClassManagement] No schedules found');
                return null;
            }
            
            // Map schedules by day name
            $schedulesByDay = [];
            foreach ($schedules as $schedule) {
                $schedulesByDay[$schedule->day_of_week] = $schedule;
            }
            
            // Tìm ngày học tiếp theo sau buổi cuối
            $currentDate = \Carbon\Carbon::parse($lastValidSession->scheduled_date);
            $newDate = $this->findNextScheduleDate($currentDate, $schedulesByDay, $class);
            
            if (!$newDate) {
                Log::warning('[ClassManagement] Could not find next date for new session');
                return null;
            }
            
            // Day mapping
            $dayNumberToName = [
                0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
                4 => 'thursday', 5 => 'friday', 6 => 'saturday',
            ];
            
            $dayNumber = $newDate->dayOfWeek;
            $dayName = $dayNumberToName[$dayNumber] ?? null;
            $schedule = $dayName ? ($schedulesByDay[$dayName] ?? null) : null;
            
            if (!$schedule) {
                Log::warning('[ClassManagement] No schedule found for new session date');
                return null;
            }
            
            // Session number mới = highest + 1
            $newSessionNumber = $lastValidSession->session_number + 1;
            
            // 🔥 FIX: Load lesson plan session theo $newSessionNumber (không phải $cancelledSessionNumber)
            // Buổi mới cần có nội dung của session_number mới, không phải buổi bị hủy
            $lessonPlanSession = null;
            if ($class->lesson_plan_id) {
                $lessonPlanSession = \App\Models\LessonPlanSession::where('lesson_plan_id', $class->lesson_plan_id)
                    ->where('session_number', $newSessionNumber)
                    ->first();
            }
            
            // 🔥 FIX: Chỉ tạo buổi mới nếu chưa đủ total_sessions
            // Đếm số buổi valid hiện tại (không tính cancelled)
            $currentValidCount = $class->lessonSessions()
                ->where('session_number', '>', 0)
                ->count();
            
            if ($currentValidCount >= $class->total_sessions) {
                Log::info('[ClassManagement] Not creating new session - already at total_sessions limit', [
                    'class_id' => $class->id,
                    'current_valid_count' => $currentValidCount,
                    'total_sessions' => $class->total_sessions,
                ]);
                return null;
            }
            
            // Tạo buổi mới
            $newSession = ClassLessonSession::create([
                'class_id' => $class->id,
                'lesson_plan_id' => $class->lesson_plan_id,
                'class_schedule_id' => $schedule->id,
                'lesson_plan_session_id' => $lessonPlanSession?->id,
                'session_number' => $newSessionNumber, // 🔥 Số mới ở cuối
                'scheduled_date' => $newDate->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'status' => 'scheduled',
                'lesson_title' => $lessonPlanSession?->lesson_title,
                'lesson_objectives' => $lessonPlanSession?->lesson_objectives,
                'lesson_content' => $lessonPlanSession?->lesson_content,
                'lesson_plan_url' => $lessonPlanSession?->lesson_plan_url,
                'materials_url' => $lessonPlanSession?->materials_url,
                'homework_url' => $lessonPlanSession?->homework_url,
                'duration_minutes' => $lessonPlanSession?->duration_minutes,
            ]);
            
            Log::info('[ClassManagement] Created new session at end', [
                'class_id' => $class->id,
                'cancelled_session_number' => $cancelledSessionNumber,
                'new_session_number' => $newSessionNumber,
                'scheduled_date' => $newDate->toDateString(),
                'new_session_id' => $newSession->id,
            ]);
            
            return $newSession;
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to create new session at end', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 🔥 DEPRECATED: Old method - replaced by shiftFutureSessions + createNewSessionAtEnd
     */
    private function createReplacementSession(ClassLessonSession $cancelledSession): ?ClassLessonSession
    {
        try {
            $class = $cancelledSession->class;
            
            // Lấy buổi học cuối cùng (không tính cancelled)
            $lastValidSession = $class->lessonSessions()
                ->where('status', '!=', 'cancelled')
                ->orderBy('scheduled_date', 'desc')
                ->first();
            
            if (!$lastValidSession) {
                Log::warning('[ClassManagement] No last valid session found for replacement');
                return null;
            }
            
            // Lấy lịch học
            $schedules = $class->schedules()->orderBy('day_of_week')->get();
            
            if ($schedules->isEmpty()) {
                Log::warning('[ClassManagement] No schedules found for replacement');
                return null;
            }
            
            // Map schedules by day name
            $schedulesByDay = [];
            foreach ($schedules as $schedule) {
                $schedulesByDay[$schedule->day_of_week] = $schedule;
            }
            
            // Tìm ngày học tiếp theo sau buổi cuối
            $currentDate = \Carbon\Carbon::parse($lastValidSession->scheduled_date);
            $newDate = $this->findNextScheduleDate($currentDate, $schedulesByDay, $class);
            
            if (!$newDate) {
                Log::warning('[ClassManagement] Could not find next date for replacement');
                return null;
            }
            
            // Day mapping
            $dayNumberToName = [
                0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
                4 => 'thursday', 5 => 'friday', 6 => 'saturday',
            ];
            
            $dayNumber = $newDate->dayOfWeek;
            $dayName = $dayNumberToName[$dayNumber] ?? null;
            $schedule = $dayName ? ($schedulesByDay[$dayName] ?? null) : null;
            
            if (!$schedule) {
                Log::warning('[ClassManagement] No schedule found for replacement date');
                return null;
            }
            
            // Load lesson plan session nếu có
            $lessonPlanSession = null;
            if ($class->lesson_plan_id) {
                $lessonPlanSession = \App\Models\LessonPlanSession::where('lesson_plan_id', $class->lesson_plan_id)
                    ->where('session_number', $cancelledSession->session_number)
                    ->first();
            }
            
            // Tạo buổi mới (cùng session_number với buổi bị hủy)
            $replacementSession = ClassLessonSession::create([
                'class_id' => $class->id,
                'lesson_plan_id' => $class->lesson_plan_id,
                'class_schedule_id' => $schedule->id,
                'lesson_plan_session_id' => $lessonPlanSession?->id,
                'session_number' => $cancelledSession->session_number, // 🔥 Cùng số thứ tự
                'scheduled_date' => $newDate->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'status' => 'scheduled',
                'lesson_title' => $cancelledSession->lesson_title ?? $lessonPlanSession?->lesson_title,
                'lesson_objectives' => $cancelledSession->lesson_objectives ?? $lessonPlanSession?->lesson_objectives,
                'lesson_content' => $cancelledSession->lesson_content ?? $lessonPlanSession?->lesson_content,
                'lesson_plan_url' => $cancelledSession->lesson_plan_url ?? $lessonPlanSession?->lesson_plan_url,
                'materials_url' => $cancelledSession->materials_url ?? $lessonPlanSession?->materials_url,
                'homework_url' => $cancelledSession->homework_url ?? $lessonPlanSession?->homework_url,
                'duration_minutes' => $cancelledSession->duration_minutes ?? $lessonPlanSession?->duration_minutes,
            ]);
            
            Log::info('[ClassManagement] Created replacement session', [
                'class_id' => $class->id,
                'cancelled_session_id' => $cancelledSession->id,
                'replacement_session_id' => $replacementSession->id,
                'session_number' => $replacementSession->session_number,
                'scheduled_date' => $replacementSession->scheduled_date,
            ]);
            
            return $replacementSession;
        } catch (\Exception $e) {
            Log::error('[ClassManagement] Failed to create replacement session', [
                'cancelled_session_id' => $cancelledSession->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find next schedule date from current date based on class schedules
     */
    private function findNextScheduleDate(
        \Carbon\Carbon $currentDate,
        array $schedulesByDay,
        ClassModel $class
    ): ?\Carbon\Carbon {
        // 🔥 FIX: Map Carbon day number (0-6) to day name string
        $dayNumberToName = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];
        
        $maxAttempts = 14; // Look ahead 2 weeks max
        $attemptDate = $currentDate->copy()->addDay();

        for ($i = 0; $i < $maxAttempts; $i++) {
            $dayNumber = $attemptDate->dayOfWeek; // 0-6 (Sunday = 0)
            $dayName = $dayNumberToName[$dayNumber] ?? null;

            // Check if this day has a schedule
            if ($dayName && isset($schedulesByDay[$dayName])) {
                // Check if it's a holiday (you can add holiday checking logic here)
                // For now, just return the date
                return $attemptDate;
            }

            $attemptDate->addDay();
        }

        return null;
    }

    /**
     * Format teacher assignment message for Zalo
     */
    private function formatTeacherAssignmentMessage(
        ClassLessonSession $session,
        \App\Models\User $teacher
    ): string {
        $timezone = \DB::table('settings')->where('key', 'timezone')->value('value') ?? 'Asia/Ho_Chi_Minh';
        $sessionDate = \Carbon\Carbon::parse($session->scheduled_date)->timezone($timezone);

        $message = "📋 THÔNG BÁO PHÂN CÔNG DẠY\n\n";
        $message .= "👋 Xin chào {$teacher->name},\n\n";
        $message .= "Bạn đã được phân công dạy:\n\n";
        $message .= "📚 Lớp: {$session->class->name}\n";
        $message .= "📝 Buổi {$session->session_number}\n";
        $message .= "📅 Ngày: {$sessionDate->format('d/m/Y')}\n";
        $message .= "🕐 Giờ: {$session->start_time->format('H:i')} - {$session->end_time->format('H:i')}\n";

        if ($session->class->room_number) {
            $message .= "🚪 Phòng: {$session->class->room_number}\n";
        }

        if ($session->lesson_title) {
            $message .= "📖 Nội dung: {$session->lesson_title}\n";
        }

        if ($session->lesson_objectives) {
            $objectives = strip_tags($session->lesson_objectives);
            $message .= "🎯 Mục tiêu: {$objectives}\n";
        }

        $message .= "\n💡 Vui lòng chuẩn bị bài giảng và đến đúng giờ. Chúc bạn buổi dạy hiệu quả! 💪";

        return $message;
    }
}
