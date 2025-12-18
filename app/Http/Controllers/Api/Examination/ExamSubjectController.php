<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\ExamSubject;
use App\Models\Examination\ExamSubjectCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExamSubjectController extends Controller
{
    /**
     * Get all subjects with their categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamSubject::with(['categories' => function ($q) {
            $q->active()->ordered();
        }]);

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $subjects = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    /**
     * Get a single subject with categories
     */
    public function show(string $id): JsonResponse
    {
        $subject = ExamSubject::with(['categories' => function ($q) {
            $q->active()->ordered();
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    /**
     * Create a new subject
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:exam_subjects,code',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject = ExamSubject::create($request->only([
            'name', 'code', 'description', 'icon', 'color', 'is_active', 'sort_order'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject,
        ], 201);
    }

    /**
     * Update a subject
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $subject = ExamSubject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:exam_subjects,code,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject->update($request->only([
            'name', 'code', 'description', 'icon', 'color', 'is_active', 'sort_order'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject->fresh(),
        ]);
    }

    /**
     * Delete a subject
     */
    public function destroy(string $id): JsonResponse
    {
        $subject = ExamSubject::findOrFail($id);

        // Check if has questions or tests
        if ($subject->questions()->exists() || $subject->tests()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subject that has questions or tests',
            ], 400);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully',
        ]);
    }

    /**
     * Get categories for a subject
     */
    public function categories(string $id): JsonResponse
    {
        $subject = ExamSubject::findOrFail($id);

        $categories = $subject->categories()->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Add category to subject
     */
    public function addCategory(Request $request, string $id): JsonResponse
    {
        $subject = ExamSubject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check unique code within subject
        $exists = $subject->categories()->where('code', $request->code)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Category code already exists in this subject',
            ], 422);
        }

        $category = $subject->categories()->create($request->only([
            'name', 'code', 'description', 'icon', 'is_active', 'sort_order'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Category added successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request, string $subjectId, string $categoryId): JsonResponse
    {
        $category = ExamSubjectCategory::where('subject_id', $subjectId)
            ->findOrFail($categoryId);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check unique code if changing
        if ($request->code && $request->code !== $category->code) {
            $exists = ExamSubjectCategory::where('subject_id', $subjectId)
                ->where('code', $request->code)
                ->where('id', '!=', $categoryId)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category code already exists in this subject',
                ], 422);
            }
        }

        $category->update($request->only([
            'name', 'code', 'description', 'icon', 'is_active', 'sort_order'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Delete a category
     */
    public function deleteCategory(string $subjectId, string $categoryId): JsonResponse
    {
        $category = ExamSubjectCategory::where('subject_id', $subjectId)
            ->findOrFail($categoryId);

        // Check if has questions
        if ($category->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has questions',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
