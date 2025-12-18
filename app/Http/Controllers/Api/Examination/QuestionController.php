<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Question;
use App\Models\Examination\QuestionCategory;
use App\Models\Examination\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Question::with(['category', 'options', 'creator', 'subject', 'subjectCategory', 'questionTags'])
            ->when($request->category_id, function ($q, $categoryId) {
                $q->byCategory($categoryId, $request->boolean('include_children', true));
            })
            ->when($request->subject_id, fn($q, $subjectId) => $q->where('subject_id', $subjectId))
            ->when($request->subject_category_id, fn($q, $catId) => $q->where('subject_category_id', $catId))
            ->when($request->skill, fn($q, $skill) => $q->bySkill($skill))
            ->when($request->difficulty, fn($q, $difficulty) => $q->byDifficulty($difficulty))
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereJsonContains('tags', $search);
                });
            })
            // Note: Examination module is global - no branch filtering
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc');

        $perPage = min($request->per_page ?? 20, 100);
        $questions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:question_categories,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'subject_category_id' => 'nullable|exists:exam_subject_categories,id',
            'skill' => 'required|in:' . implode(',', array_keys(Question::getSkills())),
            'difficulty' => 'required|in:' . implode(',', array_keys(Question::getDifficulties())),
            'type' => 'required|in:' . implode(',', array_keys(Question::getTypes())),
            'title' => 'required|string|max:500',
            'content' => 'nullable|array',
            'explanation' => 'nullable|string',
            'correct_answer' => 'nullable',
            'audio_track_id' => 'nullable|exists:audio_tracks,id',
            'passage_id' => 'nullable|exists:reading_passages,id',
            'image_url' => 'nullable|string|max:500',
            'points' => 'nullable|numeric|min:0',
            'time_limit' => 'nullable|integer|min:0',
            'partial_credit' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:question_tags,id',
            'status' => 'nullable|in:draft,active,archived',
            'options' => 'nullable|array',
            'options.*.content' => 'required_with:options|string',
            'options.*.is_correct' => 'nullable|boolean',
            'options.*.feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $question = Question::create([
                'uuid' => (string) Str::uuid(),
                'category_id' => $request->category_id,
                'subject_id' => $request->subject_id,
                'subject_category_id' => $request->subject_category_id,
                'skill' => $request->skill,
                'difficulty' => $request->difficulty,
                'type' => $request->type,
                'title' => $request->title,
                'content' => $request->content,
                'explanation' => $request->explanation,
                'correct_answer' => $request->correct_answer,
                'audio_track_id' => $request->audio_track_id,
                'passage_id' => $request->passage_id,
                'image_url' => $request->image_url,
                'points' => $request->points ?? 1,
                'time_limit' => $request->time_limit,
                'partial_credit' => $request->boolean('partial_credit', false),
                'settings' => $request->settings,
                'tags' => $request->tags,
                'branch_id' => null, // Examination module is global
                'created_by' => auth()->id(),
                'status' => $request->status ?? 'draft',
            ]);

            // Create options if provided
            if ($request->has('options')) {
                $labels = range('A', 'Z');
                foreach ($request->options as $index => $optionData) {
                    $question->options()->create([
                        'content' => $optionData['content'],
                        'label' => $labels[$index] ?? (string)($index + 1),
                        'is_correct' => $optionData['is_correct'] ?? false,
                        'sort_order' => $index,
                        'feedback' => $optionData['feedback'] ?? null,
                        'image_url' => $optionData['image_url'] ?? null,
                    ]);
                }
            }

            // Sync tags if provided
            if ($request->has('tag_ids')) {
                $question->questionTags()->sync($request->tag_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => $question->load(['category', 'options', 'questionTags']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating question: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified question.
     */
    public function show(string $id): JsonResponse
    {
        $question = Question::with([
            'category',
            'options',
            'audioTrack',
            'passage',
            'creator',
            'questionTags',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $question = Question::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:question_categories,id',
            'skill' => 'nullable|in:' . implode(',', array_keys(Question::getSkills())),
            'difficulty' => 'nullable|in:' . implode(',', array_keys(Question::getDifficulties())),
            'type' => 'nullable|in:' . implode(',', array_keys(Question::getTypes())),
            'title' => 'nullable|string|max:500',
            'content' => 'nullable|array',
            'explanation' => 'nullable|string',
            'correct_answer' => 'nullable',
            'points' => 'nullable|numeric|min:0',
            'time_limit' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,active,archived',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:question_tags,id',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $question->update($request->only([
                'category_id', 'skill', 'difficulty', 'type', 'title',
                'content', 'explanation', 'correct_answer', 'audio_track_id',
                'passage_id', 'image_url', 'points', 'time_limit',
                'partial_credit', 'settings', 'tags', 'status',
            ]));

            // Update options if provided
            if ($request->has('options')) {
                $question->options()->delete();
                $labels = range('A', 'Z');
                foreach ($request->options as $index => $optionData) {
                    $question->options()->create([
                        'content' => $optionData['content'],
                        'label' => $labels[$index] ?? (string)($index + 1),
                        'is_correct' => $optionData['is_correct'] ?? false,
                        'sort_order' => $index,
                        'feedback' => $optionData['feedback'] ?? null,
                        'image_url' => $optionData['image_url'] ?? null,
                    ]);
                }
            }

            // Sync tags if provided
            if ($request->has('tag_ids')) {
                $question->questionTags()->sync($request->tag_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question->fresh(['category', 'options', 'questionTags']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating question: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified question.
     */
    public function destroy(string $id): JsonResponse
    {
        $question = Question::findOrFail($id);

        // Check if question is used in any test
        if ($question->testQuestions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete question that is used in tests',
            ], 400);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully',
        ]);
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(string $id): JsonResponse
    {
        $question = Question::with('options')->findOrFail($id);

        try {
            DB::beginTransaction();

            $newQuestion = $question->replicate(['uuid']);
            $newQuestion->uuid = (string) Str::uuid();
            $newQuestion->title = $question->title . ' (Copy)';
            $newQuestion->status = 'draft';
            $newQuestion->created_by = auth()->id();
            $newQuestion->save();

            // Duplicate options
            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question duplicated successfully',
                'data' => $newQuestion->load('options'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating question: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get question types.
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'types' => Question::getTypes(),
                'skills' => Question::getSkills(),
                'difficulties' => Question::getDifficulties(),
            ],
        ]);
    }

    /**
     * Bulk import questions.
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:' . implode(',', array_keys(Question::getTypes())),
            'questions.*.title' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $imported = [];
            foreach ($request->questions as $questionData) {
                $question = Question::create([
                    'uuid' => (string) Str::uuid(),
                    'category_id' => $questionData['category_id'] ?? null,
                    'subject_id' => $questionData['subject_id'] ?? null,
                    'subject_category_id' => $questionData['subject_category_id'] ?? null,
                    'skill' => $questionData['skill'] ?? 'general',
                    'difficulty' => $questionData['difficulty'] ?? 'medium',
                    'type' => $questionData['type'],
                    'title' => $questionData['title'],
                    'content' => $questionData['content'] ?? null,
                    'explanation' => $questionData['explanation'] ?? null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'points' => $questionData['points'] ?? 1,
                    'tags' => $questionData['tags'] ?? null,
                    'settings' => $questionData['settings'] ?? null,
                    'branch_id' => null, // Examination module is global
                    'created_by' => auth()->id(),
                    'status' => 'draft',
                ]);

                if (isset($questionData['options'])) {
                    $labels = range('A', 'Z');
                    foreach ($questionData['options'] as $index => $optionData) {
                        $question->options()->create([
                            'content' => $optionData['content'],
                            'label' => $labels[$index] ?? (string)($index + 1),
                            'is_correct' => $optionData['is_correct'] ?? false,
                            'sort_order' => $index,
                        ]);
                    }
                }

                $imported[] = $question;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($imported) . ' questions imported successfully',
                'data' => ['count' => count($imported)],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error importing questions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
