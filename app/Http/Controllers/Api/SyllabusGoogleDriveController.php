<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyllabusGoogleDriveController extends Controller
{
    /**
     * Upload file to syllabus unit folder (Materials, Homework, or Lesson Plans)
     * Creates unit folder on-demand if it doesn't exist
     */
    public function uploadToUnit(Request $request, $syllabusId)
    {
        try {
            $validated = $request->validate([
                'unit_number' => 'required|integer|min:1',
                'folder_type' => 'required|in:materials,homework,lesson_plans',
                'file' => 'required|file|max:102400', // 100MB
            ]);
            
            // Get syllabus
            $syllabus = LessonPlan::findOrFail($syllabusId);
            
            if (!$syllabus->google_drive_folder_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Syllabus chưa có folder Google Drive',
                ], 400);
            }
            
            // Get Google Drive settings
            $setting = GoogleDriveSetting::where('branch_id', $syllabus->branch_id)->active()->first();
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive chưa được cấu hình',
                ], 400);
            }
            
            $service = new GoogleDriveService($setting);
            
            // Create or get unit folder (with Materials, Homework, Lesson Plans subfolders)
            $unitFolders = $service->createOrGetUnitFolder(
                $syllabus->google_drive_folder_id,
                $validated['unit_number'],
                $syllabus->id
            );
            
            // Get target folder ID based on type
            $targetFolderId = match($validated['folder_type']) {
                'materials' => $unitFolders['materials_folder_id'],
                'homework' => $unitFolders['homework_folder_id'],
                'lesson_plans' => $unitFolders['lesson_plans_folder_id'],
            };
            
            // Upload file
            $item = $service->uploadFile(
                $request->file('file'),
                $targetFolderId,
                $syllabus->branch_id,
                $request->user()->id
            );
            
            // Save folder IDs to lesson_plan_sessions for easy retrieval
            $session = \App\Models\LessonPlanSession::where('lesson_plan_id', $syllabusId)
                ->where('session_number', $validated['unit_number'])
                ->first();
            
            if ($session) {
                // Check if unit folder was just created (not existed before)
                $wasCreated = !$session->google_drive_folder_id || ($unitFolders['was_created'] ?? false);
                
                $updateData = [
                    'google_drive_folder_id' => $unitFolders['unit_folder_id'] ?? $session->google_drive_folder_id,
                    'materials_folder_id' => $unitFolders['materials_folder_id'] ?? $session->materials_folder_id,
                    'homework_folder_id' => $unitFolders['homework_folder_id'] ?? $session->homework_folder_id,
                    'lesson_plans_folder_id' => $unitFolders['lesson_plans_folder_id'] ?? $session->lesson_plans_folder_id,
                ];
                $session->update($updateData);
                
                if ($wasCreated) {
                    Log::info('[SyllabusGoogleDrive] Unit folder created and saved', [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $validated['unit_number'],
                        'unit_folder_id' => $unitFolders['unit_folder_id'],
                        'created_at' => $unitFolders['unit_folder_created_at'] ?? now()->toDateTimeString(),
                    ]);
                }
            }
            
            Log::info('[SyllabusGoogleDrive] File uploaded to unit', [
                'syllabus_id' => $syllabusId,
                'unit_number' => $validated['unit_number'],
                'folder_type' => $validated['folder_type'],
                'file_id' => $item->google_id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Upload thành công',
                'data' => [
                    'file' => $item,
                    'unit_folders' => $unitFolders,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[SyllabusGoogleDrive] Upload failed', [
                'error' => $e->getMessage(),
                'syllabus_id' => $syllabusId,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get files in a specific unit folder
     */
    public function getUnitFiles(Request $request, $syllabusId, $unitNumber)
    {
        try {
            $folderType = $request->input('folder_type', 'materials');
            
            // Get syllabus
            $syllabus = LessonPlan::findOrFail($syllabusId);
            
            if (!$syllabus->google_drive_folder_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }
            
            // Get Google Drive settings
            $setting = GoogleDriveSetting::where('branch_id', $syllabus->branch_id)->active()->first();
            if (!$setting) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }
            
            $service = new GoogleDriveService($setting);
            
            // Get unit folders (will create if doesn't exist)
            $unitFolders = $service->createOrGetUnitFolder(
                $syllabus->google_drive_folder_id,
                $unitNumber,
                $syllabus->id
            );
            
            // Save folder IDs to lesson_plan_sessions for easy retrieval
            $session = \App\Models\LessonPlanSession::where('lesson_plan_id', $syllabusId)
                ->where('session_number', $unitNumber)
                ->first();
            
            if ($session) {
                // Check if unit folder was just created (not existed before)
                $wasCreated = !$session->google_drive_folder_id || ($unitFolders['was_created'] ?? false);
                
                $updateData = [
                    'google_drive_folder_id' => $unitFolders['unit_folder_id'] ?? $session->google_drive_folder_id,
                    'materials_folder_id' => $unitFolders['materials_folder_id'] ?? $session->materials_folder_id,
                    'homework_folder_id' => $unitFolders['homework_folder_id'] ?? $session->homework_folder_id,
                    'lesson_plans_folder_id' => $unitFolders['lesson_plans_folder_id'] ?? $session->lesson_plans_folder_id,
                ];
                $session->update($updateData);
                
                if ($wasCreated) {
                    Log::info('[SyllabusGoogleDrive] Unit folder created and saved (from getUnitFiles)', [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $unitNumber,
                        'unit_folder_id' => $unitFolders['unit_folder_id'],
                        'created_at' => $unitFolders['unit_folder_created_at'] ?? now()->toDateTimeString(),
                    ]);
                }
            }
            
            // Get target folder ID
            $targetFolderId = match($folderType) {
                'materials' => $unitFolders['materials_folder_id'],
                'homework' => $unitFolders['homework_folder_id'],
                'lesson_plans' => $unitFolders['lesson_plans_folder_id'],
                default => $unitFolders['materials_folder_id'],
            };
            
            // List files
            $files = $service->listFiles($targetFolderId);
            
            return response()->json([
                'success' => true,
                'data' => $files['files'] ?? [],
                'folder_ids' => $unitFolders,
            ]);
        } catch (\Exception $e) {
            Log::error('[SyllabusGoogleDrive] Failed to get files', [
                'error' => $e->getMessage(),
                'syllabus_id' => $syllabusId,
                'unit_number' => $unitNumber,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải files: ' . $e->getMessage(),
            ], 500);
        }
    }
}

