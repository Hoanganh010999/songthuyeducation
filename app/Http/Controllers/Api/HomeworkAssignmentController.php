<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkAssignment;
use App\Models\LessonPlan;
use App\Models\GoogleDriveItem;
use App\Models\ClassModel;
use App\Services\CalendarEventService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeworkAssignmentController extends Controller
{
    protected $calendarEventService;

    public function __construct(CalendarEventService $calendarEventService)
    {
        $this->calendarEventService = $calendarEventService;
    }

    /**
     * Get homework assignments for a class or session
     *
     * NEW ARCHITECTURE:
     * - When session_id is provided: Returns HomeworkBank templates (linked to session)
     * - When class_id is provided: Returns HomeworkAssignment instances (assigned to class)
     */
    public function index(Request $request)
    {
        try {
            $classId = $request->input('class_id');
            $sessionId = $request->input('session_id');

            if (!$classId && !$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class ID or Session ID is required',
                ], 400);
            }

            // NEW ARCHITECTURE: Query HomeworkBank when filtering by session
            if ($sessionId) {
                $query = \App\Models\HomeworkBank::with(['lessonPlanSession', 'creator', 'subject', 'exercises']);
                $query->where('lesson_plan_session_id', $sessionId);

                $status = $request->input('status');
                if ($status) {
                    $query->where('status', $status);
                }

                $homeworks = $query->orderBy('created_at', 'desc')->get();

                // Format HomeworkBank templates to match expected structure
                $homeworks->each(function ($homework) {
                    $homework->session = $homework->lessonPlanSession; // Alias for backwards compatibility
                    $homework->class = null; // Templates are not class-specific
                    $homework->files_data = []; // Templates don't have file_ids
                    $homework->submission_status = null; // Templates don't have submissions
                });

                return response()->json([
                    'success' => true,
                    'data' => $homeworks,
                ]);
            }

            // Original logic for class-based queries (HomeworkAssignment)
            $query = HomeworkAssignment::with(['class', 'assignedBy']);

            if ($classId) {
                $query->forClass($classId);
            }

            // Filter by user if needed
            $userId = $request->input('user_id');
            if ($userId) {
                $query->forUser($userId);
            }

            $status = $request->input('status');
            if ($status) {
                $query->where('status', $status);
            }

            $homeworks = $query->latest()->get();

            // Append files data and submission status for current user
            $currentUserId = auth()->id();

            // Check if current user is a parent
            $parent = \App\Models\ParentModel::where('user_id', $currentUserId)->first();
            $childrenInClass = [];

            if ($parent) {
                // Get parent's children enrolled in this class
                $childrenInClass = $parent->students()
                    ->whereHas('classes', function($q) use ($classId) {
                        $q->where('classes.id', $classId)
                          ->where('class_students.status', 'active');
                    })
                    ->with('user')
                    ->get();
            }

            $homeworks->each(function ($homework) use ($currentUserId, $parent, $childrenInClass) {
                $homework->files_data = $homework->files;

                // If user is a parent with children in this class
                if ($parent && $childrenInClass->isNotEmpty()) {
                    // Check submission status for all children
                    $submissions = [];
                    $hasSubmitted = false;
                    $submissionStatus = 'not_submitted';

                    foreach ($childrenInClass as $child) {
                        $childStatus = $homework->getSubmissionStatusFor($child->user_id);

                        // Get the actual submission record to access unit_folder_link
                        $submission = \App\Models\HomeworkSubmission::where('homework_assignment_id', $homework->id)
                            ->where('student_id', $child->user_id)
                            ->first();

                        // Add ALL children to submissions array, not just those who submitted
                        $submissions[] = [
                            'student_id' => $child->user_id,
                            'student_name' => $child->user->name ?? 'Unknown',
                            'status' => $childStatus,
                            'unit_folder_link' => $submission?->unit_folder_link,
                            'submission_link' => $submission?->submission_link,
                        ];

                        if ($childStatus !== 'not_submitted') {
                            $hasSubmitted = true;
                            $submissionStatus = $childStatus;
                        }
                    }

                    $homework->submission_status = $submissionStatus;
                    $homework->submissions = $submissions;
                    $homework->is_parent = true;
                    $homework->children_count = $childrenInClass->count();
                } else {
                    // Regular student or teacher
                    $homework->submission_status = $homework->getSubmissionStatusFor($currentUserId);
                    $homework->is_parent = false;

                    // Get submission record for unit_folder_link
                    $submission = \App\Models\HomeworkSubmission::where('homework_assignment_id', $homework->id)
                        ->where('student_id', $currentUserId)
                        ->first();

                    if ($submission) {
                        $homework->unit_folder_link = $submission->unit_folder_link;
                        $homework->submission_link = $submission->submission_link;
                    }
                }
            });

            return response()->json([
                'success' => true,
                'data' => $homeworks,
            ]);
        } catch (\Exception $e) {
            Log::error('[Homework] Error loading homework assignments', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load homework assignments',
            ], 500);
        }
    }

    /**
     * Get upcoming homework based on user's role and access level
     *
     * Access Rules:
     * - Regular Teacher: See homework for classes they teach (homeroom or subject teacher)
     * - Subject Head (Trưởng bộ môn): See all homework for their subject's classes
     * - Department Head (Trưởng phòng chuyên môn): See ALL homework
     * - Admin/Superadmin: See ALL homework
     */
    public function getUpcomingHomework(Request $request)
    {
        try {
            $user = $request->user();
            $branchId = $request->input('branch_id') ?? $user->branch_id;

            // Start with base query
            $query = HomeworkAssignment::with(['class', 'assignedBy'])
                ->where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('deadline')
                      ->orWhere('deadline', '>=', now());
                })
                ->orderBy('deadline', 'asc');

            // Filter by branch if specified
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Role-based access control
            $hasFullAccess = false;

            // 1. Check if user is Admin/Superadmin (full access)
            if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
                $hasFullAccess = true;
                Log::info('[Homework] Admin/Superadmin accessing all homework', ['user_id' => $user->id]);
            }

            // 2. Check if user is Department Head (Trưởng phòng chuyên môn) - full access
            if (!$hasFullAccess) {
                $isDepartmentHead = \DB::table('department_user')
                    ->where('user_id', $user->id)
                    ->where('is_head', true)
                    ->where('status', 'active')
                    ->exists();

                if ($isDepartmentHead) {
                    $hasFullAccess = true;
                    Log::info('[Homework] Department Head accessing all homework', ['user_id' => $user->id]);
                }
            }

            // 3. Check if user is Subject Head (Trưởng bộ môn) - see their subject's classes
            if (!$hasFullAccess) {
                $headSubjects = \DB::table('subject_teacher')
                    ->where('user_id', $user->id)
                    ->where('is_head', true)
                    ->where('status', 'active')
                    ->pluck('subject_id')
                    ->toArray();

                if (!empty($headSubjects)) {
                    // Get all classes that have these subjects
                    $classIds = \DB::table('class_subject')
                        ->whereIn('subject_id', $headSubjects)
                        ->where('status', 'active')
                        ->pluck('class_id')
                        ->toArray();

                    if (!empty($classIds)) {
                        $query->whereIn('class_id', $classIds);
                        Log::info('[Homework] Subject Head accessing homework for their subjects', [
                            'user_id' => $user->id,
                            'subject_ids' => $headSubjects,
                            'class_count' => count($classIds)
                        ]);
                    }
                } else {
                    // 4. Regular Teacher - see only classes they teach
                    $teachingClassIds = [];

                    // Get homeroom classes
                    $homeroomClassIds = ClassModel::where('homeroom_teacher_id', $user->id)
                        ->pluck('id')
                        ->toArray();

                    // Get subject teaching classes
                    $subjectTeachingClassIds = \DB::table('class_subject')
                        ->where('teacher_id', $user->id)
                        ->where('status', 'active')
                        ->pluck('class_id')
                        ->toArray();

                    $teachingClassIds = array_unique(array_merge($homeroomClassIds, $subjectTeachingClassIds));

                    if (!empty($teachingClassIds)) {
                        $query->whereIn('class_id', $teachingClassIds);
                        Log::info('[Homework] Regular Teacher accessing homework for their classes', [
                            'user_id' => $user->id,
                            'class_count' => count($teachingClassIds)
                        ]);
                    } else {
                        // No classes to show
                        return response()->json([
                            'success' => true,
                            'data' => [],
                            'message' => 'No homework assignments found for this user'
                        ]);
                    }
                }
            }

            // Execute query
            $homeworks = $query->limit(50)->get();

            // Add additional data
            $homeworks->each(function($homework) use ($user) {
                // Load homework bank data (can't eager load because homeworkBank() returns Collection)
                $homework->homework_bank = $homework->homeworkBank();

                // Get submission count
                $submissionCount = \App\Models\HomeworkSubmission::where('homework_assignment_id', $homework->id)
                    ->whereNotNull('submitted_at')
                    ->count();

                $homework->submission_count = $submissionCount;

                // Get total students count
                if ($homework->isForAllStudents()) {
                    $totalStudents = \App\Models\ClassStudent::where('class_id', $homework->class_id)
                        ->where('status', 'active')
                        ->count();
                } else {
                    $totalStudents = count($homework->assigned_to ?? []);
                }

                $homework->total_students = $totalStudents;
                $homework->completion_rate = $totalStudents > 0 ? round(($submissionCount / $totalStudents) * 100, 1) : 0;
            });

            return response()->json([
                'success' => true,
                'data' => $homeworks,
                'total' => $homeworks->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('[Homework] Error loading upcoming homework', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load upcoming homework',
            ], 500);
        }
    }

    /**
     * Show a single homework assignment with full details
     */
    public function show(Request $request, $id)
    {
        try {
            // Parse include parameter
            $includes = $request->has('include') ? explode(',', $request->input('include')) : [];

            // Build query with relationships
            $query = HomeworkAssignment::where('id', $id);

            // Add eager loading - use assignedBy instead of creator
            $with = ['class', 'assignedBy'];

            $homework = $query->with($with)->firstOrFail();

            // Load homework bank data and exercises
            $homework->homework_bank = $homework->homeworkBank();

            // If exercises are requested, load them from homework banks
            if (in_array('exercises', $includes) || in_array('exercises.options', $includes)) {
                $exercises = collect();
                foreach ($homework->homework_bank as $bank) {
                    // Load exercises with options if requested
                    if (in_array('exercises.options', $includes)) {
                        $bank->load('exercises.options');
                    } else {
                        $bank->load('exercises');
                    }
                    $exercises = $exercises->merge($bank->exercises);
                }
                $homework->exercises = $exercises;
            }

            return response()->json([
                'success' => true,
                'data' => $homework,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Homework assignment not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[Homework] Error loading homework assignment', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load homework assignment',
            ], 500);
        }
    }

    /**
     * Get sessions from class's syllabus
     */
    public function getSessions(Request $request, $classId)
    {
        try {
            $class = ClassModel::with('lessonPlan.sessions')->findOrFail($classId);

            if (!$class->lesson_plan_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This class does not have a syllabus assigned',
                ], 400);
            }

            $sessions = $class->lessonPlan->sessions()
                ->orderBy('session_number')
                ->get(['id', 'session_number', 'lesson_title', 'homework_folder_id']);

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ]);
        } catch (\Exception $e) {
            Log::error('[Homework] Error loading sessions', [
                'error' => $e->getMessage(),
                'class_id' => $classId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sessions',
            ], 500);
        }
    }

    /**
     * Get homework files from a session's Google Drive folder
     */
    public function getSessionFiles(Request $request, $sessionId)
    {
        try {
            $session = \App\Models\LessonPlanSession::findOrFail($sessionId);

            if (!$session->homework_folder_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No homework folder for this session',
                ]);
            }

            $files = GoogleDriveItem::where('parent_id', $session->homework_folder_id)
                ->where('type', 'file')
                ->where('is_trashed', false)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);
        } catch (\Exception $e) {
            Log::error('[Homework] Error loading session files', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load files',
            ], 500);
        }
    }

    /**
     * Create new homework assignment
     */
    public function store(Request $request)
    {
        // Authorization check
        if (!$request->user()->hasPermission('course.create_homework')) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_create_homework')
            ], 403);
        }

        try {
            // Determine if this is exercise-based or file-based homework
            $isExerciseBased = $request->has('exercise_ids');

            // Log request data for debugging
            Log::info('[Homework] Creating assignment', [
                'is_exercise_based' => $isExerciseBased,
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'class_id' => 'nullable|exists:classes,id',  // Nullable for syllabus-level homework templates
                'lesson_plan_session_id' => 'required|exists:lesson_plan_sessions,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'nullable|date',
                // File-based homework fields
                'file_ids' => $isExerciseBased ? 'nullable|array' : 'required|array|min:1',
                'file_ids.*' => 'string',
                // Exercise-based homework fields
                'exercise_ids' => $isExerciseBased ? 'required|array|min:1' : 'nullable|array',
                'exercise_ids.*' => 'integer|exists:homework_exercises,id',
                'exercise_data' => $isExerciseBased ? 'required|array' : 'nullable|array',
                'exercise_data.*.exercise_id' => 'required_with:exercise_data|integer',
                'exercise_data.*.points' => 'nullable|numeric|min:0',
                'exercise_data.*.sort_order' => 'nullable|integer',
                'exercise_data.*.is_required' => 'nullable|boolean',
                // Common fields
                'assigned_to' => 'nullable|array',
                'assigned_to.*' => 'integer|exists:users,id',
                'status' => 'nullable|string|in:active,completed,cancelled',
            ]);

            // 🔥 IMPORTANT: Wrap in transaction to ensure homework and post are created atomically
            $homework = \DB::transaction(function () use ($validated, $request, $isExerciseBased) {
                $homework = HomeworkAssignment::create([
                    'class_id' => $validated['class_id'],
                    'lesson_plan_session_id' => $validated['lesson_plan_session_id'],
                    'created_by' => $request->user()->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'deadline' => $validated['deadline'] ?? null,
                    'file_ids' => $validated['file_ids'] ?? null,
                    'assigned_to' => $validated['assigned_to'] ?? null,
                    'status' => $validated['status'] ?? 'active',
                ]);

                // Attach exercises if this is exercise-based homework
                if ($isExerciseBased && !empty($validated['exercise_data'])) {
                    $exerciseData = [];
                    foreach ($validated['exercise_data'] as $data) {
                        $exerciseData[$data['exercise_id']] = [
                            'sort_order' => $data['sort_order'] ?? 0,
                            'points' => $data['points'] ?? 0,
                            'is_required' => $data['is_required'] ?? true,
                            'section' => null,
                        ];
                    }
                    $homework->exercises()->attach($exerciseData);

                    Log::info('[Homework] Attached exercises to homework', [
                        'homework_id' => $homework->id,
                        'exercise_count' => count($exerciseData),
                    ]);
                }

                // Load relationships needed for post creation
                $homework->load(['session', 'creator', 'class', 'exercises']);

                // Create course post for homework only if it's assigned to a class
                $post = null;
                if ($homework->class_id) {
                    $post = $this->createHomeworkPost($homework, $request->user());

                    if (!$post) {
                        throw new \Exception('Failed to create course post for homework');
                    }

                    Log::info('[Homework] Successfully created homework and post in transaction', [
                        'homework_id' => $homework->id,
                        'post_id' => $post->id,
                        'is_exercise_based' => $isExerciseBased,
                    ]);
                } else {
                    Log::info('[Homework] Created syllabus-level homework template (no class post)', [
                        'homework_id' => $homework->id,
                        'is_exercise_based' => $isExerciseBased,
                    ]);
                }

                return $homework;
            });

            // Make files publicly accessible (anyone with link can view) - only for file-based homework with a class
            if (!$isExerciseBased && !empty($validated['file_ids']) && !empty($validated['class_id'])) {
                try {
                    // Get Google Drive settings for this class's branch
                    $class = ClassModel::findOrFail($validated['class_id']);
                    $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();

                    if ($setting) {
                        $googleDriveService = new GoogleDriveService($setting);

                        foreach ($validated['file_ids'] as $googleId) {
                            try {
                                $googleDriveService->makeFilePublic($googleId);
                            } catch (\Exception $e) {
                                Log::warning('[Homework] Failed to make file public', [
                                    'google_id' => $googleId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    } else {
                        Log::warning('[Homework] Google Drive not configured for branch', [
                            'branch_id' => $class->branch_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[Homework] Google Drive authentication failed', [
                        'error' => $e->getMessage(),
                        'branch_id' => $class->branch_id,
                    ]);

                    // Throw error to user so they know to re-authenticate
                    throw new \Exception(
                        'Google Drive authentication expired. Please re-authenticate Google Drive in Settings. ' .
                        'Error: ' . $e->getMessage()
                    );
                }
            }

            // Load relationships
            if ($isExerciseBased) {
                $homework->load(['session', 'creator', 'class', 'exercises.options']);
            } else {
                $homework->load(['session', 'creator', 'class']);
                $homework->files_data = $homework->files;
            }

            // Sync to calendar - only for class-assigned homework
            if ($homework->class_id && $homework->deadline) {
                $this->calendarEventService->syncHomeworkToCalendar($homework);
            }

            // Note: Post creation is now done inside the transaction above

            // Send to Zalo group if configured - only for class-assigned homework
            if ($homework->class_id) {
                $this->sendHomeworkToZaloGroup($homework);
            }

            Log::info('[Homework] Created new homework assignment', [
                'homework_id' => $homework->id,
                'class_id' => $homework->class_id,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Homework assignment created successfully',
                'data' => $homework,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[Homework] Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[Homework] Error creating homework assignment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create homework assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download homework file (proxy with permission check)
     */
    public function downloadFile($homeworkId, $fileId)
    {
        try {
            // Find homework
            $homework = HomeworkAssignment::with('class')->findOrFail($homeworkId);
            
            // Check permission: User must be in the class or be a teacher/admin
            $user = auth()->user();
            $hasAccess = false;
            
            // Admin/superadmin always has access
            if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
                $hasAccess = true;
            }
            // Teacher has access if they teach this class
            elseif ($user->hasRole('teacher')) {
                $hasAccess = $homework->class->homeroom_teacher_id == $user->id;
            }
            // Student has access if assigned or all students
            else {
                $hasAccess = $homework->isAssignedTo($user->id);
            }
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this file',
                ], 403);
            }
            
            // Verify file belongs to this homework
            if (!in_array($fileId, $homework->file_ids ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in homework',
                ], 404);
            }
            
            // Get file info
            $file = GoogleDriveItem::where('google_id', $fileId)->first();
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }
            
            // Generate Google Drive download URL
            $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
            
            Log::info('[Homework] File download', [
                'homework_id' => $homeworkId,
                'file_id' => $fileId,
                'user_id' => $user->id,
            ]);
            
            // Redirect to Google Drive download URL
            return redirect($downloadUrl);
            
        } catch (\Exception $e) {
            Log::error('[Homework] Error downloading file', [
                'homework_id' => $homeworkId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
            ], 500);
        }
    }

    /**
     * Update homework assignment status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,completed,cancelled',
            ]);

            $homework = HomeworkAssignment::findOrFail($id);
            $homework->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Homework status updated successfully',
                'data' => $homework,
            ]);
        } catch (\Exception $e) {
            Log::error('[Homework] Error updating homework status', [
                'error' => $e->getMessage(),
                'homework_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update homework status',
            ], 500);
        }
    }

    /**
     * Delete homework assignment
     */
    public function destroy($id)
    {
        try {
            $homework = HomeworkAssignment::findOrFail($id);
            $homework->delete();

            return response()->json([
                'success' => true,
                'message' => 'Homework assignment deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[Homework] Error deleting homework assignment', [
                'error' => $e->getMessage(),
                'homework_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete homework assignment',
            ], 500);
        }
    }
    
    /**
     * Create a course post for homework assignment
     *
     * @return \App\Models\CoursePost The created post
     * @throws \Exception If post creation fails
     */
    protected function createHomeworkPost($homework, $user)
    {
        try {
            $class = $homework->class;
            $session = $homework->session;

            // Only include description in content, title will be shown in metadata box
            $content = $homework->description ?? "<p>" . __('course.check_homework_details_below') . "</p>";

            $post = \App\Models\CoursePost::create([
                'class_id' => $homework->class_id,
                'user_id' => $user->id,
                'content' => $content,
                'post_type' => 'homework',
                'branch_id' => $class->branch_id,
                'metadata' => [
                    'homework_id' => $homework->id,
                    'homework_title' => $homework->title,
                    'due_date' => $homework->deadline,
                    'session_info' => $session ? "Buổi {$session->session_number}: {$session->lesson_title}" : null,
                ],
            ]);

            Log::info('[Homework] Created course post for homework', [
                'homework_id' => $homework->id,
                'post_id' => $post->id,
            ]);

            return $post;
        } catch (\Exception $e) {
            Log::error('[Homework] Failed to create course post', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw exception to trigger transaction rollback
            throw $e;
        }
    }

    /**
     * Send homework to Zalo group chat if configured
     */
    protected function sendHomeworkToZaloGroup($homework)
    {
        try {
            $class = $homework->class;

            // Check if class has Zalo group configured
            if (empty($class->zalo_group_id)) {
                Log::info('[Homework] Class does not have Zalo group configured, skipping', [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                ]);
                return;
            }

            // Get primary Zalo account
            $primaryAccount = \App\Models\ZaloAccount::where('is_primary', true)
                ->where('is_connected', true)
                ->first();

            if (!$primaryAccount) {
                Log::warning('[Homework] No primary Zalo account found', [
                    'class_id' => $class->id,
                ]);
                return;
            }

            // Verify the group exists in our database
            $zaloGroup = \App\Models\ZaloGroup::where('zalo_account_id', $primaryAccount->id)
                ->where('zalo_group_id', $class->zalo_group_id)
                ->first();

            if (!$zaloGroup) {
                Log::warning('[Homework] Zalo group not found in database', [
                    'class_id' => $class->id,
                    'zalo_group_id' => $class->zalo_group_id,
                ]);
                return;
            }

            Log::info('[Homework] Sending homework to Zalo group', [
                'homework_id' => $homework->id,
                'class_id' => $class->id,
                'zalo_group_id' => $class->zalo_group_id,
                'zalo_group_name' => $zaloGroup->name,
            ]);

            // Format message content
            $message = $this->formatHomeworkMessage($homework);

            // Send message to group
            $zaloService = new \App\Services\ZaloNotificationService();
            $result = $zaloService->sendMessage(
                $class->zalo_group_id,  // to (recipient_id)
                $message,                // message content
                'group',                 // type
                $primaryAccount->id      // accountId
            );

            if ($result['success']) {
                Log::info('[Homework] Successfully sent homework to Zalo group', [
                    'homework_id' => $homework->id,
                    'zalo_group_id' => $class->zalo_group_id,
                ]);

                // Send attached files
                if (!empty($homework->file_ids)) {
                    $this->sendHomeworkFilesToZaloGroup($homework, $primaryAccount, $class->zalo_group_id);
                }
            } else {
                Log::error('[Homework] Failed to send homework to Zalo group', [
                    'homework_id' => $homework->id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[Homework] Exception while sending to Zalo group', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't fail homework creation if Zalo send fails
        }
    }

    /**
     * Format homework message for Zalo
     */
    protected function formatHomeworkMessage($homework)
    {
        $session = $homework->session;

        // Use homework title as header instead of "BAITAP MỚI"
        $message = "📚 {$homework->title}\n\n";

        // Add session info if exists
        if ($session) {
            $message .= "📝 Buổi {$session->session_number}: {$session->lesson_title}\n";
        }

        // Add description with better HTML parsing
        if ($homework->description) {
            $description = $this->parseHtmlToPlainText($homework->description);
            $message .= "📋 Mô tả:\n{$description}\n\n";
        }

        // Add deadline
        if ($homework->deadline) {
            $deadline = \Carbon\Carbon::parse($homework->deadline)->format('d/m/Y H:i');
            $message .= "⏰ Hạn nộp: {$deadline}\n\n";
        }

        // Add file count
        if (!empty($homework->file_ids)) {
            $fileCount = count($homework->file_ids);
            $message .= "📎 Có {$fileCount} file đính kèm (đang gửi...)\n";
        }

        return $message;
    }

    /**
     * Parse HTML content to plain text while preserving lists and basic formatting
     */
    protected function parseHtmlToPlainText($html)
    {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities first
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert <br> and <p> to newlines
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n", $html);
        $html = preg_replace('/<p[^>]*>/i', '', $html);

        // Handle ordered lists <ol><li>
        $html = preg_replace_callback(
            '/<ol[^>]*>(.*?)<\/ol>/is',
            function ($matches) {
                $items = $matches[1];
                // Extract <li> items
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $items, $liMatches);
                $result = '';
                foreach ($liMatches[1] as $index => $item) {
                    $cleanItem = strip_tags($item);
                    $cleanItem = trim($cleanItem);
                    $result .= ($index + 1) . ". {$cleanItem}\n";
                }
                return "\n" . $result;
            },
            $html
        );

        // Handle unordered lists <ul><li>
        $html = preg_replace_callback(
            '/<ul[^>]*>(.*?)<\/ul>/is',
            function ($matches) {
                $items = $matches[1];
                // Extract <li> items
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $items, $liMatches);
                $result = '';
                foreach ($liMatches[1] as $item) {
                    $cleanItem = strip_tags($item);
                    $cleanItem = trim($cleanItem);
                    $result .= "• {$cleanItem}\n";
                }
                return "\n" . $result;
            },
            $html
        );

        // Remove remaining HTML tags
        $text = strip_tags($html);

        // Clean up excessive whitespace
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text); // Max 2 consecutive newlines
        $text = preg_replace('/[ \t]+/', ' ', $text); // Multiple spaces to single space
        $text = trim($text);

        return $text;
    }

    /**
     * Send homework files to Zalo group
     */
    protected function sendHomeworkFilesToZaloGroup($homework, $account, $groupId)
    {
        try {
            $zaloService = new \App\Services\ZaloNotificationService();
            $class = $homework->class;

            // Get Google Drive setting for this branch
            $setting = \App\Models\GoogleDriveSetting::where('branch_id', $class->branch_id)->first();

            if (!$setting) {
                Log::warning('[Homework] No Google Drive setting found for branch', [
                    'branch_id' => $class->branch_id,
                ]);
                return;
            }

            foreach ($homework->file_ids as $index => $googleId) {
                try {
                    // Get file info from database
                    $file = GoogleDriveItem::where('google_id', $googleId)->first();

                    if (!$file) {
                        Log::warning('[Homework] File not found in database', [
                            'google_id' => $googleId,
                        ]);
                        continue;
                    }

                    Log::info('[Homework] Sending file to Zalo group', [
                        'file_name' => $file->name,
                        'file_type' => $file->mime_type,
                        'google_id' => $googleId,
                    ]);

                    // Get file download URL
                    $downloadUrl = "https://drive.google.com/uc?export=download&id={$googleId}";

                    // Send file link to Zalo group
                    $fileMessage = "📎 File " . ($index + 1) . ": {$file->name}\n";
                    $fileMessage .= "🔗 Link tải: {$downloadUrl}";

                    $result = $zaloService->sendMessage(
                        $groupId,        // to (recipient_id)
                        $fileMessage,    // message content
                        'group',         // type
                        $account->id     // accountId
                    );

                    if (!$result['success']) {
                        Log::error('[Homework] Failed to send file to Zalo', [
                            'file_name' => $file->name,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }

                    // Sleep briefly to avoid rate limiting
                    usleep(500000); // 0.5 second

                } catch (\Exception $e) {
                    Log::error('[Homework] Error sending file to Zalo', [
                        'google_id' => $googleId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('[Homework] Exception while sending files to Zalo', [
                'homework_id' => $homework->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

