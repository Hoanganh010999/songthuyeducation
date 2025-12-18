<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GoogleDriveSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RefreshGoogleTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:refresh-tokens
                          {--force : Force refresh even if not expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Google Drive access tokens before they expire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Google token refresh...');

        $force = $this->option('force');

        // Get all Google Drive settings
        $settings = GoogleDriveSetting::all();

        if ($settings->isEmpty()) {
            $this->warn('⚠️  No Google Drive settings found');
            return 0;
        }

        $refreshed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($settings as $setting) {
            $this->info("Processing branch ID: {$setting->branch_id}");

            // Check if token is expired or will expire soon (within 5 minutes)
            $willExpireSoon = $setting->token_expires_at &&
                             $setting->token_expires_at->subMinutes(5)->isPast();

            if (!$force && !$willExpireSoon) {
                $this->line("  ⏭️  Token still valid until: {$setting->token_expires_at}");
                $skipped++;
                continue;
            }

            // Refresh token
            try {
                $this->line("  🔄 Refreshing token...");

                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $setting->client_id,
                    'client_secret' => $setting->client_secret,
                    'refresh_token' => $setting->refresh_token,
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    $setting->update([
                        'access_token' => $data['access_token'],
                        'token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
                    ]);

                    $this->info("  ✅ Token refreshed! Expires at: {$setting->token_expires_at}");
                    $refreshed++;

                    Log::info('[GoogleToken] Token refreshed successfully', [
                        'branch_id' => $setting->branch_id,
                        'expires_at' => $setting->token_expires_at,
                    ]);
                } else {
                    $error = $response->json();
                    $this->error("  ❌ Failed to refresh: {$error['error']} - {$error['error_description']}");
                    $failed++;

                    Log::error('[GoogleToken] Failed to refresh token', [
                        'branch_id' => $setting->branch_id,
                        'error' => $error,
                    ]);

                    // If refresh token is invalid, notify admins
                    if ($error['error'] === 'invalid_grant') {
                        $this->error("  ⚠️  CRITICAL: Refresh token is invalid. Re-authentication required!");
                        Log::critical('[GoogleToken] Refresh token invalid - re-authentication required', [
                            'branch_id' => $setting->branch_id,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Exception: {$e->getMessage()}");
                $failed++;

                Log::error('[GoogleToken] Exception during token refresh', [
                    'branch_id' => $setting->branch_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("  ✅ Refreshed: {$refreshed}");
        $this->line("  ⏭️  Skipped: {$skipped}");
        $this->line("  ❌ Failed: {$failed}");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('⚠️  Some tokens failed to refresh. Check logs for details.');
            return 1;
        }

        return 0;
    }
}
