<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarEventController extends Controller
{
    protected $calendarService;

    public function __construct(CalendarEventService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Lấy danh sách events cho calendar view (với phân quyền)
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $category = $request->input('category');
        $branchId = $request->input('branch_id');

        if (!$startDate || !$endDate) {
            return response()->json([
                'success' => false,
                'message' => 'start_date và end_date là bắt buộc',
            ], 400);
        }

        $user = Auth::user();

        $events = $this->calendarService->getEventsBetweenDates(
            $startDate,
            $endDate,
            $user,
            $category,
            $branchId
        );

        // Format cho TOAST UI Calendar
        $formattedEvents = $events->map(function ($event) {
            return $this->calendarService->formatForToastUI($event);
        });

        return response()->json([
            'success' => true,
            'data' => $formattedEvents,
        ]);
    }

    /**
     * Lấy upcoming events (với phân quyền)
     */
    public function upcoming(Request $request)
    {
        $limit = $request->input('limit', 10);
        $user = Auth::user();

        $events = $this->calendarService->getUpcomingEvents($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Lấy overdue events (với phân quyền)
     */
    public function overdue(Request $request)
    {
        $user = Auth::user();
        $events = $this->calendarService->getOverdueEvents($user);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Tạo event mới (với branch assignment)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_all_day' => 'nullable|boolean',
            'location' => 'nullable|string',
            'color' => 'nullable|string',
            'attendees' => 'nullable|array',
            'has_reminder' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = Auth::user();
        $validated['user_id'] = $user->id;
        $validated['created_by'] = $user->id;
        $validated['eventable_type'] = null; // Standalone event
        $validated['eventable_id'] = null;

        // Nếu không chọn branch, lấy branch đầu tiên của user
        if (!isset($validated['branch_id'])) {
            $validated['branch_id'] = $user->branches()->first()?->id;
        }

        $event = CalendarEvent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sự kiện thành công',
            'data' => $this->calendarService->formatForToastUI($event->load(['user', 'branch'])),
        ], 201);
    }

    /**
     * Xem chi tiết event
     */
    public function show($id)
    {
        $event = CalendarEvent::with(['user:id,name,email', 'eventable'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Cập nhật event
     */
    public function update(Request $request, $id)
    {
        $event = CalendarEvent::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'is_all_day' => 'nullable|boolean',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'location' => 'nullable|string',
            'color' => 'nullable|string',
            'attendees' => 'nullable|array',
            'has_reminder' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer',
        ]);

        // Store old values for comparison (before update)
        $oldStartDate = $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('Y-m-d H:i:s') : null;
        $oldLocation = $event->location;

        $event->update($validated);
        $event->refresh();

        // 🔥 NEW: Send Zalo notification if placement test was updated
        if ($event->category === 'placement_test' && $event->eventable) {
            try {
                $notificationService = app(\App\Services\CustomerZaloNotificationService::class);
                
                // Check if time or location changed (compare formatted strings)
                $newStartDate = $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('Y-m-d H:i:s') : null;
                $timeChanged = $oldStartDate !== $newStartDate;
                $locationChanged = ($oldLocation ?? '') !== ($event->location ?? '');
                
                \Log::info('[CalendarEvent] Checking if placement test changed', [
                    'event_id' => $event->id,
                    'old_start_date' => $oldStartDate,
                    'new_start_date' => $newStartDate,
                    'old_location' => $oldLocation,
                    'new_location' => $event->location,
                    'time_changed' => $timeChanged,
                    'location_changed' => $locationChanged,
                ]);
                
                // Always send notification if placement test is updated (not just when changed)
                // But only if time or location actually changed
                if ($timeChanged || $locationChanged) {
                    if ($event->eventable_type === \App\Models\Customer::class) {
                        $customer = $event->eventable;
                        $notificationService->sendPlacementTestUpdatedNotification($event, $customer, null, $oldStartDate, $oldLocation);
                    } elseif ($event->eventable_type === \App\Models\CustomerChild::class) {
                        $child = $event->eventable;
                        $customer = $child->customer;
                        $notificationService->sendPlacementTestUpdatedNotification($event, $customer, $child, $oldStartDate, $oldLocation);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('[CalendarEvent] Failed to send Zalo notification for updated placement test', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't fail the request if notification fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sự kiện thành công',
            'data' => $this->calendarService->formatForToastUI($event->load('user')),
        ]);
    }

    /**
     * Xóa event
     */
    public function destroy($id)
    {
        $event = CalendarEvent::findOrFail($id);

        // Chỉ cho phép xóa standalone events (không liên kết với model khác)
        if ($event->eventable_type !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa sự kiện này. Vui lòng xóa từ module gốc.',
            ], 403);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sự kiện thành công',
        ]);
    }

    /**
     * Lấy danh sách categories
     */
    public function categories()
    {
        $categories = collect(CalendarEvent::getCategoryColors())->map(function ($color, $key) {
            return [
                'id' => $key,
                'name' => ucwords(str_replace('_', ' ', $key)),
                'color' => $color,
                'icon' => CalendarEvent::getCategoryIcons()[$key] ?? '📌',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Tạo lịch test đầu vào cho Customer
     */
    public function createPlacementTestForCustomer(Request $request, $customerId)
    {
        $customer = \App\Models\Customer::findOrFail($customerId);

        $validated = $request->validate([
            'test_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:30|max:240',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $assignedTo = $validated['assigned_to'] ?? $customer->assigned_to ?? $user->id;
        $durationMinutes = (int) ($validated['duration_minutes'] ?? 60);

        $startDate = \Carbon\Carbon::parse($validated['test_date']);
        $endDate = $startDate->copy()->addMinutes($durationMinutes);

        // Kiểm tra xem đã có lịch test chưa
        $existingTest = $customer->placementTestEvent;

        $eventData = [
            'eventable_type' => \App\Models\Customer::class,
            'eventable_id' => $customer->id,
            'title' => "Test đầu vào: {$customer->name}",
            'description' => $validated['notes'] ?? "Lịch test đầu vào cho khách hàng {$customer->name}",
            'category' => 'placement_test',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_all_day' => false,
            'status' => 'pending',
            'user_id' => $assignedTo,
            'branch_id' => 2, // Học thuật
            'created_by' => $user->id,
            'color' => '#06B6D4', // Cyan
            'icon' => '📝',
            'location' => $validated['location'] ?? null,
            'has_reminder' => true,
            'reminder_minutes_before' => 60,
            'metadata' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'test_type' => 'customer',
            ],
        ];

        if ($existingTest) {
            $existingTest->update($eventData);
            $event = $existingTest;
            $message = 'Cập nhật lịch test thành công';
        } else {
            $event = CalendarEvent::create($eventData);
            $message = 'Tạo lịch test thành công';
        }

        // Send Zalo notification to customer
        try {
            $notificationService = app(\App\Services\CustomerZaloNotificationService::class);
            $notificationService->sendPlacementTestNotification($event, $customer);
        } catch (\Exception $e) {
            \Log::warning('[CalendarEvent] Failed to send Zalo notification for customer', [
                'event_id' => $event->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if notification fails
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $this->calendarService->formatForToastUI($event->load(['user', 'branch'])),
        ], $existingTest ? 200 : 201);
    }

    /**
     * Tạo lịch test đầu vào cho CustomerChild
     */
    public function createPlacementTestForChild(Request $request, $childId)
    {
        $child = \App\Models\CustomerChild::with('customer')->findOrFail($childId);
        $customer = $child->customer;

        $validated = $request->validate([
            'test_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:30|max:240',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $assignedTo = $validated['assigned_to'] ?? $customer->assigned_to ?? $user->id;
        $durationMinutes = (int) ($validated['duration_minutes'] ?? 60);

        // 🔥 FIX: Parse test_date with explicit UTC timezone
        // Frontend sends ISO string (toISOString()) which is in UTC
        // We need to parse it as UTC, then convert to server timezone for storage
        $timezone = \DB::table('settings')->where('key', 'timezone')->value('value') ?? 'Asia/Ho_Chi_Minh';
        $startDate = \Carbon\Carbon::parse($validated['test_date'], 'UTC')->setTimezone($timezone);
        $endDate = $startDate->copy()->addMinutes($durationMinutes);

        // Kiểm tra xem đã có lịch test chưa
        $existingTest = $child->placementTestEvent;

        $eventData = [
            'eventable_type' => \App\Models\CustomerChild::class,
            'eventable_id' => $child->id,
            'title' => "Test đầu vào: {$child->name}",
            'description' => $validated['notes'] ?? "Lịch test đầu vào cho học viên {$child->name} (PH: {$customer->name})",
            'category' => 'placement_test',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_all_day' => false,
            'status' => 'pending',
            'user_id' => $assignedTo,
            'branch_id' => 2, // Học thuật
            'created_by' => $user->id,
            'color' => '#06B6D4', // Cyan
            'icon' => '📝',
            'location' => $validated['location'] ?? null,
            'has_reminder' => true,
            'reminder_minutes_before' => 60,
            'metadata' => [
                'child_id' => $child->id,
                'child_name' => $child->name,
                'child_age' => $child->age,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'test_type' => 'child',
            ],
        ];

        if ($existingTest) {
            $existingTest->update($eventData);
            $event = $existingTest;
            $message = 'Cập nhật lịch test thành công';
        } else {
            $event = CalendarEvent::create($eventData);
            $message = 'Tạo lịch test thành công';
        }

        // Send Zalo notification to customer (parent) - only for new events
        if (!$existingTest) {
            try {
                $notificationService = app(\App\Services\CustomerZaloNotificationService::class);
                $notificationService->sendPlacementTestNotificationForChild($event, $child);
            } catch (\Exception $e) {
                \Log::warning('[CalendarEvent] Failed to send Zalo notification for child', [
                    'event_id' => $event->id,
                    'child_id' => $child->id,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the request if notification fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $this->calendarService->formatForToastUI($event->load(['user', 'branch'])),
        ], $existingTest ? 200 : 201);
    }

    /**
     * Cập nhật kết quả test
     */
    public function updateTestResult(Request $request, $eventId)
    {
        $event = CalendarEvent::findOrFail($eventId);
        
        // Kiểm tra xem có phải là placement_test không
        if ($event->category !== 'placement_test') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện này không phải là lịch test',
            ], 400);
        }

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0|max:100',
            'level' => 'nullable|string',
            'notes' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        $testResult = [
            'score' => $validated['score'] ?? null,
            'level' => $validated['level'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'evaluated_by' => $user->id,
            'evaluated_by_name' => $user->name,
            'evaluated_at' => now()->toIso8601String(),
        ];

        $event->update([
            'test_result' => $testResult,
            'status' => 'completed', // Tự động chuyển sang completed khi có kết quả
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kết quả test thành công',
            'data' => $event->fresh(),
        ]);
    }

    /**
     * Trả kết quả test đầu vào (mới)
     */
    public function submitTestResult(Request $request, $eventId)
    {
        $event = CalendarEvent::findOrFail($eventId);
        $user = Auth::user();
        
        // Check permission: 
        // 1. User có quyền calendar.submit_feedback
        // 2. HOẶC user là GV được gán cho test này
        $canEdit = $user->hasPermission('calendar.submit_feedback') 
                   || $event->assigned_teacher_id == $user->id;
        
        if (!$canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền trả kết quả test',
            ], 403);
        }
        
        // Kiểm tra xem có phải là placement_test không
        if ($event->category !== 'placement_test') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện này không phải là lịch test',
            ], 400);
        }

        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'level' => 'required|string',
            'notes' => 'required|string',
        ]);

        $user = Auth::user();
        
        $testResult = [
            'score' => $validated['score'],
            'level' => $validated['level'],
            'notes' => $validated['notes'],
            'evaluated_by' => $user->id,
            'evaluated_by_name' => $user->name,
            'evaluated_at' => now()->toIso8601String(),
        ];

        $event->update([
            'test_result' => $testResult,
            'status' => 'completed',
        ]);

        // Send Zalo notification with test result
        try {
            $notificationService = app(\App\Services\CustomerZaloNotificationService::class);

            // Determine if customer or child
            if ($event->eventable_type === \App\Models\Customer::class) {
                $customer = $event->eventable;
                $notificationService->sendPlacementTestResultNotification($event->fresh(), $customer, null);
            } else if ($event->eventable_type === \App\Models\CustomerChild::class) {
                $child = $event->eventable;
                $customer = $child->customer;
                $notificationService->sendPlacementTestResultNotification($event->fresh(), $customer, $child);
            }
        } catch (\Exception $e) {
            \Log::warning('[CalendarEvent] Failed to send test result notification', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if notification fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã trả kết quả test thành công',
            'data' => $this->calendarService->formatForToastUI($event->fresh()),
        ]);
    }

    /**
     * Trả kết quả/đánh giá học thử
     */
    public function submitTrialFeedback(Request $request, $eventId)
    {
        $event = CalendarEvent::findOrFail($eventId);
        $user = Auth::user();
        
        // Kiểm tra xem có phải là class_session không
        if ($event->category !== 'class_session') {
            return response()->json([
                'success' => false,
                'message' => 'Sự kiện này không phải là buổi học',
            ], 400);
        }
        
        // Kiểm tra xem có trial students không
        if ($event->eventable_type !== \App\Models\ClassLessonSession::class) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy buổi học',
            ], 400);
        }

        $session = $event->eventable;
        $session->load('class.homeroomTeacher');
        
        // Check permission: 
        // 1. User có quyền calendar.submit_feedback
        // 2. HOẶC user là GV chủ nhiệm của lớp (dạy buổi này)
        $canEdit = $user->hasPermission('calendar.submit_feedback') 
                   || $session->class->homeroom_teacher_id == $user->id;
        
        if (!$canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền đánh giá học thử',
            ], 403);
        }

        $validated = $request->validate([
            'feedback' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        $trialStudents = $session->activeTrialStudents;
        
        if ($trialStudents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có học viên học thử trong buổi học này',
            ], 400);
        }

        $user = Auth::user();
        
        // Cập nhật feedback cho tất cả trial students trong session này
        foreach ($trialStudents as $trialStudent) {
            $trialStudent->update([
                'feedback' => $validated['feedback'],
                'rating' => $validated['rating'],
                'feedback_by' => $user->id,
                'feedback_at' => now(),
                'status' => 'attended', // Enum: registered, attended, absent, cancelled, converted
            ]);
        }

        // Cập nhật status của event
        $event->update([
            'status' => 'completed',
        ]);

        // Send Zalo notification with feedback for each trial student
        try {
            $notificationService = app(\App\Services\CustomerZaloNotificationService::class);

            // Group by customer to send one message per customer
            $groupedByCustomer = $trialStudents->groupBy(function ($trial) {
                if ($trial->trialable_type === \App\Models\Customer::class) {
                    return 'customer_' . $trial->trialable_id;
                } else {
                    return 'customer_' . $trial->trialable->customer_id;
                }
            });

            foreach ($groupedByCustomer as $key => $trials) {
                // Get customer
                $firstTrial = $trials->first();
                if ($firstTrial->trialable_type === \App\Models\Customer::class) {
                    $customer = $firstTrial->trialable;
                } else {
                    $customer = $firstTrial->trialable->customer;
                }

                // Send one notification per customer (using first trial as representative)
                $notificationService->sendTrialClassFeedbackNotification($firstTrial->fresh(), $customer);
            }
        } catch (\Exception $e) {
            \Log::warning('[CalendarEvent] Failed to send trial feedback notification', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if notification fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu đánh giá học thử thành công',
            'data' => $this->calendarService->formatForToastUI($event->fresh()),
        ]);
    }

    /**
     * Phân công giáo viên cho placement test
     */
    public function assignTeacher(Request $request, $eventId)
    {
        $event = CalendarEvent::findOrFail($eventId);
        
        // Check permission
        if (!Auth::user()->hasPermission('calendar.assign_teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền phân công giáo viên',
            ], 403);
        }
        
        // Kiểm tra xem có phải là placement_test không
        if ($event->category !== 'placement_test') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể phân công giáo viên cho lịch test',
            ], 400);
        }

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
        ]);

        $teacher = \App\Models\User::findOrFail($validated['teacher_id']);

        $event->update([
            'assigned_teacher_id' => $validated['teacher_id'],
        ]);

        // Send Zalo notification to assigned teacher
        try {
            $notificationService = app(\App\Services\TeacherZaloNotificationService::class);
            $notificationService->sendTeacherAssignmentNotification($event->fresh()->load('eventable'), $teacher);
        } catch (\Exception $e) {
            \Log::warning('[CalendarEvent] Failed to send teacher assignment notification', [
                'event_id' => $event->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if notification fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã phân công giáo viên thành công',
            'data' => $this->calendarService->formatForToastUI($event->fresh()->load('assignedTeacher')),
        ]);
    }

    /**
     * Lấy lịch học của một ngày cụ thể (mặc định là hôm nay)
     * Endpoint công khai để xem lịch học nhanh
     */
    public function today(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $dateCarbon = \Carbon\Carbon::parse($date);
        $user = $request->user();

        // 🔥 FIX: Kiểm tra xem user có phải là học viên không
        $isStudent = $user && $user->hasRole('student');
        $studentClassIds = [];
        
        if ($isStudent) {
            // Lấy danh sách class_id mà học viên đang học
            $studentClassIds = \DB::table('class_students')
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->pluck('class_id')
                ->toArray();
            
            \Log::info('[CalendarToday] Student viewing calendar', [
                'user_id' => $user->id,
                'class_ids' => $studentClassIds,
            ]);
        }

        // Lấy tất cả class_session events trong ngày - chỉ lấy những event có eventable hợp lệ
        $query = CalendarEvent::with([
            'eventable.class.homeroomTeacher',
            'eventable.class.branch',
            'eventable.teacher',
            'eventable.classSchedule.room',
            'eventable.classSchedule.subject',
            'assignedTeacher:id,name'
        ])
            ->where('category', 'class_session')
            ->whereDate('start_date', $dateCarbon)
            ->whereHas('eventable'); // Chỉ lấy events có session hợp lệ

        // 🔥 FIX: Nếu là học viên, chỉ lấy lịch của các lớp mình học
        if ($isStudent && !empty($studentClassIds)) {
            $query->whereHas('eventable', function($q) use ($studentClassIds) {
                $q->whereIn('class_id', $studentClassIds);
            });
        }

        // Lọc theo branch_id nếu có (và không phải học viên)
        if ($branchId && !$isStudent) {
            $query->where('branch_id', $branchId);
        }

        $events = $query->orderBy('start_date', 'asc')->get();

        // Nhóm theo giờ
        $sessionsByHour = [];
        $validCount = 0;
        $completedCount = 0;

        foreach ($events as $event) {
            $session = $event->eventable;
            $class = $session?->class;

            // Bỏ qua nếu không có class
            if (!$class) continue;

            // Lọc thêm theo branch của class nếu chưa có branch_id trên CalendarEvent
            if ($branchId && $class->branch_id != $branchId) continue;

            $hour = \Carbon\Carbon::parse($event->start_date)->format('H');
            if (!isset($sessionsByHour[$hour])) {
                $sessionsByHour[$hour] = [];
            }

            $teacher = $session?->getEffectiveTeacher() ?? $class?->homeroomTeacher;
            $schedule = $session?->classSchedule;
            $room = $schedule?->room ?? null;
            $subject = $schedule?->subject ?? null;

            $sessionsByHour[$hour][] = [
                'id' => $event->id,
                'session_id' => $session->id,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'class_code' => $class->code,
                'session_number' => $session->session_number,
                'total_sessions' => $class->total_sessions,
                'start_time' => \Carbon\Carbon::parse($event->start_date)->format('H:i'),
                'end_time' => \Carbon\Carbon::parse($event->end_date)->format('H:i'),
                'teacher_name' => $teacher?->name ?? 'Chưa phân công',
                'status' => $event->status,
                'lesson_title' => $session->lesson_title,
                'room_name' => $room?->name ?? $class->room_number ?? null,
                'subject_name' => $subject?->name ?? null,
                'branch_name' => $class->branch?->name ?? null,
            ];

            $validCount++;
            if ($event->status === 'completed') {
                $completedCount++;
            }
        }

        // Sắp xếp theo giờ
        ksort($sessionsByHour);

        $dayNames = [
            0 => 'Chủ nhật', 1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4',
            4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7',
        ];

        // Lấy danh sách branches để filter
        $branches = \App\Models\Branch::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $dateCarbon->format('Y-m-d'),
                'date_formatted' => $dateCarbon->format('d/m/Y'),
                'day_name' => $dayNames[$dateCarbon->dayOfWeek],
                'prev_date' => $dateCarbon->copy()->subDay()->format('Y-m-d'),
                'next_date' => $dateCarbon->copy()->addDay()->format('Y-m-d'),
                'today' => now()->format('Y-m-d'),
                'total_sessions' => $validCount,
                'completed_sessions' => $completedCount,
                'sessions_by_hour' => $sessionsByHour,
                'branches' => $branches,
                'current_branch_id' => $branchId ? (int)$branchId : null,
            ],
        ]);
    }

    /**
     * 🔥 NEW: Xóa placement test và gửi thông báo Zalo
     */
    public function deletePlacementTest($id)
    {
        $event = CalendarEvent::findOrFail($id);

        // Verify this is a placement test
        if ($event->category !== 'placement_test') {
            return response()->json([
                'success' => false,
                'message' => 'Đây không phải là lịch test đầu vào',
            ], 400);
        }

        // Get customer info before deleting
        $customer = null;
        $child = null;
        
        if ($event->eventable_type === \App\Models\Customer::class) {
            $customer = $event->eventable;
        } elseif ($event->eventable_type === \App\Models\CustomerChild::class) {
            $child = $event->eventable;
            $customer = $child->customer;
        }

        // Store event data for notification (before delete)
        $eventData = [
            'title' => $event->title,
            'start_date' => $event->start_date,
            'location' => $event->location,
        ];

        // Delete the event
        $event->delete();

        // 🔥 NEW: Send Zalo notification
        if ($customer) {
            try {
                $notificationService = app(\App\Services\CustomerZaloNotificationService::class);
                $notificationService->sendPlacementTestCancelledNotification($eventData, $customer, $child);
            } catch (\Exception $e) {
                \Log::warning('[CalendarEvent] Failed to send Zalo notification for cancelled placement test', [
                    'event_id' => $id,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the request if notification fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lịch test thành công',
        ]);
    }
}
