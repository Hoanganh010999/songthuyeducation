<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkAssignment;
use App\Models\ClassLessonSession;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HomeworkSubmissionController extends Controller
{
    /**
     * Submit homework - handles both file uploads and JSON answer submissions
     */
    public function submit(Request $request, $homeworkId)
    {
        // Determine submission type based on request content
        $hasFile = $request->hasFile('file');
        $hasAnswers = $request->has('answers');

        if ($hasFile) {
            // File upload submission (old system)
            return $this->submitFile($request, $homeworkId);
        } elseif ($hasAnswers) {
            // JSON answer submission (new homework bank system)
            return $this->submitAnswers($request, $homeworkId);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Either file or answers must be provided'
            ], 422);
        }
    }

    /**
     * Submit homework by uploading file to student's folder (old system)
     */
    protected function submitFile(Request $request, $homeworkId)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'student_id' => 'nullable|integer|exists:users,id', // Optional: for parents submitting on behalf of children
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $studentUserId = $request->input('student_id'); // Optional: ID of student (for parents)

            // Determine who is actually submitting the homework
            $submittingUser = $user; // User who clicked submit (could be parent or student)
            $actualStudentUserId = $studentUserId ?? $user->id; // The student for whom homework is submitted

            Log::info('[HomeworkSubmission] Submit request', [
                'homework_id' => $homeworkId,
                'submitting_user_id' => $user->id,
                'submitting_user_name' => $user->name,
                'student_user_id' => $actualStudentUserId,
            ]);

            // If student_id is provided, validate parent-child relationship
            if ($studentUserId) {
                $parent = \App\Models\ParentModel::where('user_id', $user->id)->first();

                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only parents can submit homework on behalf of students'
                    ], 403);
                }

                // Verify this student is a child of this parent
                $isChild = $parent->students()
                    ->where('students.user_id', $studentUserId)
                    ->exists();

                if (!$isChild) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only submit homework for your own children'
                    ], 403);
                }
            }

            // Get homework assignment with class relationship
            $homework = HomeworkAssignment::with('class')->findOrFail($homeworkId);

            // Check if the STUDENT (not the parent) is assigned to this homework
            $isAssigned = $homework->isAssignedTo($actualStudentUserId);

            Log::info('[HomeworkSubmission] Assignment check', [
                'homework_id' => $homeworkId,
                'student_user_id' => $actualStudentUserId,
                'is_assigned' => $isAssigned,
                'homework_class_id' => $homework->class_id,
                'is_for_all' => $homework->isForAllStudents(),
            ]);

            if (!$isAssigned) {
                Log::warning('[HomeworkSubmission] Student not assigned to homework', [
                    'homework_id' => $homeworkId,
                    'student_user_id' => $actualStudentUserId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'This student is not assigned to this homework'
                ], 403);
            }

            // Get class from homework (not from session)
            $class = \App\Models\ClassModel::findOrFail($homework->class_id);

            // Get Google Drive settings
            $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive not configured for this branch'
                ], 400);
            }

            $service = new GoogleDriveService($setting);

            // Get student record for the ACTUAL STUDENT (not parent)
            $student = \App\Models\Student::where('user_id', $actualStudentUserId)->first();
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student record not found'
                ], 404);
            }

            // Get student user for name
            $studentUser = \App\Models\User::find($actualStudentUserId);
            if (!$studentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student user not found'
                ], 404);
            }

            // Ensure class folder exists
            if (!$class->google_drive_folder_id) {
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
            }

            // Ensure student folder exists - use STUDENT's name, not parent's name
            $studentFolderId = $service->createOrGetStudentFolderInClass(
                $class->google_drive_folder_id,
                $studentUser->name, // ✅ Use student's name
                $student->student_code
            );

            // 📁 Create or get Unit/Session folder inside student's folder
            $session = $homework->session; // Get lesson plan session
            $unitFolderName = $session ? "Session {$session->session_number} - {$session->lesson_title}" : "Homework {$homeworkId}";

            Log::info('[HomeworkSubmission] Creating/Getting unit folder', [
                'student_folder_id' => $studentFolderId,
                'unit_folder_name' => $unitFolderName,
            ]);

            $unitFolderId = $service->createOrGetFolderInParent(
                $studentFolderId,
                $unitFolderName,
                $class->branch_id
            );

            // Upload file to unit folder (not student folder directly)
            $file = $request->file('file');
            $uploadedFile = $service->uploadFile(
                $file,
                $unitFolderId, // ✅ Upload to unit folder instead
                $class->branch_id,
                $actualStudentUserId // ✅ Use student's user_id
            );

            // Get unit folder web link
            $unitFolderLink = $service->getFolderWebViewLink($unitFolderId);

            Log::info('[HomeworkSubmission] File uploaded to Google Drive', [
                'homework_id' => $homeworkId,
                'student_id' => $actualStudentUserId, // ✅ Use student's user_id
                'file_id' => $uploadedFile->google_id,
                'file_name' => $uploadedFile->name,
                'unit_folder_id' => $unitFolderId,
                'unit_folder_link' => $unitFolderLink,
            ]);

            // Find the corresponding class_lesson_session
            // homework_submissions.session_id must reference class_lesson_sessions.id
            $classLessonSession = \App\Models\ClassLessonSession::where('class_id', $homework->class_id)
                ->where('lesson_plan_session_id', $homework->lesson_plan_session_id)
                ->first();

            if (!$classLessonSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class lesson session not found for this homework'
                ], 404);
            }

            // Create or update submission record
            // Now using homework_assignment_id to uniquely identify which homework was submitted
            $submission = HomeworkSubmission::updateOrCreate(
                [
                    'homework_assignment_id' => $homeworkId,
                    'student_id' => $actualStudentUserId, // ✅ Use student's user_id
                ],
                [
                    'session_id' => $classLessonSession->id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'submission_link' => $uploadedFile->web_view_link,
                    'unit_folder_link' => $unitFolderLink, // ✅ Save unit folder link
                ]
            );

            Log::info('[HomeworkSubmission] Homework submitted', [
                'homework_id' => $homeworkId,
                'submission_id' => $submission->id,
                'student_id' => $actualStudentUserId, // ✅ Use student's user_id
            ]);

            // Send notification to Zalo group (use student's name, not parent's name)
            $this->sendSubmissionNotificationToZalo($homework, $studentUser, $submission);

            return response()->json([
                'success' => true,
                'message' => 'Homework submitted successfully',
                'data' => [
                    'submission' => $submission,
                    'file' => [
                        'id' => $uploadedFile->google_id,
                        'name' => $uploadedFile->name,
                        'webViewLink' => $uploadedFile->web_view_link,
                    ],
                    'unit_folder_link' => $unitFolderLink, // ✅ Return unit folder link to frontend
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error submitting homework', [
                'homework_id' => $homeworkId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Check for specific Google Drive errors
            $errorMessage = $e->getMessage();
            
            // Permission denied / Forbidden errors
            if (str_contains($errorMessage, 'forbidden') || 
                str_contains($errorMessage, '403') ||
                str_contains($errorMessage, 'permission') ||
                str_contains($errorMessage, 'insufficient') ||
                str_contains($errorMessage, 'The user does not have sufficient permissions')) {
                
                $user = $request->user();
                if (empty($user->google_email)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn chưa kết nối tài khoản Google Drive. Vui lòng liên hệ Admin để được hỗ trợ kết nối.',
                        'error_type' => 'google_not_connected'
                    ], 400);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không có quyền truy cập Google Drive. Vui lòng liên hệ Admin để kiểm tra quyền truy cập.',
                        'error_type' => 'google_permission_denied'
                    ], 403);
                }
            }
            
            // Generic error
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi nộp bài tập: ' . $errorMessage,
                'error_type' => 'unknown'
            ], 500);
        }
    }
    
    /**
     * Get submissions for a homework assignment
     */
    public function getSubmissions(Request $request, $homeworkId)
    {
        try {
            $user = $request->user();
            
            // Get homework assignment
            // Get homework assignment - handle both homework_assignment ID and homework_bank ID
            $homework = HomeworkAssignment::with('class')->find($homeworkId);
            
            // If not found, check if it's a homework_bank ID
            if (!$homework) {
                // Find homework_assignment that contains this homework_bank ID in hw_ids
                $homework = HomeworkAssignment::with('class')
                    ->whereRaw("JSON_CONTAINS(hw_ids, ?)", [json_encode([intval($homeworkId)])])
                    ->first();
                
                if (!$homework) {
                    Log::error('[HomeworkSubmission] No homework assignment found', [
                        'homework_id' => $homeworkId,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Homework assignment not found'
                    ], 404);
                }
            }
            $class = $homework->class;
            
            // Check authorization - allow everyone in the class to view submissions
            $isInClass = \App\Models\ClassStudent::where('class_id', $homework->class_id)
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->exists();
            
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();
            
            $hasAccess = $user->hasRole('admin') 
                || $user->hasRole('superadmin') 
                || $isTeacher 
                || $isInClass;
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            // Get all submissions for this specific homework assignment
            // Get all submissions for this specific homework assignment
            $submissions = HomeworkSubmission::where('homework_assignment_id', $homework->id)
                ->with('student')
                ->orderBy('submitted_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $submissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error getting submissions', [
                'homework_id' => $homeworkId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading submissions'
            ], 500);
        }
    }

    /**
     * Get student's own submission with answers
     */
    public function getMySubmission(Request $request, $homeworkId)
    {
        try {
            $user = $request->user();
            $studentId = $request->input('student_id', $user->id);

            Log::info('[HomeworkSubmission] Getting submission', [
                'homework_id' => $homeworkId,
                'student_id' => $studentId,
            ]);

            // Get submission with answers and exercise options
            $submission = HomeworkSubmission::where('homework_assignment_id', $homeworkId)
                ->where('student_id', $studentId)
                ->with(['answers.exercise.options'])
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No submission found'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $submission
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error getting my submission', [
                'homework_id' => $homeworkId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading submission'
            ], 500);
        }
    }
    /**
     * Send submission notification to Zalo group
     */
    protected function sendSubmissionNotificationToZalo($homework, $user, $submission)
    {
        try {
            $class = $homework->class;

            // Check if class has Zalo group configured
            if (empty($class->zalo_group_id)) {
                Log::info('[HomeworkSubmission] Class does not have Zalo group configured, skipping notification');
                return;
            }

            // Get primary Zalo account
            $primaryAccount = \App\Models\ZaloAccount::where('is_primary', true)
                ->where('is_connected', true)
                ->first();

            if (!$primaryAccount) {
                Log::warning('[HomeworkSubmission] No primary Zalo account found');
                return;
            }

            // Verify the group exists in our database
            $zaloGroup = \App\Models\ZaloGroup::where('zalo_account_id', $primaryAccount->id)
                ->where('zalo_group_id', $class->zalo_group_id)
                ->first();

            if (!$zaloGroup) {
                Log::warning('[HomeworkSubmission] Zalo group not found in database');
                return;
            }

            // Format submission notification message
            $message = $this->formatSubmissionMessage($homework, $user, $submission);

            // Send message to group
            $zaloService = new \App\Services\ZaloNotificationService();
            $result = $zaloService->sendMessage(
                $class->zalo_group_id,  // to (recipient_id)
                $message,                // message content
                'group',                 // type
                $primaryAccount->id      // accountId
            );

            if ($result['success']) {
                Log::info('[HomeworkSubmission] Notification sent to Zalo group', [
                    'homework_id' => $homework->id,
                    'student_id' => $user->id,
                    'group_id' => $class->zalo_group_id,
                ]);
            } else {
                Log::warning('[HomeworkSubmission] Failed to send notification to Zalo', [
                    'homework_id' => $homework->id,
                    'result' => $result,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Exception while sending Zalo notification', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail submission if Zalo notification fails
        }
    }

    /**
     * Format submission notification message
     */
    protected function formatSubmissionMessage($homework, $user, $submission)
    {
        $message = "✅ BÀI TẬP ĐÃ NỘP\n\n";

        $message .= "👨‍🎓 Học viên: {$user->name}\n";
        $message .= "📚 Bài tập: {$homework->title}\n";

        // Get configured timezone from system settings
        $timezone = $this->getConfiguredTimezone();

        // Submission time (convert to configured timezone)
        $submittedAt = \Carbon\Carbon::parse($submission->submitted_at)->timezone($timezone);
        $message .= "⏰ Thời gian nộp: {$submittedAt->format('d/m/Y H:i')}\n";

        // Check if on time or late
        if ($homework->deadline) {
            $deadline = \Carbon\Carbon::parse($homework->deadline);

            if ($submittedAt->lte($deadline)) {
                $message .= "📌 Trạng thái: ✨ Đúng hạn\n\n";
            } else {
                // Calculate how late
                $diff = $submittedAt->diff($deadline);

                if ($diff->d > 0) {
                    $lateText = "Quá hạn {$diff->d} ngày";
                    if ($diff->h > 0) {
                        $lateText .= " {$diff->h} giờ";
                    }
                } elseif ($diff->h > 0) {
                    $lateText = "Quá hạn {$diff->h} giờ";
                    if ($diff->i > 0) {
                        $lateText .= " {$diff->i} phút";
                    }
                } else {
                    $lateText = "Quá hạn {$diff->i} phút";
                }

                $message .= "📌 Trạng thái: ⏰ {$lateText}\n\n";
            }
        } else {
            $message .= "\n";
        }

        $message .= "Cảm ơn em đã hoàn thành bài tập! 🎉\n";
        $message .= "Giáo viên sẽ feedback sớm nhất có thể. 📝";

        return $message;
    }

    /**
     * Get configured timezone from system settings
     */
    protected function getConfiguredTimezone(): string
    {
        try {
            $timezone = \DB::table('settings')->where('key', 'timezone')->value('value');
            return $timezone ?? 'Asia/Ho_Chi_Minh'; // Default to Vietnam timezone
        } catch (\Exception $e) {
            \Log::warning('Failed to get timezone from settings, using default', [
                'error' => $e->getMessage()
            ]);
            return 'Asia/Ho_Chi_Minh'; // Default fallback
        }
    }

    /**
     * Submit homework answers (new homework bank system)
     */
    protected function submitAnswers(Request $request, $homeworkId)
    {
        // Log incoming request data for debugging
        Log::info('[HomeworkSubmission] Incoming request data', [
            'homework_id' => $homeworkId,
            'request_data' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'homework_assignment_id' => 'required|integer|exists:homework_assignments,id',
            'student_id' => 'required|integer|exists:users,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:homework_exercises,id',
            'answers.*.text_answer' => 'nullable|string',
            'answers.*.selected_answer' => 'nullable',
            'submitted_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            Log::error('[HomeworkSubmission] Validation failed', [
                'homework_id' => $homeworkId,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $studentId = $request->input('student_id');
            $answers = $request->input('answers');

            Log::info('[HomeworkSubmission] Submit answers request', [
                'homework_id' => $homeworkId,
                'user_id' => $user->id,
                'student_id' => $studentId,
                'answers_count' => count($answers),
            ]);

            // Get homework assignment
            $homework = HomeworkAssignment::findOrFail($homeworkId);

            // Get session_id: Find class_lesson_session from homework_bank
            $sessionId = null;
            if (!empty($homework->hw_ids)) {
                $hwIds = is_string($homework->hw_ids) ? json_decode($homework->hw_ids, true) : $homework->hw_ids;
                if (is_array($hwIds) && count($hwIds) > 0) {
                    // Get first homework bank to find lesson_plan_session_id
                    $homeworkBank = \App\Models\HomeworkBank::find($hwIds[0]);
                    if ($homeworkBank && $homeworkBank->lesson_plan_session_id) {
                        // Find corresponding class_lesson_session
                        $classLessonSession = \App\Models\ClassLessonSession::where('class_id', $homework->class_id)
                            ->where('lesson_plan_session_id', $homeworkBank->lesson_plan_session_id)
                            ->first();
                        if ($classLessonSession) {
                            $sessionId = $classLessonSession->id;
                        }
                    }
                }
            }

            // Validate that we have a session_id
            if (!$sessionId) {
                Log::error('[HomeworkSubmission] Could not determine session_id', [
                    'homework_id' => $homeworkId,
                    'class_id' => $homework->class_id,
                    'hw_ids' => $homework->hw_ids,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Could not determine session for this homework assignment'
                ], 500);
            }

            // Create or update submission record
            $submission = HomeworkSubmission::updateOrCreate(
                [
                    'homework_assignment_id' => $homeworkId,
                    'student_id' => $studentId,
                ],
                [
                    'session_id' => $sessionId,
                    'status' => 'submitted',
                    'submitted_at' => $request->input('submitted_at') ?? now(),
                ]
            );

            // Save individual answers
            foreach ($answers as $answerData) {
                $exerciseId = $answerData['question_id'];

                // Get exercise to determine points_possible
                $exercise = \App\Models\HomeworkExercise::find($exerciseId);
                $pointsPossible = $exercise ? $exercise->points : 0;

                \App\Models\HomeworkSubmissionAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'exercise_id' => $exerciseId,
                    ],
                    [
                        'answer' => isset($answerData['selected_answer']) ? json_encode($answerData['selected_answer']) : null,
                        'answer_text' => $answerData['text_answer'] ?? null,
                        'points_possible' => $pointsPossible,
                        'attempt_count' => \DB::raw('attempt_count + 1'),
                    ]
                );
            }

            Log::info('[HomeworkSubmission] Answers submitted successfully', [
                'submission_id' => $submission->id,
                'homework_id' => $homeworkId,
                'student_id' => $studentId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Homework submitted successfully',
                'data' => [
                    'submission' => $submission,
                    'answers_count' => count($answers),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error submitting answers', [
                'homework_id' => $homeworkId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error submitting homework: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get submission answers with exercises for grading
     */
    public function getSubmissionAnswers(Request $request, $submissionId)
    {
        try {
            $user = $request->user();

            // Get submission
            $submission = HomeworkSubmission::with(['student', 'homeworkAssignment.class'])->findOrFail($submissionId);

            // Check authorization - only teacher/admin can grade
            $class = $submission->homeworkAssignment->class;
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();

            if (!($user->hasRole('admin') || $user->hasRole('superadmin')) && !$isTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Get answers with exercises and options
            $answers = $submission->answers()->with('exercise.options')->get();

            return response()->json([
                'success' => true,
                'submission' => $submission,
                'data' => $answers
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error getting submission answers', [
                'submission_id' => $submissionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading answers'
            ], 500);
        }
    }

    /**
     * Mark an answer as correct/incorrect
     */
    public function gradeAnswer(Request $request, $submissionId, $answerId)
    {
        try {
            Log::info('[HomeworkSubmission] 🎯 gradeAnswer called', [
                'submission_id' => $submissionId,
                'answer_id' => $answerId,
                'has_annotations' => $request->has('annotations'),
                'request_data' => $request->all()
            ]);

            $user = $request->user();

            // Get submission
            $submission = HomeworkSubmission::with('homeworkAssignment.class')->findOrFail($submissionId);

            // Check authorization
            $class = $submission->homeworkAssignment->class;
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();

            if (!($user->hasRole('admin') || $user->hasRole('superadmin')) && !$isTeacher) {
                Log::warning('[HomeworkSubmission] ❌ Unauthorized grading attempt', [
                    'user_id' => $user->id,
                    'submission_id' => $submissionId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Get answer
            $answer = \App\Models\HomeworkSubmissionAnswer::where('submission_id', $submissionId)
                ->where('id', $answerId)
                ->firstOrFail();

            Log::info('[HomeworkSubmission] 📝 Current answer state', [
                'answer_id' => $answer->id,
                'current_is_correct' => $answer->is_correct,
                'current_annotations' => $answer->annotations,
                'new_is_correct' => $request->input('is_correct'),
                'new_annotations' => $request->input('annotations')
            ]);

            // Update is_correct
            $answer->is_correct = $request->input('is_correct');

            // Update annotations if provided (for essay questions)
            if ($request->has('annotations')) {
                $annotations = $request->input('annotations');
                Log::info('[HomeworkSubmission] 📊 Setting annotations', [
                    'answer_id' => $answer->id,
                    'annotations_count' => is_array($annotations) ? count($annotations) : 0,
                    'annotations_data' => $annotations
                ]);
                $answer->annotations = $annotations;
            }

            // Update grading notes if provided
            if ($request->has('grading_notes')) {
                $answer->grading_notes = $request->input('grading_notes');
            }

            // Update AI feedback if provided
            if ($request->has('auto_feedback')) {
                $answer->auto_feedback = $request->input('auto_feedback');
                Log::info('[HomeworkSubmission] 💬 Setting auto_feedback', [
                    'answer_id' => $answer->id,
                    'feedback_length' => strlen($request->input('auto_feedback'))
                ]);
            }

            $answer->save();

            Log::info('[HomeworkSubmission] ✅ Answer graded successfully', [
                'answer_id' => $answer->id,
                'is_correct' => $answer->is_correct,
                'annotations' => $answer->annotations
            ]);

            return response()->json([
                'success' => true,
                'data' => $answer
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] ❌ Error grading answer', [
                'submission_id' => $submissionId,
                'answer_id' => $answerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error grading answer'
            ], 500);
        }
    }

    /**
     * Save overall grade and feedback for submission
     */
    public function gradeSubmission(Request $request, $submissionId)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'grade' => 'required|numeric|min:0|max:100',
                'teacher_feedback' => 'nullable|string'
            ]);

            // Get submission
            $submission = HomeworkSubmission::with('homeworkAssignment.class')->findOrFail($submissionId);

            // Check authorization
            $class = $submission->homeworkAssignment->class;
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();

            if (!($user->hasRole('admin') || $user->hasRole('superadmin')) && !$isTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Update submission
            $submission->grade = $validated['grade'];
            $submission->teacher_feedback = $validated['teacher_feedback'] ?? null;
            $submission->graded_by = $user->id;
            $submission->status = 'graded';
            $submission->save();

            Log::info('[HomeworkSubmission] Graded submission', [
                'submission_id' => $submissionId,
                'grade' => $validated['grade'],
                'graded_by' => $user->name,
            ]);

            return response()->json([
                'success' => true,
                'data' => $submission->load('student', 'gradedBy')
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] Error grading submission', [
                'submission_id' => $submissionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving grade'
            ], 500);
        }
    }

    /**
     * Grade essay with AI
     */
    public function gradeEssayWithAI(Request $request)
    {
        try {
            $validated = $request->validate([
                'essay_text' => 'required|string|min:10',
                'question' => 'nullable|string',
                'provider' => 'required|string|in:openai,anthropic,azure',
                'model' => 'required|string',
            ]);

            Log::info('[HomeworkSubmission] 🤖 AI Essay Grading Request', [
                'essay_length' => strlen($validated['essay_text']),
                'has_question' => !empty($validated['question']),
                'provider' => $validated['provider'],
                'model' => $validated['model']
            ]);

            // Get AI settings from quality_management module for specific provider
            $branchId = $request->header('X-Branch-Id') ?? auth()->user()->branch_id ?? 0;
            $aiSettings = \App\Models\AiSetting::getSettingsByProvider($branchId, 'quality_management', $validated['provider']);

            // ✅ FALLBACK: If not found in quality_management, try examination_grading
            if (!$aiSettings || !$aiSettings->is_active) {
                Log::info('[HomeworkSubmission] Quality management AI not found, trying examination_grading fallback', [
                    'branch_id' => $branchId,
                    'provider' => $validated['provider']
                ]);
                $aiSettings = \App\Models\AiSetting::getSettingsByProvider($branchId, 'examination_grading', $validated['provider']);
            }

            if (!$aiSettings || !$aiSettings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => "AI provider '{$validated['provider']}' is not configured. Please configure AI settings in Quality Management or Examination module."
                ], 400);
            }
            
            Log::info('[HomeworkSubmission] Using AI settings', [
                'module' => $aiSettings->module,
                'provider' => $aiSettings->provider,
                'model' => $aiSettings->model
            ]);

            // Build prompt for AI
            $systemPrompt = $this->getEssayGradingPrompt();
            $userPrompt = $this->buildEssayGradingUserPrompt($validated['essay_text'], $validated['question']);

            // Call AI API
            $aiResponse = $this->callAI(
                $validated['provider'],
                $aiSettings->api_key,
                $validated['model'],
                $systemPrompt,
                $userPrompt,
                3000
            );

            Log::info('[HomeworkSubmission] 🤖 AI Response received', [
                'response_length' => strlen($aiResponse)
            ]);

            // Parse AI response
            $gradingResult = $this->parseEssayGradingResponse($aiResponse, $validated['essay_text']);

            Log::info('[HomeworkSubmission] ✅ AI Grading completed', [
                'annotations_count' => count($gradingResult['annotations']),
                'has_feedback' => !empty($gradingResult['feedback']),
                'score' => $gradingResult['score'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $gradingResult
            ]);

        } catch (\Exception $e) {
            Log::error('[HomeworkSubmission] ❌ Error in AI essay grading', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error grading essay with AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get essay grading prompt
     */
    private function getEssayGradingPrompt()
    {
        return <<<'PROMPT'
You are an expert English teacher grading student essays. Your task is to:

1. Identify grammar errors, vocabulary mistakes, and writing issues (type: "error")
2. Mark text that should be deleted - unnecessary or incorrect text (type: "delete")
3. Identify missing words or phrases that should be added (type: "missing")
4. Provide constructive feedback in Vietnamese

CRITICAL: You MUST respond with ONLY valid JSON in this exact format (no markdown, no code blocks):

{
  "annotations": [
    {
      "type": "error",
      "text": "the exact text from essay with error",
      "comment": "Giải thích lỗi bằng tiếng Việt",
      "errorCode": "T",
      "color": "#3498db",
      "position": 0
    },
    {
      "type": "delete",
      "text": "redundant text to be deleted",
      "comment": "Giải thích tại sao nên xóa (bằng tiếng Việt)",
      "position": 50
    },
    {
      "type": "missing",
      "text": "two words before",
      "suggestion": "the",
      "position": 100
    }
  ],
  "feedback": "Nhận xét tổng quan bằng tiếng Việt về chất lượng bài viết, điểm mạnh, điểm yếu và hướng cải thiện",
  "score": 75
}

ANNOTATION TYPES EXPLAINED:

TYPE 1: "error" - Highlight text with error (will show colored underline)
- "text": exact text from essay that has the error
- "comment": explanation in Vietnamese
- "errorCode": one of the codes below (WW, T, SP, etc.)
- "color": use the color specified for each error code
- "position": character index where the error text starts (0-based)

TYPE 2: "delete" - Mark text to be deleted (will show strikethrough)
- "text": exact text that should be removed
- "comment": reason why it should be deleted (in Vietnamese)
- "position": character index where the text starts (0-based)
- Note: Frontend will display this with red strikethrough

TYPE 3: "missing" - Indicate missing word/phrase (will show insertion point)
- "text": the TWO WORDS BEFORE the gap where word is missing
- "suggestion": the word/phrase that should be added
- "position": character index of the TWO WORDS BEFORE the gap
- Example: Essay has "I to school" → "text": "I to", "suggestion": "go", "position": (index of "I")
- Frontend will show insertion marker after these two words

ERROR CODES (Từ vựng - Vocabulary):
- WW: Wrong word / Dùng sai từ (color: #e74c3c - red)
- WF: Wrong form / Sai dạng từ (color: #e74c3c - red)
- WC: Word choice not suitable / Chọn từ chưa phù hợp (color: #e74c3c - red)
- REP: Repetition / Lặp từ (color: #f39c12 - orange)
- COL: Unnatural collocation / Kết hợp từ không tự nhiên (color: #e74c3c - red)
- IDM: Wrong idiom usage / Dùng idiom sai/chưa tự nhiên (color: #e74c3c - red)

ERROR CODES (Ngữ pháp - Grammar):
- WO: Word order / Sai trật tự từ (color: #3498db - blue)
- T: Tense / Sai thì (color: #3498db - blue)
- SV: Subject-verb agreement / Sự hòa hợp chủ ngữ-động từ (color: #3498db - blue)
- ART: Articles (a/an/the) / Mạo từ (color: #3498db - blue)
- PREP: Preposition / Giới từ (color: #3498db - blue)
- PL: Plural/Singular / Số ít/số nhiều (color: #3498db - blue)
- PASS: Passive voice / Câu bị động (color: #3498db - blue)
- MOD: Modal verbs / Động từ khuyết thiếu (color: #3498db - blue)
- PR: Pronoun / Đại từ (color: #3498db - blue)
- IF: Conditional / Câu điều kiện (color: #3498db - blue)

ERROR CODES (Chính tả & Hình thức - Spelling & Format):
- SP: Spelling / Chính tả (color: #9b59b6 - purple)
- CAP: Capitalization / Viết hoa (color: #9b59b6 - purple)
- PUNC: Punctuation / Dấu câu (color: #9b59b6 - purple)
- FORM: Informal style / Không phù hợp văn phong học thuật (color: #9b59b6 - purple)

ERROR CODES (Cấu & Diễn đạt - Structure & Expression):
- RO: Run-on sentence / Câu quá dài, nối sai (color: #f39c12 - orange)
- FS: Fragment sentence / Câu thiếu thành phần (color: #f39c12 - orange)
- AWK: Awkward expression / Diễn đạt gượng, không tự nhiên (color: #f39c12 - orange)
- CL: Unclear / Chưa rõ ý (color: #f39c12 - orange)
- PAR: Paraphrasing issue / Diễn đạt lại chưa tốt (color: #f39c12 - orange)

ERROR CODES (Mạch lạc & Phát triển - Coherence & Development):
- TR: Not answering the question / Chưa trả lời đúng yêu cầu đề (color: #16a085 - teal)
- DEV: Underdeveloped idea / Ý chưa được phát triển (color: #16a085 - teal)
- COH: Lack of coherence / Thiếu mạch lạc (color: #16a085 - teal)
- CC: Connectors / Từ nối (color: #16a085 - teal)
- EX: Lack of examples / Thiếu ví dụ (color: #16a085 - teal)

IMPORTANT RULES:
1. "position" is the character index (0-based) where the text starts in the essay
2. "text" MUST be EXACT match from essay (copy exactly, including spaces/punctuation)
3. For "error" type: include errorCode and color from the list above
4. For "delete" type: mark redundant/incorrect text that should be removed
5. For "missing" type: "text" = 2 words BEFORE gap, "suggestion" = missing word(s)
6. All "comment" fields must be in Vietnamese
7. Score is 0-100 (0=very poor, 100=perfect)
8. Return ONLY valid JSON, no markdown blocks, no explanations

EXAMPLES:

Example Essay: "I go to school yesterday. The weather is very very hot."

Correct Annotations:
[
  {
    "type": "error",
    "text": "go",
    "comment": "Sai thì - phải dùng quá khứ 'went' vì có 'yesterday'",
    "errorCode": "T",
    "color": "#3498db",
    "position": 2
  },
  {
    "type": "error",
    "text": "is",
    "comment": "Sai thì - phải dùng 'was' vì nói về quá khứ",
    "errorCode": "T",
    "color": "#3498db",
    "position": 35
  },
  {
    "type": "delete",
    "text": "very ",
    "comment": "Lặp từ - chỉ cần một 'very' là đủ",
    "position": 48
  }
]

Example 2: "She wants go home."

Correct Annotation for missing word:
{
  "type": "missing",
  "text": "wants go",
  "suggestion": "to",
  "position": 4
}
(Frontend will show insertion marker: "wants [+to] go")

NOW GRADE THE ESSAY BELOW:
PROMPT;
    }

    /**
     * Build user prompt for essay grading
     */
    private function buildEssayGradingUserPrompt($essayText, $question)
    {
        $prompt = "Grade this student essay:\n\n";

        if ($question) {
            $prompt .= "QUESTION: {$question}\n\n";
        }

        $prompt .= "ESSAY:\n{$essayText}\n\n";
        $prompt .= "Provide detailed grading with annotations in the required JSON format.";

        return $prompt;
    }

    /**
     * Parse AI response for essay grading
     */
    public function parseEssayGradingResponse($response, $essayText)
    {
        Log::info('[HomeworkSubmission] 🔍 Parsing AI response', [
            'response_length' => strlen($response),
            'response_preview' => substr($response, 0, 500)
        ]);

        // Step 1: Remove markdown code blocks
        $cleaned = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', $response);
        $cleaned = trim($cleaned);

        // Step 2: Try multiple strategies to extract JSON
        $jsonString = null;
        
        // Strategy 1: Direct JSON extraction (most common)
        if (preg_match('/\{[\s\S]*\}/', $cleaned, $jsonMatch)) {
            $jsonString = $jsonMatch[0];
        }
        
        // Strategy 2: Look for JSON with annotations marker
        if (!$jsonString && preg_match('/\{["\']annotations["\'][\s\S]*\}/', $cleaned, $jsonMatch)) {
            $jsonString = $jsonMatch[0];
        }
        
        // Strategy 3: Extract largest JSON object if multiple exist
        if (!$jsonString) {
            preg_match_all('/\{[^\{\}]*(?:\{[^\{\}]*\}[^\{\}]*)*\}/s', $cleaned, $allMatches);
            if (!empty($allMatches[0])) {
                // Find the longest JSON string (likely the complete one)
                $jsonString = collect($allMatches[0])->sortByDesc(function($item) {
                    return strlen($item);
                })->first();
            }
        }

        if (!$jsonString) {
            Log::error('[HomeworkSubmission] ❌ No JSON found in content', [
                'cleaned_content' => substr($cleaned, 0, 500)
            ]);
            throw new \Exception('Could not extract JSON from AI response');
        }

        // Fix potential encoding issues
        $jsonString = mb_convert_encoding($jsonString, 'UTF-8', 'UTF-8');
        
        Log::info('[HomeworkSubmission] 🧹 Cleaned JSON', [
            'cleaned_length' => strlen($jsonString),
            'cleaned_preview' => substr($jsonString, 0, 500)
        ]);

        // Try to parse JSON
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[HomeworkSubmission] ❌ JSON decode failed', [
                'error' => json_last_error_msg(),
                'json' => substr($jsonString, 0, 500)
            ]);
            
            // Try to fix common JSON issues
            $fixedJson = $this->tryFixJsonIssuesForEssay($jsonString);
            $data = json_decode($fixedJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON from AI: ' . json_last_error_msg());
            }
        }

        Log::info('[HomeworkSubmission] ✅ JSON parsed successfully', [
            'has_annotations' => isset($data['annotations']),
            'annotations_count' => count($data['annotations'] ?? []),
            'has_feedback' => isset($data['feedback']),
            'has_score' => isset($data['score'])
        ]);

        // Validate and clean annotations
        $annotations = $data['annotations'] ?? [];
        $validAnnotations = [];
        $rejectedCount = 0;

        Log::info('[HomeworkSubmission] 📊 Validating annotations', [
            'total_annotations' => count($annotations),
            'essay_length' => strlen($essayText),
            'essay_preview' => substr($essayText, 0, 200)
        ]);

        foreach ($annotations as $index => $annotation) {
            // Verify the text exists in essay at the given position
            if (isset($annotation['position']) && isset($annotation['text'])) {
                $annotationText = $annotation['text'];
                
                // Clean annotation text - remove leading/trailing "..." that AI might add
                $cleanedText = preg_replace('/^\.{3,}\s*/', '', $annotationText);
                $cleanedText = preg_replace('/\s*\.{3,}$/', '', $cleanedText);
                
                $textAtPosition = substr($essayText, $annotation['position'], strlen($cleanedText));

                // Try exact match with cleaned text
                if ($textAtPosition === $cleanedText) {
                    $annotation['text'] = $cleanedText; // Use cleaned text
                    $validAnnotations[] = $annotation;
                    Log::info("[HomeworkSubmission] ✅ Annotation #{$index} valid (exact match)", [
                        'type' => $annotation['type'],
                        'text' => substr($cleanedText, 0, 50),
                        'position' => $annotation['position']
                    ]);
                } else {
                    // Try to find the cleaned text elsewhere
                    $foundPosition = strpos($essayText, $cleanedText);
                    if ($foundPosition !== false) {
                        $annotation['position'] = $foundPosition;
                        $annotation['text'] = $cleanedText;
                        $validAnnotations[] = $annotation;
                        Log::info("[HomeworkSubmission] ✅ Annotation #{$index} valid (corrected position)", [
                            'type' => $annotation['type'],
                            'text' => substr($cleanedText, 0, 50),
                            'original_text' => substr($annotationText, 0, 50),
                            'old_position' => $annotation['position'],
                            'new_position' => $foundPosition
                        ]);
                    } else {
                        // Try fuzzy match - check if text is substring
                        $fuzzyPosition = stripos($essayText, $cleanedText);
                        if ($fuzzyPosition !== false) {
                            $annotation['position'] = $fuzzyPosition;
                            $annotation['text'] = substr($essayText, $fuzzyPosition, strlen($cleanedText));
                            $validAnnotations[] = $annotation;
                            Log::info("[HomeworkSubmission] ✅ Annotation #{$index} valid (fuzzy match)", [
                                'type' => $annotation['type'],
                                'search_text' => substr($cleanedText, 0, 50),
                                'found_text' => substr($annotation['text'], 0, 50),
                                'position' => $fuzzyPosition
                            ]);
                        } else {
                            $rejectedCount++;
                            Log::warning("[HomeworkSubmission] ❌ Annotation #{$index} rejected (text not found)", [
                                'type' => $annotation['type'],
                                'original_text' => substr($annotationText, 0, 100),
                                'cleaned_text' => substr($cleanedText, 0, 100),
                                'position' => $annotation['position'],
                                'text_at_position' => substr($textAtPosition, 0, 100)
                            ]);
                        }
                    }
                }
            } else {
                $rejectedCount++;
                Log::warning("[HomeworkSubmission] ❌ Annotation #{$index} rejected (missing position or text)", [
                    'has_position' => isset($annotation['position']),
                    'has_text' => isset($annotation['text']),
                    'annotation' => $annotation
                ]);
            }
        }

        Log::info('[HomeworkSubmission] 📊 Validation complete', [
            'valid_annotations' => count($validAnnotations),
            'rejected_annotations' => $rejectedCount
        ]);

        return [
            'annotations' => $validAnnotations,
            'feedback' => $data['feedback'] ?? '',
            'score' => $data['score'] ?? null
        ];
    }

    /**
     * Try to fix common JSON issues
     */
    private function tryFixJsonIssuesForEssay(string $jsonString): string
    {
        Log::info('[HomeworkSubmission] 🔧 Attempting to fix JSON issues');
        
        $fixed = $jsonString;
        
        // Fix 1: Remove trailing commas before closing braces/brackets
        $fixed = preg_replace('/,\s*(\}|\])/', '$1', $fixed);
        
        // Fix 2: Add missing commas between fields
        $fixed = preg_replace('/"\s*\n\s*"/', '",\n"', $fixed);
        
        // Fix 3: Remove any trailing commas
        $fixed = preg_replace('/,\s*$/', '', $fixed);
        
        Log::info('[HomeworkSubmission] 🔧 JSON fix applied', [
            'changes_made' => $fixed !== $jsonString
        ]);
        
        return $fixed;
    }

    /**
     * Call AI API
     */
    public function callAI($provider, $apiKey, $model, $systemPrompt, $userPrompt, $maxTokens = 2000)
    {
        if ($provider === 'openai') {
            return $this->callOpenAI($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
        } elseif ($provider === 'anthropic') {
            return $this->callAnthropic($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
        }

        throw new \Exception('Unsupported AI provider: ' . $provider);
    }

    /**
     * Call OpenAI API (supports both GPT-4 and GPT-5 models)
     */
    private function callOpenAI($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens)
    {
        // Check if using GPT-5 family (requires Responses API)
        $isGPT5Family = str_starts_with($model, 'gpt-5');
        
        if ($isGPT5Family) {
            Log::info('[HomeworkSubmission] Using GPT-5 Responses API', ['model' => $model]);
            
            // Use Responses API for GPT-5
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(180)->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => [
                    [
                        'type' => 'message',
                        'role' => 'user',
                        'content' => $systemPrompt . "\n\n" . $userPrompt
                    ]
                ],
                'temperature' => 0.3,
            ]);
            
            if (!$response->successful()) {
                Log::error('[HomeworkSubmission] OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            Log::info('[HomeworkSubmission] GPT-5 response structure', [
                'keys' => array_keys($data),
                'has_output' => isset($data['output'])
            ]);
            
            // Try to extract content from response
            if (isset($data['output']) && is_array($data['output'])) {
                foreach ($data['output'] as $item) {
                    if (isset($item['type']) && $item['type'] === 'message' && isset($item['content'])) {
                        if (is_array($item['content'])) {
                            foreach ($item['content'] as $c) {
                                if (($c['type'] ?? '') === 'output_text' || ($c['type'] ?? '') === 'text') {
                                    return $c['text'] ?? $c['output_text'] ?? '';
                                }
                            }
                        } else {
                            return $item['content'];
                        }
                    }
                }
            }
            
            // Fallback
            return $data['output']['content'] ?? $data['content'] ?? '';
        } else {
            Log::info('[HomeworkSubmission] Using GPT-4 Chat Completions API', ['model' => $model]);
            
            // Use Chat Completions API for GPT-4
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_completion_tokens' => $maxTokens,
                'temperature' => 0.3,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';
        }
    }

    /**
     * Call Anthropic API
     */
    private function callAnthropic($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens)
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();
        return $data['content'][0]['text'] ?? '';
    }
}
