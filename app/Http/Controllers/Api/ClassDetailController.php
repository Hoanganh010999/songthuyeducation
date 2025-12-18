<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassStudent;
use App\Models\Attendance;
use App\Models\HomeworkSubmission;
use App\Models\SessionComment;
use App\Models\ClassLessonSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClassDetailController extends Controller
{
    /**
     * Get class details with all relationships
     */
    public function show($id)
    {
        $class = ClassModel::with([
            'branch',
            'subject',
            'homeroomTeacher',
            'semester',
            'lessonPlan.sessions',
            'schedules.subject',
            'schedules.teacher',
            'schedules.room',
            'schedules.studyPeriod',
            'students.student',
            'lessonSessions.lessonPlanSession',
            'lessonSessions.classSchedule.teacher',
            'lessonSessions.classSchedule.room',
            'lessonSessions.attendances.student',
            'lessonSessions.homeworkSubmissions.student',
            'lessonSessions.sessionComments.student',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $class
        ]);
    }

    /**
     * Get weekly schedule for calendar view
     */
    public function getWeeklySchedule($id, Request $request)
    {
        $class = ClassModel::findOrFail($id);
        $weekStart = $request->input('week_start', Carbon::now()->startOfWeek());
        
        $schedules = $class->schedules()
            ->with(['subject', 'teacher', 'room', 'studyPeriod'])
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'success' => true,
            'data' => [
                'schedules' => $schedules,
                'week_start' => $weekStart
            ]
        ]);
    }

    /**
     * Get lesson sessions with attendance and homework data
     */
    public function getLessonSessions($id, Request $request)
    {
        $class = ClassModel::findOrFail($id);
        
        $sessions = $class->lessonSessions()
            ->with([
                'lessonPlanSession',
                'classSchedule.teacher',
                'attendances.student',
                'homeworkSubmissions.student',
                'sessionComments.student'
            ])
            ->withCount(['activeTrialStudents as trial_students_count'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get students list with statistics
     */
    public function getStudents($id)
    {
        $user = auth()->user();
        $class = ClassModel::findOrFail($id);
        
        // Authorization: Check if user has access to this class
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            // Check if user is teacher of this class
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();
            
            // Check if user is student in this class
            $isStudent = \App\Models\Student::where('user_id', $user->id)
                ->whereHas('classes', function($q) use ($class) {
                    $q->where('classes.id', $class->id)
                      ->where('class_students.status', 'active');
                })
                ->exists();
            
            // Check if user is parent of student in this class
            $isParent = \App\Models\ParentModel::where('user_id', $user->id)
                ->whereHas('students.classes', function($q) use ($class) {
                    $q->where('classes.id', $class->id)
                      ->where('class_students.status', 'active');
                })
                ->exists();
            
            if (!$isTeacher && !$isStudent && !$isParent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view students of this class.'
                ], 403);
            }
        }
        
        $students = $class->students()
            ->with(['student', 'student.user', 'student.wallet'])
            ->get()
            ->map(function ($classStudent) use ($class) {
                $student = $classStudent->student;
                
                if (!$student) {
                    return null;
                }
                
                // Calculate statistics
                $totalSessions = $class->lessonSessions()->where('status', 'completed')->count();
                
                // Use $classStudent->student_id which is actually user_id
                $attendances = Attendance::where('student_id', $classStudent->student_id)
                    ->whereIn('session_id', $class->lessonSessions()->pluck('id'))
                    ->get();
                
                $homeworks = HomeworkSubmission::where('student_id', $classStudent->student_id)
                    ->whereIn('session_id', $class->lessonSessions()->pluck('id'))
                    ->get();
                
                return [
                    'id' => $classStudent->id,
                    'student_id' => $student->id,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->name ?? $student->full_name ?? 'N/A',
                    'full_name' => $student->full_name ?? $student->user->name ?? 'N/A',
                    'student_code' => $student->student_code,
                    'english_name' => $student->user->english_name ?? null,
                    'user_account' => $student->user->email ?? null,
                    'user_email' => $student->user->email ?? null,
                    'enrollment_date' => $classStudent->enrollment_date,
                    'status' => $classStudent->status,
                    'discount_percent' => $classStudent->discount_percent,
                    'notes' => $classStudent->notes,
                    'user' => $student->user ? [
                        'id' => $student->user->id,
                        'name' => $student->user->name,
                        'email' => $student->user->email,
                        'english_name' => $student->user->english_name,
                    ] : null,
                    'statistics' => [
                        'total_sessions' => $totalSessions,
                        'present_count' => $attendances->where('status', 'present')->count(),
                        'absent_count' => $attendances->where('status', 'absent')->count(),
                        'late_count' => $attendances->where('status', 'late')->count(),
                        'absence_rate' => $totalSessions > 0 
                            ? round(($attendances->where('status', 'absent')->count() / $totalSessions) * 100, 2)
                            : 0,
                        'homework_submitted' => $homeworks->whereIn('status', ['submitted', 'graded'])->count(),
                        'homework_total' => $homeworks->count(),
                        'homework_completion_rate' => $homeworks->count() > 0
                            ? round(($homeworks->whereIn('status', ['submitted', 'graded'])->count() / $homeworks->count()) * 100, 2)
                            : 0,
                        'average_grade' => $homeworks->where('status', 'graded')->avg('grade') ?? 0,
                    ]
                ];
            })
            ->filter(); // Remove null values

        return response()->json([
            'success' => true,
            'data' => $students->values() // Re-index array
        ]);
    }

    /**
     * Get class overview/summary
     */
    public function getOverview($id)
    {
        $class = ClassModel::with(['students', 'lessonSessions'])->findOrFail($id);
        
        $totalSessions = $class->total_sessions ?? 0;
        $completedSessions = $class->lessonSessions()->where('status', 'completed')->count();
        $scheduledSessions = $class->lessonSessions()->where('status', 'scheduled')->count();
        $cancelledSessions = $class->lessonSessions()->where('status', 'cancelled')->count();
        $remainingSessions = max(0, $totalSessions - $completedSessions - $cancelledSessions);
        
        $activeStudents = $class->students()->where('status', 'active')->count();
        
        // Calculate expected revenue
        $expectedRevenue = 0;
        if ($class->hourly_rate) {
            $expectedRevenue = $activeStudents * $class->hourly_rate * $totalSessions;
            
            // Apply discounts
            foreach ($class->students as $student) {
                if ($student->discount_percent > 0) {
                    $discount = ($student->discount_percent / 100) * ($class->hourly_rate * $totalSessions);
                    $expectedRevenue -= $discount;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'scheduled_sessions' => $scheduledSessions,
                'cancelled_sessions' => $cancelledSessions,
                'remaining_sessions' => $remainingSessions,
                'progress_percentage' => $totalSessions > 0 
                    ? round(($completedSessions / $totalSessions) * 100, 2)
                    : 0,
                'active_students' => $activeStudents,
                'total_students' => $class->students()->count(),
                'capacity' => $class->capacity,
                'occupancy_rate' => $class->capacity > 0
                    ? round(($activeStudents / $class->capacity) * 100, 2)
                    : 0,
                'expected_revenue' => round($expectedRevenue, 2),
                'start_date' => $class->start_date,
                'end_date' => $class->end_date,
                'status' => $class->status,
            ]
        ]);
    }

    /**
     * Add student to class
     */
    public function addStudent(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'enrollment_date' => 'required|date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $class = ClassModel::with('students.student.user')->findOrFail($id);
        
        // Custom authorization: Admin or teacher of this class
        $user = $request->user();
        $canManage = $user->hasRole('admin') 
                  || $user->hasRole('superadmin')
                  || $user->hasPermission('classes.manage_students')
                  || $class->homeroom_teacher_id == $user->id
                  || $class->schedules()->where('teacher_id', $user->id)->exists();
        
        if (!$canManage) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to add students to this class'
            ], 403);
        }

        // Check if student is already in class
        $exists = $class->students()->where('student_id', $request->student_id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Student is already in this class'
            ], 400);
        }

        $classStudent = ClassStudent::create([
            'class_id' => $id,
            'student_id' => $request->student_id,
            'enrollment_date' => $request->enrollment_date,
            'discount_percent' => $request->discount_percent ?? 0,
            'notes' => $request->notes,
            'status' => 'active'
        ]);

        // Update current_students count
        $class->increment('current_students');
        
        // Handle Google Drive permissions and student folder
        try {
            $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
            if ($setting) {
                $service = new \App\Services\GoogleDriveService($setting);
                
                // Step 1: Ensure class folder exists, create if not
                if (!$class->google_drive_folder_id) {
                    Log::info('[ClassDetail] Class folder does not exist, creating...', [
                        'class_id' => $id,
                    ]);
                    
                    $result = $service->createEmptyClassFolder(
                        $class->name,
                        $class->code,
                        $class->id,
                        $class->branch_id
                    );
                    
                    $class->update([
                        'google_drive_folder_id' => $result['folder_id'],
                        'google_drive_folder_name' => $result['folder_name'],
                    ]);
                    
                    Log::info('[ClassDetail] Created class folder when adding student', [
                        'class_id' => $id,
                        'folder_id' => $result['folder_id'],
                    ]);
                } else {
                    Log::info('[ClassDetail] Class folder already exists', [
                        'class_id' => $id,
                        'folder_id' => $class->google_drive_folder_id,
                    ]);
                }
                
                // Step 2: Get user and student info
                $user = \App\Models\User::find($request->student_id);
                $student = \App\Models\Student::where('user_id', $request->student_id)->first();
                
                Log::info('[ClassDetail] Student info for folder creation', [
                    'user_found' => !!$user,
                    'user_name' => $user ? $user->name : null,
                    'user_google_email' => $user ? $user->google_email : null,
                    'student_found' => !!$student,
                    'student_code' => $student ? $student->student_code : null,
                ]);
                
                // Step 3: Create student folder (ALWAYS, even if no google_email)
                if ($user && $student) {
                    $studentFolderId = $service->createOrGetStudentFolderInClass(
                        $class->google_drive_folder_id,
                        $user->name,
                        $student->student_code
                    );
                    
                    Log::info('[ClassDetail] Student folder created/retrieved', [
                        'class_id' => $id,
                        'student_id' => $request->student_id,
                        'student_folder_id' => $studentFolderId,
                    ]);
                    
                    // Step 4: Grant permissions ONLY if google_email exists
                    if ($user->google_email) {
                        // Grant student reader permission to class folder
                        $service->shareFile($class->google_drive_folder_id, $user->google_email, 'reader');
                        
                        // Grant student writer permission to their own folder
                        $service->shareFile($studentFolderId, $user->google_email, 'writer');
                        
                        Log::info('[ClassDetail] Granted Google Drive permissions to student', [
                            'class_id' => $id,
                            'student_email' => $user->google_email,
                        ]);
                    } else {
                        Log::warning('[ClassDetail] Student has no google_email, folder created but no permissions granted', [
                            'class_id' => $id,
                            'student_id' => $request->student_id,
                        ]);
                    }
                } else {
                    Log::error('[ClassDetail] Cannot create student folder - user or student record not found', [
                        'class_id' => $id,
                        'student_id' => $request->student_id,
                        'user_found' => !!$user,
                        'student_found' => !!$student,
                    ]);
                }
            } else {
                Log::warning('[ClassDetail] No Google Drive settings found for branch', [
                    'class_id' => $id,
                    'branch_id' => $class->branch_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ClassDetail] Error handling Google Drive for new student', [
                'class_id' => $id,
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue anyway, don't block student enrollment
        }

        return response()->json([
            'success' => true,
            'message' => 'Student added successfully',
            'data' => $classStudent->load('student')
        ]);
    }

    /**
     * Update student in class
     */
    public function updateStudent(Request $request, $id, $studentId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,completed,dropped,transferred',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $classStudent = ClassStudent::where('class_id', $id)
            ->where('id', $studentId)
            ->firstOrFail();

        $classStudent->update($request->only(['status', 'discount_percent', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $classStudent->load('student')
        ]);
    }

    /**
     * Remove student from class
     */
    public function removeStudent($id, $studentId)
    {
        $class = ClassModel::findOrFail($id);
        
        $classStudent = ClassStudent::where('class_id', $id)
            ->where('id', $studentId)
            ->firstOrFail();
        
        // Handle Google Drive permission revocation
        if ($class->google_drive_folder_id) {
            try {
                // Get user (student_id in class_students is actually user_id)
                $user = \App\Models\User::find($classStudent->student_id);
                
                if ($user && $user->google_email) {
                    $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
                    if ($setting) {
                        $service = new \App\Services\GoogleDriveService($setting);
                        
                        // Revoke student's access to class folder
                        $service->revokePermission($class->google_drive_folder_id, $user->google_email);
                        
                        Log::info('[ClassDetail] Student removed from class, Google Drive permission revoked', [
                            'class_id' => $id,
                            'student_id' => $classStudent->student_id,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[ClassDetail] Error revoking Google Drive permission for removed student', [
                    'class_id' => $id,
                    'student_id' => $classStudent->student_id,
                    'error' => $e->getMessage(),
                ]);
                // Continue anyway, don't block student removal
            }
        }

        $classStudent->delete();

        // Update current_students count
        if ($class->current_students > 0) {
            $class->decrement('current_students');
        }

        return response()->json([
            'success' => true,
            'message' => 'Student removed successfully'
        ]);
    }

    /**
     * Mark attendance for a session
     */
    public function markAttendance(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.check_in_time' => 'nullable|date_format:H:i',
            'attendances.*.homework_score' => 'nullable|integer|min:1|max:10',
            'attendances.*.participation_score' => 'nullable|integer|min:1|max:5',
            'attendances.*.notes' => 'nullable|string',
            'attendances.*.evaluation_data' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $session = ClassLessonSession::findOrFail($sessionId);
        $markedBy = auth()->id();
        $savedAttendances = [];

        foreach ($request->attendances as $attendanceData) {
            $attendance = Attendance::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'student_id' => $attendanceData['student_id']
                ],
                [
                    'status' => $attendanceData['status'],
                    'check_in_time' => $attendanceData['check_in_time'] ?? null,
                    'homework_score' => $attendanceData['homework_score'] ?? null,
                    'participation_score' => $attendanceData['participation_score'] ?? null,
                    'notes' => $attendanceData['notes'] ?? null,
                    'evaluation_data' => $attendanceData['evaluation_data'] ?? null,
                    'marked_by' => $markedBy
                ]
            );
            
            $savedAttendances[] = $attendance;
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'data' => $savedAttendances
        ]);
    }

    /**
     * Submit or update homework
     */
    public function submitHomework(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'submission_link' => 'nullable|url',
            'status' => 'nullable|in:not_submitted,submitted,late,graded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $session = ClassLessonSession::findOrFail($sessionId);

        $homework = HomeworkSubmission::updateOrCreate(
            [
                'session_id' => $sessionId,
                'student_id' => $request->student_id
            ],
            [
                'status' => $request->status ?? 'submitted',
                'submission_link' => $request->submission_link,
                'submitted_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Homework submitted successfully',
            'data' => $homework
        ]);
    }

    /**
     * Grade homework
     */
    public function gradeHomework(Request $request, $homeworkId)
    {
        $validator = Validator::make($request->all(), [
            'grade' => 'required|integer|min:0|max:100',
            'teacher_feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $homework = HomeworkSubmission::findOrFail($homeworkId);
        
        $homework->update([
            'grade' => $request->grade,
            'teacher_feedback' => $request->teacher_feedback,
            'status' => 'graded',
            'graded_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Homework graded successfully',
            'data' => $homework
        ]);
    }

    /**
     * Add or update session comment
     */
    public function addSessionComment(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'comment' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'behavior' => 'nullable|in:excellent,good,average,needs_improvement',
            'participation' => 'nullable|in:active,moderate,passive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $session = ClassLessonSession::findOrFail($sessionId);

        $sessionComment = SessionComment::updateOrCreate(
            [
                'session_id' => $sessionId,
                'student_id' => $request->student_id,
                'teacher_id' => auth()->id()
            ],
            [
                'comment' => $request->comment,
                'rating' => $request->rating,
                'behavior' => $request->behavior,
                'participation' => $request->participation,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $sessionComment->load(['student', 'teacher'])
        ]);
    }

    /**
     * Get session details with attendance and comments
     */
    public function getSessionDetail($sessionId)
    {
        $session = ClassLessonSession::with([
            'lessonPlanSession',
            'classSchedule.teacher',
            'class.students.student',
            'attendances.student',
            'homeworkSubmissions.student',
            'sessionComments.student',
            'sessionComments.teacher',
            'valuationForm.fields'
        ])->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }
}
