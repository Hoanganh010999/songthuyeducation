<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Strip JSON format specifications from existing AI prompts in database.
     * JSON formats are now managed as backend constants.
     */
    public function up(): void
    {
        // Get all AI prompts
        $prompts = DB::table('ai_prompts')->get();

        foreach ($prompts as $prompt) {
            $updatedPrompt = $this->stripJsonFormat($prompt->prompt, $prompt->module);

            // Only update if the prompt was modified
            if ($updatedPrompt !== $prompt->prompt) {
                DB::table('ai_prompts')
                    ->where('id', $prompt->id)
                    ->update(['prompt' => $updatedPrompt]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is non-reversible as we're cleaning up data
        // The old format with JSON specs is no longer needed
    }

    /**
     * Strip JSON format specifications from prompt text
     */
    private function stripJsonFormat(string $prompt, string $module): string
    {
        // Common patterns to remove
        $patterns = [
            // Remove "CRITICAL: Respond ONLY..." sections
            '/CRITICAL:\s*Respond ONLY.*?Output ONLY the raw JSON\./is',

            // Remove "REQUIRED JSON FORMAT" or "REQUIRED JSON OUTPUT FORMAT" sections
            '/REQUIRED JSON (?:OUTPUT )?FORMAT.*?\{.*?\}/is',

            // Remove standalone RULES sections at the end
            '/\n\nRULES:.*$/is',

            // Remove "Return your response in this EXACT JSON format:" sections
            '/Return your response in this EXACT JSON format:.*?\}/is',

            // Remove "Required JSON format:" sections
            '/Required JSON format:.*?\}/is',

            // Remove "Band scores must be between..." rules at the end
            '/Band scores must be between.*$/is',
            '/Overall band_score is the average.*$/is',

            // Remove "Output ONLY the JSON..." instructions
            '/- Output ONLY the JSON.*$/im',
            '/Output ONLY the JSON object.*$/im',

            // Remove markdown code block indicators
            '/```json/i',
            '/```/i',

            // Remove NO markdown formatting instructions
            '/NO markdown formatting.*$/im',

            // Clean up multiple consecutive blank lines
            '/\n{3,}/',
        ];

        $replacements = [
            '', // Remove CRITICAL sections
            '', // Remove JSON format examples
            '', // Remove RULES sections
            '', // Remove Return your response sections
            '', // Remove Required JSON format sections
            '', // Remove Band scores rules
            '', // Remove Overall band_score rules
            '', // Remove Output ONLY instructions
            '', // Remove Output ONLY instructions (variant)
            '', // Remove ```json
            '', // Remove ```
            '', // Remove NO markdown instructions
            "\n\n", // Replace multiple blank lines with double newline
        ];

        $cleaned = preg_replace($patterns, $replacements, $prompt);

        // Trim trailing whitespace
        $cleaned = rtrim($cleaned);

        return $cleaned;
    }
};
