<?php

namespace App\Console\Commands;

use App\Models\VocabularyAudioRecording;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanVocabularyAudio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vocabulary:clean-audio {--hours=24 : Delete files older than X hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old vocabulary pronunciation audio recordings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $threshold = Carbon::now()->subHours($hours);

        $this->info("Cleaning vocabulary audio files older than {$hours} hours...");
        $this->info("Threshold: {$threshold->toDateTimeString()}");

        // Get all recordings older than threshold
        $recordings = VocabularyAudioRecording::where('created_at', '<', $threshold)->get();

        $count = $recordings->count();
        
        if ($count === 0) {
            $this->info('No audio files to clean.');
            return 0;
        }

        $this->info("Found {$count} audio files to delete.");

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($recordings as $recording) {
            try {
                $filePath = $recording->file_path;
                $recording->delete(); // This will trigger the deleteFile() method in the model
                $deletedCount++;
                
                Log::info('[VocabularyAudioCleanup] Deleted audio file', [
                    'recording_id' => $recording->id,
                    'file_path' => $filePath,
                    'age_hours' => $recording->created_at->diffInHours(Carbon::now())
                ]);
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('[VocabularyAudioCleanup] Failed to delete audio file', [
                    'recording_id' => $recording->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("✓ Deleted {$deletedCount} audio files.");
        
        if ($failedCount > 0) {
            $this->warn("⚠ Failed to delete {$failedCount} files.");
        }

        Log::info('[VocabularyAudioCleanup] Cleanup completed', [
            'threshold' => $threshold->toDateTimeString(),
            'deleted' => $deletedCount,
            'failed' => $failedCount
        ]);

        return 0;
    }
}

