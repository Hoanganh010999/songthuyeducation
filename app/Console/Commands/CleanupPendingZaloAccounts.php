<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupPendingZaloAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zalo:cleanup-pending {--minutes=30 : Minutes to keep pending records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently clean up orphan pending Zalo accounts that were never completed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int) $this->option('minutes');

        $this->info("Cleaning up pending Zalo accounts older than {$minutes} minutes...");

        // Use DB query to permanently delete (bypass soft delete)
        $deletedCount = DB::table('zalo_accounts')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->delete();

        if ($deletedCount > 0) {
            $this->info("Permanently deleted {$deletedCount} pending account(s).");
            Log::info('[CleanupPendingZaloAccounts] Permanently cleaned up orphan pending accounts', [
                'deleted_count' => $deletedCount,
                'older_than_minutes' => $minutes,
            ]);
        } else {
            $this->info('No pending accounts to clean up.');
        }

        return Command::SUCCESS;
    }
}
