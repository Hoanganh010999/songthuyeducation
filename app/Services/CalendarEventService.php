<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Model;

class CalendarEventService
{
    /**
     * Tạo hoặc cập nhật calendar event từ model khác
     * 
     * @param Model $eventable - Model gốc (CustomerInteraction, Task, etc.)
     * @param array $data - Thông tin event
     * @return CalendarEvent|null
     */
    public function syncEvent(Model $eventable, array $data): ?CalendarEvent
    {
        // Nếu không có start_date thì không tạo event
        if (empty($data['start_date'])) {
            // Xóa event cũ nếu có
            $this->deleteEvent($eventable);
            return null;
        }

        // Tìm hoặc tạo mới event
        $event = CalendarEvent::where('eventable_type', get_class($eventable))
            ->where('eventable_id', $eventable->id)
            ->first();

        $eventData = [
            'eventable_type' => get_class($eventable),
            'eventable_id' => $eventable->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'general',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? $data['start_date'],
            'is_all_day' => $data['is_all_day'] ?? false,
            'status' => $data['status'] ?? 'pending',
            'user_id' => $data['user_id'],
            'assigned_teacher_id' => $data['assigned_teacher_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'created_by' => $data['created_by'] ?? $data['user_id'],
            'manager_id' => $data['manager_id'] ?? null,
            'attendees' => $data['attendees'] ?? null,
            'color' => $data['color'] ?? $this->getDefaultColor($data['category'] ?? 'general'),
            'icon' => $data['icon'] ?? $this->getDefaultIcon($data['category'] ?? 'general'),
            'location' => $data['location'] ?? null,
            'has_reminder' => $data['has_reminder'] ?? false,
            'reminder_minutes_before' => $data['reminder_minutes_before'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ];

        if ($event) {
            $event->update($eventData);
        } else {
            $event = CalendarEvent::create($eventData);
        }

        return $event;
    }

    /**
     * Xóa calendar event liên kết với model
     */
    public function deleteEvent(Model $eventable): void
    {
        CalendarEvent::where('eventable_type', get_class($eventable))
            ->where('eventable_id', $eventable->id)
            ->delete();
    }

    /**
     * Cập nhật trạng thái event
     */
    public function updateEventStatus(Model $eventable, string $status): void
    {
        CalendarEvent::where('eventable_type', get_class($eventable))
            ->where('eventable_id', $eventable->id)
            ->update(['status' => $status]);
    }

    /**
     * Lấy events trong khoảng thời gian (với phân quyền)
     */
    public function getEventsBetweenDates($startDate, $endDate, $user, ?string $category = null, ?int $branchId = null)
    {
        $query = CalendarEvent::with([
            'user:id,name',
            'branch:id,name',
            'eventable',
            'assignedTeacher:id,name'
        ])
            ->betweenDates($startDate, $endDate)
            ->accessibleBy($user);

        if ($category) {
            $query->byCategory($category);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('start_date', 'asc')->get();
    }

    /**
     * Lấy upcoming events (với phân quyền)
     */
    public function getUpcomingEvents($user, int $limit = 10)
    {
        $query = CalendarEvent::with(['user:id,name', 'branch:id,name', 'eventable'])
            ->upcoming()
            ->accessibleBy($user);

        return $query->limit($limit)->get();
    }

    /**
     * Lấy overdue events (với phân quyền)
     */
    public function getOverdueEvents($user)
    {
        $query = CalendarEvent::with(['user:id,name', 'branch:id,name', 'eventable'])
            ->overdue()
            ->accessibleBy($user);

        return $query->get();
    }

    /**
     * Lấy màu mặc định theo category
     */
    private function getDefaultColor(string $category): string
    {
        $colors = CalendarEvent::getCategoryColors();
        return $colors[$category] ?? $colors['general'];
    }

    /**
     * Lấy icon mặc định theo category
     */
    private function getDefaultIcon(string $category): string
    {
        $icons = CalendarEvent::getCategoryIcons();
        return $icons[$category] ?? $icons['general'];
    }

    /**
     * Format event data cho TOAST UI Calendar
     */
    public function formatForToastUI(CalendarEvent $event): array
    {
        // Lấy thông tin customer/child từ eventable
        $customerInfo = $this->extractCustomerInfo($event);
        
        // Format dates without timezone (local time)
        $startDate = \Carbon\Carbon::parse($event->start_date)->format('Y-m-d\TH:i:s');
        $endDate = \Carbon\Carbon::parse($event->end_date)->format('Y-m-d\TH:i:s');
        
        return [
            'id' => $event->id,
            'calendarId' => $event->category,
            'title' => $event->title,
            'body' => $event->description,
            'start' => $startDate, // Local time, no timezone
            'end' => $endDate, // Local time, no timezone
            'isAllday' => $event->is_all_day,
            'category' => $event->is_all_day ? 'allday' : 'time',
            'backgroundColor' => $event->color,
            'borderColor' => $event->color,
            'color' => '#ffffff',
            'location' => $event->location,
            'attendees' => $event->attendees,
            'state' => $event->status,
            'raw' => [
                'eventable_type' => $event->eventable_type,
                'eventable_id' => $event->eventable_id,
                'icon' => $event->icon,
                'metadata' => $event->metadata,
                'customer_info' => $customerInfo,
                'status' => $event->status,
                'test_result' => $event->test_result,
                'assigned_teacher_id' => $event->assigned_teacher_id,
                'assigned_teacher' => $event->assignedTeacher ? [
                    'id' => $event->assignedTeacher->id,
                    'name' => $event->assignedTeacher->name,
                ] : null,
                'trial_students_count' => $customerInfo['trial_students_count'] ?? 0,
            ],
        ];
    }
    
    /**
     * Đồng bộ buổi học lên calendar
     */
    public function syncClassSessionToCalendar(\App\Models\ClassLessonSession $session): ?CalendarEvent
    {
        // Load relationships cần thiết (bao gồm teacher từ session)
        $session->load('class.homeroomTeacher', 'class.branch', 'classSchedule.teacher', 'teacher');
        $class = $session->class;

        // Nếu chưa có scheduled_date thì không tạo event
        if (!$session->scheduled_date) {
            $session->calendarEvent?->delete();
            return null;
        }

        // Nếu không có class (đã xóa) thì không tạo event
        if (!$class) {
            $session->calendarEvent?->delete();
            return null;
        }

        // Lấy teacher theo thứ tự ưu tiên: session teacher > class_schedule teacher > homeroomTeacher
        $teacher = $session->getEffectiveTeacher();
        
        // Tạo start_date và end_date
        // start_time và end_time có thể là Carbon objects hoặc null
        $startTimeStr = $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i:s') : '14:00:00';
        $endTimeStr = $session->end_time ? \Carbon\Carbon::parse($session->end_time)->format('H:i:s') : '16:00:00';
        
        $startDate = \Carbon\Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $startTimeStr);
        $endDate = \Carbon\Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $endTimeStr);
        
        // Map status
        $calendarStatus = match($session->status) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'scheduled', 'rescheduled' => 'pending',
            'holiday' => 'cancelled',
            default => 'pending',
        };
        
        // Chuẩn bị metadata
        $metadata = [
            'session_id' => $session->id, // Add session ID for calendar actions
            'class_id' => $class->id,
            'class_name' => $class->name,
            'class_code' => $class->code,
            'session_number' => $session->session_number,
            'lesson_plan_id' => $session->lesson_plan_id,
            'total_sessions' => $class->total_sessions,
            'student_count' => $class->current_students,
            'room_number' => $class->room_number,
            'status' => $session->status,
            'teacher_id' => $teacher?->id,
            'teacher_name' => $teacher?->full_name ?? $teacher?->name,
        ];
        
        // Tạo description với thông tin giáo viên
        $description = $session->lesson_objectives ?? $session->lesson_content ?? "Buổi học số {$session->session_number}";
        if ($teacher) {
            $description = "Giáo viên: {$teacher->full_name}\n\n" . $description;
        }
        
        // Dữ liệu event
        $eventData = [
            'title' => "{$class->code} - Buổi {$session->session_number}: {$session->lesson_title}",
            'description' => $description,
            'category' => 'class_session',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_all_day' => false,
            'status' => $calendarStatus,
            'user_id' => $teacher->id ?? 1, // Giáo viên đứng lớp hoặc giáo viên chủ nhiệm
            'assigned_teacher_id' => $teacher->id ?? null, // Lưu teacher ID vào assigned_teacher_id
            'branch_id' => $class->branch_id,
            'created_by' => $teacher->id ?? 1,
            'color' => '#14B8A6', // Teal
            'icon' => '🎓',
            'location' => $session->classSchedule?->room?->name ?? $class->room_number,
            'has_reminder' => true,
            'reminder_minutes_before' => 30, // Nhắc trước 30 phút
            'metadata' => $metadata,
        ];
        
        return $this->syncEvent($session, $eventData);
    }

    /**
     * Sync homework assignment to calendar
     */
    public function syncHomeworkToCalendar(\App\Models\HomeworkAssignment $homework): ?CalendarEvent
    {
        // Load relationships
        $homework->load('class.homeroomTeacher', 'class.branch', 'session', 'creator');
        $class = $homework->class;
        
        // Nếu không có deadline thì không tạo event
        if (!$homework->deadline) {
            $homework->calendarEvent?->delete();
            return null;
        }
        
        // Nếu không có class (đã xóa) thì không tạo event
        if (!$class) {
            $homework->calendarEvent?->delete();
            return null;
        }
        
        $teacher = $class->homeroomTeacher;
        
        // Parse deadline
        $deadline = \Carbon\Carbon::parse($homework->deadline);
        
        // Map status
        $calendarStatus = match($homework->status) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'active' => 'pending',
            default => 'pending',
        };
        
        // Chuẩn bị metadata
        $metadata = [
            'class_id' => $class->id,
            'class_name' => $class->name,
            'class_code' => $class->code,
            'homework_id' => $homework->id,
            'session_id' => $homework->lesson_plan_session_id,
            'session_number' => $homework->session?->session_number,
        ];
        
        // Dữ liệu event
        $eventData = [
            'title' => "[{$class->code}] 📝 {$homework->title}",
            'description' => $homework->description ?? "Bài tập về nhà",
            'category' => 'homework',
            'start_date' => $deadline,
            'end_date' => $deadline,
            'is_all_day' => false,
            'status' => $calendarStatus,
            'user_id' => $teacher?->id ?? $homework->created_by,
            'assigned_teacher_id' => $teacher?->id ?? null,
            'branch_id' => $class->branch_id,
            'created_by' => $homework->created_by,
            'color' => '#F59E0B', // Orange for homework
            'icon' => '📝',
            'has_reminder' => true,
            'reminder_minutes_before' => 1440, // Nhắc trước 1 ngày
            'metadata' => $metadata,
        ];
        
        return $this->syncEvent($homework, $eventData);
    }

    /**
     * Trích xuất thông tin customer/class từ eventable
     */
    private function extractCustomerInfo(CalendarEvent $event): ?array
    {
        if (!$event->eventable) {
            return null;
        }
        
        $eventable = $event->eventable;
        
        // Nếu là ClassLessonSession
        if ($eventable instanceof \App\Models\ClassLessonSession) {
            $eventable->load('class.homeroomTeacher', 'classSchedule.teacher', 'trialStudents');
            $class = $eventable->class;
            
            if (!$class) {
                return null;
            }
            
            // Ưu tiên lấy teacher từ class_schedule (giáo viên đứng lớp), nếu không có thì lấy homeroomTeacher
            $teacher = $eventable->classSchedule?->teacher ?? $class->homeroomTeacher;
            
            // Count active trial students
            $trialCount = $eventable->activeTrialStudents()->count();
            
            // Get trial feedback if exists (lấy feedback từ trial student đầu tiên có feedback)
            $trialFeedback = null;
            $trialStudentWithFeedback = $eventable->trialStudents()
                ->whereNotNull('feedback')
                ->with('feedbackBy')
                ->first();
            
            if ($trialStudentWithFeedback) {
                $trialFeedback = [
                    'feedback' => $trialStudentWithFeedback->feedback,
                    'rating' => $trialStudentWithFeedback->rating,
                    'feedback_by' => $trialStudentWithFeedback->feedbackBy ? [
                        'id' => $trialStudentWithFeedback->feedbackBy->id,
                        'name' => $trialStudentWithFeedback->feedbackBy->name,
                    ] : null,
                    'feedback_at' => $trialStudentWithFeedback->feedback_at?->format('Y-m-d H:i:s'),
                ];
            }
            
            return [
                'type' => 'class_session',
                'id' => $eventable->id,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'class_code' => $class->code,
                'session_number' => $eventable->session_number,
                'lesson_title' => $eventable->lesson_title,
                'teacher_name' => $teacher?->full_name ?? $teacher?->name ?? 'N/A',
                'teacher_id' => $teacher?->id ?? $class->homeroom_teacher_id,
                'student_count' => $class->current_students,
                'room_number' => $class->room_number,
                'status' => $eventable->status,
                'total_sessions' => $class->total_sessions,
                'trial_students_count' => $trialCount,
                'trial_feedback' => $trialFeedback, // NEW: Trial class feedback
            ];
        }
        
        // Nếu là CustomerInteraction
        if ($eventable instanceof \App\Models\CustomerInteraction) {
            $customer = $eventable->customer;
            return [
                'type' => 'customer',
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'stage' => $customer->stage,
                'source' => $customer->source,
            ];
        }
        
        // Nếu là Customer (placement test)
        if ($eventable instanceof \App\Models\Customer) {
            return [
                'type' => 'customer',
                'id' => $eventable->id,
                'name' => $eventable->name,
                'phone' => $eventable->phone,
                'email' => $eventable->email,
                'stage' => $eventable->stage,
                'source' => $eventable->source,
            ];
        }
        
        // Nếu là CustomerChild (placement test)
        if ($eventable instanceof \App\Models\CustomerChild) {
            return [
                'type' => 'child',
                'id' => $eventable->id,
                'name' => $eventable->name,
                'date_of_birth' => $eventable->date_of_birth ? $eventable->date_of_birth->format('d/m/Y') : null,
                'school' => $eventable->school,
                'grade' => $eventable->grade,
                'notes' => $eventable->notes,
                'parent_id' => $eventable->customer_id,
                'parent_name' => $eventable->customer->name,
                'parent_phone' => $eventable->customer->phone,
            ];
        }
        
        // Nếu là HomeworkAssignment
        if ($eventable instanceof \App\Models\HomeworkAssignment) {
            $eventable->load('class', 'session', 'creator');
            $class = $eventable->class;
            
            if (!$class) {
                return null;
            }
            
            // Get files data
            $files = [];
            if (!empty($eventable->file_ids)) {
                $googleFiles = \App\Models\GoogleDriveItem::whereIn('google_id', $eventable->file_ids)
                    ->where('is_trashed', false)
                    ->get();
                
                foreach ($googleFiles as $file) {
                    $files[] = [
                        'id' => $file->id,
                        'google_id' => $file->google_id,
                        'name' => $file->name,
                        'mime_type' => $file->mime_type,
                        'size' => $file->size,
                        'url' => "/api/course/homework/{$eventable->id}/file/{$file->google_id}",
                    ];
                }
            }
            
            return [
                'type' => 'homework',
                'id' => $eventable->id,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'class_code' => $class->code,
                'homework_title' => $eventable->title,
                'description' => $eventable->description,
                'session_number' => $eventable->session?->session_number,
                'session_title' => $eventable->session?->lesson_title,
                'deadline' => $eventable->deadline?->format('Y-m-d H:i:s'),
                'files' => $files,
            ];
        }
        
        return null;
    }
}

