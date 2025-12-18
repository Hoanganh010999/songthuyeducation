<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GoogleDriveItem;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;

class SyncGoogleDrivePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdrive:sync-permissions 
                            {--branch= : Specific branch ID to sync}
                            {--folder= : Specific folder google_id to sync}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Drive folder permissions from Google to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Google Drive permissions sync...');
        
        $branchId = $this->option('branch');
        $folderId = $this->option('folder');
        $force = $this->option('force');
        
        try {
            // Get folders to sync
            $folders = $this->getFoldersToSync($branchId, $folderId, $force);
            
            if ($folders->isEmpty()) {
                $this->info('📭 No folders to sync.');
                return Command::SUCCESS;
            }
            
            $this->info("📂 Found {$folders->count()} folder(s) to sync.");
            
            $synced = 0;
            $errors = 0;
            
            $progressBar = $this->output->createProgressBar($folders->count());
            $progressBar->start();
            
            foreach ($folders as $folder) {
                try {
                    $setting = GoogleDriveSetting::forBranch($folder->branch_id)
                        ->active()
                        ->first();
                    
                    if (!$setting) {
                        $this->warn("\n⚠️  No Google Drive setting for branch {$folder->branch_id}");
                        $errors++;
                        $progressBar->advance();
                        continue;
                    }
                    
                    $service = new GoogleDriveService($setting);
                    $count = $service->syncFolderPermissions($folder->google_id);
                    
                    $synced += $count;
                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    Log::error('[SyncGoogleDrivePermissions] Error syncing folder', [
                        'folder_id' => $folder->id,
                        'google_id' => $folder->google_id,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                    $progressBar->advance();
                }
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info("✅ Sync completed!");
            $this->info("📊 Statistics:");
            $this->info("   - Folders processed: {$folders->count()}");
            $this->info("   - Permissions synced: {$synced}");
            $this->info("   - Errors: {$errors}");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            Log::error('[SyncGoogleDrivePermissions] Fatal error', [
                'error' => $e->getMessage(),
            ]);
            
            $this->error("❌ Fatal error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Get folders that need permission sync
     */
    protected function getFoldersToSync($branchId, $folderId, $force)
    {
        $query = GoogleDriveItem::where('type', 'folder')
            ->where('is_trashed', false);
        
        // Filter by specific folder
        if ($folderId) {
            $query->where('google_id', $folderId);
        }
        
        // Filter by branch
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        // Only sync folders that haven't been synced recently (unless forced)
        if (!$force) {
            $query->where(function ($q) {
                $q->whereHas('userPermissions', function ($pq) {
                    $pq->where('synced_at', '<', now()->subHours(24))
                        ->orWhereNull('synced_at');
                })
                ->orWhereDoesntHave('userPermissions');
            });
        }
        
        return $query->get();
    }
}
