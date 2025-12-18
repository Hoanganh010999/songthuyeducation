<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\PracticeTest;
use App\Models\Examination\Test;
use App\Models\Examination\Submission;
use App\Models\Examination\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PracticeTestController extends Controller
{
    /**
     * Display a listing of practice tests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PracticeTest::with([
                'readingTest:id,title,type,subtype',
                'writingTest:id,title,type,subtype',
                'listeningTest:id,title,type,subtype',
                'speakingTest:id,title,type,subtype',
                'creator:id,name'
            ])
                ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
                ->when($request->difficulty, fn($q, $difficulty) => $q->where('difficulty', $difficulty))
                ->when($request->search, function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->orderBy($request->sort_by ?? 'order', $request->sort_order ?? 'asc');

            $perPage = min($request->per_page ?? 20, 100);

            if ($request->paginate === 'false' || $request->paginate === false) {
                $practiceTests = $query->get();
                return response()->json([
                    'success' => true,
                    'data' => $practiceTests,
                ]);
            }

            $practiceTests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $practiceTests,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching practice tests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch practice tests',
            ], 500);
        }
    }

    /**
     * Store a newly created practice test
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'reading_test_id' => 'nullable|exists:tests,id',
                'writing_test_id' => 'nullable|exists:tests,id',
                'listening_test_id' => 'nullable|exists:tests,id',
                'speaking_test_id' => 'nullable|exists:tests,id',
                'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
                'is_active' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            // Set created_by to current user
            $validated['created_by'] = auth()->id();

            // Set branch_id if user has one
            $user = auth()->user();
            if ($user && $user->branch_id) {
                $validated['branch_id'] = $user->branch_id;
            }

            $practiceTest = PracticeTest::create($validated);

            // Load relationships
            $practiceTest->load([
                'readingTest:id,title,type,subtype',
                'writingTest:id,title,type,subtype',
                'listeningTest:id,title,type,subtype',
                'speakingTest:id,title,type,subtype',
                'creator:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Practice test created successfully',
                'data' => $practiceTest,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating practice test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create practice test',
            ], 500);
        }
    }

    /**
     * Display the specified practice test
     */
    public function show(int $id): JsonResponse
    {
        try {
            $practiceTest = PracticeTest::with([
                'readingTest:id,title,type,subtype,status,settings',
                'writingTest:id,title,type,subtype,status,settings',
                'listeningTest:id,title,type,subtype,status,settings',
                'speakingTest:id,title,type,subtype,status,settings',
                'creator:id,name',
                'branch:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $practiceTest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Practice test not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching practice test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch practice test',
            ], 500);
        }
    }

    /**
     * Update the specified practice test
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $practiceTest = PracticeTest::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'reading_test_id' => 'nullable|exists:tests,id',
                'writing_test_id' => 'nullable|exists:tests,id',
                'listening_test_id' => 'nullable|exists:tests,id',
                'speaking_test_id' => 'nullable|exists:tests,id',
                'difficulty' => ['sometimes', 'required', Rule::in(['beginner', 'intermediate', 'advanced'])],
                'is_active' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            $practiceTest->update($validated);

            // Reload relationships
            $practiceTest->load([
                'readingTest:id,title,type,subtype',
                'writingTest:id,title,type,subtype',
                'listeningTest:id,title,type,subtype',
                'speakingTest:id,title,type,subtype',
                'creator:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Practice test updated successfully',
                'data' => $practiceTest,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Practice test not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating practice test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update practice test',
            ], 500);
        }
    }

    /**
     * Remove the specified practice test
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $practiceTest = PracticeTest::findOrFail($id);
            $practiceTest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Practice test deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Practice test not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting practice test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete practice test',
            ], 500);
        }
    }

    /**
     * Get available tests for each skill type
     */
    public function availableTests(Request $request): JsonResponse
    {
        try {
            $type = $request->type; // reading, writing, listening, speaking

            $query = Test::where('type', 'ielts')
                ->where('status', 'active')
                ->select('id', 'title', 'type', 'subtype', 'description');

            if ($type) {
                $query->where('subtype', $type);
            }

            $tests = $query->orderBy('title')->get();

            // Group by subtype if no specific type requested
            if (!$type) {
                $grouped = $tests->groupBy('subtype');
                return response()->json([
                    'success' => true,
                    'data' => $grouped,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tests,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching available tests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available tests',
            ], 500);
        }
    }

    /**
     * Duplicate a practice test
     */
    public function duplicate(int $id): JsonResponse
    {
        try {
            $original = PracticeTest::findOrFail($id);

            $duplicate = $original->replicate();
            $duplicate->title = $original->title . ' (Copy)';
            $duplicate->created_by = auth()->id();
            $duplicate->created_at = now();
            $duplicate->updated_at = now();
            $duplicate->save();

            $duplicate->load([
                'readingTest:id,title,type,subtype',
                'writingTest:id,title,type,subtype',
                'listeningTest:id,title,type,subtype',
                'speakingTest:id,title,type,subtype',
                'creator:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Practice test duplicated successfully',
                'data' => $duplicate,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Practice test not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error duplicating practice test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate practice test',
            ], 500);
        }
    }

    /**
     * Submit a practice test (direct submission without start)
     * Note: test_id is the ID from tests table (individual skill test), 
     * not practice_tests table (full test package)
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            Log::info('📝 Practice test submission received', [
                'user_id' => auth()->id(),
                'test_id' => $request->test_id,
                'has_answers' => $request->has('answers'),
                'answers_count' => is_array($request->answers) ? count($request->answers) : 0,
            ]);

            $validated = $request->validate([
                'test_id' => 'required|exists:tests,id',
                'answers' => 'nullable|array',
                'time_taken' => 'nullable|integer|min:0',
            ]);

            Log::info('✅ Validation passed', [
                'validated' => array_keys($validated)
            ]);

            $test = Test::findOrFail($validated['test_id']);
            $userId = auth()->id();
            
            Log::info('📚 Test found', [
                'test_id' => $test->id,
                'test_type' => $test->type,
                'test_subtype' => $test->subtype,
            ]);

            DB::beginTransaction();

            // Find or create "Self Practice" assignment for this test
            // Create individual assignment for this user
            $assignment = Assignment::firstOrCreate(
                [
                    'test_id' => $test->id,
                    'title' => 'Self Practice - ' . $test->title,
                    'assign_type' => 'user', // Individual assignment
                ],
                [
                    'assigned_by' => $userId,
                    'start_date' => now(),
                    'due_date' => now()->addYears(10),
                    'status' => 'active',
                    'max_attempts' => 999,
                ]
            );

            // Create assignment target to link user with assignment
            \App\Models\Examination\AssignmentTarget::firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'targetable_type' => 'App\Models\User',
                    'targetable_id' => $userId,
                ]
            );

            Log::info('📋 Assignment created/found', [
                'assignment_id' => $assignment->id,
                'title' => $assignment->title,
                'user_id' => $userId,
            ]);

            // Create submission linked to assignment
            $submission = Submission::create([
                'uuid' => (string) Str::uuid(),
                'assignment_id' => $assignment->id,
                'practice_test_id' => null, // Individual skill test, not full practice test
                'user_id' => $userId,
                'started_at' => now()->subSeconds($validated['time_taken'] ?? 0),
                'submitted_at' => now(),
                'status' => Submission::STATUS_SUBMITTED,
                'time_spent' => $validated['time_taken'] ?? 0,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                // Store answers in activity_log for reference
                'activity_log' => [
                    'test_id' => $test->id,
                    'test_type' => $test->type,
                    'test_subtype' => $test->subtype,
                    'answers' => $validated['answers'] ?? [],
                    'submitted_from' => 'ielts_practice',
                ],
            ]);

            // Save answers to submission_answers table
            if (!empty($validated['answers'])) {
                Log::info('💾 Saving answers to submission_answers', [
                    'submission_id' => $submission->id,
                    'answers_count' => count($validated['answers'])
                ]);

                foreach ($validated['answers'] as $questionNumber => $answer) {
                    // Determine if this is a writing answer (task1/task2)
                    $isWritingAnswer = in_array($questionNumber, ['task1', 'task2']);
                    
                    // For writing: store text in text_answer column and identifier in answer JSON
                    if ($isWritingAnswer) {
                        $textAnswer = is_string($answer) ? $answer : (is_array($answer) ? json_encode($answer) : '');
                        $answerJson = json_encode(['task' => $questionNumber]);
                        // Use task number (1 or 2) as question_number
                        $questionNumberInt = $questionNumber === 'task1' ? 1 : 2;
                    } else {
                        // For other tests: encode answer as JSON
                        $textAnswer = null;
                        $answerJson = is_array($answer) ? json_encode($answer) : json_encode($answer);
                        // Convert question_number to integer if numeric, otherwise null
                        $questionNumberInt = is_numeric($questionNumber) ? (int)$questionNumber : null;
                    }
                    
                    DB::table('submission_answers')->insert([
                        'submission_id' => $submission->id,
                        'question_id' => null, // We'll need to map question numbers to IDs if needed
                        'question_number' => $questionNumberInt,
                        'answer' => $answerJson,
                        'text_answer' => $textAnswer,
                        'is_correct' => null, // Will be set during grading
                        'points_earned' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info('✅ Answers saved successfully', [
                    'submission_id' => $submission->id,
                    'answers_saved' => count($validated['answers'])
                ]);
            } else {
                Log::warning('⚠️ No answers provided in submission', [
                    'submission_id' => $submission->id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'uuid' => $submission->uuid,
                    'status' => $submission->status,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting practice test: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'test_id' => $request->test_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit practice test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
