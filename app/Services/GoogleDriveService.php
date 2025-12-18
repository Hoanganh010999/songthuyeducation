<?php

namespace App\Services;

use App\Models\GoogleDriveSetting;
use App\Models\GoogleDriveItem;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleDriveService
{
    protected $setting;
    protected $accessToken;

    const GOOGLE_DRIVE_API_URL = 'https://www.googleapis.com/drive/v3';
    const GOOGLE_OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(?GoogleDriveSetting $setting = null)
    {
        $this->setting = $setting;
        if ($setting) {
            $this->ensureValidAccessToken();
        }
    }

    /**
     * Ensure we have a valid access token
     */
    protected function ensureValidAccessToken()
    {
        if (!$this->setting->isTokenExpired()) {
            $this->accessToken = $this->setting->access_token;
            return;
        }

        // Token expired, refresh it
        $this->refreshAccessToken();
    }

    /**
     * Refresh access token using refresh token
     */
    protected function refreshAccessToken()
    {
        try {
            $response = Http::asForm()->post(self::GOOGLE_OAUTH_TOKEN_URL, [
                'client_id' => $this->setting->client_id,
                'client_secret' => $this->setting->client_secret,
                'refresh_token' => $this->setting->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->accessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                $this->setting->update([
                    'access_token' => $this->accessToken,
                    'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
                ]);

                Log::info('[GoogleDrive] Access token refreshed successfully');
            } else {
                Log::error('[GoogleDrive] Failed to refresh access token', [
                    'response' => $response->body(),
                ]);
                throw new \Exception('Failed to refresh access token');
            }
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error refreshing token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find or create "School Drive" folder with branch-specific name
     * Format: "{branch_id} - {branch_name}"
     */
    public function findOrCreateSchoolDriveFolder()
    {
        // Generate folder name based on branch
        $folderName = $this->generateBranchFolderName();
        
        Log::info('[GoogleDrive] Finding or creating School Drive folder', [
            'branch_id' => $this->setting->branch_id,
            'folder_name' => $folderName,
        ]);

        // Check if we already have the folder ID
        if ($this->setting->school_drive_folder_id) {
            // Verify it still exists
            if ($this->folderExists($this->setting->school_drive_folder_id)) {
                Log::info('[GoogleDrive] School Drive folder already exists', [
                    'folder_id' => $this->setting->school_drive_folder_id,
                    'current_name' => $this->setting->school_drive_folder_name,
                    'new_name' => $folderName,
                ]);
                
                // Update folder name if it changed (branch name might have been updated)
                if ($this->setting->school_drive_folder_name !== $folderName) {
                    Log::info('[GoogleDrive] Folder name changed, updating...');
                    $this->updateFolderName($this->setting->school_drive_folder_id, $folderName);
                    
                    // Update database
                    $this->setting->update(['school_drive_folder_name' => $folderName]);
                }
                
                return $this->setting->school_drive_folder_id;
            } else {
                Log::warning('[GoogleDrive] Cached School Drive folder ID is invalid');
            }
        }

        // Search for folder by name
        $folderId = $this->searchFolder($folderName);

        if (!$folderId) {
            // Create new folder
            Log::info('[GoogleDrive] Creating new School Drive folder');
            $folderId = $this->createFolder($folderName);
        }

        // Save folder ID and name
        $this->setting->update([
            'school_drive_folder_id' => $folderId,
            'school_drive_folder_name' => $folderName,
        ]);

        Log::info('[GoogleDrive] School Drive folder ready', [
            'folder_id' => $folderId,
            'folder_name' => $folderName,
        ]);

        return $folderId;
    }

    /**
     * Generate branch-specific folder name
     * Format: "{branch_id} - {branch_name}"
     */
    protected function generateBranchFolderName()
    {
        $branch = $this->setting->branch;
        
        if (!$branch) {
            // Fallback if branch not found
            return "Branch {$this->setting->branch_id} - School Drive";
        }
        
        return "{$branch->id} - {$branch->name}";
    }

    /**
     * Update folder name on Google Drive
     */
    protected function updateFolderName($folderId, $newName)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->patch(self::GOOGLE_DRIVE_API_URL . "/files/{$folderId}", [
                    'name' => $newName,
                ]);

            if ($response->successful()) {
                Log::info('[GoogleDrive] Folder name updated', [
                    'folder_id' => $folderId,
                    'new_name' => $newName,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error updating folder name', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Find or create "Syllabus" folder inside School Drive
     */
    public function findOrCreateSyllabusFolder()
    {
        // First ensure School Drive folder exists
        $schoolDriveFolderId = $this->findOrCreateSchoolDriveFolder();

        Log::info('[GoogleDrive] Finding or creating Syllabus folder', [
            'school_drive_folder_id' => $schoolDriveFolderId,
        ]);

        // Check if we already have the Syllabus folder ID
        if ($this->setting->syllabus_folder_id) {
            // Verify it still exists
            if ($this->folderExists($this->setting->syllabus_folder_id)) {
                Log::info('[GoogleDrive] Syllabus folder already exists', [
                    'folder_id' => $this->setting->syllabus_folder_id,
                ]);
                return $this->setting->syllabus_folder_id;
            } else {
                Log::warning('[GoogleDrive] Cached Syllabus folder ID is invalid, searching again');
            }
        }

        // Search for "Syllabus" folder inside School Drive
        $syllabusFolderId = $this->searchFolderInParent(
            $this->setting->syllabus_folder_name, 
            $schoolDriveFolderId
        );

        if (!$syllabusFolderId) {
            // Create new Syllabus folder inside School Drive
            Log::info('[GoogleDrive] Creating new Syllabus folder');
            $syllabusFolderId = $this->createFolder(
                $this->setting->syllabus_folder_name, 
                $schoolDriveFolderId
            );
            
            // Also save to google_drive_items for caching
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $syllabusFolderId,
                    'branch_id' => $this->setting->branch_id,
                ],
                [
                    'name' => $this->setting->syllabus_folder_name,
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $schoolDriveFolderId,
                    'is_trashed' => false,
                ]
            );
        }

        // Save Syllabus folder ID to settings
        $this->setting->update(['syllabus_folder_id' => $syllabusFolderId]);

        Log::info('[GoogleDrive] Syllabus folder ready', [
            'folder_id' => $syllabusFolderId,
        ]);

        return $syllabusFolderId;
    }

    /**
     * Find or create "Lesson Plan" folder inside School Drive
     */
    public function findOrCreateLessonPlanFolder()
    {
        // First ensure School Drive folder exists
        $schoolDriveFolderId = $this->findOrCreateSchoolDriveFolder();

        Log::info('[GoogleDrive] Finding or creating Lesson Plan folder', [
            'school_drive_folder_id' => $schoolDriveFolderId,
        ]);

        // Check if we already have the Lesson Plan folder ID
        if ($this->setting->lesson_plan_folder_id) {
            // Verify it still exists
            if ($this->folderExists($this->setting->lesson_plan_folder_id)) {
                Log::info('[GoogleDrive] Lesson Plan folder already exists', [
                    'folder_id' => $this->setting->lesson_plan_folder_id,
                ]);
                return $this->setting->lesson_plan_folder_id;
            } else {
                Log::warning('[GoogleDrive] Cached Lesson Plan folder ID is invalid, searching again');
            }
        }

        // Search for "Lesson Plan" folder inside School Drive
        $lessonPlanFolderId = $this->searchFolderInParent('Lesson Plan', $schoolDriveFolderId);

        if (!$lessonPlanFolderId) {
            Log::info('[GoogleDrive] Lesson Plan folder not found, creating new one');
            
            // Create new "Lesson Plan" folder
            $lessonPlanFolderId = $this->createFolder('Lesson Plan', $schoolDriveFolderId);

            Log::info('[GoogleDrive] Lesson Plan folder created', [
                'folder_id' => $lessonPlanFolderId,
            ]);

            // Save to database
            \App\Models\GoogleDriveItem::updateOrCreate(
                ['google_id' => $lessonPlanFolderId],
                [
                    'name' => 'Lesson Plan',
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $schoolDriveFolderId,
                    'branch_id' => $this->setting->branch_id,
                    'is_trashed' => false,
                    'metadata' => json_encode(['type' => 'lesson_plan_root']),
                ]
            );
        }

        // Cache the folder ID in settings
        $this->setting->update(['lesson_plan_folder_id' => $lessonPlanFolderId]);

        Log::info('[GoogleDrive] Lesson Plan folder ready', [
            'folder_id' => $lessonPlanFolderId,
        ]);

        return $lessonPlanFolderId;
    }

    /**
     * Find or create "Class History" folder in root
     */
    public function findOrCreateClassHistoryFolder()
    {
        $rootFolderId = $this->findOrCreateSchoolDriveFolder();
        
        Log::info('[GoogleDrive] Finding or creating Class History folder', [
            'root_folder_id' => $rootFolderId,
        ]);
        
        // Check if Class History folder already exists
        $classHistoryFolderId = $this->searchFolderInParent('Class History', $rootFolderId);
        
        if ($classHistoryFolderId) {
            Log::info('[GoogleDrive] Class History folder already exists', [
                'folder_id' => $classHistoryFolderId,
            ]);
            return $classHistoryFolderId;
        }
        
        // Create new Class History folder
        Log::info('[GoogleDrive] Class History folder not found, creating new one');
        $classHistoryFolderId = $this->createFolder('Class History', $rootFolderId);
        
        // Save to database
        GoogleDriveItem::updateOrCreate(
            ['google_id' => $classHistoryFolderId],
            [
                'name' => 'Class History',
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $rootFolderId,
                'branch_id' => $this->setting->branch_id,
                'is_trashed' => false,
                'metadata' => json_encode(['type' => 'class_history_root']),
            ]
        );
        
        // TODO: Share with head teacher (cần thông tin head_teacher_id từ subject/branch)
        // $this->shareFile($classHistoryFolderId, $headTeacherEmail, 'writer');
        
        Log::info('[GoogleDrive] Class History folder created', [
            'folder_id' => $classHistoryFolderId,
        ]);
        
        return $classHistoryFolderId;
    }

    /**
     * Create a folder for a specific syllabus with unit subfolders
     * 
     * @param string $syllabusName Name of the syllabus
     * @param string $syllabusCode Code of the syllabus
     * @param int $syllabusId ID of the syllabus (for database reference)
     * @param int $totalUnits Number of unit folders to create
     * @param bool $useExisting Whether to use existing folder if found
     * @return array ['folder_id' => string, 'folder_name' => string, 'exists' => bool, 'has_permission' => bool]
     */
    public function createFolderForSyllabus($syllabusName, $syllabusCode, $syllabusId, $totalUnits = 0, $useExisting = false)
    {
        // Ensure Class History folder exists (sẽ tạo nếu chưa có)
        try {
            $this->findOrCreateClassHistoryFolder();
        } catch (\Exception $e) {
            Log::warning('[GoogleDrive] Failed to ensure Class History folder exists', [
                'error' => $e->getMessage(),
            ]);
        }
        
        // First ensure Syllabus parent folder exists
        $syllabusFolderId = $this->findOrCreateSyllabusFolder();

        // Generate folder name with code: "CourseName - CODE"
        $folderName = $this->sanitizeFolderName($syllabusName) . ' - ' . strtoupper($syllabusCode);

        Log::info('[GoogleDrive] Creating folder for syllabus', [
            'syllabus_name' => $syllabusName,
            'syllabus_code' => $syllabusCode,
            'syllabus_id' => $syllabusId,
            'folder_name' => $folderName,
            'parent_folder_id' => $syllabusFolderId,
            'total_units' => $totalUnits,
        ]);

        // Check if folder already exists for this syllabus
        $existingFolderId = $this->searchFolderInParent($folderName, $syllabusFolderId);

        if ($existingFolderId) {
            Log::info('[GoogleDrive] Folder already exists for syllabus', [
                'folder_id' => $existingFolderId,
                'folder_name' => $folderName,
            ]);
            
            // Return conflict information to frontend
            return [
                'folder_id' => $existingFolderId,
                'folder_name' => $folderName,
                'exists' => true,
                'has_permission' => $this->userHasPermission($existingFolderId),
            ];
        }

        // Create new folder for this syllabus
        $newFolderId = $this->createFolder($folderName, $syllabusFolderId);

        // Save to google_drive_items for caching
        GoogleDriveItem::updateOrCreate(
            [
                'google_id' => $newFolderId,
                'branch_id' => $this->setting->branch_id,
            ],
            [
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $syllabusFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'syllabus_id' => $syllabusId,
                    'syllabus_name' => $syllabusName,
                ],
            ]
        );

        // Don't create unit folders anymore - too slow
        // Units will be created on-demand when uploading materials
        
        // Transfer ownership to API owner (service account email)
        $this->transferOwnershipToApi($newFolderId);

        Log::info('[GoogleDrive] Folder created for syllabus successfully', [
            'folder_id' => $newFolderId,
            'folder_name' => $folderName,
            'units_created' => $totalUnits,
        ]);

        return [
            'folder_id' => $newFolderId,
            'folder_name' => $folderName,
            'exists' => false,
            'has_permission' => true,
        ];
    }

    /**
     * Create or get unit folder on-demand
     * Returns: ['unit_folder_id' => ..., 'materials_folder_id' => ..., 'homework_folder_id' => ..., 'lesson_plans_folder_id' => ...]
     */
    public function createOrGetUnitFolder($syllabusFolderId, $unitNumber, $syllabusId)
    {
        try {
            // Check if unit folder already exists
            $unitFolderName = "Unit {$unitNumber}";
            $existingUnit = GoogleDriveItem::where('parent_id', $syllabusFolderId)
                ->where('name', $unitFolderName)
                ->where('branch_id', $this->setting->branch_id)
                ->first();
            
            if ($existingUnit) {
                // Unit folder already exists - get creation time from database
                $unitCreatedAt = $existingUnit->created_at;
                
                // Get existing subfolders
                $subfolders = GoogleDriveItem::where('parent_id', $existingUnit->google_id)
                    ->where('branch_id', $this->setting->branch_id)
                    ->get();
                
                $materialsFolderId = null;
                $homeworkFolderId = null;
                $lessonPlansFolderId = null;
                
                foreach ($subfolders as $subfolder) {
                    $metadata = is_string($subfolder->metadata) 
                        ? json_decode($subfolder->metadata, true) 
                        : $subfolder->metadata;
                    
                    if (is_array($metadata)) {
                        $type = $metadata['type'] ?? null;
                        if ($type === 'materials') {
                            $materialsFolderId = $subfolder->google_id;
                        } elseif ($type === 'homework') {
                            $homeworkFolderId = $subfolder->google_id;
                        } elseif ($type === 'lesson_plans') {
                            $lessonPlansFolderId = $subfolder->google_id;
                        }
                    }
                }
                
                // Create missing subfolders
                if (!$materialsFolderId) {
                    $materialsFolderId = $this->createFolder('Materials', $existingUnit->google_id);
                    GoogleDriveItem::create([
                        'google_id' => $materialsFolderId,
                        'branch_id' => $this->setting->branch_id,
                        'name' => 'Materials',
                        'type' => 'folder',
                        'mime_type' => 'application/vnd.google-apps.folder',
                        'parent_id' => $existingUnit->google_id,
                        'is_trashed' => false,
                        'metadata' => [
                            'syllabus_id' => $syllabusId,
                            'unit_number' => $unitNumber,
                            'type' => 'materials',
                        ],
                    ]);
                }
                
                if (!$homeworkFolderId) {
                    $homeworkFolderId = $this->createFolder('Homework', $existingUnit->google_id);
                    GoogleDriveItem::create([
                        'google_id' => $homeworkFolderId,
                        'branch_id' => $this->setting->branch_id,
                        'name' => 'Homework',
                        'type' => 'folder',
                        'mime_type' => 'application/vnd.google-apps.folder',
                        'parent_id' => $existingUnit->google_id,
                        'is_trashed' => false,
                        'metadata' => [
                            'syllabus_id' => $syllabusId,
                            'unit_number' => $unitNumber,
                            'type' => 'homework',
                        ],
                    ]);
                }
                
                if (!$lessonPlansFolderId) {
                    $lessonPlansFolderId = $this->createFolder('Lesson Plans', $existingUnit->google_id);
                    GoogleDriveItem::create([
                        'google_id' => $lessonPlansFolderId,
                        'branch_id' => $this->setting->branch_id,
                        'name' => 'Lesson Plans',
                        'type' => 'folder',
                        'mime_type' => 'application/vnd.google-apps.folder',
                        'parent_id' => $existingUnit->google_id,
                        'is_trashed' => false,
                        'metadata' => [
                            'syllabus_id' => $syllabusId,
                            'unit_number' => $unitNumber,
                            'type' => 'lesson_plans',
                        ],
                    ]);
                }
                
                return [
                    'unit_folder_id' => $existingUnit->google_id,
                    'unit_folder_created_at' => $unitCreatedAt ? $unitCreatedAt->toDateTimeString() : null,
                    'was_created' => false, // Folder already existed
                    'materials_folder_id' => $materialsFolderId,
                    'homework_folder_id' => $homeworkFolderId,
                    'lesson_plans_folder_id' => $lessonPlansFolderId,
                ];
            }
            
            // Create new unit folder
            Log::info("[GoogleDrive] Creating Unit {$unitNumber} on-demand", [
                'syllabus_folder_id' => $syllabusFolderId,
                'syllabus_id' => $syllabusId,
            ]);
            
            $unitFolderId = $this->createFolder($unitFolderName, $syllabusFolderId);
            
            GoogleDriveItem::create([
                'google_id' => $unitFolderId,
                'branch_id' => $this->setting->branch_id,
                'name' => $unitFolderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $syllabusFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'syllabus_id' => $syllabusId,
                    'unit_number' => $unitNumber,
                ],
            ]);
            
            // Create subfolders
            $materialsFolderId = $this->createFolder('Materials', $unitFolderId);
            GoogleDriveItem::create([
                'google_id' => $materialsFolderId,
                'branch_id' => $this->setting->branch_id,
                'name' => 'Materials',
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $unitFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'syllabus_id' => $syllabusId,
                    'unit_number' => $unitNumber,
                    'type' => 'materials',
                ],
            ]);
            
            $homeworkFolderId = $this->createFolder('Homework', $unitFolderId);
            GoogleDriveItem::create([
                'google_id' => $homeworkFolderId,
                'branch_id' => $this->setting->branch_id,
                'name' => 'Homework',
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $unitFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'syllabus_id' => $syllabusId,
                    'unit_number' => $unitNumber,
                    'type' => 'homework',
                ],
            ]);
            
            $lessonPlansFolderId = $this->createFolder('Lesson Plans', $unitFolderId);
            GoogleDriveItem::create([
                'google_id' => $lessonPlansFolderId,
                'branch_id' => $this->setting->branch_id,
                'name' => 'Lesson Plans',
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $unitFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'syllabus_id' => $syllabusId,
                    'unit_number' => $unitNumber,
                    'type' => 'lesson_plans',
                ],
            ]);
            
            $createdAt = now();
            
            Log::info("[GoogleDrive] Created Unit {$unitNumber} successfully", [
                'unit_folder_id' => $unitFolderId,
                'created_at' => $createdAt->toDateTimeString(),
            ]);
            
            return [
                'unit_folder_id' => $unitFolderId,
                'unit_folder_created_at' => $createdAt->toDateTimeString(),
                'was_created' => true, // Folder was just created
                'materials_folder_id' => $materialsFolderId,
                'homework_folder_id' => $homeworkFolderId,
                'lesson_plans_folder_id' => $lessonPlansFolderId,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating/getting unit folder', [
                'error' => $e->getMessage(),
                'syllabus_folder_id' => $syllabusFolderId,
                'unit_number' => $unitNumber,
            ]);
            throw $e;
        }
    }

    /**
     * Create unit folders with materials and homework subfolders (DEPRECATED - Use createOrGetUnitFolder)
     */
    protected function createUnitFolders($parentFolderId, $totalUnits, $syllabusId)
    {
        Log::info('[GoogleDrive] Starting to create unit folders', [
            'total_units' => $totalUnits,
            'syllabus_id' => $syllabusId,
        ]);
        
        for ($i = 1; $i <= $totalUnits; $i++) {
            $startTime = microtime(true);
            
            $unitFolderName = "Unit {$i}";
            $unitFolderId = $this->createFolder($unitFolderName, $parentFolderId);
            
            Log::info("[GoogleDrive] Created unit folder {$i}/{$totalUnits}", [
                'unit_name' => $unitFolderName,
                'folder_id' => $unitFolderId,
            ]);

            // Save unit folder
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $unitFolderId,
                    'branch_id' => $this->setting->branch_id,
                ],
                [
                    'name' => $unitFolderName,
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $parentFolderId,
                    'is_trashed' => false,
                    'metadata' => [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $i,
                    ],
                ]
            );

            // Create Materials subfolder
            $materialsFolderId = $this->createFolder('Materials', $unitFolderId);
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $materialsFolderId,
                    'branch_id' => $this->setting->branch_id,
                ],
                [
                    'name' => 'Materials',
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $unitFolderId,
                    'is_trashed' => false,
                    'metadata' => [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $i,
                        'type' => 'materials',
                    ],
                ]
            );

            // Create Homework subfolder
            $homeworkFolderId = $this->createFolder('Homework', $unitFolderId);
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $homeworkFolderId,
                    'branch_id' => $this->setting->branch_id,
                ],
                [
                    'name' => 'Homework',
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $unitFolderId,
                    'is_trashed' => false,
                    'metadata' => [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $i,
                        'type' => 'homework',
                    ],
                ]
            );

            // Create Lesson Plans subfolder
            $lessonPlansFolderId = $this->createFolder('Lesson Plans', $unitFolderId);
            GoogleDriveItem::updateOrCreate(
                [
                    'google_id' => $lessonPlansFolderId,
                    'branch_id' => $this->setting->branch_id,
                ],
                [
                    'name' => 'Lesson Plans',
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $unitFolderId,
                    'is_trashed' => false,
                    'metadata' => [
                        'syllabus_id' => $syllabusId,
                        'unit_number' => $i,
                        'type' => 'lesson_plans',
                    ],
                ]
            );
            
            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info("[GoogleDrive] Completed unit {$i}/{$totalUnits} in {$elapsed}s");
        }
        
        Log::info('[GoogleDrive] Finished creating all unit folders', [
            'total_units' => $totalUnits,
        ]);
    }

    /**
     * Transfer folder ownership to the API service account
     */
    protected function transferOwnershipToApi($folderId)
    {
        try {
            // Get the service account email from credentials
            $serviceAccountEmail = $this->setting->credentials['client_email'] ?? null;
            
            if (!$serviceAccountEmail) {
                Log::warning('[GoogleDrive] Cannot transfer ownership: service account email not found');
                return false;
            }

            Log::info('[GoogleDrive] Transferring ownership', [
                'folder_id' => $folderId,
                'to_email' => $serviceAccountEmail,
            ]);

            // Note: Service accounts cannot be owners, they already have full access
            // Instead, we ensure the service account has organizer role
            // The folder is already created by the service account, so it has full access
            
            return true;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error transferring ownership', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if current user's google_email has permission to a folder
     */
    public function userHasPermission($folderId)
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->google_email) {
                return false;
            }

            $permissions = $this->client->files->listPermissions($folderId, [
                'fields' => 'permissions(id,emailAddress,role)'
            ]);

            foreach ($permissions->getPermissions() as $permission) {
                if ($permission->getEmailAddress() === $user->google_email) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error checking user permission', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Rename an existing folder by appending .oldXX suffix
     */
    public function renameToOld($folderId, $currentName)
    {
        try {
            $parentFolderId = $this->getParentFolderId($folderId);
            
            // Find the next available .oldXX suffix
            $suffix = 1;
            $newName = "{$currentName}.old{$suffix}";
            
            while ($this->searchFolderInParent($newName, $parentFolderId)) {
                $suffix++;
                $newName = "{$currentName}.old{$suffix}";
            }

            // Rename the folder
            $fileMetadata = new \Google_Service_Drive_DriveFile([
                'name' => $newName
            ]);

            $this->client->files->update($folderId, $fileMetadata);

            Log::info('[GoogleDrive] Folder renamed to old', [
                'folder_id' => $folderId,
                'old_name' => $currentName,
                'new_name' => $newName,
            ]);

            return $newName;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error renaming folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get parent folder ID of a file/folder
     */
    protected function getParentFolderId($fileId)
    {
        try {
            $file = $this->client->files->get($fileId, ['fields' => 'parents']);
            $parents = $file->getParents();
            return $parents[0] ?? null;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting parent folder', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sanitize folder name for Google Drive
     */
    protected function sanitizeFolderName($name)
    {
        // Remove or replace characters that might cause issues
        // Google Drive allows most characters, but we'll clean up for consistency
        $sanitized = preg_replace('/[\/\\\\]/', '-', $name); // Replace slashes
        $sanitized = trim($sanitized);
        
        return $sanitized;
    }

    /**
     * Create or get folder for a lesson session
     * Structure: Syllabus/[Syllabus Name]/Buổi [session_number]/
     * 
     * @param int $sessionId Session ID
     * @param int $sessionNumber Session number
     * @param string $syllabusName Syllabus name
     * @param string $syllabusFolderId Syllabus folder ID
     * @return string Session folder ID
     */
    public function createOrGetSessionFolder($sessionId, $sessionNumber, $syllabusName, $syllabusFolderId)
    {
        $folderName = "Buổi {$sessionNumber}";
        
        Log::info('[GoogleDrive] Creating/getting session folder', [
            'session_id' => $sessionId,
            'session_number' => $sessionNumber,
            'syllabus_name' => $syllabusName,
            'syllabus_folder_id' => $syllabusFolderId,
            'folder_name' => $folderName,
        ]);

        // Check if folder already exists
        $existingFolderId = $this->searchFolderInParent($folderName, $syllabusFolderId);

        if ($existingFolderId) {
            Log::info('[GoogleDrive] Session folder already exists', [
                'folder_id' => $existingFolderId,
            ]);
            return $existingFolderId;
        }

        // Create new folder
        $newFolderId = $this->createFolder($folderName, $syllabusFolderId);

        // Cache to database
        GoogleDriveItem::updateOrCreate(
            [
                'google_id' => $newFolderId,
                'branch_id' => $this->setting->branch_id,
            ],
            [
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $syllabusFolderId,
                'is_trashed' => false,
                'metadata' => [
                    'session_id' => $sessionId,
                    'session_number' => $sessionNumber,
                    'syllabus_name' => $syllabusName,
                ],
            ]
        );

        Log::info('[GoogleDrive] Session folder created', [
            'folder_id' => $newFolderId,
        ]);

        return $newFolderId;
    }

    /**
     * Create or get Materials subfolder in session folder
     * 
     * @param string $sessionFolderId Session folder ID
     * @return string Materials folder ID
     */
    public function createOrGetMaterialsFolder($sessionFolderId)
    {
        $folderName = "Materials";
        
        Log::info('[GoogleDrive] Creating/getting Materials folder', [
            'session_folder_id' => $sessionFolderId,
        ]);

        // Check if folder already exists
        $existingFolderId = $this->searchFolderInParent($folderName, $sessionFolderId);

        if ($existingFolderId) {
            Log::info('[GoogleDrive] Materials folder already exists', [
                'folder_id' => $existingFolderId,
            ]);
            return $existingFolderId;
        }

        // Create new folder
        $newFolderId = $this->createFolder($folderName, $sessionFolderId);

        // Cache to database
        GoogleDriveItem::updateOrCreate(
            [
                'google_id' => $newFolderId,
                'branch_id' => $this->setting->branch_id,
            ],
            [
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $sessionFolderId,
                'is_trashed' => false,
            ]
        );

        return $newFolderId;
    }

    /**
     * Create or get Homework subfolder in session folder
     * 
     * @param string $sessionFolderId Session folder ID
     * @return string Homework folder ID
     */
    public function createOrGetHomeworkFolder($sessionFolderId)
    {
        $folderName = "Homework";
        
        Log::info('[GoogleDrive] Creating/getting Homework folder', [
            'session_folder_id' => $sessionFolderId,
        ]);

        // Check if folder already exists
        $existingFolderId = $this->searchFolderInParent($folderName, $sessionFolderId);

        if ($existingFolderId) {
            Log::info('[GoogleDrive] Homework folder already exists', [
                'folder_id' => $existingFolderId,
            ]);
            return $existingFolderId;
        }

        // Create new folder
        $newFolderId = $this->createFolder($folderName, $sessionFolderId);

        // Cache to database
        GoogleDriveItem::updateOrCreate(
            [
                'google_id' => $newFolderId,
                'branch_id' => $this->setting->branch_id,
            ],
            [
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $sessionFolderId,
                'is_trashed' => false,
            ]
        );

        return $newFolderId;
    }

    /**
     * Create or get Lesson Plans subfolder in session folder
     * 
     * @param string $sessionFolderId Session folder ID
     * @return string Lesson Plans folder ID
     */
    public function createOrGetLessonPlansFolder($sessionFolderId)
    {
        $folderName = "Lesson Plans";
        
        Log::info('[GoogleDrive] Creating/getting Lesson Plans folder', [
            'session_folder_id' => $sessionFolderId,
        ]);

        // Check if folder already exists
        $existingFolderId = $this->searchFolderInParent($folderName, $sessionFolderId);

        if ($existingFolderId) {
            Log::info('[GoogleDrive] Lesson Plans folder already exists', [
                'folder_id' => $existingFolderId,
            ]);
            return $existingFolderId;
        }

        // Create new folder
        $newFolderId = $this->createFolder($folderName, $sessionFolderId);

        // Cache to database
        GoogleDriveItem::updateOrCreate(
            [
                'google_id' => $newFolderId,
                'branch_id' => $this->setting->branch_id,
            ],
            [
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $sessionFolderId,
                'is_trashed' => false,
            ]
        );

        return $newFolderId;
    }

    /**
     * Check if folder exists
     */
    protected function folderExists($folderId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::GOOGLE_DRIVE_API_URL . "/files/{$folderId}", [
                    'fields' => 'id,name,trashed',
                ]);

            if ($response->successful()) {
                $file = $response->json();
                return !($file['trashed'] ?? false);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Search for a folder by name
     */
    protected function searchFolder($folderName)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get(self::GOOGLE_DRIVE_API_URL . '/files', [
                    'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false",
                    'fields' => 'files(id,name)',
                    'pageSize' => 1,
                ]);

            if ($response->successful()) {
                $files = $response->json('files', []);
                return $files[0]['id'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error searching folder', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Search for a folder by name in specific parent folder
     */
    protected function searchFolderInParent($folderName, $parentId)
    {
        try {
            $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed=false";
            
            Log::info('[GoogleDrive] Searching folder in parent', [
                'folder_name' => $folderName,
                'parent_id' => $parentId,
                'query' => $query,
            ]);

            $response = Http::withToken($this->accessToken)
                ->get(self::GOOGLE_DRIVE_API_URL . '/files', [
                    'q' => $query,
                    'fields' => 'files(id,name,parents)',
                    'pageSize' => 1,
                ]);

            if ($response->successful()) {
                $files = $response->json('files', []);
                
                if (!empty($files)) {
                    Log::info('[GoogleDrive] Folder found in parent', [
                        'folder_id' => $files[0]['id'],
                    ]);
                    return $files[0]['id'];
                }
                
                Log::info('[GoogleDrive] Folder not found in parent');
                return null;
            }

            Log::error('[GoogleDrive] Failed to search folder in parent', [
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error searching folder in parent', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a new folder
     */
    public function createFolder($folderName, $parentId = null)
    {
        try {
            $metadata = [
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
            ];

            if ($parentId) {
                $metadata['parents'] = [$parentId];
            }

            $response = Http::withToken($this->accessToken)
                ->post(self::GOOGLE_DRIVE_API_URL . '/files', $metadata);

            if ($response->successful()) {
                $folder = $response->json();
                Log::info('[GoogleDrive] Folder created', [
                    'folder_id' => $folder['id'],
                    'folder_name' => $folderName,
                ]);
                return $folder['id'];
            }

            throw new \Exception('Failed to create folder: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating folder', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List files/folders in a specific folder
     */
    public function listFiles($folderId = null, $pageSize = 100, $pageToken = null)
    {
        try {
            $query = $folderId 
                ? "'{$folderId}' in parents and trashed=false"
                : "trashed=false";

            $params = [
                'q' => $query,
                'fields' => 'nextPageToken, files(id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,trashed,permissions)',
                'pageSize' => $pageSize,
                'orderBy' => 'folder,name',
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::withToken($this->accessToken)
                ->get(self::GOOGLE_DRIVE_API_URL . '/files', $params);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to list files: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error listing files', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
            ]);
            throw $e;
        }
    }

    /**
     * List all files/folders in a specific folder (returns simple array, no pagination)
     */
    protected function listFilesInFolder($folderId)
    {
        try {
            $allFiles = [];
            $pageToken = null;

            do {
                $result = $this->listFiles($folderId, 100, $pageToken);
                $files = $result['files'] ?? [];
                $allFiles = array_merge($allFiles, $files);
                $pageToken = $result['nextPageToken'] ?? null;
            } while ($pageToken);

            return $allFiles;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error listing files in folder', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
            ]);
            throw $e;
        }
    }

    /**
     * Sync files/folders from Google Drive to database
     */
    public function syncToDatabase($folderId = null, $branchId = null)
    {
        $synced = 0;
        $pageToken = null;

        do {
            $result = $this->listFiles($folderId, 100, $pageToken);
            $files = $result['files'] ?? [];

            foreach ($files as $file) {
                $this->saveOrUpdateItem($file, $branchId);
                $synced++;

                // If it's a folder, recursively sync its contents
                if ($file['mimeType'] === 'application/vnd.google-apps.folder') {
                    $synced += $this->syncToDatabase($file['id'], $branchId);
                }
            }

            $pageToken = $result['nextPageToken'] ?? null;
        } while ($pageToken);

        return $synced;
    }

    /**
     * Save or update item in database
     */
    protected function saveOrUpdateItem($fileData, $branchId = null)
    {
        $type = $fileData['mimeType'] === 'application/vnd.google-apps.folder' ? 'folder' : 'file';
        $parentId = isset($fileData['parents']) && is_array($fileData['parents']) ? $fileData['parents'][0] : null;

        GoogleDriveItem::updateOrCreate(
            ['google_id' => $fileData['id']],
            [
                'branch_id' => $branchId,
                'name' => $fileData['name'],
                'type' => $type,
                'mime_type' => $fileData['mimeType'],
                'parent_id' => $parentId,
                'size' => $fileData['size'] ?? null,
                'web_view_link' => $fileData['webViewLink'] ?? null,
                'web_content_link' => $fileData['webContentLink'] ?? null,
                'thumbnail_link' => $fileData['thumbnailLink'] ?? null,
                'icon_link' => $fileData['iconLink'] ?? null,
                'google_created_at' => isset($fileData['createdTime']) ? Carbon::parse($fileData['createdTime']) : null,
                'google_modified_at' => isset($fileData['modifiedTime']) ? Carbon::parse($fileData['modifiedTime']) : null,
                'is_trashed' => $fileData['trashed'] ?? false,
                'permissions' => $fileData['permissions'] ?? null,
            ]
        );
    }

    /**
     * Create a folder on Google Drive
     */
    public function createFolderOnDrive($folderName, $parentId, $branchId = null, $userId = null)
    {
        try {
            $googleFolderId = $this->createFolder($folderName, $parentId);

            // Save to database
            $item = GoogleDriveItem::create([
                'branch_id' => $branchId,
                'google_id' => $googleFolderId,
                'name' => $folderName,
                'type' => 'folder',
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $parentId,
                'created_by' => $userId,
            ]);

            return $item;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating folder on drive', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload a file to Google Drive
     */
    public function uploadFile($file, $parentId, $branchId = null, $userId = null)
    {
        try {
            Log::info('[GoogleDrive] Starting file upload', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'parent_id' => $parentId,
            ]);

            // Use multipart upload (single request with metadata + content)
            $boundary = uniqid();
            $metadata = [
                'name' => $file->getClientOriginalName(),
                'parents' => [$parentId],
            ];

            // Build multipart request body
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
            $body .= json_encode($metadata) . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$file->getMimeType()}\r\n\r\n";
            $body .= file_get_contents($file->getRealPath()) . "\r\n";
            $body .= "--{$boundary}--";

            $uploadResponse = Http::withToken($this->accessToken)
                ->withHeaders([
                    'Content-Type' => "multipart/related; boundary={$boundary}",
                ])
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime');

            Log::info('[GoogleDrive] Upload response received', [
                'status' => $uploadResponse->status(),
                'successful' => $uploadResponse->successful(),
            ]);

            if (!$uploadResponse->successful()) {
                Log::error('[GoogleDrive] Upload failed', [
                    'status' => $uploadResponse->status(),
                    'response' => $uploadResponse->body(),
                ]);
                throw new \Exception('Failed to upload file: ' . $uploadResponse->body());
            }
            
            $fileData = $uploadResponse->json();
            
            Log::info('[GoogleDrive] File uploaded successfully', [
                'google_id' => $fileData['id'],
                'file_name' => $fileData['name'],
            ]);

            // Save to database
            $item = GoogleDriveItem::create([
                'branch_id' => $branchId,
                'google_id' => $fileData['id'],
                'name' => $fileData['name'],
                'type' => 'file',
                'mime_type' => $fileData['mimeType'],
                'parent_id' => $parentId,
                'size' => $fileData['size'] ?? null,
                'web_view_link' => $fileData['webViewLink'] ?? null,
                'web_content_link' => $fileData['webContentLink'] ?? null,
                'thumbnail_link' => $fileData['thumbnailLink'] ?? null,
                'icon_link' => $fileData['iconLink'] ?? null,
                'google_created_at' => isset($fileData['createdTime']) ? Carbon::parse($fileData['createdTime']) : null,
                'google_modified_at' => isset($fileData['modifiedTime']) ? Carbon::parse($fileData['modifiedTime']) : null,
                'created_by' => $userId,
            ]);

            return $item;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error uploading file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_name' => $file->getClientOriginalName() ?? 'unknown',
            ]);
            throw $e;
        }
    }

    /**
     * Upload a file to Google Drive with custom name
     * Returns only the file ID (not database item)
     */
    protected function uploadFileWithCustomName($file, $parentId, $customName)
    {
        try {
            Log::info('[GoogleDrive] Starting file upload with custom name', [
                'original_name' => $file->getClientOriginalName(),
                'custom_name' => $customName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'parent_id' => $parentId,
            ]);

            // Use multipart upload (single request with metadata + content)
            $boundary = uniqid();
            $metadata = [
                'name' => $customName,
                'parents' => [$parentId],
            ];

            // Build multipart request body
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
            $body .= json_encode($metadata) . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$file->getMimeType()}\r\n\r\n";
            $body .= file_get_contents($file->getRealPath()) . "\r\n";
            $body .= "--{$boundary}--";

            $uploadResponse = Http::withToken($this->accessToken)
                ->withHeaders([
                    'Content-Type' => "multipart/related; boundary={$boundary}",
                ])
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,mimeType,webViewLink');

            if (!$uploadResponse->successful()) {
                Log::error('[GoogleDrive] Upload failed', [
                    'status' => $uploadResponse->status(),
                    'response' => $uploadResponse->body(),
                ]);
                throw new \Exception('Failed to upload file: ' . $uploadResponse->body());
            }
            
            $fileData = $uploadResponse->json();
            
            Log::info('[GoogleDrive] File uploaded successfully with custom name', [
                'google_id' => $fileData['id'],
                'file_name' => $fileData['name'],
            ]);

            return $fileData['id'];
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error uploading file with custom name', [
                'error' => $e->getMessage(),
                'custom_name' => $customName,
            ]);
            throw $e;
        }
    }

    /**
     * Delete file/folder from Google Drive
     */
    public function deleteFile($googleId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->delete(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}");

            if ($response->successful()) {
                // Mark as trashed in database
                GoogleDriveItem::where('google_id', $googleId)->update([
                    'is_trashed' => true,
                    'trashed_at' => Carbon::now(),
                ]);

                return true;
            }

            throw new \Exception('Failed to delete file: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error deleting file', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Rename file/folder on Google Drive
     */
    public function renameFile($googleId, $newName, $userId = null)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->patch(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}", [
                    'name' => $newName,
                ]);

            if ($response->successful()) {
                // Update in database
                GoogleDriveItem::where('google_id', $googleId)->update([
                    'name' => $newName,
                    'updated_by' => $userId,
                ]);

                return true;
            }

            throw new \Exception('Failed to rename file: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error renaming file', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Move file/folder to another folder
     */
    public function moveFile($googleId, $newParentId, $userId = null)
    {
        try {
            // Get current parents
            $item = GoogleDriveItem::where('google_id', $googleId)->first();
            if (!$item) {
                throw new \Exception('File not found in database');
            }

            $oldParentId = $item->parent_id;

            // Move on Google Drive
            $response = Http::withToken($this->accessToken)
                ->patch(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}", [
                    'addParents' => $newParentId,
                    'removeParents' => $oldParentId,
                ]);

            if ($response->successful()) {
                // Update in database
                $item->update([
                    'parent_id' => $newParentId,
                    'updated_by' => $userId,
                ]);

                return true;
            }

            throw new \Exception('Failed to move file: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error moving file', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Get permissions for a file/folder
     */
    public function getPermissions($googleId)
    {
        try {
            $this->ensureValidAccessToken();

            $response = Http::withToken($this->accessToken)
                ->get(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}/permissions", [
                    'fields' => 'permissions(id,type,emailAddress,role,displayName)',
                ]);

            if ($response->successful()) {
                return $response->json()['permissions'] ?? [];
            }

            throw new \Exception('Failed to get permissions: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting permissions', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Share file/folder with a user
     */
    public function shareFile($googleId, $email, $role = 'reader')
    {
        try {
            $this->ensureValidAccessToken();

            $response = Http::withToken($this->accessToken)
                ->post(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}/permissions", [
                    'type' => 'user',
                    'role' => $role,
                    'emailAddress' => $email,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to share file: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error sharing file', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
                'email' => $email,
            ]);
            throw $e;
        }
    }

    /**
     * Make file/folder publicly accessible (anyone with link can view)
     */
    public function makeFilePublic($googleId)
    {
        try {
            $this->ensureValidAccessToken();

            $response = Http::withToken($this->accessToken)
                ->post(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}/permissions", [
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);

            if ($response->successful()) {
                Log::info('[GoogleDrive] File made public', [
                    'google_id' => $googleId,
                ]);
                return $response->json();
            }

            throw new \Exception('Failed to make file public: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error making file public', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Remove permission from file/folder
     */
    public function removePermission($googleId, $permissionId)
    {
        try {
            $this->ensureValidAccessToken();

            $response = Http::withToken($this->accessToken)
                ->delete(self::GOOGLE_DRIVE_API_URL . "/files/{$googleId}/permissions/{$permissionId}");

            if ($response->successful()) {
                return true;
            }

            throw new \Exception('Failed to remove permission: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error removing permission', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
                'permission_id' => $permissionId,
            ]);
            throw $e;
        }
    }

    /**
     * Verify if a specific email has permission for a file/folder
     * Returns the permission details if found, null otherwise
     */
    public function verifyUserPermission($googleId, $email)
    {
        try {
            $permissions = $this->getPermissions($googleId);
            
            foreach ($permissions as $permission) {
                if (isset($permission['emailAddress']) && $permission['emailAddress'] === $email) {
                    return $permission;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error verifying user permission', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
                'email' => $email,
            ]);
            return null;
        }
    }

    /**
     * Grant permissions to class folder for teachers and students
     */
    public function grantClassFolderPermissions($classFolderId, $teacherEmails = [], $studentEmails = [])
    {
        try {
            Log::info('[GoogleDrive] Granting class folder permissions', [
                'folder_id' => $classFolderId,
                'teachers_count' => count($teacherEmails),
                'students_count' => count($studentEmails),
            ]);
            
            // Grant writer permission to teachers
            foreach ($teacherEmails as $email) {
                if ($email) {
                    try {
                        $this->shareFile($classFolderId, $email, 'writer');
                    } catch (\Exception $e) {
                        Log::warning('[GoogleDrive] Failed to share with teacher', [
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            // Grant reader permission to students
            foreach ($studentEmails as $email) {
                if ($email) {
                    try {
                        $this->shareFile($classFolderId, $email, 'reader');
                    } catch (\Exception $e) {
                        Log::warning('[GoogleDrive] Failed to share with student', [
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error granting class folder permissions', [
                'error' => $e->getMessage(),
                'folder_id' => $classFolderId,
            ]);
            throw $e;
        }
    }

    /**
     * Create or get student folder in class folder
     */
    public function createOrGetStudentFolderInClass($classFolderId, $studentName, $studentCode)
    {
        try {
            $folderName = $this->sanitizeFolderName($studentName) . ' - ' . strtoupper($studentCode);
            
            Log::info('[GoogleDrive] Creating/getting student folder in class', [
                'class_folder_id' => $classFolderId,
                'folder_name' => $folderName,
            ]);
            
            // Check if folder already exists
            $existingFolderId = $this->searchFolderInParent($folderName, $classFolderId);
            
            if ($existingFolderId) {
                Log::info('[GoogleDrive] Student folder already exists', [
                    'folder_id' => $existingFolderId,
                ]);
                return $existingFolderId;
            }
            
            // Create new folder
            $folderId = $this->createFolder($folderName, $classFolderId);
            
            // Save to database
            GoogleDriveItem::updateOrCreate(
                ['google_id' => $folderId],
                [
                    'name' => $folderName,
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $classFolderId,
                    'branch_id' => $this->setting->branch_id,
                    'is_trashed' => false,
                    'metadata' => json_encode([
                        'type' => 'student_homework',
                        'student_code' => $studentCode,
                    ]),
                ]
            );
            
            Log::info('[GoogleDrive] Student folder created', [
                'folder_id' => $folderId,
            ]);
            
            return $folderId;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating student folder', [
                'error' => $e->getMessage(),
                'class_folder_id' => $classFolderId,
                'student_code' => $studentCode,
            ]);
            throw $e;
        }
    }

    /**
     * Create or get a folder in a parent folder (generic method)
     *
     * @param string $parentFolderId Parent folder ID
     * @param string $folderName Name of the folder to create/get
     * @param int|null $branchId Branch ID for database record
     * @return string Folder ID
     */
    public function createOrGetFolderInParent($parentFolderId, $folderName, $branchId = null)
    {
        try {
            $sanitizedName = $this->sanitizeFolderName($folderName);

            Log::info('[GoogleDrive] Creating/getting folder in parent', [
                'parent_folder_id' => $parentFolderId,
                'folder_name' => $sanitizedName,
            ]);

            // Check if folder already exists
            $existingFolderId = $this->searchFolderInParent($sanitizedName, $parentFolderId);

            if ($existingFolderId) {
                Log::info('[GoogleDrive] Folder already exists', [
                    'folder_id' => $existingFolderId,
                ]);
                return $existingFolderId;
            }

            // Create new folder
            $folderId = $this->createFolder($sanitizedName, $parentFolderId);

            // Save to database
            GoogleDriveItem::updateOrCreate(
                ['google_id' => $folderId],
                [
                    'name' => $sanitizedName,
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $parentFolderId,
                    'branch_id' => $branchId ?: $this->setting->branch_id,
                    'is_trashed' => false,
                ]
            );

            Log::info('[GoogleDrive] Folder created', [
                'folder_id' => $folderId,
                'folder_name' => $sanitizedName,
            ]);

            return $folderId;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating folder in parent', [
                'error' => $e->getMessage(),
                'parent_folder_id' => $parentFolderId,
                'folder_name' => $folderName,
            ]);
            throw $e;
        }
    }

    /**
     * Get the web view link for a folder
     *
     * @param string $folderId Google Drive folder ID
     * @return string Web view link
     */
    public function getFolderWebViewLink($folderId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->get(self::GOOGLE_DRIVE_API_URL . "/files/{$folderId}", [
                    'fields' => 'webViewLink'
                ]);

            if (!$response->successful()) {
                Log::warning('[GoogleDrive] Failed to get folder web link', [
                    'folder_id' => $folderId,
                    'status' => $response->status(),
                ]);
                return "https://drive.google.com/drive/folders/{$folderId}"; // Fallback
            }

            $webViewLink = $response->json()['webViewLink'] ?? "https://drive.google.com/drive/folders/{$folderId}";

            Log::info('[GoogleDrive] Got folder web view link', [
                'folder_id' => $folderId,
                'link' => $webViewLink,
            ]);

            return $webViewLink;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting folder web link', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
            ]);
            // Return fallback link
            return "https://drive.google.com/drive/folders/{$folderId}";
        }
    }

    /**
     * Revoke permission from a file/folder by email address
     * Finds the permission ID for the given email and removes it
     *
     * @param string $googleId Google Drive file/folder ID
     * @param string $email Email address to revoke permission from
     * @return bool True if permission was revoked, false if not found
     * @throws \Exception
     */
    public function revokePermission($googleId, $email)
    {
        try {
            // First, find the permission ID for this email
            $permissions = $this->getPermissions($googleId);
            
            $permissionId = null;
            foreach ($permissions as $permission) {
                if (isset($permission['emailAddress']) && $permission['emailAddress'] === $email) {
                    $permissionId = $permission['id'];
                    break;
                }
            }
            
            if (!$permissionId) {
                Log::info('[GoogleDrive] Permission not found for email, may already be removed', [
                    'google_id' => $googleId,
                    'email' => $email,
                ]);
                return false;
            }
            
            // Remove the permission
            $this->removePermission($googleId, $permissionId);
            
            Log::info('[GoogleDrive] Permission revoked successfully', [
                'google_id' => $googleId,
                'email' => $email,
                'permission_id' => $permissionId,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error revoking permission', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
                'email' => $email,
            ]);
            throw $e;
        }
    }

    /**
     * Sync all permissions for a folder from Google Drive to database
     */
    public function syncFolderPermissions($googleId)
    {
        try {
            $item = GoogleDriveItem::where('google_id', $googleId)->first();
            
            if (!$item) {
                throw new \Exception("Item not found in database: {$googleId}");
            }
            
            $permissions = $this->getPermissions($googleId);
            $syncedCount = 0;
            $skippedEmails = [];
            $skippedReasons = [];
            
            foreach ($permissions as $permission) {
                // Skip owner and domain-wide permissions
                if ($permission['type'] !== 'user' || !isset($permission['emailAddress'])) {
                    if ($permission['type'] === 'domain' || $permission['type'] === 'anyone') {
                        $skippedReasons[] = "Skipped {$permission['type']} permission";
                    }
                    continue;
                }
                
                $email = $permission['emailAddress'];
                
                // Find user by google_email
                $user = \App\Models\User::where('google_email', $email)->first();
                
                if (!$user) {
                    // Log email that doesn't have matching user
                    $skippedEmails[] = $email;
                    Log::warning('[GoogleDrive] Permission skipped - no user with google_email', [
                        'google_id' => $googleId,
                        'folder_name' => $item->name,
                        'email' => $email,
                        'role' => $permission['role'],
                        'hint' => 'Assign this google_email to a user in Users Management'
                    ]);
                    continue;
                }
                
                // Create or update permission record
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
                
                $syncedCount++;
            }
            
            Log::info('[GoogleDrive] Synced folder permissions', [
                'google_id' => $googleId,
                'folder_name' => $item->name,
                'total_permissions' => count($permissions),
                'synced_count' => $syncedCount,
                'skipped_emails' => $skippedEmails,
                'skipped_reasons' => $skippedReasons,
            ]);
            
            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error syncing folder permissions', [
                'error' => $e->getMessage(),
                'google_id' => $googleId,
            ]);
            throw $e;
        }
    }

    /**
     * Copy syllabus folder to Class History for a class
     * Returns: ['folder_id' => ..., 'folder_name' => ..., 'unit_folders' => [...]]
     */
    public function copySyllabusFolderForClass($syllabusFolderId, $className, $classCode, $classId, $branchId)
    {
        try {
            Log::info('[GoogleDrive] Starting copy syllabus folder for class', [
                'syllabus_folder_id' => $syllabusFolderId,
                'class_name' => $className,
                'class_code' => $classCode,
                'class_id' => $classId,
            ]);

            // 1. Get or verify Class History folder exists
            $rootFolderId = $this->findOrCreateSchoolDriveFolder();
            $classHistoryFolderId = $this->searchFolderInParent('Class History', $rootFolderId);

            if (!$classHistoryFolderId) {
                throw new \Exception('CLASS_HISTORY_NOT_FOUND');
            }

            // 2. Generate class folder name
            $classFolderName = $this->sanitizeFolderName($className) . ' - ' . strtoupper($classCode);

            // 3. Check if class folder already exists (for update case)
            $existingClassFolderId = $this->searchFolderInParent($classFolderName, $classHistoryFolderId);
            if ($existingClassFolderId) {
                // Delete old folder first
                $this->deleteFolder($existingClassFolderId);
                Log::info('[GoogleDrive] Deleted existing class folder', [
                    'folder_id' => $existingClassFolderId,
                    'folder_name' => $classFolderName,
                ]);
            }

            // 4. Copy syllabus folder structure
            $classFolderId = $this->copyFolder($syllabusFolderId, $classHistoryFolderId, $classFolderName);

            // 5. Get all unit folders with their subfolders (Materials, Homework, Lesson Plans)
            $unitFolders = $this->getUnitFolders($classFolderId, $classId, $branchId);

            Log::info('[GoogleDrive] Successfully copied syllabus folder for class', [
                'class_folder_id' => $classFolderId,
                'class_folder_name' => $classFolderName,
                'units_count' => count($unitFolders),
            ]);
            
            // Grant permissions to teachers and existing students
            try {
                $class = \App\Models\ClassModel::with(['homeroomTeacher', 'schedules.teacher', 'students.student.user'])->find($classId);
                if ($class) {
                    // Collect teacher emails
                    $teacherEmails = [];
                    if ($class->homeroomTeacher && $class->homeroomTeacher->google_email) {
                        $teacherEmails[] = $class->homeroomTeacher->google_email;
                    }
                    foreach ($class->schedules as $schedule) {
                        if ($schedule->teacher && $schedule->teacher->google_email) {
                            $teacherEmails[] = $schedule->teacher->google_email;
                        }
                    }
                    $teacherEmails = array_unique($teacherEmails);
                    
                    // Collect student emails
                    $studentEmails = [];
                    foreach ($class->students as $classStudent) {
                        if ($classStudent->student && $classStudent->student->user && $classStudent->student->user->google_email) {
                            $studentEmails[] = $classStudent->student->user->google_email;
                        }
                    }
                    
                    // Grant permissions
                    if (!empty($teacherEmails) || !empty($studentEmails)) {
                        $this->grantClassFolderPermissions($classFolderId, $teacherEmails, $studentEmails);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[GoogleDrive] Error granting permissions after copying syllabus', [
                    'error' => $e->getMessage(),
                    'class_id' => $classId,
                ]);
                // Don't throw, folder is already created
            }

            return [
                'folder_id' => $classFolderId,
                'folder_name' => $classFolderName,
                'unit_folders' => $unitFolders,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error copying syllabus folder for class', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create an empty folder for a class in Class History
     * 
     * @param string $className Name of the class
     * @param string $classCode Code of the class
     * @param int $classId ID of the class (for database reference)
     * @param int $branchId Branch ID
     * @return array ['folder_id' => string, 'folder_name' => string]
     */
    public function createEmptyClassFolder($className, $classCode, $classId, $branchId)
    {
        try {
            Log::info('[GoogleDrive] Creating empty folder for class', [
                'class_name' => $className,
                'class_code' => $classCode,
                'class_id' => $classId,
            ]);

            // 1. Get or create Class History folder
            $classHistoryFolderId = $this->findOrCreateClassHistoryFolder();

            // 2. Generate class folder name
            $classFolderName = $this->sanitizeFolderName($className) . ' - ' . strtoupper($classCode);

            // 3. Check if class folder already exists
            $existingClassFolderId = $this->searchFolderInParent($classFolderName, $classHistoryFolderId);
            if ($existingClassFolderId) {
                Log::info('[GoogleDrive] Class folder already exists, returning existing', [
                    'folder_id' => $existingClassFolderId,
                    'folder_name' => $classFolderName,
                ]);
                
                return [
                    'folder_id' => $existingClassFolderId,
                    'folder_name' => $classFolderName,
                ];
            }

            // 4. Create new empty folder
            $classFolderId = $this->createFolder($classFolderName, $classHistoryFolderId);

            // 5. Save to database
            GoogleDriveItem::updateOrCreate(
                ['google_id' => $classFolderId],
                [
                    'name' => $classFolderName,
                    'type' => 'folder',
                    'mime_type' => 'application/vnd.google-apps.folder',
                    'parent_id' => $classHistoryFolderId,
                    'branch_id' => $branchId,
                    'is_trashed' => false,
                    'metadata' => json_encode([
                        'type' => 'class_folder',
                        'class_id' => $classId,
                    ]),
                ]
            );

            Log::info('[GoogleDrive] Successfully created empty class folder', [
                'class_folder_id' => $classFolderId,
                'class_folder_name' => $classFolderName,
            ]);
            
            // 6. Grant permissions to teachers and existing students
            try {
                $class = \App\Models\ClassModel::with(['homeroomTeacher', 'schedules.teacher', 'students.student.user'])->find($classId);
                if ($class) {
                    // Collect teacher emails
                    $teacherEmails = [];
                    if ($class->homeroomTeacher && $class->homeroomTeacher->google_email) {
                        $teacherEmails[] = $class->homeroomTeacher->google_email;
                    }
                    foreach ($class->schedules as $schedule) {
                        if ($schedule->teacher && $schedule->teacher->google_email) {
                            $teacherEmails[] = $schedule->teacher->google_email;
                        }
                    }
                    $teacherEmails = array_unique($teacherEmails);
                    
                    // Collect student emails
                    $studentEmails = [];
                    foreach ($class->students as $classStudent) {
                        if ($classStudent->student && $classStudent->student->user && $classStudent->student->user->google_email) {
                            $studentEmails[] = $classStudent->student->user->google_email;
                        }
                    }
                    
                    // Grant permissions
                    if (!empty($teacherEmails) || !empty($studentEmails)) {
                        $this->grantClassFolderPermissions($classFolderId, $teacherEmails, $studentEmails);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[GoogleDrive] Error granting permissions for class folder', [
                    'error' => $e->getMessage(),
                    'class_id' => $classId,
                ]);
                // Don't throw, folder is already created
            }

            return [
                'folder_id' => $classFolderId,
                'folder_name' => $classFolderName,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error creating empty class folder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Copy a folder and all its contents recursively
     */
    protected function copyFolder($sourceFolderId, $destinationParentId, $newName = null, $depth = 0)
    {
        try {
            // Get source folder metadata
            $response = Http::withToken($this->accessToken)
                ->timeout(30) // Add timeout for each request
                ->get(self::GOOGLE_DRIVE_API_URL . "/files/{$sourceFolderId}", [
                    'fields' => 'id,name,mimeType',
                ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to get source folder: " . $response->body());
            }

            $sourceFolder = $response->json();
            $folderName = $newName ?? $sourceFolder['name'];

            Log::info("[GoogleDrive] Copying folder (depth: {$depth})", [
                'folder_name' => $folderName,
                'source_id' => $sourceFolderId,
            ]);

            // Create new folder in destination
            $newFolderId = $this->createFolder($folderName, $destinationParentId);

            // Get all items in source folder
            $items = $this->listFilesInFolder($sourceFolderId);

            Log::info("[GoogleDrive] Found {count} items to copy in folder: {$folderName}", [
                'count' => count($items),
            ]);

            // Copy each item recursively
            $copiedCount = 0;
            foreach ($items as $item) {
                if ($item['mimeType'] === 'application/vnd.google-apps.folder') {
                    // Recursively copy subfolder
                    $this->copyFolder($item['id'], $newFolderId, null, $depth + 1);
                } else {
                    // Copy file
                    $this->copyFile($item['id'], $newFolderId);
                }
                $copiedCount++;
                
                // Log progress every 10 items
                if ($copiedCount % 10 === 0) {
                    Log::info("[GoogleDrive] Progress: {$copiedCount}/{count} items copied in {$folderName}", [
                        'count' => count($items),
                    ]);
                }
            }

            Log::info("[GoogleDrive] Finished copying folder: {$folderName}", [
                'items_copied' => $copiedCount,
            ]);

            return $newFolderId;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error copying folder', [
                'error' => $e->getMessage(),
                'source_folder_id' => $sourceFolderId,
                'depth' => $depth,
            ]);
            throw $e;
        }
    }

    /**
     * Copy a file to a new parent folder
     */
    protected function copyFile($fileId, $destinationParentId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30) // Add timeout
                ->post(self::GOOGLE_DRIVE_API_URL . "/files/{$fileId}/copy", [
                    'parents' => [$destinationParentId],
                ]);

            if (!$response->successful()) {
                Log::warning('[GoogleDrive] Failed to copy file, skipping', [
                    'file_id' => $fileId,
                    'status' => $response->status(),
                ]);
                // Don't throw error, just skip this file
                return null;
            }

            return $response->json()['id'];
        } catch (\Exception $e) {
            Log::warning('[GoogleDrive] Error copying file, skipping', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
            ]);
            // Don't throw error, just skip this file
            return null;
        }
    }

    /**
     * Delete a folder (move to trash)
     */
    protected function deleteFolder($folderId)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->delete(self::GOOGLE_DRIVE_API_URL . "/files/{$folderId}");

            if (!$response->successful()) {
                throw new \Exception("Failed to delete folder: " . $response->body());
            }

            return true;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error deleting folder', [
                'error' => $e->getMessage(),
                'folder_id' => $folderId,
            ]);
            throw $e;
        }
    }

    /**
     * Get all unit folders with their Materials, Homework, and Lesson Plans subfolders
     */
    protected function getUnitFolders($classFolderId, $classId, $branchId)
    {
        try {
            $items = $this->listFilesInFolder($classFolderId);
            $unitFolders = [];

            foreach ($items as $item) {
                if ($item['mimeType'] === 'application/vnd.google-apps.folder' && 
                    preg_match('/^Unit (\d+)$/', $item['name'], $matches)) {
                    
                    $unitNumber = (int)$matches[1];
                    $unitFolderId = $item['id'];

                    // Get subfolders (Materials, Homework, Lesson Plans)
                    $subfolders = $this->listFilesInFolder($unitFolderId);
                    $materialsFolderId = null;
                    $homeworkFolderId = null;
                    $lessonPlansFolderId = null;

                    foreach ($subfolders as $subfolder) {
                        if ($subfolder['mimeType'] === 'application/vnd.google-apps.folder') {
                            if ($subfolder['name'] === 'Materials') {
                                $materialsFolderId = $subfolder['id'];
                            } elseif ($subfolder['name'] === 'Homework') {
                                $homeworkFolderId = $subfolder['id'];
                            } elseif ($subfolder['name'] === 'Lesson Plans') {
                                $lessonPlansFolderId = $subfolder['id'];
                            }
                        }
                    }

                    // Create Lesson Plans folder if it doesn't exist
                    if (!$lessonPlansFolderId) {
                        $lessonPlansFolderId = $this->createFolder('Lesson Plans', $unitFolderId);
                        Log::info('[GoogleDrive] Created Lesson Plans folder', [
                            'unit_folder_id' => $unitFolderId,
                            'lesson_plans_folder_id' => $lessonPlansFolderId,
                        ]);
                    }

                    // Save to database
                    GoogleDriveItem::updateOrCreate(
                        [
                            'google_id' => $unitFolderId,
                            'branch_id' => $branchId,
                        ],
                        [
                            'name' => $item['name'],
                            'type' => 'folder',
                            'mime_type' => $item['mimeType'],
                            'parent_id' => $classFolderId,
                            'is_trashed' => false,
                            'metadata' => [
                                'type' => 'class_unit',
                                'class_id' => $classId,
                                'unit_number' => $unitNumber,
                                'materials_folder_id' => $materialsFolderId,
                                'homework_folder_id' => $homeworkFolderId,
                                'lesson_plans_folder_id' => $lessonPlansFolderId,
                            ],
                        ]
                    );

                    $unitFolders[] = [
                        'unit_number' => $unitNumber,
                        'unit_folder_id' => $unitFolderId,
                        'materials_folder_id' => $materialsFolderId,
                        'homework_folder_id' => $homeworkFolderId,
                        'lesson_plans_folder_id' => $lessonPlansFolderId,
                    ];
                }
            }

            // Sort by unit number
            usort($unitFolders, function($a, $b) {
                return $a['unit_number'] <=> $b['unit_number'];
            });

            return $unitFolders;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting unit folders', [
                'error' => $e->getMessage(),
                'class_folder_id' => $classFolderId,
            ]);
            throw $e;
        }
    }

    /**
     * Upload lesson plan file with naming convention
     * Naming: LP_{ClassCode}_Unit{X}_{LessonName}_{Date}_{Version}.{ext}
     */
    public function uploadLessonPlan($lessonPlansFolderId, $file, $classCode, $unitNumber, $lessonName)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $date = now()->format('Ymd');
            
            // Get existing lesson plans to determine version
            $existingFiles = $this->listFilesInFolder($lessonPlansFolderId);
            $prefix = "LP_{$classCode}_Unit{$unitNumber}_" . $this->sanitizeFileName($lessonName) . "_{$date}_";
            
            $version = 1;
            foreach ($existingFiles as $existingFile) {
                if (str_starts_with($existingFile['name'], $prefix)) {
                    $version++;
                }
            }

            $fileName = $prefix . str_pad($version, 2, '0', STR_PAD_LEFT) . ".{$extension}";

            // Upload file with custom name
            $fileId = $this->uploadFileWithCustomName($file, $lessonPlansFolderId, $fileName);

            Log::info('[GoogleDrive] Uploaded lesson plan', [
                'file_id' => $fileId,
                'file_name' => $fileName,
                'class_code' => $classCode,
                'unit_number' => $unitNumber,
            ]);

            return [
                'file_id' => $fileId,
                'file_name' => $fileName,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error uploading lesson plan', [
                'error' => $e->getMessage(),
                'class_code' => $classCode,
                'unit_number' => $unitNumber,
            ]);
            throw $e;
        }
    }

    /**
     * Get all lesson plans for a specific class code in a folder
     * Returns files starting with LP_{ClassCode}_
     */
    public function getLessonPlansByClassCode($lessonPlansFolderId, $classCode)
    {
        try {
            $allFiles = $this->listFilesInFolder($lessonPlansFolderId);
            $lessonPlans = [];

            $prefix = "LP_{$classCode}_";

            foreach ($allFiles as $file) {
                if (str_starts_with($file['name'], $prefix)) {
                    $lessonPlans[] = [
                        'id' => $file['id'],
                        'name' => $file['name'],
                        'mimeType' => $file['mimeType'],
                        'webViewLink' => $file['webViewLink'] ?? null,
                        'webContentLink' => $file['webContentLink'] ?? null,
                    ];
                }
            }

            // Sort by name (which includes date and version)
            usort($lessonPlans, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $lessonPlans;
        } catch (\Exception $e) {
            Log::error('[GoogleDrive] Error getting lesson plans by class code', [
                'error' => $e->getMessage(),
                'class_code' => $classCode,
            ]);
            throw $e;
        }
    }

    /**
     * Sanitize file name (remove special characters)
     */
    protected function sanitizeFileName($name)
    {
        // Remove special characters, keep only alphanumeric and basic punctuation
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        // Replace multiple spaces with single space
        $name = preg_replace('/\s+/', ' ', $name);
        // Trim and replace spaces with underscores
        return str_replace(' ', '_', trim($name));
    }
}

