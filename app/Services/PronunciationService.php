<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class PronunciationService
{
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl = env('PRONUNCIATION_API_URL', 'http://localhost:8001');
        $this->timeout = 60; // 60 seconds for audio processing
    }

    /**
     * Assess pronunciation of an audio file
     *
     * @param string|UploadedFile $audioFile Path to audio file or UploadedFile
     * @param string $referenceText The correct text
     * @param string $language Language code (default: en-US)
     * @return array Assessment results
     * @throws \Exception
     */
    public function assessPronunciation($audioFile, string $referenceText, string $language = 'en-US'): array
    {
        try {
            Log::info('[PronunciationService] Starting assessment', [
                'reference_text' => $referenceText,
                'language' => $language,
                'audio_file' => is_string($audioFile) ? $audioFile : $audioFile->getClientOriginalName()
            ]);

            // Get file path and name
            if ($audioFile instanceof UploadedFile) {
                $filePath = $audioFile->getRealPath();
                $fileName = $audioFile->getClientOriginalName();
                $mimeType = $audioFile->getMimeType();
            } else {
                $filePath = $audioFile;
                $fileName = basename($audioFile);
                
                // Check file exists before getting mime type
                if (!file_exists($filePath)) {
                    throw new \Exception("Audio file not found: {$filePath}");
                }
                
                $mimeType = mime_content_type($audioFile);
            }

            // Call pronunciation API
            $response = Http::timeout($this->timeout)
                ->attach('audio_file', file_get_contents($filePath), $fileName)
                ->post("{$this->apiUrl}/assess-pronunciation", [
                    'reference_text' => $referenceText,
                    'language' => $language
                ]);

            if (!$response->successful()) {
                Log::error('[PronunciationService] API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception("Pronunciation API error: {$response->status()} - {$response->body()}");
            }

            $result = $response->json();

            Log::info('[PronunciationService] Assessment completed', [
                'overall_score' => $result['overall_score'] ?? null,
                'transcribed_text' => $result['transcribed_text'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('[PronunciationService] Assessment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Transcribe audio file
     *
     * @param string|UploadedFile $audioFile Path to audio file or UploadedFile
     * @param string $language Language code
     * @return array Transcription result
     * @throws \Exception
     */
    public function transcribe($audioFile, string $language = 'en'): array
    {
        try {
            Log::info('[PronunciationService] Starting transcription', [
                'language' => $language
            ]);

            // Get file path and name
            if ($audioFile instanceof UploadedFile) {
                $filePath = $audioFile->getRealPath();
                $fileName = $audioFile->getClientOriginalName();
            } else {
                $filePath = $audioFile;
                $fileName = basename($audioFile);
            }

            if (!file_exists($filePath)) {
                throw new \Exception("Audio file not found: {$filePath}");
            }

            // Call pronunciation API
            $response = Http::timeout($this->timeout)
                ->attach('audio_file', file_get_contents($filePath), $fileName)
                ->post("{$this->apiUrl}/transcribe", [
                    'language' => $language
                ]);

            if (!$response->successful()) {
                throw new \Exception("Transcription API error: {$response->status()}");
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('[PronunciationService] Transcription failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/health");
            return $response->successful() && $response->json('status') === 'healthy';
        } catch (\Exception $e) {
            Log::warning('[PronunciationService] Health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Format phoneme errors for display
     *
     * @param array $phonemeResults Raw phoneme results from API
     * @return array Formatted errors
     */
    public function formatPhonemeErrors(array $phonemeResults): array
    {
        $errors = [];
        
        foreach ($phonemeResults as $phoneme) {
            if (isset($phoneme['error_type']) && $phoneme['error_type'] !== 'correct') {
                $errors[] = [
                    'expected' => $phoneme['expected'] ?? '',
                    'actual' => $phoneme['actual'] ?? '',
                    'error_type' => $phoneme['error_type'],
                    'position' => $phoneme['position'] ?? 0,
                    'description' => $this->getErrorDescription($phoneme)
                ];
            }
        }
        
        return $errors;
    }

    /**
     * Get human-readable error description
     *
     * @param array $phoneme Phoneme data
     * @return string Error description in Vietnamese
     */
    private function getErrorDescription(array $phoneme): string
    {
        $expected = $phoneme['expected'] ?? '';
        $actual = $phoneme['actual'] ?? '';
        $type = $phoneme['error_type'] ?? '';

        switch ($type) {
            case 'substitution':
                return "Phát âm '{$actual}' thay vì '{$expected}'";
            case 'insertion':
                return "Thêm âm '{$actual}' không cần thiết";
            case 'deletion':
                return "Thiếu âm '{$expected}'";
            case 'mispronunciation':
                return "Phát âm sai âm '{$expected}'";
            default:
                return "Lỗi phát âm";
        }
    }
}

