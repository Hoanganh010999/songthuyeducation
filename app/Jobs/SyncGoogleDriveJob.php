<?php

namespace App\Jobs;

use App\Models\GoogleDriveSetting;
use App\Models\GoogleDriveItem;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SyncGoogleDriveJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    protected $branchId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $branchId, int $userId)
    {
        $this->branchId = $branchId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[GoogleDrive Job] Starting sync', [
            'branch_id' => $this->branchId,
            'user_id' => $this->userId,
        ]);

        // Update sync status to "in_progress"
        $this->updateSyncStatus('in_progress', 0);

        try {
            // Get active setting for the branch
            $setting = GoogleDriveSetting::where('branch_id', $this->branchId)
                ->where('is_active', true)
                ->first();
            
            if (!$setting) {
                throw new \Exception('No active Google Drive settings found for branch ' . $this->branchId);
            }

            $service = new GoogleDriveService($setting);
            
            // Check if root folder existed before sync
            $rootFolderExisted = !empty($setting->school_drive_folder_id);
            
            // Get School Drive folder ID (will create if not exists)
            $folderId = $service->findOrCreateSchoolDriveFolder();
            
            // Reload setting to get updated folder name
            $setting->refresh();

            Log::info('[GoogleDrive Job] Root folder ready', [
                'folder_id' => $folderId,
                'folder_name' => $setting->school_drive_folder_name,
                'folder_existed' => $rootFolderExisted,
            ]);

            // Update sync status with folder info
            $this->updateSyncStatus('in_progress', 10, [
                'root_folder_name' => $setting->school_drive_folder_name,
                'root_folder_existed' => $rootFolderExisted,
            ]);

            // If folder was just created, no need to sync (it's empty!)
            if (!$rootFolderExisted) {
                Log::info('[GoogleDrive Job] Root folder just created, skipping sync (folder is empty)');
                
                // Update last synced time
                $setting->update(['last_synced_at' => now()]);
                
                // Prepare result
                $result = [
                    'root_folder_name' => $setting->school_drive_folder_name,
                    'root_folder_action' => 'vừa được tạo mới',
                    'root_folder_existed' => false,
                    'files_synced' => 0,
                    'permissions_synced' => 0,
                    'folders_processed' => 0,
                    'message' => 'Root folder created successfully. No files to sync yet.',
                ];

                Log::info('[GoogleDrive Job] Sync completed (new folder, nothing to sync)', $result);

                // Update sync status to "completed"
                $this->updateSyncStatus('completed', 100, $result);
                
                return;
            }

            // Sync files/folders from this folder (only if folder already existed)
            $syncedCount = $service->syncToDatabase($folderId, $this->branchId);

            Log::info('[GoogleDrive Job] Files synced', [
                'count' => $syncedCount,
            ]);

            // Update progress
            $this->updateSyncStatus('in_progress', 50, [
                'files_synced' => $syncedCount,
            ]);

            // Sync permissions for all folders in this branch
            $permissionsSynced = 0;
            $folders = GoogleDriveItem::where('branch_id', $this->branchId)
                ->where('type', 'folder')
                ->where('is_trashed', false)
                ->get();
            
            $totalFolders = $folders->count();
            $processedFolders = 0;

            foreach ($folders as $folder) {
                try {
                    $count = $service->syncFolderPermissions($folder->google_id);
                    $permissionsSynced += $count;
                    $processedFolders++;

                    // Update progress
                    $progress = 50 + (($processedFolders / $totalFolders) * 45);
                    $this->updateSyncStatus('in_progress', (int)$progress, [
                        'permissions_synced' => $permissionsSynced,
                        'folders_processed' => $processedFolders,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[GoogleDrive Job] Failed to sync permissions for folder', [
                        'folder_id' => $folder->id,
                        'google_id' => $folder->google_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with next folder even if one fails
                }
            }

            // Update last synced time
            $setting->update(['last_synced_at' => now()]);

            // Prepare final result
            $folderAction = $rootFolderExisted ? 'đã được xác minh' : 'vừa được tạo mới';

            $result = [
                'root_folder_name' => $setting->school_drive_folder_name,
                'root_folder_action' => $folderAction,
                'root_folder_existed' => $rootFolderExisted,
                'files_synced' => $syncedCount,
                'permissions_synced' => $permissionsSynced,
                'folders_processed' => $totalFolders,
            ];

            Log::info('[GoogleDrive Job] Sync completed', $result);

            // Update sync status to "completed"
            $this->updateSyncStatus('completed', 100, $result);

        } catch (\Exception $e) {
            Log::error('[GoogleDrive Job] Sync failed', [
                'branch_id' => $this->branchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync status to "failed"
            $this->updateSyncStatus('failed', 0, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update sync status in cache
     */
    protected function updateSyncStatus(string $status, int $progress = 0, array $data = []): void
    {
        $cacheKey = "google_drive_sync_status_{$this->branchId}_{$this->userId}";
        
        Cache::put($cacheKey, [
            'status' => $status,
            'progress' => $progress,
            'data' => $data,
            'updated_at' => now()->toIso8601String(),
        ], 600); // Cache for 10 minutes
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[GoogleDrive Job] Job failed permanently', [
            'branch_id' => $this->branchId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Update sync status to "failed"
        $this->updateSyncStatus('failed', 0, [
            'error' => $exception->getMessage(),
        ]);
    }
}
