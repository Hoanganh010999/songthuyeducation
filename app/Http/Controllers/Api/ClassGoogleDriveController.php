<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GoogleDriveItem;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClassGoogleDriveController extends Controller
{
    /**
     * Get all unit folders for a class with their subfolders
     */
    public function getClassUnitFolders($classId)
    {
        try {
            $class = ClassModel::findOrFail($classId);

            if (!$class->google_drive_folder_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.no_google_drive_folder'),
                ], 404);
            }

            // Get all unit folders from database
            $unitFolders = GoogleDriveItem::where('parent_id', $class->google_drive_folder_id)
                ->where('branch_id', $class->branch_id)
                ->where('type', 'folder')
                ->where('is_trashed', false)
                ->whereRaw("metadata->>'type' = 'class_unit'")
                ->orderByRaw("CAST(metadata->>'unit_number' AS INTEGER)")
                ->get();

            $result = $unitFolders->map(function($unitFolder) {
                $metadata = $unitFolder->metadata ?? [];
                return [
                    'unit_number' => $metadata['unit_number'] ?? null,
                    'unit_folder_id' => $unitFolder->google_id,
                    'unit_folder_name' => $unitFolder->name,
                    'materials_folder_id' => $metadata['materials_folder_id'] ?? null,
                    'homework_folder_id' => $metadata['homework_folder_id'] ?? null,
                    'lesson_plans_folder_id' => $metadata['lesson_plans_folder_id'] ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('[Class Google Drive] Error getting unit folders', [
                'error' => $e->getMessage(),
                'class_id' => $classId,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Upload lesson plan to a specific unit's Lesson Plans folder
     */
    public function uploadLessonPlan(Request $request, $classId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_number' => 'required|integer|min:1',
                'lesson_name' => 'required|string|max:255',
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.validation_failed'),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $class = ClassModel::findOrFail($classId);

            if (!$class->google_drive_folder_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.no_google_drive_folder'),
                ], 404);
            }

            // Get unit folder
            $unitFolder = GoogleDriveItem::where('parent_id', $class->google_drive_folder_id)
                ->where('branch_id', $class->branch_id)
                ->whereRaw("metadata->>'type' = 'class_unit'")
                ->whereRaw("metadata->>'unit_number' = ?", [$request->unit_number])
                ->first();

            if (!$unitFolder) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.unit_folder_not_found'),
                ], 404);
            }

            $metadata = $unitFolder->metadata ?? [];
            $lessonPlansFolderId = $metadata['lesson_plans_folder_id'] ?? null;

            if (!$lessonPlansFolderId) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.lesson_plans_folder_not_found'),
                ], 404);
            }

            // Upload file to Google Drive
            $setting = GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $result = $service->uploadLessonPlan(
                $lessonPlansFolderId,
                $request->file('file'),
                $class->code,
                $request->unit_number,
                $request->lesson_name
            );

            Log::info('[Class Google Drive] Lesson plan uploaded', [
                'class_id' => $classId,
                'unit_number' => $request->unit_number,
                'file_id' => $result['file_id'],
                'file_name' => $result['file_name'],
            ]);

            return response()->json([
                'success' => true,
                'message' => __('classes.lesson_plan_uploaded'),
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('[Class Google Drive] Error uploading lesson plan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class_id' => $classId,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('classes.lesson_plan_upload_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all lesson plans for a specific unit
     */
    public function getLessonPlans($classId, $unitNumber)
    {
        try {
            $class = ClassModel::findOrFail($classId);

            if (!$class->google_drive_folder_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.no_google_drive_folder'),
                ], 404);
            }

            // Get unit folder
            $unitFolder = GoogleDriveItem::where('parent_id', $class->google_drive_folder_id)
                ->where('branch_id', $class->branch_id)
                ->whereRaw("metadata->>'type' = 'class_unit'")
                ->whereRaw("metadata->>'unit_number' = ?", [$unitNumber])
                ->first();

            if (!$unitFolder) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.unit_folder_not_found'),
                ], 404);
            }

            $metadata = $unitFolder->metadata ?? [];
            $lessonPlansFolderId = $metadata['lesson_plans_folder_id'] ?? null;

            if (!$lessonPlansFolderId) {
                return response()->json([
                    'success' => false,
                    'message' => __('classes.lesson_plans_folder_not_found'),
                ], 404);
            }

            // Get lesson plans from Google Drive
            $setting = GoogleDriveSetting::where('branch_id', $class->branch_id)->first();
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $lessonPlans = $service->getLessonPlansByClassCode($lessonPlansFolderId, $class->code);

            return response()->json([
                'success' => true,
                'data' => $lessonPlans,
            ]);

        } catch (\Exception $e) {
            Log::error('[Class Google Drive] Error getting lesson plans', [
                'error' => $e->getMessage(),
                'class_id' => $classId,
                'unit_number' => $unitNumber,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
            ], 500);
        }
    }
}
