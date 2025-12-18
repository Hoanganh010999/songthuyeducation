<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;

class SyncGoogleDriveBranchFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:sync-branch-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Drive folder names to include branch ID and name (format: {branch_id} - {branch_name})';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Google Drive branch folders sync...');

        $settings = GoogleDriveSetting::with('branch')
            ->where('is_active', true)
            ->get();

        if ($settings->isEmpty()) {
            $this->warn('No active Google Drive settings found.');
            return 0;
        }

        $this->info("Found {$settings->count()} active Google Drive setting(s).");

        foreach ($settings as $setting) {
            $this->info("\nProcessing branch: {$setting->branch->name} (ID: {$setting->branch_id})");

            try {
                $service = new GoogleDriveService($setting);
                
                // This will automatically check, create or update the folder name
                $folderId = $service->findOrCreateSchoolDriveFolder();
                
                $this->info("✓ Folder synced successfully");
                $this->info("  Folder ID: {$folderId}");
                $this->info("  Folder Name: {$setting->school_drive_folder_name}");
                
            } catch (\Exception $e) {
                $this->error("✗ Error syncing folder for branch {$setting->branch_id}");
                $this->error("  Error: {$e->getMessage()}");
                Log::error('[GoogleDrive] Sync failed', [
                    'branch_id' => $setting->branch_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\n✓ Sync completed!");
        return 0;
    }
}
