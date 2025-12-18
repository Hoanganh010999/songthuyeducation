<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkTagController extends Controller
{
    /**
     * Display a listing of tags
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = WorkTag::where('branch_id', $user->primary_branch_id)
            ->withCount('workItems');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $tags = $query->get();

        return response()->json($tags);
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:work_tags,name,NULL,id,branch_id,' . auth()->user()->primary_branch_id,
            'color' => 'nullable|string|max:7', // Hex color code
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = auth()->user();

            $tag = WorkTag::create([
                'name' => $request->name,
                'color' => $request->input('color', $this->generateRandomColor()),
                'branch_id' => $user->primary_branch_id,
            ]);

            return response()->json([
                'message' => 'Tag created successfully',
                'data' => $tag
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create tag: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified tag
     */
    public function show($id)
    {
        $tag = WorkTag::with(['workItems' => function ($query) {
            $query->with(['creator', 'assignments.user'])
                  ->orderBy('created_at', 'desc')
                  ->limit(20);
        }])
        ->withCount('workItems')
        ->findOrFail($id);

        // Check if tag belongs to user's branch
        $user = auth()->user();
        if ($tag->branch_id !== $user->primary_branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($tag);
    }

    /**
     * Update the specified tag
     */
    public function update(Request $request, $id)
    {
        $tag = WorkTag::findOrFail($id);

        // Check if tag belongs to user's branch
        $user = auth()->user();
        if ($tag->branch_id !== $user->primary_branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:work_tags,name,' . $id . ',id,branch_id,' . $user->primary_branch_id,
            'color' => 'nullable|string|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $tag->update($request->only(['name', 'color']));

            return response()->json([
                'message' => 'Tag updated successfully',
                'data' => $tag
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update tag: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified tag
     */
    public function destroy($id)
    {
        $tag = WorkTag::findOrFail($id);

        // Check if tag belongs to user's branch
        $user = auth()->user();
        if ($tag->branch_id !== $user->primary_branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Detach from all work items
            $tag->workItems()->detach();

            // Delete tag
            $tag->delete();

            return response()->json(['message' => 'Tag deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete tag: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get popular tags
     */
    public function popular()
    {
        $user = auth()->user();

        $tags = WorkTag::where('branch_id', $user->primary_branch_id)
            ->withCount('workItems')
            ->orderBy('work_items_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($tags);
    }

    /**
     * Generate a random color
     */
    private function generateRandomColor()
    {
        $colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // yellow
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#06B6D4', // cyan
            '#F97316', // orange
            '#14B8A6', // teal
            '#6366F1', // indigo
        ];

        return $colors[array_rand($colors)];
    }
}
