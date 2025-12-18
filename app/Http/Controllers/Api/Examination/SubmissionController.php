<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Assignment;
use App\Models\Examination\Submission;
use App\Models\Examination\SubmissionAnswer;
use App\Models\Examination\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    /**
     * Get list of submissions for grading (teacher view).
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $userId = $user->id;
        
        // Check permissions
        $canGrade = $user->hasPermission('examination.submissions.grade') || 
                    $user->hasPermission('examination.grading.view');
        $canViewSubmissions = $user->hasPermission('examination.submissions.view');
        $hasSpecialView = $user->hasPermission('examination.submissions.special_view');

        $query = Submission::with([
            'user:id,name,email,avatar',
            'assignment.test:id,title,type,subtype',
            'grader:id,name',
            'practiceTest:id,title',
        ])
        ->whereIn('status', [
            Submission::STATUS_SUBMITTED,
            Submission::STATUS_GRADING,
            Submission::STATUS_GRADED,
        ]);

        // Filter by permission: Only show submissions user can view
        // If user has grade permission OR view submissions permission → see all
        if (!$canGrade && !$canViewSubmissions) {
            // User không có quyền grade VÀ không có quyền view submissions → chỉ xem được:
            // 1. Submissions của chính họ
            // 2. Submissions có allow_special_view = true VÀ user có quyền special_view
            $query->where(function ($q) use ($userId, $hasSpecialView) {
                $q->where('user_id', $userId); // Own submissions
                
                if ($hasSpecialView) {
                    $q->orWhere(function ($sq) {
                        $sq->where('allow_special_view', true);
                    });
                }
            });
        }
        // If canGrade = true OR canViewSubmissions = true → xem được tất cả (không filter)

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by test type
        if ($request->filled('type')) {
            $query->whereHas('assignment.test', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        // Filter by skill/subtype
        if ($request->filled('skill')) {
            $query->whereHas('assignment.test', function ($q) use ($request) {
                $q->where('subtype', $request->skill);
            });
        }

        // Search by student name
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $submissions = $query->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $submissions,
        ]);
    }

    /**
     * Get pending submissions count (for badge notification).
     */
    public function pendingCount(): JsonResponse
    {
        $counts = [
            'total' => Submission::whereIn('status', [
                Submission::STATUS_SUBMITTED,
                Submission::STATUS_GRADING,
            ])->count(),
            'submitted' => Submission::where('status', Submission::STATUS_SUBMITTED)->count(),
            'grading' => Submission::where('status', Submission::STATUS_GRADING)->count(),
            'by_skill' => [],
        ];

        // Count by skill
        $skillCounts = Submission::whereIn('submissions.status', [
                Submission::STATUS_SUBMITTED,
                Submission::STATUS_GRADING,
            ])
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('tests', 'assignments.test_id', '=', 'tests.id')
            ->selectRaw('tests.subtype as skill, count(*) as count')
            ->groupBy('tests.subtype')
            ->pluck('count', 'skill');

        $counts['by_skill'] = $skillCounts;

        return response()->json([
            'success' => true,
            'data' => $counts,
        ]);
    }

    /**
     * Get single submission for grading.
     */
    public function show(string $id): JsonResponse
    {
        $submission = Submission::with([
            'user:id,name,email,avatar',
            'assignment.test.sections.passage',
            'assignment.test.sections.testQuestions.question.options',
            'answers.question.options',
            'grader:id,name',
        ])->findOrFail($id);

        Log::info('🔍 Loading submission for grading', [
            'submission_id' => $id,
            'test_id' => $submission->assignment->test->id ?? null,
            'test_subtype' => $submission->assignment->test->subtype ?? null,
            'answers_count' => $submission->answers->count(),
        ]);

        // For practice tests without question records, enrich answers with test settings
        if ($submission->assignment && $submission->assignment->test) {
            $test = $submission->assignment->test;
            $settings = $test->settings;
            
            Log::info('📚 Enriching answers with test settings', [
                'test_id' => $test->id,
                'has_settings' => !empty($settings),
            ]);
            
            // Enrich each answer with question data from test settings
            $enrichedCount = 0;
            foreach ($submission->answers as $answer) {
                if (!$answer->question && $answer->question_number) {
                    $questionData = $this->findQuestionInSettings($settings, $test->subtype, $answer->question_number);
                    if ($questionData) {
                        Log::info('✅ Found question in settings', [
                            'question_number' => $answer->question_number,
                            'has_content' => !empty($questionData['content']),
                            'has_correct_answer' => !empty($questionData['correctAnswer']),
                            'content_value' => substr($questionData['content'] ?? '', 0, 100),
                        ]);
                        
                        // Create a temporary question object for display
                        $answer->question = (object)[
                            'id' => null,
                            'content' => $questionData['content'] ?? '',
                            'type' => $questionData['type'] ?? 'short_answer',
                            'correct_answer' => $questionData['correctAnswer'] ?? null,
                            'options' => $questionData['options'] ?? null,
                            'imageUrl' => $questionData['imageUrl'] ?? null,
                            'visualType' => $questionData['visualType'] ?? null,
                            'imageSource' => $questionData['imageSource'] ?? null,
                        ];
                        $enrichedCount++;
                    } else {
                        Log::warning('⚠️ Question not found in settings', [
                            'question_number' => $answer->question_number,
                        ]);
                    }
                }
            }
            
            Log::info('📊 Enrichment complete', [
                'enriched_count' => $enrichedCount,
                'total_answers' => $submission->answers->count(),
            ]);
        }

        // Log sample answer to verify structure
        if ($submission->answers->count() > 0) {
            $sampleAnswer = $submission->answers->first();
            Log::info('📋 Sample answer structure', [
                'has_question_object' => !is_null($sampleAnswer->question),
                'question_content' => $sampleAnswer->question->content ?? 'null',
                'question_type' => $sampleAnswer->question->type ?? 'null',
                'question_correct_answer' => $sampleAnswer->question->correct_answer ?? 'null',
            ]);
        }

        // Convert to array to include dynamically set properties
        $submissionArray = $submission->toArray();
        
        // Add total questions from test (before toArray())
        if ($submission->assignment && $submission->assignment->test) {
            $test = $submission->assignment->test; // Use model, not array
            $totalQuestions = $this->calculateTotalQuestions($test);
            $submissionArray['assignment']['test']['total_questions'] = $totalQuestions;
        }
        
        // Manually add enriched question data to each answer
        foreach ($submission->answers as $index => $answer) {
            if ($answer->question && !$answer->question_id) {
                // This is an enriched question from settings
                $submissionArray['answers'][$index]['question'] = [
                    'id' => $answer->question->id,
                    'content' => $answer->question->content,
                    'type' => $answer->question->type,
                    'correct_answer' => $answer->question->correct_answer,
                    'options' => $answer->question->options,
                    'imageUrl' => $answer->question->imageUrl ?? null,
                    'visualType' => $answer->question->visualType ?? null,
                    'imageSource' => $answer->question->imageSource ?? null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $submissionArray,
        ]);
    }

    /**
     * Find question data in test settings by question number
     */
    private function findQuestionInSettings($settings, $subtype, $questionNumber)
    {
        if (!$settings) return null;

        $currentQuestionNum = 1;

        switch ($subtype) {
            case 'listening':
                if (isset($settings['listening']['parts'])) {
                    foreach ($settings['listening']['parts'] as $part) {
                        if (isset($part['questionGroups'])) {
                            foreach ($part['questionGroups'] as $group) {
                                if (isset($group['questions'])) {
                                    foreach ($group['questions'] as $question) {
                                        if ($currentQuestionNum == $questionNumber) {
                                            // Try multiple fields for question text
                                            $text = '';
                                            if (!empty($question['question'])) {
                                                $text = $question['question'];
                                            } elseif (!empty($question['text'])) {
                                                $text = $question['text'];
                                            } elseif (!empty($question['statement'])) {
                                                $text = $question['statement'];
                                            } elseif (!empty($question['sentence'])) {
                                                $text = $question['sentence'];
                                            } elseif (!empty($question['information'])) {
                                                $text = $question['information'];
                                            }
                                            
                                            return [
                                                'content' => $text,  // Changed from 'text' to 'content'
                                                'type' => $question['type'] ?? 'short_answer',
                                                'correctAnswer' => $question['correctAnswer'] ?? $question['answer'] ?? null,
                                                'options' => $question['options'] ?? null,
                                            ];
                                        }
                                        $currentQuestionNum++;
                                    }
                                }
                            }
                        }
                    }
                }
                break;

            case 'reading':
                if (isset($settings['reading']['passages'])) {
                    foreach ($settings['reading']['passages'] as $passage) {
                        if (isset($passage['questionGroups'])) {
                            foreach ($passage['questionGroups'] as $group) {
                                if (isset($group['questions'])) {
                                    foreach ($group['questions'] as $question) {
                                        if ($currentQuestionNum == $questionNumber) {
                                            // Debug log
                                            Log::info('🔍 Checking question fields', [
                                                'question_number' => $questionNumber,
                                                'question_isset' => isset($question['question']),
                                                'question_empty' => empty($question['question']),
                                                'statement_isset' => isset($question['statement']),
                                                'statement_empty' => empty($question['statement']),
                                                'statement_value' => substr($question['statement'] ?? '', 0, 50),
                                            ]);
                                            
                                            // Try multiple fields for question text
                                            $text = '';
                                            if (!empty($question['question'])) {
                                                $text = $question['question'];
                                            } elseif (!empty($question['text'])) {
                                                $text = $question['text'];
                                            } elseif (!empty($question['statement'])) {
                                                $text = $question['statement'];
                                            } elseif (!empty($question['sentence'])) {
                                                $text = $question['sentence'];
                                            } elseif (!empty($question['information'])) {
                                                $text = $question['information'];
                                            }
                                            
                                            return [
                                                'content' => $text,  // Changed from 'text' to 'content'
                                                'type' => $question['type'] ?? 'short_answer',
                                                'correctAnswer' => $question['correctAnswer'] ?? $question['answer'] ?? null,
                                                'options' => $question['options'] ?? null,
                                            ];
                                        }
                                        $currentQuestionNum++;
                                    }
                                }
                            }
                        }
                    }
                }
                break;

            case 'writing':
                if (isset($settings['writing']['tasks'])) {
                    foreach ($settings['writing']['tasks'] as $index => $task) {
                        if (($index + 1) == $questionNumber) {
                            // Writing task prompt can be in 'prompt', 'content', or 'question'
                            $text = $task['prompt'] ?? $task['content'] ?? $task['question'] ?? '';
                            
                            return [
                                'content' => $text,  // Changed from 'text' to 'content'
                                'type' => 'writing',
                                'correctAnswer' => null, // No correct answer for writing
                                'imageUrl' => $task['imageUrl'] ?? $task['imagePath'] ?? null,
                                'visualType' => $task['visualType'] ?? null,
                                'imageSource' => $task['imageSource'] ?? null,
                            ];
                        }
                    }
                }
                break;
        }

        return null;
    }

    /**
     * Start a new submission (begin test).
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:assignments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignment = Assignment::with('test.testQuestions.question')->findOrFail($request->assignment_id);
        $userId = auth()->id();

        // Check if assignment is available
        if (!$assignment->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment is not available',
            ], 400);
        }

        // Check max attempts
        if (!$assignment->canUserAttempt($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts reached',
            ], 400);
        }

        // Check for existing in-progress submission
        $existingSubmission = $assignment->submissions()
            ->where('user_id', $userId)
            ->where('status', Submission::STATUS_IN_PROGRESS)
            ->first();

        if ($existingSubmission) {
            // Return existing submission
            return response()->json([
                'success' => true,
                'message' => 'Continuing existing submission',
                'data' => $this->formatSubmissionForTest($existingSubmission, $assignment),
            ]);
        }

        try {
            DB::beginTransaction();

            // Create new submission
            $submission = Submission::create([
                'uuid' => (string) Str::uuid(),
                'assignment_id' => $assignment->id,
                'user_id' => $userId,
                'started_at' => now(),
                'status' => Submission::STATUS_IN_PROGRESS,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Pre-create answer records for all questions
            foreach ($assignment->test->testQuestions as $testQuestion) {
                SubmissionAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $testQuestion->question_id,
                    'test_question_id' => $testQuestion->id,
                    'max_points' => $testQuestion->points ?? $testQuestion->question->points ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test started successfully',
                'data' => $this->formatSubmissionForTest($submission, $assignment),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error starting test: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save answer for a question.
     */
    public function saveAnswer(Request $request, string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        // Verify ownership
        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if can be modified
        if (!$submission->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission cannot be modified',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable',
            'text_answer' => 'nullable|string',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer = $submission->answers()
            ->where('question_id', $request->question_id)
            ->first();

        if (!$answer) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found in this submission',
            ], 404);
        }

        // Update answer
        if ($request->has('answer')) {
            $answer->updateAnswer($request->answer);
        }

        if ($request->has('text_answer')) {
            $answer->updateTextAnswer($request->text_answer);
        }

        if ($request->has('time_spent')) {
            $answer->time_spent = ($answer->time_spent ?? 0) + $request->time_spent;
            $answer->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Answer saved',
            'data' => [
                'question_id' => $answer->question_id,
                'answered' => $answer->isAnswered(),
            ],
        ]);
    }

    /**
     * Save audio response for speaking questions.
     */
    public function saveAudioResponse(Request $request, string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$submission->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission cannot be modified',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'audio' => 'required|file|mimes:webm,mp3,wav,ogg|max:10240',
            'duration' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer = $submission->answers()
            ->where('question_id', $request->question_id)
            ->first();

        if (!$answer) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found in this submission',
            ], 404);
        }

        // Store audio file
        $path = $request->file('audio')->store(
            "submissions/{$submission->id}/audio",
            'public'
        );

        $answer->setAudioResponse($path, $request->duration);

        return response()->json([
            'success' => true,
            'message' => 'Audio response saved',
            'data' => [
                'question_id' => $answer->question_id,
                'audio_path' => $path,
                'duration' => $request->duration,
            ],
        ]);
    }

    /**
     * Submit the test.
     */
    public function submit(string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$submission->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'Submission already submitted',
            ], 400);
        }

        try {
            $submission->submit();

            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'max_score' => $submission->max_score,
                    'percentage' => $submission->percentage,
                    'passed' => $submission->passed,
                    'band_score' => $submission->band_score,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting test: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get submission result.
     */
    public function result(string $id): JsonResponse
    {
        $submission = Submission::with([
            'assignment.test',
            'practiceTest',
            'answers.question',
            'user',
        ])->findOrFail($id);

        // Check permission using new canBeViewedBy method
        $user = auth()->user();
        if (!$submission->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $userId = $user->id;
        $isOwner = $submission->user_id === $userId;
        $isGrader = $user->hasPermission('examination.submissions.grade') || $user->hasPermission('examination.grading.view');

        // Check if results are published (for students only)
        $isPublished = $submission->isPublished();
        if ($isOwner && !$isGrader && !$isPublished) {
            // Student viewing unpublished results - show limited info
            return response()->json([
                'success' => true,
                'data' => [
                    'submission' => [
                        'id' => $submission->id,
                        'uuid' => $submission->uuid,
                        'status' => $submission->isGraded() ? 'grading' : $submission->status, // Show as "grading" instead of "graded"
                        'started_at' => $submission->started_at,
                        'submitted_at' => $submission->submitted_at,
                        'time_spent' => $submission->time_spent,
                        'is_published' => false,
                        'allow_special_view' => $submission->allow_special_view,
                        'pending_message' => 'Bài làm của bạn đang được chấm điểm. Điểm số sẽ được công bố sau.',
                    ],
                    'test' => $submission->assignment ? [
                        'id' => $submission->assignment->test->id,
                        'title' => $submission->assignment->test->title,
                        'type' => $submission->assignment->test->type,
                        'subtype' => $submission->assignment->test->subtype,
                    ] : null,
                    'answers' => [], // Don't show answers
                ],
            ]);
        }

        // Handle both assignment-based and practice test submissions
        $assignment = $submission->assignment;
        $test = null;
        $showAnswers = $isGrader;
        
        if ($assignment) {
            // Assignment-based submission
            $test = $assignment->test;
            $showAnswers = $test->show_answers || $isGrader;
        } else {
            // Practice test submission - get test info from activity_log
            $activityLog = $submission->activity_log;
            if ($activityLog && isset($activityLog['test_id'])) {
                $test = Test::find($activityLog['test_id']);
                if ($test) {
                    $showAnswers = $test->show_answers || $isGrader;
                }
            }
        }

        $result = [
            'submission' => [
                'id' => $submission->id,
                'uuid' => $submission->uuid,
                'status' => $submission->status,
                'started_at' => $submission->started_at,
                'submitted_at' => $submission->submitted_at,
                'time_spent' => $submission->time_spent,
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
                'passed' => $submission->passed,
                'band_score' => $submission->band_score,
                'skill_scores' => $submission->skill_scores,
                'feedback' => $submission->feedback,
                'is_published' => $isPublished,
                'allow_special_view' => $submission->allow_special_view,
                'published_at' => $submission->published_at,
                'published_by' => $submission->published_by,
            ],
            'test' => $test ? [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'subtype' => $test->subtype ?? null,
                'total_questions' => $this->calculateTotalQuestions($test),
            ] : null,
            'answers' => $submission->answers->map(function ($answer) use ($showAnswers) {
                $data = [
                    'question_id' => $answer->question_id,
                    'question_title' => $answer->question ? $answer->question->title : null,
                    'question_type' => $answer->question ? $answer->question->type : null,
                    'user_answer' => $answer->answer ?? $answer->text_answer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'max_points' => $answer->max_points,
                    'feedback' => $answer->feedback,
                    'ai_feedback' => $answer->ai_feedback, // ✅ Add AI overall feedback
                    'audio_file_path' => $answer->audio_file_path,
                    'audio_duration' => $answer->audio_duration,
                    'grading_criteria' => $answer->grading_criteria, // Add grading criteria
                    'band_score' => $answer->band_score, // Add band_score for IELTS
                ];

                if ($showAnswers && $answer->question) {
                    $data['correct_answer'] = $answer->question->correct_answer;
                    $data['explanation'] = $answer->question->explanation;
                }

                return $data;
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Grade a submission answer (manual grading).
     */
    public function gradeAnswer(Request $request, string $id, string $answerId): JsonResponse
    {
        Log::info('📝 [GradeAnswer] Request received', [
            'submission_id' => $id,
            'answer_id' => $answerId,
            'payload_keys' => array_keys($request->all()),
            'grading_criteria_type' => $request->has('grading_criteria') ? gettype($request->grading_criteria) : 'not_present',
            'grading_criteria_sample' => $request->has('grading_criteria') ? json_encode($request->grading_criteria) : 'N/A'
        ]);
        
        $submission = Submission::findOrFail($id);
        $answer = SubmissionAnswer::where('submission_id', $id)
            ->where('id', $answerId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'points' => 'nullable|numeric|min:0',
            'band_score' => 'nullable|numeric|min:0|max:9',
            'feedback' => 'nullable|string',
            'ai_feedback' => 'nullable|string',
            'grading_criteria' => 'nullable|array',
            'is_correct' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::error('❌ [GradeAnswer] Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update answer with band score (for IELTS Writing/Speaking)
            // band_score accessor stores to points_earned
            if ($request->has('band_score')) {
                $answer->band_score = $request->band_score;
            } elseif ($request->has('points')) {
                $answer->points_earned = $request->points;
            }

            if ($request->has('feedback')) {
                $answer->feedback = $request->feedback;
            }

            if ($request->has('ai_feedback')) {
                $answer->ai_feedback = $request->ai_feedback;
            }

            if ($request->has('is_correct')) {
                $answer->is_correct = $request->is_correct;
            }

            if ($request->has('grading_criteria')) {
                $answer->grading_criteria = $request->grading_criteria;
            }

            $answer->graded_by = auth()->id();
            $answer->graded_at = now();
            $answer->save();
            
            Log::info('✅ [GradeAnswer] Answer saved successfully', [
                'answer_id' => $answer->id,
                'band_score' => $answer->band_score,
                'has_grading_criteria' => !empty($answer->grading_criteria)
            ]);

            // Update submission status to grading
            if ($submission->status === Submission::STATUS_SUBMITTED) {
                $submission->status = Submission::STATUS_GRADING;
                $submission->graded_by = auth()->id();
                $submission->graded_at = now();
                $submission->save();
            }

            // Reload submission to get latest relationships
            $submission = $submission->fresh();
            $testSubtype = $submission->assignment->test->subtype ?? null;
            
            // For Writing tests: Auto-calculate overall band score from Task 1 & Task 2
            if ($testSubtype === 'writing') {
                $overallBand = $this->calculateWritingOverallBand($submission);
                if ($overallBand !== null) {
                    $submission->band_score = $overallBand;
                    $submission->save();
                    
                    Log::info('✅ Auto-calculated Writing overall band', [
                        'submission_id' => $submission->id,
                        'overall_band' => $overallBand,
                    ]);
                }
            }

            // For Speaking tests with AI grading, auto-complete when all answers are graded
            $isSpeaking = $testSubtype === 'speaking';

            if ($isSpeaking) {
                // Update submission band_score from answer band_score
                $this->calculateSpeakingOverallBand($submission);
                
                // Refresh submission to get updated band_score
                $submission = $submission->fresh();
                
                // Check if all answers are graded
                $totalAnswers = $submission->answers()->count();
                $gradedAnswers = $submission->answers()->whereNotNull('graded_at')->count();

                if ($totalAnswers > 0 && $gradedAnswers === $totalAnswers) {
                    // All answers graded - mark submission as completed
                    $submission->status = Submission::STATUS_GRADED;
                    $submission->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Answer graded successfully',
                'data' => [
                    'band_score' => $answer->band_score,
                    'points' => $answer->points_earned,
                    'submission_score' => $submission->fresh()->score,
                    'submission_status' => $submission->status,
                    'overall_band' => $submission->band_score, // Return overall band (Writing/Speaking)
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [GradeAnswer] Exception caught', [
                'answer_id' => $answerId,
                'submission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error grading answer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add feedback to submission.
     */
    public function addFeedback(Request $request, string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'feedback' => 'nullable|string',
            'band_score' => 'nullable|numeric|min:0|max:9',
            'status' => 'nullable|string|in:submitted,grading,graded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('feedback')) {
            $submission->feedback = $request->feedback;
        }

        if ($request->has('band_score')) {
            $submission->band_score = $request->band_score;
        }

        $submission->graded_by = auth()->id();
        $submission->graded_at = now();

        // Update status
        if ($request->status === 'graded') {
            $submission->status = Submission::STATUS_GRADED;
            
            // Calculate score and percentage
            $test = $submission->assignment->test;
            if ($test) {
                // Calculate total questions from test settings
                $totalQuestions = $this->calculateTotalQuestions($test);
                
                // Count correct answers
                $correctCount = $submission->answers()->where('is_correct', true)->count();
                
                // Update submission scores
                $submission->score = $correctCount;
                $submission->max_score = $totalQuestions;
                $submission->percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;
                
                Log::info('📊 Calculated submission scores', [
                    'submission_id' => $submission->id,
                    'correct_count' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'percentage' => $submission->percentage,
                ]);
            }
        } elseif ($submission->status === Submission::STATUS_SUBMITTED) {
            $submission->status = Submission::STATUS_GRADING;
        }

        $submission->save();

        return response()->json([
            'success' => true,
            'message' => 'Feedback saved successfully',
            'data' => [
                'band_score' => $submission->band_score,
                'status' => $submission->status,
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
            ],
        ]);
    }

    /**
     * Log activity (tab switch, focus lost).
     */
    public function logActivity(Request $request, string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:tab_switch,focus_lost,page_leave',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        switch ($request->action) {
            case 'tab_switch':
                $submission->incrementTabSwitch();
                break;
            case 'focus_lost':
                $submission->incrementFocusLost();
                break;
            default:
                $submission->logActivity($request->action, $request->data ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Activity logged',
        ]);
    }

    /**
     * Get remaining time for submission.
     */
    public function remainingTime(string $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        if ($submission->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'remaining_seconds' => $submission->getRemainingTime(),
                'started_at' => $submission->started_at,
            ],
        ]);
    }

    /**
     * Format submission data for test-taking.
     */
    private function formatSubmissionForTest(Submission $submission, Assignment $assignment): array
    {
        $test = $assignment->test->load([
            'sections.audioTrack',
            'sections.passage',
            'sections.testQuestions.question' => function ($q) {
                $q->select('id', 'uuid', 'type', 'title', 'content', 'image_url', 'points', 'time_limit');
            },
            'sections.testQuestions.question.options' => function ($q) {
                $q->select('id', 'question_id', 'content', 'label', 'sort_order', 'image_url');
            },
            'testQuestions.question' => function ($q) {
                $q->select('id', 'uuid', 'type', 'title', 'content', 'image_url', 'points', 'time_limit');
            },
            'testQuestions.question.options' => function ($q) {
                $q->select('id', 'question_id', 'content', 'label', 'sort_order', 'image_url');
            },
        ]);

        // Get saved answers
        $savedAnswers = $submission->answers->keyBy('question_id');

        return [
            'submission_id' => $submission->id,
            'submission_uuid' => $submission->uuid,
            'started_at' => $submission->started_at,
            'time_limit' => $assignment->getEffectiveTimeLimit(),
            'remaining_time' => $submission->getRemainingTime(),
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'instructions' => $test->instructions,
                'shuffle_questions' => $assignment->shuffle_questions ?? $test->shuffle_questions,
                'shuffle_options' => $assignment->shuffle_options ?? $test->shuffle_options,
                'prevent_copy' => $test->prevent_copy,
                'prevent_tab_switch' => $test->prevent_tab_switch,
            ],
            'sections' => $test->sections,
            'questions' => $test->testQuestions->map(function ($tq) use ($savedAnswers) {
                $question = $tq->question;
                $savedAnswer = $savedAnswers->get($question->id);

                return [
                    'test_question_id' => $tq->id,
                    'question_id' => $question->id,
                    'section_id' => $tq->section_id,
                    'sort_order' => $tq->sort_order,
                    'points' => $tq->points ?? $question->points,
                    'question' => $question,
                    'saved_answer' => $savedAnswer ? [
                        'answer' => $savedAnswer->answer,
                        'text_answer' => $savedAnswer->text_answer,
                        'answered_at' => $savedAnswer->answered_at,
                    ] : null,
                ];
            }),
            'answered_count' => $submission->answers->filter->isAnswered()->count(),
            'total_questions' => $test->testQuestions->count(),
        ];
    }

    /**
     * Calculate total questions for a test based on its type and settings
     */
    private function calculateTotalQuestions($test)
    {
        if (!$test) return 0;

        $settings = $test->settings ?? [];

        // IELTS tests
        if ($test->type === 'ielts') {
            $total = 0;

            switch ($test->subtype) {
                case 'listening':
                    // Count questions from all 4 parts
                    if (isset($settings['listening']['parts'])) {
                        foreach ($settings['listening']['parts'] as $part) {
                            if (isset($part['questionGroups'])) {
                                foreach ($part['questionGroups'] as $group) {
                                    $total += count($group['questions'] ?? []);
                                }
                            }
                        }
                    }
                    break;

                case 'reading':
                    // Count questions from all 3 passages
                    if (isset($settings['reading']['passages'])) {
                        foreach ($settings['reading']['passages'] as $passage) {
                            if (isset($passage['questionGroups'])) {
                                foreach ($passage['questionGroups'] as $group) {
                                    $total += count($group['questions'] ?? []);
                                }
                            }
                        }
                    }
                    break;

                case 'writing':
                    // Writing has 2 tasks
                    $total = isset($settings['writing']['tasks']) ? count($settings['writing']['tasks']) : 0;
                    break;

                case 'speaking':
                    // Speaking: count all questions across 3 parts
                    if (isset($settings['speaking']['parts'])) {
                        foreach ($settings['speaking']['parts'] as $part) {
                            $total += count($part['questions'] ?? []);
                        }
                    }
                    break;
            }

            return $total;
        }

        // Regular tests - count from testQuestions relationship
        return $test->testQuestions()->count();
    }

    /**
     * Calculate Writing overall band score from Task 1 and Task 2
     * Formula: Overall = (T1 + 2 × T2) / 3, rounded to nearest 0.5
     */
    private function calculateWritingOverallBand($submission)
    {
        $answers = $submission->answers;
        
        // Find Task 1 and Task 2 scores
        $task1Score = null;
        $task2Score = null;
        
        foreach ($answers as $answer) {
            if ($answer->question_number === 1 && $answer->band_score !== null) {
                $task1Score = $answer->band_score;
            } elseif ($answer->question_number === 2 && $answer->band_score !== null) {
                $task2Score = $answer->band_score;
            }
        }
        
        // Both tasks must be graded to calculate overall
        if ($task1Score === null || $task2Score === null) {
            return null;
        }
        
        // Apply IELTS Writing formula: Overall = (T1 + 2 × T2) / 3
        $overall = ($task1Score + (2 * $task2Score)) / 3;
        
        // Round to nearest 0.5 (IELTS band score format)
        $roundedOverall = round($overall * 2) / 2;
        
        // Ensure within valid range (0.0 - 9.0)
        $roundedOverall = max(0, min(9, $roundedOverall));
        
        Log::info('📊 Writing overall calculation', [
            'task1_score' => $task1Score,
            'task2_score' => $task2Score,
            'raw_overall' => $overall,
            'rounded_overall' => $roundedOverall,
        ]);
        
        return $roundedOverall;
    }

    /**
     * Calculate overall band score for Speaking test
     * For Speaking: average of all graded answers (usually just 1 answer)
     */
    private function calculateSpeakingOverallBand($submission): void
    {
        // Query using 'points_earned' (band_score is an accessor, not a real column)
        $answers = $submission->answers()
            ->whereNotNull('graded_at')
            ->whereNotNull('points_earned')
            ->get();
        
        if ($answers->isEmpty()) {
            Log::info('⚠️ No graded Speaking answers found', [
                'submission_id' => $submission->id,
            ]);
            return;
        }
        
        // Calculate average of all answer band scores (points_earned = band_score)
        $totalBandScore = $answers->sum('points_earned'); // points_earned stores band_score
        $count = $answers->count();
        $average = $totalBandScore / $count;
        
        // Round to nearest 0.5 (IELTS band score format)
        $roundedOverall = round($average * 2) / 2;
        
        // Ensure within valid range (0.0 - 9.0)
        $roundedOverall = max(0, min(9, $roundedOverall));
        
        // Update submission band score
        $submission->band_score = $roundedOverall;
        $submission->save();
        
        Log::info('✅ Calculated Speaking Overall Band', [
            'submission_id' => $submission->id,
            'answers_count' => $count,
            'total_points' => $totalBandScore,
            'average' => $average,
            'rounded_overall' => $roundedOverall,
        ]);
    }

    /**
     * Publish submission results (make visible to student)
     */
    public function publish(string $id): JsonResponse
    {
        try {
            $submission = Submission::findOrFail($id);

            // Check if can be published
            if (!$submission->canBePublished()) {
                return response()->json([
                    'success' => false,
                    'message' => $submission->isPublished() 
                        ? 'Bài làm này đã được công bố'
                        : 'Chỉ có thể công bố bài làm đã chấm điểm',
                ], 400);
            }

            // Publish
            $submission->published_at = now();
            $submission->published_by = auth()->id();
            $submission->save();

            Log::info('📢 Published submission', [
                'submission_id' => $submission->id,
                'published_by' => auth()->id(),
                'published_at' => $submission->published_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã công bố điểm thành công',
                'data' => [
                    'published_at' => $submission->published_at,
                    'published_by' => $submission->published_by,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to publish submission', [
                'submission_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể công bố điểm: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unpublish submission results (hide from student)
     */
    public function unpublish(string $id): JsonResponse
    {
        try {
            $submission = Submission::findOrFail($id);

            // Check if can be unpublished
            if (!$submission->canBeUnpublished()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bài làm này chưa được công bố',
                ], 400);
            }

            // Unpublish
            $submission->published_at = null;
            $submission->published_by = null;
            $submission->save();

            Log::info('🔒 Unpublished submission', [
                'submission_id' => $submission->id,
                'unpublished_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã thu hồi công bố thành công',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to unpublish submission', [
                'submission_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể thu hồi công bố: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk publish multiple submissions
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'required|integer|exists:submissions,id',
            ]);

            $submissionIds = $validated['submission_ids'];
            $published = 0;
            $failed = 0;

            foreach ($submissionIds as $submissionId) {
                $submission = Submission::find($submissionId);
                
                if ($submission && $submission->canBePublished()) {
                    $submission->published_at = now();
                    $submission->published_by = auth()->id();
                    $submission->save();
                    $published++;
                } else {
                    $failed++;
                }
            }

            Log::info('📢 Bulk published submissions', [
                'total' => count($submissionIds),
                'published' => $published,
                'failed' => $failed,
                'by_user' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Đã công bố {$published} bài làm thành công",
                'data' => [
                    'published' => $published,
                    'failed' => $failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to bulk publish submissions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể công bố hàng loạt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle allow_special_view setting for a submission
     */
    public function toggleSpecialView(string $id): JsonResponse
    {
        try {
            $submission = Submission::findOrFail($id);
            
            // Only the owner can toggle this setting
            if ($submission->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Only completed submissions can be shared
            if (!in_array($submission->status, [Submission::STATUS_SUBMITTED, Submission::STATUS_GRADED, Submission::STATUS_LATE])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể chia sẻ bài làm đã hoàn thành',
                ], 400);
            }

            $submission->allow_special_view = !$submission->allow_special_view;
            $submission->save();

            return response()->json([
                'success' => true,
                'message' => $submission->allow_special_view 
                    ? 'Đã cho phép người có quyền đặc biệt xem bài làm này' 
                    : 'Đã tắt chia sẻ bài làm này',
                'data' => [
                    'allow_special_view' => $submission->allow_special_view,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle special view', [
                'submission_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật cài đặt',
            ], 500);
        }
    }
}

