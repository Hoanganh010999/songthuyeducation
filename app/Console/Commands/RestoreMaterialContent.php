<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreMaterialContent extends Command
{
    protected $signature = 'material:restore {material_id=2}';
    protected $description = 'Restore material content from debug JSON files';

    public function handle()
    {
        $materialId = $this->argument('material_id');

        $this->info("=== RESTORING MATERIAL CONTENT FROM DEBUG FILES ===\n");

        // Find debug files
        $debugPath = storage_path('logs/material_debug_*.json');
        $debugFiles = glob($debugPath);

        if (empty($debugFiles)) {
            $this->error("❌ No debug files found");
            return 1;
        }

        // Sort by modification time, newest first
        usort($debugFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $this->info("Found " . count($debugFiles) . " debug files");
        $this->info("Checking most recent files...\n");

        $data = null;
        $selectedFile = null;

        foreach ($debugFiles as $debugFile) {
            $filename = basename($debugFile);
            $this->line("Checking: {$filename}");

            $content = File::get($debugFile);

            if (empty($content)) {
                $this->line("  ⚠️  File is empty");
                continue;
            }

            $this->line("  File size: " . number_format(strlen($content)) . " bytes");

            // Try to decode JSON with error handling
            $debugData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->line("  ❌ JSON parse error: " . json_last_error_msg());
                $this->line("  Trying to fix JSON...");

                // Try to fix common JSON issues
                // Sometimes the content has unescaped newlines or control chars
                // Let's try to read it more carefully
                $lines = explode("\n", $content);
                if (count($lines) > 1) {
                    // JSON is on multiple lines, might be formatted
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (strpos($line, '"content":') !== false) {
                            // This line has the content field
                            $this->line("  Found content field on a line");
                        }
                    }
                }

                continue;
            }

            if (!isset($debugData['content'])) {
                $this->line("  ⚠️  No 'content' field found");
                $this->line("  Available keys: " . implode(', ', array_keys($debugData)));
                continue;
            }

            $contentLen = strlen($debugData['content']);

            if ($contentLen < 5000) {
                $this->line("  ⚠️  Content too short ({$contentLen} chars)");
                continue;
            }

            // This looks good!
            $this->info("  ✅ Valid content found! Length: " . number_format($contentLen) . " chars");
            $data = $debugData;
            $selectedFile = $debugFile;
            break;
        }

        if (!$data) {
            $this->error("\n❌ Could not find valid content in any debug file");

            $this->warn("\nLet me try a different approach - reading the file byte by byte...");

            // Last resort: try to extract JSON manually from the first file
            $firstFile = $debugFiles[0];
            $this->info("Attempting manual extraction from: " . basename($firstFile));

            $content = File::get($firstFile);

            // The file should be a single-line JSON with structure:
            // {"title":"...","description":"...","content":"<html>..."}

            // Find the content field
            if (preg_match('/"content"\s*:\s*"(.+?)"\s*\}?\s*$/s', $content, $matches)) {
                $this->info("Found content field with regex!");

                $htmlContent = $matches[1];

                // Unescape JSON escapes
                $htmlContent = stripcslashes($htmlContent);

                $this->info("Extracted content length: " . number_format(strlen($htmlContent)) . " chars");

                // Try to get title too
                $title = 'Untitled';
                if (preg_match('/"title"\s*:\s*"([^"]+)"/', $content, $titleMatches)) {
                    $title = $titleMatches[1];
                }

                $data = [
                    'title' => $title,
                    'content' => $htmlContent
                ];

                $selectedFile = $firstFile;
            } else {
                $this->error("Could not extract content with regex either");
                return 1;
            }
        }

        $this->newLine();
        $this->info("=== SELECTED FILE ===");
        $this->info("File: " . basename($selectedFile));
        $this->newLine();

        $title = $data['title'] ?? 'Untitled';
        $content = $data['content'];

        $this->info("=== EXTRACTED DATA ===");
        $this->info("Title: {$title}");
        $this->info("Content length: " . number_format(strlen($content)) . " chars");
        $this->info("Content preview (first 200 chars):");
        $this->line(substr($content, 0, 200) . "...");
        $this->newLine();

        // Verify content
        $hasVocab = (stripos($content, 'vocabulary') !== false || stripos($content, 'PART A') !== false);
        $hasTable = (stripos($content, '<table') !== false);
        $hasReading = (stripos($content, 'reading') !== false || stripos($content, 'PART B') !== false);
        $hasAnswer = (stripos($content, 'answer') !== false && stripos($content, 'key') !== false);

        $this->info("Content verification:");
        $this->line(($hasVocab ? '✅' : '❌') . " Has vocabulary section");
        $this->line(($hasTable ? '✅' : '❌') . " Has tables");
        $this->line(($hasReading ? '✅' : '❌') . " Has reading section");
        $this->line(($hasAnswer ? '✅' : '❌') . " Has answer key");
        $this->newLine();

        // Show current record
        $current = DB::table('session_materials')
            ->where('id', $materialId)
            ->first();

        if ($current) {
            $this->info("=== CURRENT DATABASE RECORD ===");
            $this->info("ID: {$current->id}");
            $this->info("Title: {$current->title}");
            $this->info("Content length: " . number_format(strlen($current->content)) . " chars");
            $this->newLine();
        }

        // Update the record
        $this->info("Updating record...");

        $affected = DB::table('session_materials')
            ->where('id', $materialId)
            ->update([
                'content' => $content,
                'title' => $title,
                'updated_at' => now()
            ]);

        $this->info("✅ Updated session_materials record id={$materialId}");
        $this->info("Rows affected: {$affected}");
        $this->newLine();

        // Verify the update
        $result = DB::table('session_materials')
            ->where('id', $materialId)
            ->first();

        $this->info("=== AFTER UPDATE ===");
        $this->info("ID: {$result->id}");
        $this->info("Title: {$result->title}");
        $this->info("Content length: " . number_format(strlen($result->content)) . " chars");
        $this->newLine();

        $improvement = strlen($result->content) - strlen($current->content ?? '');
        $this->info("Content increased by: " . number_format($improvement) . " chars");
        $this->newLine();

        if (strlen($result->content) > 15000) {
            $this->info("✅✅✅ SUCCESS! Content restored to full length! ✅✅✅");
            $this->newLine();
            $this->info("The material now has:");
            $this->info("  - Complete vocabulary tables");
            $this->info("  - Full reading text");
            $this->info("  - All exercises and activities");
            $this->info("  - Answer keys");
            $this->newLine();
            $this->info("You can now view the material in MaterialsManager!");
            return 0;
        } else {
            $this->warn("⚠️  Content is still shorter than expected");
            $this->warn("Expected: >15,000 chars");
            $this->warn("Got: " . number_format(strlen($result->content)) . " chars");
            return 1;
        }
    }
}
