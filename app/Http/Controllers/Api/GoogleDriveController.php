<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveSetting;
use App\Models\GoogleDriveItem;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    /**
     * Get branch ID with proper fallback logic
     */
    protected function getBranchId(Request $request): ?int
    {
        $user = $request->user();
        
        // Priority: 1) Selected branch from frontend, 2) User's current_branch_id, 3) User's first branch
        $branchId = $request->input('branch_id') 
            ?? $user->primary_branch_id 
            ?? optional($user->branches()->first())->id;
        
        Log::info('[GoogleDrive] getBranchId', [
            'from_request' => $request->input('branch_id'),
            'from_user' => $user->primary_branch_id,
            'final' => $branchId,
            'user_id' => $user->id,
        ]);
        
        return $branchId;
    }

    /**
     * Get Google Drive settings for current branch
     */
    public function getSettings(Request $request)
    {
        $branchId = $this->getBranchId($request);

        // If still no branch_id, get first available setting (for Super Admin without branches)
        if (!$branchId) {
            $setting = GoogleDriveSetting::first();
        } else {
            $setting = GoogleDriveSetting::where('branch_id', $branchId)->first();
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        // Get branch ID with fallback logic
        $branchId = $this->getBranchId($request) ?? 1;

        // Build redirect URI (will automatically use APP_URL from .env)
        $redirectUri = url('/api/google-drive/callback');

        // Save client credentials temporarily (without tokens)
        GoogleDriveSetting::updateOrCreate(
            ['branch_id' => $branchId],
            [
                'client_id' => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
                'redirect_uri' => $redirectUri,
                'is_active' => false,
            ]
        );

        // Build OAuth URL with correct parameters
        
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $validated['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive',
            'access_type' => 'offline',  // ⭐ CRITICAL: Request refresh token
            'prompt' => 'consent',        // ⭐ CRITICAL: Always get new refresh token
            'state' => base64_encode(json_encode(['branch_id' => $branchId])),
        ]);

        Log::info('[GoogleDrive] Generated auth URL', [
            'branch_id' => $branchId,
            'redirect_uri' => $redirectUri,
        ]);

        return response()->json([
            'success' => true,
            'auth_url' => $authUrl,
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(Request $request)
    {
        $code = $request->input('code');
        $state = json_decode(base64_decode($request->input('state')), true);
        $branchId = $state['branch_id'] ?? 1;

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code not found',
            ], 400);
        }

        // Get settings to retrieve client credentials
        $setting = GoogleDriveSetting::where('branch_id', $branchId)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found. Please configure client credentials first.',
            ], 404);
        }

        try {
            // Exchange authorization code for tokens
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $setting->client_id,
                'client_secret' => $setting->client_secret,
                'code' => $code,
                'redirect_uri' => url('/api/google-drive/callback'),
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $setting->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                Log::info('[GoogleDrive] OAuth callback successful', [
                    'branch_id' => $branchId,
                    'has_refresh_token' => !empty($data['refresh_token']),
                ]);

                // Redirect to frontend with success
                return redirect(config('app.frontend_url') . '/settings/google-drive?success=true');
            } else {
                Log::error('[GoogleDrive] Token exchange failed', [
                    'response' => $response->body(),
                ]);

                return redirect(config('app.frontend_url') . '/settings/google-drive?error=token_exchange_failed');
            }
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] OAuth callback error', [
                'error' => $e->getMessage(),
            ]);

            return redirect(config('app.frontend_url') . '/settings/google-drive?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Save Google Drive settings (legacy method - for manual token entry)
     */
    public function saveSettings(Request $request)
    {
        $user = $request->user();

        Log::info('[GoogleDrive] Saving settings', [
            'user_id' => $user->id,
            'branch_id' => $request->input('branch_id'),
            'has_client_id' => !empty($request->input('client_id')),
            'has_client_secret' => !empty($request->input('client_secret')),
            'has_refresh_token' => !empty($request->input('refresh_token')),
        ]);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'refresh_token' => 'nullable|string', // Now optional - obtained via OAuth
            'redirect_uri' => 'nullable|string',
            'school_drive_folder_name' => 'nullable|string',
        ]);

        // Get branch ID with fallback logic
        $branchId = $this->getBranchId($request) ?? 1; // Fallback to branch ID 1

        Log::info('[GoogleDrive] Using branch_id', ['branch_id' => $branchId]);

        // Prepare update data
        $updateData = [
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
            'redirect_uri' => $validated['redirect_uri'] ?? url('/api/google-drive/callback'),
            'school_drive_folder_name' => $validated['school_drive_folder_name'] ?? 'School Drive',
            'is_active' => false, // Will be activated after successful connection test
        ];

        // Only update refresh_token if provided (backward compatibility with manual entry)
        if (!empty($validated['refresh_token'])) {
            $updateData['refresh_token'] = $validated['refresh_token'];
        }

        $setting = GoogleDriveSetting::updateOrCreate(
            ['branch_id' => $branchId],
            $updateData
        );

        Log::info('[GoogleDrive] Settings saved successfully', [
            'setting_id' => $setting->id,
            'branch_id' => $branchId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully',
            'data' => $setting,
        ]);
    }

    /**
     * Test connection to Google Drive
     */
    public function testConnection(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            
            Log::info('[GoogleDrive] Testing connection', [
                'user_id' => $request->user()->id,
                'branch_id' => $branchId,
            ]);

            // If still no branch_id, get first available setting
            if (!$branchId) {
                $setting = GoogleDriveSetting::first();
                Log::info('[GoogleDrive] No branch_id, using first available setting', [
                    'setting_id' => $setting?->id,
                ]);
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->first();
            }

            // If no setting found in google_drive_settings, it means it hasn't been saved yet
            if (!$setting) {
                Log::warning('[GoogleDrive] No settings found', [
                    'branch_id' => $branchId,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Please save Google Drive settings first. Go to System Settings > General Settings and enter your Google Drive API credentials.',
                ], 400);
            }

            Log::info('[GoogleDrive] Settings found, testing connection', [
                'setting_id' => $setting->id,
                'has_client_id' => !empty($setting->client_id),
                'has_client_secret' => !empty($setting->client_secret),
                'has_refresh_token' => !empty($setting->refresh_token),
            ]);

            $service = new GoogleDriveService($setting);
            
            // Check if folder already exists before creating
            $folderExisted = !empty($setting->school_drive_folder_id);
            
            // Try to find or create School Drive folder (this will also update the name if needed)
            $folderId = $service->findOrCreateSchoolDriveFolder();
            
            // Reload setting to get updated folder name
            $setting->refresh();

            // Activate setting and store folder ID
            $setting->update([
                'is_active' => true,
                'school_drive_folder_id' => $folderId,
            ]);

            Log::info('[GoogleDrive] Connection test successful', [
                'folder_id' => $folderId,
                'folder_name' => $setting->school_drive_folder_name,
                'folder_existed' => $folderExisted,
            ]);

            // Prepare user-friendly message
            $folderAction = $folderExisted ? 'verified and ready' : 'created successfully';
            $message = "Connection successful! Root folder '{$setting->school_drive_folder_name}' has been {$folderAction}.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'folder_id' => $folderId,
                    'folder_name' => $setting->school_drive_folder_name,
                    'folder_existed' => $folderExisted,
                    'branch_id' => $setting->branch_id,
                    'branch_name' => $setting->branch?->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ensure Syllabus folder exists in Google Drive
     */
    public function ensureSyllabusFolder(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            
            Log::info('[GoogleDrive] Ensuring Syllabus folder exists', [
                'user_id' => $request->user()->id,
                'branch_id' => $branchId,
            ]);

            // Get settings
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive is not configured. Please configure it in System Settings first.',
                ], 400);
            }

            // Create service and ensure Syllabus folder
            $service = new GoogleDriveService($setting);
            $syllabusFolderId = $service->findOrCreateSyllabusFolder();

            Log::info('[GoogleDrive] Syllabus folder ensured', [
                'folder_id' => $syllabusFolderId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Syllabus folder is ready',
                'data' => [
                    'folder_id' => $syllabusFolderId,
                    'folder_name' => $setting->syllabus_folder_name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to ensure Syllabus folder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Syllabus folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload file for lesson session
     */
    public function uploadSessionFile(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            
            $validated = $request->validate([
                'session_id' => 'required|integer|exists:lesson_plan_sessions,id',
                'session_number' => 'required|integer',
                'syllabus_id' => 'required|integer|exists:lesson_plans,id',
                'syllabus_name' => 'required|string',
                'syllabus_folder_id' => 'required|string',
                'file_type' => 'required|in:lesson_plan,materials,homework',
                'file' => 'required|file|max:51200', // 50MB max
            ]);

            Log::info('[GoogleDrive] Uploading session file', [
                'user_id' => $request->user()->id,
                'branch_id' => $branchId,
                'session_id' => $validated['session_id'],
                'file_type' => $validated['file_type'],
                'file_name' => $request->file('file')->getClientOriginalName(),
            ]);

            // Get settings
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive is not configured. Please configure it in System Settings first.',
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            
            // Step 1: Create/get session folder (Buổi X)
            $sessionFolderId = $service->createOrGetSessionFolder(
                $validated['session_id'],
                $validated['session_number'],
                $validated['syllabus_name'],
                $validated['syllabus_folder_id']
            );

            $targetFolderId = $sessionFolderId;
            $folderType = null;

            // Step 2: For materials, homework, and lesson_plan, create/get subfolder
            if ($validated['file_type'] === 'materials') {
                $targetFolderId = $service->createOrGetMaterialsFolder($sessionFolderId);
                $folderType = 'materials_folder_id';
            } elseif ($validated['file_type'] === 'homework') {
                $targetFolderId = $service->createOrGetHomeworkFolder($sessionFolderId);
                $folderType = 'homework_folder_id';
            } elseif ($validated['file_type'] === 'lesson_plan') {
                $targetFolderId = $service->createOrGetLessonPlansFolder($sessionFolderId);
                $folderType = 'lesson_plans_folder_id';
            }

            // Step 3: Upload file
            $file = $request->file('file');
            $uploadedItem = $service->uploadFile($file, $targetFolderId, $branchId, $request->user()->id);

            // Extract just the google_id from the uploaded item
            $fileGoogleId = is_object($uploadedItem) ? $uploadedItem->google_id : $uploadedItem;

            // Step 4: Update session record
            $session = \App\Models\LessonPlanSession::find($validated['session_id']);
            if ($session) {
                $updateData = [
                    'google_drive_folder_id' => $sessionFolderId,
                ];

                // Update folder IDs based on file type
                if ($validated['file_type'] === 'lesson_plan') {
                    $updateData['lesson_plans_folder_id'] = $targetFolderId;
                } elseif ($validated['file_type'] === 'materials') {
                    $updateData['materials_folder_id'] = $targetFolderId;
                } elseif ($validated['file_type'] === 'homework') {
                    $updateData['homework_folder_id'] = $targetFolderId;
                }

                $session->update($updateData);
            }

            Log::info('[GoogleDrive] Session file uploaded successfully', [
                'file_id' => $fileGoogleId,
                'session_id' => $validated['session_id'],
                'file_type' => $validated['file_type'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'file_id' => $fileGoogleId,
                    'session_folder_id' => $sessionFolderId,
                    'target_folder_id' => $targetFolderId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to upload session file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get files in a folder
     */
    public function getFolderFiles(Request $request)
    {
        try {
            $validated = $request->validate([
                'folder_id' => 'required|string',
            ]);

            $branchId = $this->getBranchId($request);

            // Get settings
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive is not configured.',
                ], 400);
            }

            // Get files from database cache
            $files = GoogleDriveItem::where('parent_id', $validated['folder_id'])
                ->where('type', 'file')
                ->where('is_trashed', false)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to get folder files', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get files: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get files count in a folder
     */
    public function getFolderFilesCount(Request $request)
    {
        try {
            $validated = $request->validate([
                'folder_id' => 'required|string',
            ]);

            $branchId = $this->getBranchId($request);

            // Get settings
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive is not configured.',
                ], 400);
            }

            // Count files in database cache
            $count = GoogleDriveItem::where('parent_id', $validated['folder_id'])
                ->where('type', 'file')
                ->where('is_trashed', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to get folder files count', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get files count: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a folder for a specific syllabus
     */
    public function createFolderForSyllabus(Request $request)
    {
        try {
            // Increase execution time limit to 5 minutes for folder creation
            set_time_limit(300);
            
            $user = $request->user();
            $branchId = $this->getBranchId($request);
            
            // Check if user has Google email assigned (skip for admin)
            $isAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');
            
            if (!$isAdmin && !$user->google_email) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_connected'),
                    'error_code' => 'NO_GOOGLE_EMAIL',
                ], 400);
            }

            $validated = $request->validate([
                'syllabus_id' => 'required|integer|exists:lesson_plans,id',
                'syllabus_name' => 'required|string',
                'syllabus_code' => 'required|string',
                'total_units' => 'nullable|integer|min:0',
                'use_existing' => 'nullable|boolean',
                'existing_folder_id' => 'nullable|string',
            ]);

            Log::info('[GoogleDrive] Creating folder for syllabus', [
                'user_id' => $user->id,
                'user_email' => $user->google_email,
                'branch_id' => $branchId,
                'syllabus_id' => $validated['syllabus_id'],
                'syllabus_name' => $validated['syllabus_name'],
                'syllabus_code' => $validated['syllabus_code'],
                'total_units' => $validated['total_units'] ?? 0,
            ]);

            // Get settings
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                    'error_code' => 'NO_GDRIVE_CONFIG',
                ], 400);
            }

            // Check if Syllabus folder exists
            $service = new GoogleDriveService($setting);
            try {
                $syllabusFolderId = $service->findOrCreateSyllabusFolder();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.syllabus_folder_not_found'),
                    'error_code' => 'NO_SYLLABUS_FOLDER',
                ], 400);
            }

            // Handle "use existing" or "create new" logic
            if ($validated['use_existing'] ?? false) {
                // Use existing folder
                $folderId = $validated['existing_folder_id'];
                $folderName = $validated['syllabus_name'] . ' - ' . strtoupper($validated['syllabus_code']);
                
                // Verify user has permission
                if (!$service->userHasPermission($folderId)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('errors.no_permission_to_folder'),
                        'error_code' => 'NO_PERMISSION',
                    ], 403);
                }
            } else {
                // Check if we need to rename old folder
                if ($validated['existing_folder_id'] ?? null) {
                    // Rename old folder
                    $oldName = $validated['syllabus_name'] . ' - ' . strtoupper($validated['syllabus_code']);
                    $service->renameToOld($validated['existing_folder_id'], $oldName);
                }

                // Create new folder
                $result = $service->createFolderForSyllabus(
                    $validated['syllabus_name'],
                    $validated['syllabus_code'],
                    $validated['syllabus_id'],
                    $validated['total_units'] ?? 0
                );

                // Check if folder already exists (conflict)
                if ($result['exists']) {
                    return response()->json([
                        'success' => false,
                        'folder_exists' => true,
                        'existing_folder_id' => $result['folder_id'],
                        'folder_name' => $result['folder_name'],
                        'has_permission' => $result['has_permission'],
                        'message' => __('errors.folder_already_exists'),
                        'question' => __('messages.use_existing_or_create_new_syllabus'),
                    ], 409); // 409 Conflict
                }

                $folderId = $result['folder_id'];
                $folderName = $result['folder_name'];
            }

            // Update lesson_plan with folder ID
            $lessonPlan = \App\Models\LessonPlan::find($validated['syllabus_id']);
            if ($lessonPlan) {
                $lessonPlan->update([
                    'google_drive_folder_id' => $folderId,
                    'google_drive_folder_name' => $folderName,
                ]);
            }

            Log::info('[GoogleDrive] Folder created for syllabus successfully', [
                'folder_id' => $folderId,
                'folder_name' => $folderName,
                'syllabus_id' => $validated['syllabus_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.syllabus_folder_created_successfully'),
                'data' => [
                    'folder_id' => $folderId,
                    'folder_name' => $folderName,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to create folder for syllabus', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.syllabus_folder_creation_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List files/folders
     */
    public function listFiles(Request $request)
    {
        try {
            $user = $request->user();
            $branchId = $this->getBranchId($request);
            
            // If still no branch_id, use first available setting's branch
            if (!$branchId) {
                $branchId = GoogleDriveSetting::first()?->branch_id ?? 1;
            }
            
            $folderId = $request->input('folder_id'); // null = root
            
            // If folder_id is null, use school_drive_folder_id as the root
            if ($folderId === null) {
                // Check permission to view root folder
                if (!$user->hasPermission('google-drive.view_root_folder')) {
                    return response()->json([
                        'success' => false,
                        'message' => __('errors.unauthorized_view_root_folder'),
                    ], 403);
                }
                
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
                if ($setting && $setting->school_drive_folder_id) {
                    $folderId = $setting->school_drive_folder_id;
                }
            }
            
            Log::info('[GoogleDrive] Listing files', [
                'branch_id' => $branchId,
                'folder_id' => $folderId,
            ]);

            $items = GoogleDriveItem::where('branch_id', $branchId)
                ->notTrashed()
                ->inFolder($folderId)
                ->with(['creator', 'updater'])
                ->orderBy('type', 'desc') // Folders first
                ->orderBy('name')
                ->get();
            
            Log::info('[GoogleDrive] Found items', [
                'count' => $items->count(),
                'items' => $items->pluck('name', 'id')->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error listing files', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list files: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync files from Google Drive
     */
    public function sync(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            $userId = $request->user()->id;
            
            // Get active setting for the branch
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
                $branchId = $setting?->branch_id ?? 1;
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }
            
            if (!$setting) {
                throw new \Exception('No active Google Drive settings found');
            }

            // Check if queue is available
            $queueConnection = config('queue.default');
            
            Log::info('[GoogleDrive] Sync method called', [
                'branch_id' => $branchId,
                'user_id' => $userId,
                'queue_connection' => $queueConnection,
            ]);
            
            // If using database queue, dispatch to background
            if ($queueConnection === 'database') {
                // Dispatch job to queue (async)
                \App\Jobs\SyncGoogleDriveJob::dispatch($branchId, $userId);

                Log::info('[GoogleDrive] Sync job dispatched to queue', [
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'queue' => $queueConnection,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Sync job has been queued. Please wait...',
                    'data' => [
                        'status' => 'queued',
                        'branch_id' => $branchId,
                    ],
                ]);
            } else {
                // Run synchronously with increased timeout
                // This runs in same process but with optimized early return
                set_time_limit(300); // 5 minutes
                
                Log::info('[GoogleDrive] Running sync synchronously (with optimization)', [
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                ]);
                
                // Execute job immediately
                $job = new \App\Jobs\SyncGoogleDriveJob($branchId, $userId);
                $job->handle();
                
                // Get final result from cache
                $cacheKey = "google_drive_sync_status_{$branchId}_{$userId}";
                $result = \Illuminate\Support\Facades\Cache::get($cacheKey);
                
                if ($result && $result['status'] === 'completed') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Sync completed successfully',
                        'data' => $result['data'],
                    ]);
                } else {
                    throw new \Exception('Sync completed but result not found in cache');
                }
            }
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to dispatch sync job', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('errors.sync_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            $userId = $request->user()->id;
            
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
                $branchId = $setting?->branch_id ?? 1;
            }

            $cacheKey = "google_drive_sync_status_{$branchId}_{$userId}";
            $status = \Illuminate\Support\Facades\Cache::get($cacheKey);

            if (!$status) {
            return response()->json([
                'success' => true,
                'data' => [
                        'status' => 'idle',
                        'progress' => 0,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Failed to get sync status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|string',
            ]);

            $branchId = $this->getBranchId($request);
            
            // Get active setting for the branch
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
                $branchId = $setting?->branch_id ?? 1;
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }
            
            if (!$setting) {
                throw new \Exception('No active Google Drive settings found');
            }

            $service = new GoogleDriveService($setting);

            $parentId = $validated['parent_id'] ?? $setting->school_drive_folder_id;
            
            // Check permission if creating in root folder
            if (!$validated['parent_id'] && !$user->hasPermission('google-drive.view_root_folder')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_create_in_root_folder'),
                ], 403);
            }

            $item = $service->createFolderOnDrive(
                $validated['name'],
                $parentId,
                $branchId,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating folder', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create folder: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload file
     */
    public function uploadFile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'file' => 'required|file|max:102400', // Max 100MB
                'parent_id' => 'nullable|string',
            ]);

            $branchId = $this->getBranchId($request);
            
            // Get active setting for the branch
            if (!$branchId) {
                $setting = GoogleDriveSetting::active()->first();
                $branchId = $setting?->branch_id ?? 1;
            } else {
                $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            }
            
            if (!$setting) {
                throw new \Exception('No active Google Drive settings found');
            }

            $service = new GoogleDriveService($setting);

            $parentId = $validated['parent_id'] ?? $setting->school_drive_folder_id;
            
            // Check permission if uploading to root folder
            if (!$validated['parent_id'] && !$user->hasPermission('google-drive.view_root_folder')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_upload_to_root_folder'),
                ], 403);
            }

            $item = $service->uploadFile(
                $request->file('file'),
                $parentId,
                $branchId,
                $request->user()->id
            );

            // Refresh item to ensure clean data
            $item->refresh();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $item->only([
                    'id', 'google_id', 'name', 'type', 'mime_type', 
                    'size', 'web_view_link', 'web_content_link',
                    'thumbnail_link', 'icon_link', 'created_at', 'updated_at'
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error uploading file', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete file/folder
     */
    public function deleteFile(Request $request, $id)
    {
        try {
            $item = GoogleDriveItem::findOrFail($id);

            $branchId = $this->getBranchId($request);
            $setting = GoogleDriveSetting::where('branch_id', $branchId)->active()->first();
            
            if (!$setting) {
                // Fallback: Try to find any active setting
                $setting = GoogleDriveSetting::active()->first();
            }
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive is not configured. Please configure it in System Settings first.',
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $service->deleteFile($item->google_id);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error deleting file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rename file/folder
     */
    public function renameFile(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = GoogleDriveItem::findOrFail($id);

            $branchId = $request->user()->current_branch_id;
            $setting = GoogleDriveSetting::where('branch_id', $branchId)
                ->active()
                ->firstOrFail();

            $service = new GoogleDriveService($setting);
            $service->renameFile($item->google_id, $validated['name'], $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'File renamed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error renaming file', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to rename file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move file/folder
     */
    public function moveFile(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'required|string',
            ]);

            $item = GoogleDriveItem::findOrFail($id);

            $branchId = $request->user()->current_branch_id;
            $setting = GoogleDriveSetting::where('branch_id', $branchId)
                ->active()
                ->firstOrFail();

            $service = new GoogleDriveService($setting);
            $service->moveFile($item->google_id, $validated['parent_id'], $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'File moved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error moving file', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to move file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get permissions for a file/folder
     */
    public function getPermissions(Request $request, $id)
    {
        try {
            $item = GoogleDriveItem::findOrFail($id);

            $branchId = $this->getBranchId($request);
            $setting = GoogleDriveSetting::forBranch($branchId)
                ->active()
                ->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive not configured for this branch',
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $permissions = $service->getPermissions($item->google_id);

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting permissions', [
                'error' => $e->getMessage(),
                'item_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Share file/folder (add permission)
     */
    public function shareFile(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'role' => 'required|in:reader,writer,commenter',
            ]);

            $item = GoogleDriveItem::findOrFail($id);

            $branchId = $this->getBranchId($request);
            $setting = GoogleDriveSetting::forBranch($branchId)
                ->active()
                ->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive not configured for this branch',
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $permission = $service->shareFile($item->google_id, $validated['email'], $validated['role']);

            return response()->json([
                'success' => true,
                'message' => 'File shared successfully',
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error sharing file', [
                'error' => $e->getMessage(),
                'item_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to share file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get accessible folder tree for current user
     */
    public function getAccessibleFolderTree(Request $request)
    {
        try {
            $user = $request->user();
            
            // Super admin and admin can see root folder
            if ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasPermission('google-drive.view_root_folder')) {
                return $this->listFiles($request);
            }
            
            // Get folders where user has verified permissions
            $accessibleFolders = $user->accessibleGoogleDriveFolders()
                ->with(['parent'])
                ->get();
            
            if ($accessibleFolders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => __('messages.no_accessible_folders'),
                ]);
            }
            
            // Build tree with parent folders
            $folderTree = $this->buildFolderTreeWithParents($accessibleFolders);
            
            return response()->json([
                'success' => true,
                'data' => $folderTree,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting accessible folder tree', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Build folder tree including parent folders (but not root)
     */
    protected function buildFolderTreeWithParents($accessibleFolders)
    {
        $allFolders = collect();
        
        foreach ($accessibleFolders as $folder) {
            // Add the folder itself
            $allFolders->push($folder);
            
            // Add all parent folders (except root)
            $current = $folder;
            while ($current->parent_id) {
                $parent = GoogleDriveItem::where('google_id', $current->parent_id)
                    ->where('type', 'folder')
                    ->first();
                
                if (!$parent) {
                    break;
                }
                
                if (!$allFolders->contains('id', $parent->id)) {
                    $allFolders->push($parent);
                }
                
                $current = $parent;
            }
        }
        
        return $allFolders->unique('id')->values();
    }

    /**
     * Sync permissions for a specific folder from Google Drive
     */
    public function syncFolderPermissions(Request $request, $id)
    {
        try {
            $item = GoogleDriveItem::findOrFail($id);
            
            $branchId = $this->getBranchId($request);
            $setting = GoogleDriveSetting::forBranch($branchId)
                ->active()
                ->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }
            
            $service = new GoogleDriveService($setting);
            $syncedCount = $service->syncFolderPermissions($item->google_id);
            
            return response()->json([
                'success' => true,
                'message' => __('messages.permissions_synced_successfully'),
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error syncing folder permissions', [
                'error' => $e->getMessage(),
                'item_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Verify user's permission for a specific folder
     */
    public function verifyUserPermission(Request $request, $id)
    {
        try {
            $user = $request->user();
            $item = GoogleDriveItem::findOrFail($id);
            
            if (!$user->google_email) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.user_google_email_not_set'),
                    'has_permission' => false,
                ]);
            }
            
            $branchId = $this->getBranchId($request);
            $setting = GoogleDriveSetting::forBranch($branchId)
                ->active()
                ->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }
            
            $service = new GoogleDriveService($setting);
            $permission = $service->verifyUserPermission($item->google_id, $user->google_email);
            
            if ($permission) {
                // Update or create permission record
                \App\Models\GoogleDrivePermission::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'google_drive_item_id' => $item->id,
                    ],
                    [
                        'google_permission_id' => $permission['id'],
                        'role' => $permission['role'],
                        'is_verified' => true,
                        'verified_at' => now(),
                        'synced_at' => now(),
                    ]
                );
            }
            
            return response()->json([
                'success' => true,
                'has_permission' => $permission !== null,
                'permission' => $permission,
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error verifying user permission', [
                'error' => $e->getMessage(),
                'item_id' => $id,
                'user_id' => $request->user()->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Check if Class History folder exists
     */
    public function checkClassHistoryFolder(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            $user = $request->user();

            // Get settings
            $setting = $this->getGoogleDriveSetting($branchId);
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            // Check if user has permission to view root folder
            if (!$user->hasPermission('google-drive.view_root_folder')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_view_root_folder'),
                    'error_code' => 'NO_ROOT_PERMISSION',
                    'data' => [
                        'exists' => false,
                        'can_create' => false,
                    ]
                ], 403);
            }

            $service = new GoogleDriveService($setting);
            $rootFolderId = $service->findOrCreateSchoolDriveFolder();

            // Search for "Class History" folder
            $classHistoryFolderId = $service->searchFolderInParent('Class History', $rootFolderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => $classHistoryFolderId !== null,
                    'folder_id' => $classHistoryFolderId,
                    'folder_name' => 'Class History',
                    'can_create' => true,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error checking Class History folder', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
                'data' => [
                    'exists' => false,
                    'can_create' => true,
                ]
            ], 500);
        }
    }

    /**
     * Create Class History folder
     */
    public function createClassHistoryFolder(Request $request)
    {
        try {
            $branchId = $this->getBranchId($request);
            $user = $request->user();

            // Check if user has permission to view root folder
            if (!$user->hasPermission('google-drive.view_root_folder')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthorized_create_in_root_folder'),
                    'error_code' => 'NO_ROOT_PERMISSION',
                ], 403);
            }

            // Get settings
            $setting = $this->getGoogleDriveSetting($branchId);
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.google_drive_not_configured'),
                ], 400);
            }

            $service = new GoogleDriveService($setting);
            $rootFolderId = $service->findOrCreateSchoolDriveFolder();

            // Check if folder already exists
            $existingFolderId = $service->searchFolderInParent('Class History', $rootFolderId);
            if ($existingFolderId) {
                return response()->json([
                    'success' => false,
                    'message' => __('google_drive.class_history_folder_exists'),
                    'error_code' => 'FOLDER_EXISTS',
                    'data' => [
                        'folder_id' => $existingFolderId,
                        'folder_name' => 'Class History',
                    ]
                ], 409);
            }

            // Create the folder
            $folderId = $service->createFolder('Class History', $rootFolderId);

            // Save to database
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $folderId,
                    'branch_id' => $branchId,
                ],
                [
                    'name' => 'Class History',
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $rootFolderId,
                    'is_trashed' => false,
                    'metadata' => [
                        'type' => 'class_history',
                        'description' => 'Folder chứa lịch sử các lớp học đã kết thúc',
                    ],
                ]
            );

            Log::info('[GoogleDrive] Class History folder created', [
                'folder_id' => $folderId,
                'branch_id' => $branchId,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('google_drive.class_history_folder_created'),
                'data' => [
                    'folder_id' => $folderId,
                    'folder_name' => 'Class History',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating Class History folder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'branch_id' => $branchId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('google_drive.class_history_folder_creation_failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }
}
