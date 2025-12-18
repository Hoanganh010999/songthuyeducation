<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserGoogleDriveController extends Controller
{
    /**
     * Encode phone number for folder name using hash
     * This ensures uniqueness and prevents reverse engineering
     */
    protected function encodePhone($phone)
    {
        // Remove non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Use SHA256 hash and take first 8 characters for readability
        // This gives us 8 alphanumeric characters that are:
        // 1. Unique for each phone
        // 2. Consistent (same phone = same hash)
        // 3. Cannot be reversed to get original phone
        $hash = hash('sha256', $phone);
        
        // Take first 8 chars: enough for uniqueness, short enough for folder names
        return substr($hash, 0, 8);
    }

    /**
     * Generate folder name with branch
     */
    protected function generateFolderName($user, $branchId)
    {
        $encodedPhone = $this->encodePhone($user->phone);
        return "{$branchId}.{$user->name}.{$encodedPhone}";
    }

    /**
     * Check if folder with similar name exists
     */
    protected function checkExistingFolder($service, $rootFolderId, $folderName)
    {
        try {
            // List files in root folder
            $response = $service->listFiles($rootFolderId);
            
            if (isset($response['files'])) {
                foreach ($response['files'] as $file) {
                    if ($file['name'] === $folderName && 
                        isset($file['mimeType']) && 
                        $file['mimeType'] === 'application/vnd.google-apps.folder') {
                        return $file['id'];
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning('[UserGoogleDrive] Error checking existing folder', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Assign Google email to user and create personal folder
     */
    public function assignGoogleEmail(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'google_email' => 'required|email',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            $user = User::with('roles')->findOrFail($userId);

            // Check if user is a student (students don't need phone)
            $isStudent = $user->roles->contains('name', 'student');

            // Check if phone exists and is unique (only for non-students)
            if (!$isStudent) {
            if (empty($user->phone)) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.user_phone_required'),
                ], 400);
            }

            // Check if phone is unique
            $phoneCount = User::where('phone', $user->phone)
                ->where('id', '!=', $user->id)
                ->count();

            if ($phoneCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.user_phone_not_unique'),
                ], 400);
                }
            }

            DB::beginTransaction();

            // Get Google Drive settings
            $branchId = $validated['branch_id'] ?? $user->getPrimaryBranch()?->id ?? null;
            
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            $service = new GoogleDriveService($setting);

            // Generate folder name with branch ID and encoded phone
            $folderName = $this->generateFolderName($user, $branchId);
            
            // Get or create root School Drive folder
            $rootFolderId = $service->findOrCreateSchoolDriveFolder();

            Log::info('[UserGoogleDrive] Checking for existing folder', [
                'user_id' => $user->id,
                'folder_name' => $folderName,
                'root_folder_id' => $rootFolderId,
            ]);

            // Check if folder already exists
            $existingFolderId = $this->checkExistingFolder($service, $rootFolderId, $folderName);
            
            if ($existingFolderId) {
                // Folder exists, return warning with option
                return response()->json([
                    'success' => false,
                    'folder_exists' => true,
                    'existing_folder_id' => $existingFolderId,
                    'folder_name' => $folderName,
                    'message' => __('errors.folder_already_exists'),
                    'question' => __('messages.use_existing_or_create_new'),
                ], 409); // 409 Conflict
            }

            Log::info('[UserGoogleDrive] Creating user folder', [
                'user_id' => $user->id,
                'folder_name' => $folderName,
                'google_email' => $validated['google_email'],
            ]);

            // Create user's personal folder
            $userFolderId = $service->createFolder($folderName, $rootFolderId);

            // Share folder with user's Google email (writer permission)
            $permission = $service->shareFile($userFolderId, $validated['google_email'], 'writer');

            // Update user record
            $user->update([
                'google_email' => $validated['google_email'],
                'google_drive_folder_id' => $userFolderId,
            ]);

            DB::commit();

            Log::info('[UserGoogleDrive] Successfully assigned Google email and created folder', [
                'user_id' => $user->id,
                'folder_id' => $userFolderId,
                'permission_id' => $permission['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.google_email_assigned_successfully'),
                'data' => [
                    'user_id' => $user->id,
                    'google_email' => $user->google_email,
                    'folder_id' => $user->google_drive_folder_id,
                    'folder_name' => $folderName,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('errors.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('[UserGoogleDrive] Error assigning Google email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.google_email_assignment_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign Google email with force option to use existing folder or create new
     */
    public function assignGoogleEmailForce(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'google_email' => 'required|email',
                'branch_id' => 'nullable|exists:branches,id',
                'use_existing' => 'required|boolean',
                'existing_folder_id' => 'required_if:use_existing,true|string',
            ]);

            $user = User::with('roles')->findOrFail($userId);

            // Check if user is a student (students don't need phone)
            $isStudent = $user->roles->contains('name', 'student');

            // Phone validation (only for non-students)
            if (!$isStudent && empty($user->phone)) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.user_phone_required'),
                ], 400);
            }

            DB::beginTransaction();

            // Get Google Drive settings
            $branchId = $validated['branch_id'] ?? $user->getPrimaryBranch()?->id ?? null;
            
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $folderName = $this->generateFolderName($user, $branchId);

            if ($validated['use_existing']) {
                // Use existing folder
                $userFolderId = $validated['existing_folder_id'];
                
                Log::info('[UserGoogleDrive] Using existing folder', [
                    'user_id' => $user->id,
                    'folder_id' => $userFolderId,
                ]);
            } else {
                // Create new folder with timestamp suffix to avoid conflict
                $timestamp = now()->format('YmdHis');
                $newFolderName = "{$folderName}.{$timestamp}";
                
                $rootFolderId = $service->findOrCreateSchoolDriveFolder();
                $userFolderId = $service->createFolder($newFolderName, $rootFolderId);
                
                Log::info('[UserGoogleDrive] Created new folder with timestamp', [
                    'user_id' => $user->id,
                    'folder_name' => $newFolderName,
                    'folder_id' => $userFolderId,
                ]);
            }

            // Share folder with user's Google email
            $permission = $service->shareFile($userFolderId, $validated['google_email'], 'writer');

            // Update user record
            $user->update([
                'google_email' => $validated['google_email'],
                'google_drive_folder_id' => $userFolderId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.google_email_assigned_successfully'),
                'data' => [
                    'user_id' => $user->id,
                    'google_email' => $user->google_email,
                    'folder_id' => $user->google_drive_folder_id,
                    'used_existing' => $validated['use_existing'],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('[UserGoogleDrive] Error in forced assign', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.google_email_assignment_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Google email for user
     */
    public function updateGoogleEmail(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'google_email' => 'required|email',
            ]);

            $user = User::findOrFail($userId);

            // If user already has a folder and just changing email, re-share permissions
            if ($user->google_drive_folder_id) {
                // Get Google Drive settings
                $branchId = $user->getPrimaryBranch()?->id ?? null;
                
                if (!$branchId) {
                    $setting = GoogleDriveSetting::active()->first();
                } else {
                    $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
                }

                if ($setting) {
                    $service = new GoogleDriveService($setting);
                    
                    // Remove old email permission if exists
                    if ($user->google_email && $user->google_email !== $validated['google_email']) {
                        try {
                            $permissions = $service->getPermissions($user->google_drive_folder_id);
                            foreach ($permissions as $perm) {
                                if (isset($perm['emailAddress']) && $perm['emailAddress'] === $user->google_email) {
                                    $service->removePermission($user->google_drive_folder_id, $perm['id']);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('[UserGoogleDrive] Could not remove old permission', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Add new email permission
                    $service->shareFile($user->google_drive_folder_id, $validated['google_email'], 'writer');
                }
            }

            // Update user record
            $user->update([
                'google_email' => $validated['google_email'],
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.google_email_updated_successfully'),
                'data' => [
                    'user_id' => $user->id,
                    'google_email' => $user->google_email,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[UserGoogleDrive] Error updating Google email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.google_email_update_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove Google email from user
     */
    public function removeGoogleEmail(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Remove permission from Google Drive if folder exists
            if ($user->google_drive_folder_id && $user->google_email) {
                $branchId = $user->getPrimaryBranch()?->id ?? null;
                
                if (!$branchId) {
                    $setting = GoogleDriveSetting::active()->first();
                } else {
                    $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
                }

                if ($setting) {
                    $service = new GoogleDriveService($setting);
                    
                    try {
                        $permissions = $service->getPermissions($user->google_drive_folder_id);
                        foreach ($permissions as $perm) {
                            if (isset($perm['emailAddress']) && $perm['emailAddress'] === $user->google_email) {
                                $service->removePermission($user->google_drive_folder_id, $perm['id']);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('[UserGoogleDrive] Could not remove permission', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Update user record (keep folder_id, just remove email)
            $user->update([
                'google_email' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.google_email_removed_successfully'),
            ]);

        } catch (\Exception $e) {
            Log::error('[UserGoogleDrive] Error removing Google email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.google_email_removal_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
