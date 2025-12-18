<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassLessonSession;
use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClassLessonSessionController extends Controller
{
    /**
     * Get homework submissions for a session
     */
    public function getHomeworkSubmissions($classId, $sessionId)
    {
        try {
            Log::info('[HomeworkSubmissions] 🔍 API called', [
                'class_id' => $classId,
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            $user = auth()->user();
            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Authorization: Check if user has access to this class
            if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                // Check if user is teacher of this class
                $isTeacher = $class->homeroom_teacher_id === $user->id
                    || $class->schedules()->where('teacher_id', $user->id)->exists();

                // Check if user is student in this class
                $isStudent = \App\Models\Student::where('user_id', $user->id)
                    ->whereHas('classes', function($q) use ($class) {
                        $q->where('classes.id', $class->id);
                    })
                    ->exists();

                // Check if user is parent of student in this class
                $isParent = \App\Models\ParentModel::where('user_id', $user->id)
                    ->whereHas('students.classes', function($q) use ($class) {
                        $q->where('classes.id', $class->id);
                    })
                    ->exists();

                if (!$isTeacher && !$isStudent && !$isParent) {
                    Log::warning('[HomeworkSubmissions] ⚠️ Unauthorized access attempt', [
                        'user_id' => $user->id,
                        'class_id' => $classId,
                        'session_id' => $sessionId,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to view homework submissions for this class.'
                    ], 403);
                }
            }

            // Get homework submissions for this session
            // Note: homework_assignments table uses hw_ids (JSON), not lesson_plan_session_id
            // For now, return empty array as EvaluationModal doesn't need homework data
            // Homework is displayed in SessionHomeworkPanel (expand row) instead
            
            Log::info('[HomeworkSubmissions] 📊 Returning empty homework list', [
                'session_id' => $sessionId,
                'note' => 'Homework data moved to SessionHomeworkPanel',
            ]);

            return response()->json([
                'success' => true,
                'data' => []
            ]);

        } catch (\Exception $e) {
            Log::error('[ClassLessonSession] Error loading homework submissions', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading homework submissions'
            ], 500);
        }
    }

    /**
     * Get attendance records for a session
     */
    public function getAttendance($classId, $sessionId)
    {
        try {
            Log::info('[Attendance] Getting attendance for session', [
                'class_id' => $classId,
                'session_id' => $sessionId,
            ]);

            $user = auth()->user();
            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Authorization: Only teachers can access
            if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                $isTeacher = $class->homeroom_teacher_id === $user->id
                    || $class->schedules()->where('teacher_id', $user->id)->exists();

                if (!$isTeacher) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to view attendance.'
                    ], 403);
                }
            }

            // Get all active students in class
            $students = \App\Models\Student::whereHas('classes', function($q) use ($class) {
                $q->where('classes.id', $class->id)
                  ->where('class_students.status', 'active');
            })->with('user')->get();

            // Get existing attendance records
            $attendances = \App\Models\Attendance::where('session_id', $sessionId)->get()->keyBy('student_id');

            $data = $students->map(function($student) use ($attendances) {
                $attendance = $attendances->get($student->user_id);

                return [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->full_name,
                    'student_code' => $student->student_code,
                    'status' => $attendance ? $attendance->status : null,
                    'check_in_time' => $attendance && $attendance->check_in_time
                        ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i:s')
                        : null,
                    'late_minutes' => $attendance ? $attendance->late_minutes : 0,
                    // Evaluation fields (for EvaluationModal)
                    'homework_score' => $attendance ? $attendance->homework_score : null,
                    'participation_score' => $attendance ? $attendance->participation_score : null,
                    'notes' => $attendance ? $attendance->notes : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('[Attendance] Error loading attendance', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading attendance'
            ], 500);
        }
    }

    /**
     * Save attendance records
     */
    public function saveAttendance(Request $request, $classId, $sessionId)
    {
        try {
            Log::info('[Attendance] Saving attendance', [
                'class_id' => $classId,
                'session_id' => $sessionId,
                'data' => $request->all(),
            ]);

            $user = auth()->user();
            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Authorization: Only teachers can save
            if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                $isTeacher = $class->homeroom_teacher_id === $user->id
                    || $class->schedules()->where('teacher_id', $user->id)->exists();

                if (!$isTeacher) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to save attendance.'
                    ], 403);
                }
            }

            $attendanceData = $request->input('attendance', []);

            foreach ($attendanceData as $data) {
                \App\Models\Attendance::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'student_id' => $data['user_id'], // Note: attendance.student_id is actually user_id
                    ],
                    [
                        'status' => $data['status'],
                        'check_in_time' => $data['check_in_time'] ?? null,
                        'late_minutes' => $data['late_minutes'] ?? 0,
                        'marked_by' => $user->id,
                    ]
                );
            }

            // Auto-update session status to 'completed' after saving attendance
            if ($session->status !== 'completed') {
                $session->update(['status' => 'completed']);
                
                // Recalculate completed sessions count for the class
                $completedCount = ClassLessonSession::where('class_id', $classId)
                    ->where('status', 'completed')
                    ->count();
                $class->update(['completed_sessions' => $completedCount]);
                
                Log::info('[Attendance] Session marked as completed', [
                    'session_id' => $sessionId,
                    'class_id' => $classId,
                    'completed_count' => $completedCount,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[Attendance] Error saving attendance', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving attendance'
            ], 500);
        }
    }

    /**
     * Save evaluation records (detailed evaluation)
     */
    public function saveEvaluations(Request $request, $classId, $sessionId)
    {
        try {
            Log::info('[Evaluation] Saving evaluations', [
                'class_id' => $classId,
                'session_id' => $sessionId,
            ]);

            $user = auth()->user();
            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Authorization: Only teachers can save
            if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                $isTeacher = $class->homeroom_teacher_id === $user->id
                    || $class->schedules()->where('teacher_id', $user->id)->exists();

                if (!$isTeacher) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to save evaluations.'
                    ], 403);
                }
            }

            $evaluations = $request->input('evaluations', []);

            foreach ($evaluations as $data) {
                \App\Models\Attendance::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'student_id' => $data['user_id'],
                    ],
                    [
                        'homework_score' => $data['homework_achieved_points'] ?? null,
                        'participation_score' => $data['interaction_score'] ?? null,
                        'notes' => $data['notes'] ?? '',
                        'marked_by' => $user->id,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Evaluations saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[Evaluation] Error saving evaluations', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving evaluations'
            ], 500);
        }
    }

    /**
     * Get student statistics (attendance percentage, homework completion rate)
     */
    public function getStudentStats($classId)
    {
        try {
            Log::info('[StudentStats] Getting stats for class', [
                'class_id' => $classId,
            ]);

            $class = \App\Models\ClassModel::findOrFail($classId);

            // Get all students in class
            $students = \App\Models\Student::whereHas('classes', function($q) use ($class) {
                $q->where('classes.id', $class->id)
                  ->where('class_students.status', 'active');
            })->get();

            // Get only COMPLETED sessions (scheduled_date <= today, NOT cancelled) for this class
            $sessionIds = $class->lessonSessions()
                ->where('scheduled_date', '<=', now())
                ->whereNotIn('status', ['cancelled', 'postponed'])
                ->pluck('id');
            $totalSessions = $sessionIds->count();

            $stats = [];

            foreach ($students as $student) {
                // Calculate attendance percentage (only for completed sessions)
                $attendedCount = \App\Models\Attendance::where('student_id', $student->user_id)
                    ->whereIn('session_id', $sessionIds)
                    ->where('status', 'present')
                    ->count();

                $attendancePercentage = $totalSessions > 0
                    ? round(($attendedCount / $totalSessions) * 100, 1)
                    : 0;

                // Calculate homework completion rate (only for completed sessions)
                $homeworkSubmittedCount = HomeworkSubmission::where('student_id', $student->user_id)
                    ->whereIn('session_id', $sessionIds)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->count();

                $homeworkCompletionRate = $totalSessions > 0
                    ? round(($homeworkSubmittedCount / $totalSessions) * 100, 1)
                    : 0;

                $stats[$student->user_id] = [
                    'attendance_percentage' => $attendancePercentage,
                    'homework_completion_rate' => $homeworkCompletionRate,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('[StudentStats] Error getting stats', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting student statistics'
            ], 500);
        }
    }

    /**
     * Send attendance notification to Zalo group
     */
    public function sendAttendanceNotification($classId, $sessionId)
    {
        try {
            Log::info('[ZaloNotification] Sending attendance notification', [
                'class_id' => $classId,
                'session_id' => $sessionId,
            ]);

            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Get attendance records for this session
            $attendances = \App\Models\Attendance::where('session_id', $sessionId)
                ->with('student')
                ->get();

            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance records found'
                ], 400);
            }

            // Build Zalo message
            $message = "📊 ĐIỂM DANH - Buổi {$session->session_number}";
            $message .= "\n📅 " . \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
            $message .= "\n\n";

            foreach ($attendances as $attendance) {
                $studentName = $attendance->student->full_name ?? 'Unknown';

                switch ($attendance->status) {
                    case 'present':
                        $message .= "✅ {$studentName} - Có mặt";
                        if ($attendance->check_in_time) {
                            $message .= " (đúng giờ)";
                        }
                        break;
                    case 'late':
                        $message .= "⏰ {$studentName} - Đi muộn";
                        if ($attendance->late_minutes) {
                            $message .= " ({$attendance->late_minutes} phút)";
                        }
                        break;
                    case 'absent':
                        $message .= "❌ {$studentName} - Vắng mặt";
                        break;
                    case 'excused':
                        $message .= "📋 {$studentName} - Có phép";
                        break;
                }
                $message .= "\n";
            }

            // Send to Zalo
            $zaloService = app(\App\Services\ZaloNotificationService::class);
            $result = $zaloService->sendToClassGroup($class->id, $message);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send Zalo notification'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('[ZaloNotification] Error sending attendance notification', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sending notification'
            ], 500);
        }
    }

    /**
     * Send evaluation summary notification to Zalo group
     */
    public function sendEvaluationNotification($classId, $sessionId)
    {
        try {
            Log::info('[ZaloNotification] Sending evaluation notification', [
                'class_id' => $classId,
                'session_id' => $sessionId,
            ]);

            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Get session comments (evaluation records) for this session
            $sessionComments = \App\Models\SessionComment::where('session_id', $sessionId)
                ->with('student')
                ->get();

            if ($sessionComments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No evaluation records found'
                ], 400);
            }

            // Get homework submissions
            $submissions = HomeworkSubmission::where('session_id', $sessionId)->get()->keyBy('student_id');

            // Calculate stats for each student (only for completed sessions, NOT cancelled)
            $sessionIds = $class->lessonSessions()
                ->where('scheduled_date', '<=', now())
                ->whereNotIn('status', ['cancelled', 'postponed'])
                ->pluck('id');
            $totalSessions = $sessionIds->count();

            // Build Zalo message
            $message = "📝 ĐÁNH GIÁ BUỔI HỌC - Buổi {$session->session_number}";
            $message .= "\n📅 " . \Carbon\Carbon::parse($session->scheduled_date)->format('d/m/Y');
            if ($session->lesson_title) {
                $message .= "\n📚 Bài học: {$session->lesson_title}";
            }
            $message .= "\n\n";

            foreach ($sessionComments as $comment) {
                $studentName = $comment->student->full_name ?? 'Unknown';
                $message .= "👤 {$studentName}\n";

                // Rating (stars)
                if ($comment->rating !== null) {
                    $fullStars = (int)$comment->rating;
                    $hasHalfStar = ($comment->rating - $fullStars) >= 0.5;
                    
                    $stars = str_repeat('⭐', $fullStars);
                    if ($hasHalfStar) {
                        $stars .= '½'; // half star symbol
                    }
                    $message .= "⭐ Đánh giá: {$stars} ({$comment->rating}/5)\n";
                }

                // Behavior & Participation on same line
                $evalDetails = [];
                if ($comment->behavior) {
                    $evalDetails[] = "Hành vi: {$comment->behavior}";
                }
                if ($comment->participation) {
                    $evalDetails[] = "Tham gia: {$comment->participation}";
                }
                if (!empty($evalDetails)) {
                    $message .= implode(' | ', $evalDetails) . "\n";
                }

                // Course Progress Stats
                // Attendance percentage
                $attendedCount = \App\Models\Attendance::where('student_id', $comment->student_id)
                    ->whereIn('session_id', $sessionIds)
                    ->where('status', 'present')
                    ->count();
                $attendancePercentage = $totalSessions > 0
                    ? round(($attendedCount / $totalSessions) * 100, 1)
                    : 0;

                // Homework completion rate
                $homeworkSubmittedCount = HomeworkSubmission::where('student_id', $comment->student_id)
                    ->whereIn('session_id', $sessionIds)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->count();
                $homeworkCompletionRate = $totalSessions > 0
                    ? round(($homeworkSubmittedCount / $totalSessions) * 100, 1)
                    : 0;

                // Homework quality - Get from attendance table
                $attendance = \App\Models\Attendance::where('session_id', $sessionId)
                    ->where('student_id', $comment->student_id)
                    ->first();
                $submission = $submissions->get($comment->student_id);
                $homeworkQuality = null;
                if ($submission && $attendance && $attendance->homework_score !== null) {
                    $lessonMaxPoints = $session->lesson_max_points ?? 100;
                    $homeworkQuality = $lessonMaxPoints > 0
                        ? round(($attendance->homework_score / $lessonMaxPoints) * 100, 1)
                        : 0;
                }

                // All stats on one line
                $message .= "📊 ĐD:{$attendancePercentage}% | BTVN:{$homeworkCompletionRate}%";
                if ($homeworkQuality !== null) {
                    $message .= " | CL:{$homeworkQuality}%";
                }
                $message .= "\n\n";
            }

            // Send to Zalo
            $zaloService = app(\App\Services\ZaloNotificationService::class);
            $result = $zaloService->sendToClassGroup($class->id, $message);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Evaluation notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send Zalo notification'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('[ZaloNotification] Error sending evaluation notification', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sending notification'
            ], 500);
        }
    }

    /**
     * Get Quick Attendance - Only check permission, not teacher role
     * For users with attendance.quick_mark permission
     */
    public function getQuickAttendance($sessionId)
    {
        try {
            Log::info('[QuickAttendance] Getting attendance for session', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Get all active students in class
            $students = \App\Models\Student::whereHas('classes', function($q) use ($class) {
                $q->where('classes.id', $class->id)
                  ->where('class_students.status', 'active');
            })->with('user')->get();

            // Get existing attendance records
            $attendances = \App\Models\Attendance::where('session_id', $sessionId)->get()->keyBy('student_id');

            $data = $students->map(function($student) use ($attendances) {
                $attendance = $attendances->get($student->user_id);

                return [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->full_name,
                    'student_code' => $student->student_code,
                    'status' => $attendance ? $attendance->status : null,
                    'check_in_time' => $attendance && $attendance->check_in_time
                        ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i:s')
                        : null,
                    'late_minutes' => $attendance ? $attendance->late_minutes : 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('[QuickAttendance] Error loading attendance', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading attendance'
            ], 500);
        }
    }

    /**
     * Save Quick Attendance - Only check permission, not teacher role
     */
    public function saveQuickAttendance(Request $request, $sessionId)
    {
        try {
            Log::info('[QuickAttendance] Saving attendance', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $attendanceData = $request->input('attendance', []);

            foreach ($attendanceData as $data) {
                if (!isset($data['user_id'])) {
                    continue;
                }

                $attendance = \App\Models\Attendance::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'student_id' => $data['user_id']
                    ],
                    [
                        'status' => $data['status'] ?? null,
                        'check_in_time' => $data['check_in_time'] ?? null,
                        'late_minutes' => $data['late_minutes'] ?? 0,
                    ]
                );

                Log::debug('[QuickAttendance] Updated attendance', [
                    'student_id' => $data['user_id'],
                    'status' => $data['status'],
                ]);
            }

            // Auto-update session status to 'completed' after saving attendance
            if ($session->status !== 'completed') {
                $session->update(['status' => 'completed']);
                
                // Recalculate completed sessions count for the class
                $class = $session->class;
                $completedCount = ClassLessonSession::where('class_id', $class->id)
                    ->where('status', 'completed')
                    ->count();
                $class->update(['completed_sessions' => $completedCount]);
                
                Log::info('[QuickAttendance] Session marked as completed', [
                    'session_id' => $sessionId,
                    'class_id' => $class->id,
                    'completed_count' => $completedCount,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[QuickAttendance] Error saving attendance', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving attendance'
            ], 500);
        }
    }

    /**
     * Get Quick Comments - Only check permission, not teacher role
     */
    public function getQuickComments($sessionId)
    {
        try {
            Log::info('[QuickComments] Getting comments for session', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            $session = ClassLessonSession::with('class')->findOrFail($sessionId);
            $class = $session->class;

            // Get all active students in class
            $students = \App\Models\Student::whereHas('classes', function($q) use ($class) {
                $q->where('classes.id', $class->id)
                  ->where('class_students.status', 'active');
            })->with('user')->get();

            // Get existing session comments
            $commentsQuery = \App\Models\SessionComment::where('session_id', $sessionId);
            
            // Log raw SQL
            Log::info('[QuickComments] SQL Query', [
                'sql' => $commentsQuery->toSql(),
                'bindings' => $commentsQuery->getBindings(),
            ]);
            
            $comments = $commentsQuery->get()->keyBy('student_id');
            
            Log::info('[QuickComments] Comments loaded from DB', [
                'count' => $comments->count(),
                'sample' => $comments->take(3)->map(fn($c) => [
                    'student_id' => $c->student_id,
                    'rating' => $c->rating,
                    'rating_raw' => $c->getAttributes()['rating'] ?? null,
                ]),
            ]);

            $data = $students->map(function($student) use ($comments) {
                $comment = $comments->get($student->user_id);
                $ratingValue = $comment ? $comment->rating : null;
                
                if ($comment && $comment->rating !== null) {
                    Log::info('[QuickComments] Loading rating', [
                        'user_id' => $student->user_id,
                        'rating_db' => $ratingValue,
                        'rating_type' => gettype($ratingValue),
                    ]);
                }

                return [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->full_name,
                    'student_code' => $student->student_code,
                    'comment' => $comment ? $comment->comment : null,
                    'rating' => $ratingValue,
                    'behavior' => $comment ? $comment->behavior : null,
                    'participation' => $comment ? $comment->participation : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('[QuickComments] Error loading comments', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading comments'
            ], 500);
        }
    }

    /**
     * Save Quick Comments - Only check permission, not teacher role
     */
    public function saveQuickComments(Request $request, $sessionId)
    {
        try {
            Log::info('[QuickComments] Saving comments', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            $session = ClassLessonSession::findOrFail($sessionId);
            $commentsData = $request->input('comments', []);

            $teacher_id = auth()->id();
            
            foreach ($commentsData as $data) {
                if (!isset($data['user_id'])) {
                    continue;
                }

                $ratingValue = $data['rating'] ?? null;
                
                // Force convert to float/string to preserve decimal
                if ($ratingValue !== null) {
                    $ratingValue = (float) $ratingValue;
                }
                
                Log::info('[QuickComments] Saving rating', [
                    'user_id' => $data['user_id'],
                    'rating_received' => $data['rating'] ?? null,
                    'rating_converted' => $ratingValue,
                    'rating_type' => gettype($ratingValue),
                ]);

                // Use raw SQL to ensure decimal is preserved
                $existing = \DB::table('session_comments')
                    ->where('session_id', $sessionId)
                    ->where('student_id', $data['user_id'])
                    ->first();
                
                $updateData = [
                    'teacher_id' => $teacher_id,
                    'comment' => $data['comment'] ?? '',
                    'rating' => $ratingValue,
                    'behavior' => $data['behavior'] ?? null,
                    'participation' => $data['participation'] ?? null,
                    'updated_at' => now(),
                ];
                
                if ($existing) {
                    \DB::table('session_comments')
                        ->where('id', $existing->id)
                        ->update($updateData);
                } else {
                    $updateData['session_id'] = $sessionId;
                    $updateData['student_id'] = $data['user_id'];
                    $updateData['created_at'] = now();
                    \DB::table('session_comments')->insert($updateData);
                }
                
                // Get raw value from DB
                $rawRating = \DB::table('session_comments')
                    ->where('session_id', $sessionId)
                    ->where('student_id', $data['user_id'])
                    ->value('rating');
                
                Log::info('[QuickComments] After save (raw SQL)', [
                    'user_id' => $data['user_id'],
                    'rating_raw_db' => $rawRating,
                    'rating_raw_type' => gettype($rawRating),
                ]);
            }

            Log::info('[QuickComments] Comments saved successfully', [
                'session_id' => $sessionId,
                'count' => count($commentsData),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comments saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[QuickComments] Error saving comments', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving comments'
            ], 500);
        }
    }
}

