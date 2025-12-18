<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LessonPlanSession;
use App\Models\User;
use App\Http\Controllers\Api\Quality\QualityAIController;
use Illuminate\Http\Request;

class TestMaterialGeneration extends Command
{
    protected $signature = 'test:material {session_id}';
    protected $description = 'Test material generation';

    public function handle()
    {
        $sessionId = $this->argument('session_id');

        $this->info("=== TESTING MATERIAL GENERATION ===\n");

        // Get session
        $session = LessonPlanSession::with(['blocks.stages.procedure'])->find($sessionId);

        if (!$session) {
            $this->error("Session not found: $sessionId");
            return 1;
        }

        $this->info("Session: {$session->lesson_title}");
        $this->info("Level: " . ($session->level ?? 'Not set'));
        $this->info("Duration: {$session->duration_minutes} minutes\n");

        // Get user
        $user = User::find(53);
        auth()->login($user);

        $this->info("Generating material with new prompt...");
        $this->info("Provider: anthropic");
        $this->info("Model: claude-sonnet-4-5-20250929\n");

        // Create request
        $request = Request::create('/', 'POST', [
            'session_id' => $sessionId,
            'ai_provider' => 'anthropic',
            'ai_model' => 'claude-sonnet-4-5-20250929'
        ]);

        $this->info("⏳ Calling AI... (30-60 seconds)\n");

        $start = microtime(true);

        try {
            $controller = app(QualityAIController::class);
            $response = $controller->generateMaterial($request, $sessionId);

            $end = microtime(true);
            $duration = round($end - $start, 2);

            $data = $response->getData(true);

            if ($data['success']) {
                $material = $data['material'];

                $this->info("\n✅ SUCCESS in {$duration}s\n");
                $this->info("=== MATERIAL INFO ===");
                $this->info("ID: {$material['id']}");
                $this->info("Title: {$material['title']}");
                $this->info("Description: " . strlen($material['description'] ?? '') . " chars");
                $this->info("Content: " . number_format(strlen($material['content'])) . " chars");

                // Save to file
                $file = "/tmp/generated_material_{$material['id']}.html";
                file_put_contents($file, $material['content']);
                $this->info("\n✅ Saved to: $file\n");

                // Analyze
                $this->info("=== ANALYSIS ===");
                $content = $material['content'];
                $wordCount = str_word_count(strip_tags($content));

                $this->info("Word count: " . number_format($wordCount));
                $this->info((stripos($content, 'vocabulary') !== false ? '✅' : '❌') . " Has vocabulary");
                $this->info((stripos($content, '_____') !== false || stripos($content, 'border-bottom') !== false ? '✅' : '❌') . " Has blanks");
                $this->info((stripos($content, 'answer') !== false && stripos($content, 'key') !== false ? '✅' : '❌') . " Has answer key");
                $this->info((stripos($content, '<table') !== false ? '✅' : '❌') . " Has tables");

                $this->info("\n=== PREVIEW (first 500 chars) ===");
                $this->line(substr($content, 0, 500) . "...");

                return 0;
            } else {
                $this->error("\n❌ FAILED");
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
    }
}
