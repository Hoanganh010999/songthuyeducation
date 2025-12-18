<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\QuestionTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class QuestionTagController extends Controller
{
    /**
     * Display a listing of question tags.
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuestionTag::with('creator')
            ->when($request->search, function ($q, $search) {
                $q->search($search);
            })
            ->when($request->created_by, function ($q, $userId) {
                $q->byCreator($userId);
            })
            ->orderBy($request->sort_by ?? 'name', $request->sort_order ?? 'asc');

        if ($request->boolean('paginate', false)) {
            $perPage = min($request->per_page ?? 20, 100);
            $tags = $query->paginate($perPage);
        } else {
            $tags = $query->get();
        }

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:question_tags,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tag = QuestionTag::create([
                'name' => $request->name,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'data' => $tag,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified tag.
     */
    public function show(int $id): JsonResponse
    {
        $tag = QuestionTag::with(['creator', 'questions'])->find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tag,
        ]);
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tag = QuestionTag::find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }

        // Check if user can update this tag
        if ($tag->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update tags you created',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:question_tags,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tag->update([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'data' => $tag,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(int $id): JsonResponse
    {
        $tag = QuestionTag::find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }

        // Check if user can delete this tag
        if ($tag->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete tags you created',
            ], 403);
        }

        try {
            $tag->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tag deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag: ' . $e->getMessage(),
            ], 500);
        }
    }
}
