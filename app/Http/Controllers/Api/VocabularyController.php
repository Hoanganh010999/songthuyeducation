<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VocabularyEntry;
use App\Models\VocabularyAudioRecording;
use App\Services\PronunciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VocabularyController extends Controller
{
    /**
     * Get user's vocabulary entries
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = VocabularyEntry::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');
        
        // Filter by search term
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('word', 'like', "%{$search}%")
                  ->orWhere('definition', 'like', "%{$search}%");
            });
        }
        
        // Filter by mastery level
        if ($request->has('mastery_level')) {
            $query->where('mastery_level', $request->mastery_level);
        }
        
        $entries = $query->paginate($request->per_page ?? 50);
        
        return response()->json([
            'success' => true,
            'data' => $entries
        ]);
    }

    /**
     * Store a new vocabulary entry
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'word' => 'required|string|max:255',
            'word_form' => 'nullable|string|max:50',
            'definition' => 'nullable|string',
            'synonym' => 'nullable|string',
            'antonym' => 'nullable|string',
            'example' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = $request->user();
        $branchId = $request->header('X-Branch-Id') ?? $user->branch_id ?? 0;

        // Auto-correct spelling (basic implementation - can be enhanced with API)
        $correctedWord = $this->correctSpelling($request->word);

        $entry = VocabularyEntry::create([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'word' => $correctedWord,
            'word_form' => $request->word_form,
            'definition' => $request->definition,
            'synonym' => $request->synonym,
            'antonym' => $request->antonym,
            'example' => $request->example
        ]);

        Log::info('[Vocabulary] Entry created', [
            'user_id' => $user->id,
            'word' => $entry->word,
            'entry_id' => $entry->id
        ]);

        return response()->json([
            'success' => true,
            'data' => $entry,
            'message' => 'Vocabulary entry created successfully'
        ]);
    }

    /**
     * Update an existing vocabulary entry
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $entry = VocabularyEntry::where('user_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'word' => 'sometimes|required|string|max:255',
            'word_form' => 'nullable|string|max:50',
            'definition' => 'nullable|string',
            'synonym' => 'nullable|string',
            'antonym' => 'nullable|string',
            'example' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->has('word')) {
            $entry->word = $this->correctSpelling($request->word);
        }
        
        $entry->fill($request->only([
            'word_form', 'definition', 'synonym', 'antonym', 'example'
        ]));
        
        $entry->save();

        return response()->json([
            'success' => true,
            'data' => $entry,
            'message' => 'Vocabulary entry updated successfully'
        ]);
    }

    /**
     * Delete a vocabulary entry
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $entry = VocabularyEntry::where('user_id', $user->id)->findOrFail($id);

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary entry deleted successfully'
        ]);
    }

    /**
     * Get random entries for flashcard review
     */
    public function getRandomForReview(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 10;

        // Prioritize words with lower mastery level
        $entries = VocabularyEntry::where('user_id', $user->id)
            ->orderBy('mastery_level', 'asc')
            ->orderBy('last_reviewed_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $entries
        ]);
    }

    /**
     * Record review result
     */
    public function recordReview(Request $request, $id)
    {
        $user = $request->user();
        
        $entry = VocabularyEntry::where('user_id', $user->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'correct' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $entry->incrementReview();
        $entry->updateMastery($request->correct);

        return response()->json([
            'success' => true,
            'data' => $entry,
            'message' => 'Review recorded successfully'
        ]);
    }

    /**
     * Upload pronunciation audio and check pronunciation
     */
    public function uploadPronunciationAudio(Request $request, $id)
    {
        $user = $request->user();
        
        $entry = VocabularyEntry::where('user_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|max:5120' // max 5MB - accept all audio types
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('audio');
            
            // Get extension, default to 'webm' if not available
            $extension = $file->getClientOriginalExtension() ?: 'webm';
            
            // Force webm extension for browser recordings
            if (in_array($extension, ['', 'bin', 'octet-stream'])) {
                $extension = 'webm';
            }
            
            // Ensure directory exists
            $userDir = 'vocabulary_audio/' . $user->id;
            Storage::disk('public')->makeDirectory($userDir);
            
            // Generate filename and store file
            $filename = Str::random(40) . '.' . $extension;
            $dbPath = $userDir . '/' . $filename;
            
            // Store file using put() method which is more reliable
            $fileContents = file_get_contents($file->getRealPath());
            $stored = Storage::disk('public')->put($dbPath, $fileContents);
            
            if (!$stored) {
                throw new \Exception('Failed to store audio file');
            }

            // Create audio recording record
            $recording = VocabularyAudioRecording::create([
                'user_id' => $user->id,
                'vocabulary_entry_id' => $entry->id,
                'file_path' => $dbPath,
                'duration_seconds' => 3,
                'check_status' => 'pending'
            ]);

            Log::info('[Vocabulary] Audio uploaded, starting pronunciation check', [
                'user_id' => $user->id,
                'entry_id' => $entry->id,
                'word' => $entry->word,
                'file_path' => $dbPath,
                'extension' => $extension,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType()
            ]);

            // Call pronunciation service to check pronunciation
            try {
                $recording->update(['check_status' => 'processing']);
                
                $pronunciationService = app(PronunciationService::class);
                
                // Get full file path from public disk
                $fullPath = Storage::disk('public')->path($dbPath);
                
                Log::info('[Vocabulary] Full path for pronunciation check', [
                    'db_path' => $dbPath,
                    'full_path' => $fullPath,
                    'exists' => file_exists($fullPath)
                ]);
                
                // Assess pronunciation
                $result = $pronunciationService->assessPronunciation(
                    $fullPath,
                    $entry->word,
                    'en-US'
                );

                // Extract phoneme errors from words for easy display
                $phonemeErrors = [];
                if (isset($result['words']) && is_array($result['words'])) {
                    foreach ($result['words'] as $word) {
                        if (isset($word['phonemes']) && is_array($word['phonemes'])) {
                            foreach ($word['phonemes'] as $phoneme) {
                                if (isset($phoneme['is_correct']) && !$phoneme['is_correct']) {
                                    $phonemeErrors[] = [
                                        'expected' => $phoneme['expected_phoneme'] ?? '',
                                        'actual' => $phoneme['phoneme'] ?? '',
                                        'score' => $phoneme['accuracy_score'] ?? 0,
                                        'description' => "Phoneme mismatch"
                                    ];
                                }
                            }
                        }
                    }
                }

                // Update recording with results
                $recording->update([
                    'check_status' => 'completed',
                    'overall_score' => $result['overall_score'] ?? 0,
                    'accuracy_score' => $result['accuracy_score'] ?? 0,
                    'fluency_score' => $result['fluency_score'] ?? 0,
                    'completeness_score' => $result['completeness_score'] ?? 0,
                    'transcribed_text' => $result['transcribed_text'] ?? null,
                    'phoneme_results' => $result['words'] ?? [], // Store full words data
                    'feedback' => $result['feedback'] ?? null,
                    'checked_at' => now()
                ]);

                Log::info('[Vocabulary] Pronunciation check completed', [
                    'recording_id' => $recording->id,
                    'overall_score' => $recording->overall_score,
                    'transcribed_text' => $recording->transcribed_text
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'recording_id' => $recording->id,
                        'file_url' => Storage::url($dbPath),
                        'pronunciation_check' => [
                            'status' => 'completed',
                            'overall_score' => $recording->overall_score,
                            'accuracy_score' => $recording->accuracy_score,
                            'fluency_score' => $recording->fluency_score,
                            'completeness_score' => $recording->completeness_score,
                            'transcribed_text' => $recording->transcribed_text,
                            'expected_text' => $entry->word,
                            'feedback' => $recording->feedback,
                            'phoneme_errors' => $phonemeErrors,
                            'total_errors' => count($phonemeErrors)
                        ]
                    ],
                    'message' => 'Audio uploaded and pronunciation checked successfully'
                ]);

            } catch (\Exception $pronError) {
                // Pronunciation check failed, but audio is still uploaded
                Log::error('[Vocabulary] Pronunciation check failed', [
                    'recording_id' => $recording->id,
                    'error' => $pronError->getMessage()
                ]);

                $recording->update([
                    'check_status' => 'failed',
                    'feedback' => 'Pronunciation check failed: ' . $pronError->getMessage()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'recording_id' => $recording->id,
                        'file_url' => Storage::url($dbPath),
                        'pronunciation_check' => [
                            'status' => 'failed',
                            'error' => $pronError->getMessage()
                        ]
                    ],
                    'message' => 'Audio uploaded but pronunciation check failed'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[Vocabulary] Audio upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload audio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Basic spelling correction (can be enhanced with external API)
     */
    private function correctSpelling($word)
    {
        // Basic cleanup: trim, lowercase, remove extra spaces
        $corrected = trim(strtolower($word));
        $corrected = preg_replace('/\s+/', ' ', $corrected);
        
        // TODO: Integrate with spelling API like LanguageTool or similar
        // For now, just return cleaned word
        
        return $corrected;
    }

    /**
     * Get vocabulary statistics
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();

        $total = VocabularyEntry::where('user_id', $user->id)->count();
        $reviewed = VocabularyEntry::where('user_id', $user->id)
            ->where('review_count', '>', 0)
            ->count();
        $mastered = VocabularyEntry::where('user_id', $user->id)
            ->where('mastery_level', '>=', 4)
            ->count();

        $masteryDistribution = VocabularyEntry::where('user_id', $user->id)
            ->selectRaw('mastery_level, COUNT(*) as count')
            ->groupBy('mastery_level')
            ->orderBy('mastery_level')
            ->get()
            ->pluck('count', 'mastery_level');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'reviewed' => $reviewed,
                'mastered' => $mastered,
                'mastery_distribution' => $masteryDistribution
            ]
        ]);
    }
}

