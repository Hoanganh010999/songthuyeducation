<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDiscussion;
use App\Models\WorkItem;
use App\Models\WorkActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkDiscussionController extends Controller
{
    /**
     * Get discussions for a work item
     */
    public function index($workItemId)
    {
        $workItem = WorkItem::findOrFail($workItemId);

        // Check permission to view work item
        // (Simplified - should use the same permission logic as WorkItemController)

        $discussions = WorkDiscussion::where('work_item_id', $workItemId)
            ->with(['user', 'replies.user'])
            ->topLevel()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($discussions);
    }

    /**
     * Store a new discussion/comment
     */
    public function store(Request $request, $workItemId)
    {
        $workItem = WorkItem::findOrFail($workItemId);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:work_discussions,id',
            'is_internal' => 'boolean',
            'mentions' => 'nullable|array',
            'mentions.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();

            $discussion = WorkDiscussion::create([
                'work_item_id' => $workItemId,
                'user_id' => $user->id,
                'parent_id' => $request->parent_id,
                'content' => $request->content,
                'is_internal' => $request->input('is_internal', false),
                'mentions' => $request->mentions,
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $workItem,
                $request->parent_id ? 'reply_added' : 'comment_added',
                $user,
                null,
                ['discussion_id' => $discussion->id],
                ['mentions' => $request->mentions]
            );

            DB::commit();

            return response()->json([
                'message' => 'Discussion posted successfully',
                'data' => $discussion->load('user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to post discussion: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a discussion
     */
    public function update(Request $request, $id)
    {
        $discussion = WorkDiscussion::findOrFail($id);
        $user = auth()->user();

        // Only the author can edit
        if ($discussion->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldContent = $discussion->content;

            $discussion->update([
                'content' => $request->content,
                'is_edited' => true,
            ]);

            // Log activity
            WorkActivityLog::logActivity(
                $discussion->workItem,
                'comment_updated',
                $user,
                ['content' => $oldContent],
                ['content' => $request->content],
                ['discussion_id' => $discussion->id]
            );

            DB::commit();

            return response()->json([
                'message' => 'Discussion updated successfully',
                'data' => $discussion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update discussion: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a discussion
     */
    public function destroy($id)
    {
        $discussion = WorkDiscussion::findOrFail($id);
        $user = auth()->user();

        // Only the author or admin can delete
        if ($discussion->user_id !== $user->id && !$user->can('work_items.delete')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // Log activity before deletion
            WorkActivityLog::logActivity(
                $discussion->workItem,
                'comment_deleted',
                $user,
                ['content' => $discussion->content],
                null,
                ['discussion_id' => $discussion->id]
            );

            $discussion->delete();

            DB::commit();

            return response()->json(['message' => 'Discussion deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete discussion: ' . $e->getMessage()], 500);
        }
    }
}
