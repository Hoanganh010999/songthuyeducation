<?php

namespace App\Console\Commands;

use App\Models\HomeworkAssignment;
use App\Models\CoursePost;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixMissingHomeworkPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'homework:fix-missing-posts
                            {--dry-run : Run without making any changes}
                            {--limit=100 : Maximum number of homework to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for homework assignments without course posts and create missing posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Checking for homework assignments without posts...');
        $this->info('Dry run mode: ' . ($dryRun ? 'YES' : 'NO'));
        $this->newLine();

        // Get all homework assignments (ordered by most recent first)
        $homeworks = HomeworkAssignment::with(['class', 'creator', 'session'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $this->info("Checking {$homeworks->count()} homework assignments...");
        $this->newLine();

        $missingCount = 0;
        $fixedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($homeworks->count());
        $progressBar->start();

        foreach ($homeworks as $homework) {
            $progressBar->advance();

            // Check if post exists
            $post = CoursePost::where('post_type', 'homework')
                ->whereJsonContains('metadata->homework_id', $homework->id)
                ->first();

            if (!$post) {
                $missingCount++;

                $this->newLine();
                $this->warn("❌ MISSING POST for Homework ID: {$homework->id}");
                $this->line("   Title: {$homework->title}");
                $this->line("   Class: {$homework->class->name} (ID: {$homework->class_id})");
                $this->line("   Created: {$homework->created_at}");
                $this->line("   Creator: " . ($homework->creator ? $homework->creator->name : 'Unknown') . " (ID: {$homework->created_by})");

                if (!$dryRun) {
                    try {
                        // Create the missing post
                        $creator = $homework->creator ?? User::find($homework->created_by);

                        if (!$creator) {
                            $this->error("   ❌ Cannot find creator user (ID: {$homework->created_by})");
                            $errorCount++;
                            continue;
                        }

                        $session = $homework->session;
                        $content = $homework->description ?? "<p>Check homework details below</p>";

                        $post = CoursePost::create([
                            'class_id' => $homework->class_id,
                            'user_id' => $homework->created_by,
                            'content' => $content,
                            'post_type' => 'homework',
                            'branch_id' => $homework->class->branch_id,
                            'metadata' => [
                                'homework_id' => $homework->id,
                                'homework_title' => $homework->title,
                                'due_date' => $homework->deadline,
                                'session_info' => $session ? "Buổi {$session->session_number}: {$session->lesson_title}" : null,
                            ],
                        ]);

                        $this->info("   ✅ Post created with ID: {$post->id}");
                        $fixedCount++;

                        Log::info('[FixMissingHomeworkPosts] Created missing post', [
                            'homework_id' => $homework->id,
                            'post_id' => $post->id,
                        ]);
                    } catch (\Exception $e) {
                        $this->error("   ❌ Error creating post: {$e->getMessage()}");
                        $errorCount++;

                        Log::error('[FixMissingHomeworkPosts] Failed to create post', [
                            'homework_id' => $homework->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Summary ===');
        $this->line("Total homework checked: {$homeworks->count()}");
        $this->line("Missing posts found: {$missingCount}");

        if (!$dryRun) {
            $this->line("Posts created: {$fixedCount}");
            $this->line("Errors: {$errorCount}");
        } else {
            $this->warn("Running in dry-run mode - no changes were made");
            $this->info("Run without --dry-run to create missing posts");
        }

        return 0;
    }
}
