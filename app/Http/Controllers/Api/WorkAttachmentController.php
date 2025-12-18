<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkAttachment;
use App\Models\WorkItem;
use App\Models\WorkActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkAttachmentController extends Controller
{
    /**
     * Upload attachment
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'attachable_type' => 'required|in:work_item,work_discussion,work_submission',
            'attachable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $file = $request->file('file');

            // Determine the model class
            $attachableType = match($request->attachable_type) {
                'work_item' => \App\Models\WorkItem::class,
                'work_discussion' => \App\Models\WorkDiscussion::class,
                'work_submission' => \App\Models\WorkSubmission::class,
            };

            // Verify the attachable exists
            $attachable = $attachableType::findOrFail($request->attachable_id);

            // Get file info
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $fileType = $this->determineFileType($mimeType);

            // Store file locally first
            $filePath = $file->store('work_attachments', 'public');

            // TODO: Upload to Google Drive
            // For now, we'll just store locally
            // In the future, integrate with Google Drive API
            $googleDriveId = null;
            $googleDriveUrl = null;

            // Create attachment record
            $attachment = WorkAttachment::create([
                'attachable_type' => $attachableType,
                'attachable_id' => $request->attachable_id,
                'uploaded_by' => $user->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'google_drive_id' => $googleDriveId,
                'google_drive_url' => $googleDriveUrl,
            ]);

            // Log activity if it's a work item
            if ($attachable instanceof WorkItem) {
                WorkActivityLog::logActivity(
                    $attachable,
                    'attachment_uploaded',
                    $user,
                    null,
                    [
                        'file_name' => $fileName,
                        'file_size' => $fileSize
                    ],
                    ['attachment_id' => $attachment->id]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'File uploaded successfully',
                'data' => $attachment->load('uploader')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to upload file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function destroy($id)
    {
        $attachment = WorkAttachment::findOrFail($id);
        $user = auth()->user();

        // Check if user is the uploader or has permission
        if ($attachment->uploaded_by !== $user->id && !$user->can('work_items.delete')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // Delete file from storage
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // TODO: Delete from Google Drive if exists
            // if ($attachment->google_drive_id) {
            //     GoogleDriveService::deleteFile($attachment->google_drive_id);
            // }

            // Log activity if it's a work item
            if ($attachment->attachable instanceof WorkItem) {
                WorkActivityLog::logActivity(
                    $attachment->attachable,
                    'attachment_deleted',
                    $user,
                    [
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size
                    ],
                    null,
                    ['attachment_id' => $attachment->id]
                );
            }

            $attachment->delete();

            DB::commit();

            return response()->json(['message' => 'Attachment deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete attachment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download attachment
     */
    public function download($id)
    {
        $attachment = WorkAttachment::findOrFail($id);

        // If Google Drive URL exists, redirect to it
        if ($attachment->google_drive_url) {
            return redirect($attachment->google_drive_url);
        }

        // Otherwise, serve from local storage
        if (!$attachment->file_path || !Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Get attachments for an attachable
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attachable_type' => 'required|in:work_item,work_discussion,work_submission',
            'attachable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachableType = match($request->attachable_type) {
            'work_item' => \App\Models\WorkItem::class,
            'work_discussion' => \App\Models\WorkDiscussion::class,
            'work_submission' => \App\Models\WorkSubmission::class,
        };

        $attachments = WorkAttachment::where('attachable_type', $attachableType)
            ->where('attachable_id', $request->attachable_id)
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($attachments);
    }

    /**
     * Determine file type from MIME type
     */
    private function determineFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        if (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])) {
            return 'document';
        }

        if (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])) {
            return 'spreadsheet';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'other';
    }
}
