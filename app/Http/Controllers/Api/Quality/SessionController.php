<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Controller;
use App\Models\LessonPlanSession;
use App\Models\LessonPlanBlock;
use App\Models\LessonPlanStage;
use App\Models\LessonPlanProcedure;
use App\Models\AiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    /**
     * Get session with lesson plan blocks
     */
    public function show($id)
    {
        try {
            $session = LessonPlanSession::with([
                'blocks' => function ($query) {
                    $query->orderBy('block_number');
                },
                'blocks.stages' => function ($query) {
                    $query->orderBy('stage_number');
                },
                'blocks.stages.procedure'
            ])->findOrFail($id);

            // Format blocks for frontend
            $formattedBlocks = $session->blocks->map(function ($block) {
                return [
                    'id' => $block->id,
                    'block_number' => $block->block_number,
                    'block_title' => $block->block_title,
                    'block_description' => $block->block_description,
                    'expanded' => false, // UI state
                    'stages' => $block->stages->map(function ($stage) {
                        $procedureData = null;
                        if ($stage->procedure) {
                            $procedureData = [
                                'id' => $stage->procedure->id,
                                'stage_content' => $stage->procedure->stage_content,
                                'instructions' => $stage->procedure->instructions,
                                'icqs' => $stage->procedure->icqs,
                                'instruction_timing' => $stage->procedure->instruction_timing,
                                'instruction_interaction' => $stage->procedure->instruction_interaction,
                                'task_completion' => $stage->procedure->task_completion,
                                'monitoring_points' => $stage->procedure->monitoring_points,
                                'task_timing' => $stage->procedure->task_timing,
                                'task_interaction' => $stage->procedure->task_interaction,
                                'feedback' => $stage->procedure->feedback,
                                'feedback_timing' => $stage->procedure->feedback_timing,
                                'feedback_interaction' => $stage->procedure->feedback_interaction,
                                'learner_problems' => $stage->procedure->learner_problems,
                                'learner_problems_text' => json_encode($stage->procedure->learner_problems ?? []),
                                'task_problems' => $stage->procedure->task_problems,
                                'task_problems_text' => json_encode($stage->procedure->task_problems ?? []),
                            ];
                        }

                        return [
                            'id' => $stage->id,
                            'stage_number' => $stage->stage_number,
                            'stage_name' => $stage->stage_name,
                            'stage_aim' => $stage->stage_aim,
                            'total_timing' => $stage->total_timing, // Frontend expects total_timing
                            'interaction_pattern' => $stage->procedure?->instruction_interaction ?? 'T-Ss', // Default from instruction interaction
                            'expanded' => false, // UI state
                            'procedure' => $procedureData
                        ];
                    })->toArray()
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'session_number' => $session->session_number,
                    'session_name' => $session->session_name,
                    'lesson_title' => $session->lesson_title,
                    // TECP Lesson Plan fields
                    'teacher_name' => $session->teacher_name,
                    'lesson_focus' => $session->lesson_focus,
                    'level' => $session->level,
                    'lesson_date' => $session->lesson_date,
                    'tp_number' => $session->tp_number,
                    'duration_minutes' => $session->duration_minutes,
                    // Lesson Aims
                    'communicative_outcome' => $session->communicative_outcome,
                    'linguistic_aim' => $session->linguistic_aim,
                    'productive_subskills_focus' => $session->productive_subskills_focus,
                    'receptive_subskills_focus' => $session->receptive_subskills_focus,
                    'framework_shape' => $session->framework_shape,
                    // Personal Aims
                    'teaching_aspects_to_improve' => $session->teaching_aspects_to_improve,
                    'improvement_methods' => $session->improvement_methods,
                    // Language Analysis Sheet
                    'language_area' => $session->language_area,
                    'examples_of_language' => $session->examples_of_language,
                    'context' => $session->context,
                    'concept_checking_methods' => $session->concept_checking_methods,
                    'concept_checking_in_lesson' => $session->concept_checking_in_lesson,
                    'blocks' => $formattedBlocks
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save lesson plan for session
     */
    public function saveLessonPlan(Request $request, $id)
    {
        try {
            $session = LessonPlanSession::findOrFail($id);

            DB::beginTransaction();

            // Get existing block IDs to track what needs to be deleted
            $existingBlockIds = $session->blocks->pluck('id')->toArray();
            $submittedBlockIds = [];

            // Process each block
            foreach ($request->blocks as $blockData) {
                if (isset($blockData['id']) && in_array($blockData['id'], $existingBlockIds)) {
                    // Update existing block
                    $block = LessonPlanBlock::find($blockData['id']);
                    $submittedBlockIds[] = $blockData['id'];
                } else {
                    // Create new block
                    $block = new LessonPlanBlock();
                    $block->lesson_plan_session_id = $session->id;
                }

                $block->block_number = $blockData['block_number'];
                $block->block_title = $blockData['block_title'] ?? '';
                $block->block_description = $blockData['block_description'] ?? '';
                $block->save();

                if (!isset($blockData['id'])) {
                    $submittedBlockIds[] = $block->id;
                }

                // Get existing stage IDs for this block
                $existingStageIds = $block->stages->pluck('id')->toArray();
                $submittedStageIds = [];

                // Process each stage
                foreach ($blockData['stages'] ?? [] as $stageData) {
                    if (isset($stageData['id']) && in_array($stageData['id'], $existingStageIds)) {
                        // Update existing stage
                        $stage = LessonPlanStage::find($stageData['id']);
                        $submittedStageIds[] = $stageData['id'];
                    } else {
                        // Create new stage
                        $stage = new LessonPlanStage();
                        $stage->lesson_plan_block_id = $block->id;
                    }

                    $stage->stage_number = $stageData['stage_number'];
                    $stage->stage_name = $stageData['stage_name'] ?? '';
                    $stage->stage_aim = $stageData['stage_aim'] ?? '';
                    $stage->total_timing = $stageData['timing'] ?? 0; // Map frontend 'timing' to DB 'total_timing'
                    $stage->save();

                    if (!isset($stageData['id'])) {
                        $submittedStageIds[] = $stage->id;
                    }

                    // Process procedure
                    if (isset($stageData['procedure'])) {
                        $procedureData = $stageData['procedure'];

                        $procedure = $stage->procedure ?? new LessonPlanProcedure();
                        $procedure->lesson_plan_stage_id = $stage->id;

                        // Helper function to extract first interaction pattern if multiple provided
                        $extractFirstInteraction = function($interaction, $default) {
                            if (!$interaction) return $default;
                            // If contains comma, take only the first pattern
                            $patterns = array_map('trim', explode(',', $interaction));
                            return $patterns[0] ?? $default;
                        };

                        $procedure->instructions = $procedureData['instructions'] ?? '';
                        $procedure->icqs = $procedureData['icqs'] ?? '';
                        $procedure->instruction_timing = $procedureData['instruction_timing'] ?? 0;
                        $procedure->instruction_interaction = $extractFirstInteraction($procedureData['instruction_interaction'] ?? null, 'T-Ss');

                        $procedure->task_completion = $procedureData['task_completion'] ?? '';
                        $procedure->monitoring_points = $procedureData['monitoring_points'] ?? '';
                        $procedure->task_timing = $procedureData['task_timing'] ?? 0;
                        $procedure->task_interaction = $extractFirstInteraction($procedureData['task_interaction'] ?? null, 'SS-Ss');

                        $procedure->feedback = $procedureData['feedback'] ?? '';
                        $procedure->feedback_timing = $procedureData['feedback_timing'] ?? 0;
                        $procedure->feedback_interaction = $extractFirstInteraction($procedureData['feedback_interaction'] ?? null, 'Ss-T');

                        // Parse JSON problems if they're strings
                        $learnerProblems = $procedureData['learner_problems'] ?? [];
                        if (is_string($learnerProblems)) {
                            $learnerProblems = json_decode($learnerProblems, true) ?? [];
                        }
                        $procedure->learner_problems = $learnerProblems;

                        $taskProblems = $procedureData['task_problems'] ?? [];
                        if (is_string($taskProblems)) {
                            $taskProblems = json_decode($taskProblems, true) ?? [];
                        }
                        $procedure->task_problems = $taskProblems;

                        $procedure->save();
                    }
                }

                // Delete stages that were removed
                $stagesToDelete = array_diff($existingStageIds, $submittedStageIds);

                Log::info('Stage Deletion Check', [
                    'block_id' => $block->id,
                    'existing_stage_ids' => $existingStageIds,
                    'submitted_stage_ids' => $submittedStageIds,
                    'stages_to_delete' => $stagesToDelete
                ]);

                if (!empty($stagesToDelete)) {
                    $deletedCount = LessonPlanStage::whereIn('id', $stagesToDelete)->delete();
                    Log::info('Stages Deleted', [
                        'block_id' => $block->id,
                        'stage_ids' => $stagesToDelete,
                        'deleted_count' => $deletedCount
                    ]);
                }
            }

            // Delete blocks that were removed
            $blocksToDelete = array_diff($existingBlockIds, $submittedBlockIds);

            Log::info('Block Deletion Check', [
                'session_id' => $session->id,
                'existing_block_ids' => $existingBlockIds,
                'submitted_block_ids' => $submittedBlockIds,
                'blocks_to_delete' => $blocksToDelete
            ]);

            if (!empty($blocksToDelete)) {
                $deletedCount = LessonPlanBlock::whereIn('id', $blocksToDelete)->delete();
                Log::info('Blocks Deleted', [
                    'session_id' => $session->id,
                    'block_ids' => $blocksToDelete,
                    'deleted_count' => $deletedCount
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson plan saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving lesson plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save lesson plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all materials for a session
     */
    public function getMaterials($id)
    {
        try {
            $session = LessonPlanSession::findOrFail($id);

            $materials = DB::table('session_materials')
                ->where('session_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading materials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single material
     */
    public function getMaterial($sessionId, $materialId)
    {
        try {
            $material = DB::table('session_materials')
                ->where('session_id', $sessionId)
                ->where('id', $materialId)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $material
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new material
     */
    public function saveMaterials(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = LessonPlanSession::findOrFail($id);

            $materialId = DB::table('session_materials')->insertGetId([
                'session_id' => $id,
                'title' => $request->title,
                'description' => $request->description,
                'content' => $request->content,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material created successfully',
                'data' => ['id' => $materialId]
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update material
     */
    public function updateMaterial(Request $request, $sessionId, $materialId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $material = DB::table('session_materials')
                ->where('session_id', $sessionId)
                ->where('id', $materialId)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found'
                ], 404);
            }

            DB::table('session_materials')
                ->where('id', $materialId)
                ->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'content' => $request->content,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Material updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete material
     */
    public function deleteMaterial($sessionId, $materialId)
    {
        try {
            $material = DB::table('session_materials')
                ->where('session_id', $sessionId)
                ->where('id', $materialId)
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found'
                ], 404);
            }

            DB::table('session_materials')
                ->where('id', $materialId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit material content with AI
     */
    public function editMaterialWithAI(Request $request, $sessionId)
    {
        try {
            // Increase PHP execution time for AI API calls (can take 1-2 minutes)
            set_time_limit(300); // 5 minutes

            $validated = $request->validate([
                'content' => 'required|string',
                'prompt' => 'required|string',
                'model' => 'nullable|string'
            ]);

            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            // Get OpenAI settings specifically (like material generation does)
            $aiSettings = AiSetting::getSettingsByProvider($branchId, 'quality_management', 'openai');

            if (!$aiSettings || !$aiSettings->api_key) {
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI is not configured for material editing. Please set up OpenAI API key in Quality Settings first.'
                ], 400);
            }

            $provider = 'openai';
            $model = $aiSettings->model ?: 'gpt-4'; // Use configured model or default to gpt-4
            $apiKey = $aiSettings->api_key;

            // Build the AI prompts
            $systemPrompt = "You are an expert educational content editor. Your task is to edit and improve educational materials based on the user's instructions. Maintain the HTML formatting of the content.";

            $userPrompt = "Please edit the following educational material content based on these instructions:\n\n";
            $userPrompt .= "Instructions: " . $validated['prompt'] . "\n\n";
            $userPrompt .= "Content to edit:\n" . $validated['content'] . "\n\n";
            $userPrompt .= "Return ONLY the edited content with proper HTML formatting. Do not include any explanations or additional text.";

            // Call AI (GPT-5.1 uses Responses API)
            $content = $this->callAIForEditing($provider, $apiKey, $model, $systemPrompt, $userPrompt);

            if (empty($content)) {
                throw new \Exception('Empty AI response');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'edited_content' => $content
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error editing material with AI: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to edit content with AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit selected text with AI
     */
    public function editTextWithAI(Request $request, $sessionId)
    {
        try {
            // Increase PHP execution time for AI API calls (can take 1-2 minutes)
            set_time_limit(300); // 5 minutes

            $validated = $request->validate([
                'text' => 'required|string',
                'prompt' => 'required|string',
                'model' => 'nullable|string'
            ]);

            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            // Get OpenAI settings specifically (like material generation does)
            $aiSettings = AiSetting::getSettingsByProvider($branchId, 'quality_management', 'openai');

            if (!$aiSettings || !$aiSettings->api_key) {
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI is not configured for material editing. Please set up OpenAI API key in Quality Settings first.'
                ], 400);
            }

            $provider = 'openai';
            $model = $aiSettings->model ?: 'gpt-4'; // Use configured model or default to gpt-4
            $apiKey = $aiSettings->api_key;

            // Build the AI prompts
            $systemPrompt = "You are an expert text editor. Your task is to edit text based on the user's instructions while preserving the original meaning and context.";

            $userPrompt = "Please edit the following text based on these instructions:\n\n";
            $userPrompt .= "Instructions: " . $validated['prompt'] . "\n\n";
            $userPrompt .= "Text to edit:\n" . $validated['text'] . "\n\n";
            $userPrompt .= "Return ONLY the edited text. Do not include any explanations, quotation marks, or additional text.";

            // Call AI (GPT-5.1 uses Responses API)
            $content = $this->callAIForEditing($provider, $apiKey, $model, $systemPrompt, $userPrompt);

            if (empty($content)) {
                throw new \Exception('Empty AI response');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'edited_text' => trim($content)
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error editing text with AI: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to edit text with AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call AI for content editing (GPT-5.1 uses Responses API)
     */
    private function callAIForEditing(
        string $provider,
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt
    ): string {
        if ($provider === 'openai') {
            // Check if it's GPT-5 family (uses Responses API)
            $isGPT5 = str_starts_with($model, 'gpt-5');

            if ($isGPT5) {
                return $this->callOpenAIResponsesForEditing($apiKey, $model, $systemPrompt, $userPrompt);
            }

            return $this->callOpenAIChatForEditing($apiKey, $model, $systemPrompt, $userPrompt);
        }

        throw new \Exception('Only OpenAI provider is currently supported for editing');
    }

    /**
     * Call OpenAI Responses API (GPT-5.1)
     */
    private function callOpenAIResponsesForEditing(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt
    ): string {
        // Combine system prompt and user prompt for GPT-5.1 Responses API
        $fullPrompt = "System instructions:\n{$systemPrompt}\n\nUser request:\n{$userPrompt}";

        Log::info('GPT-5 Edit Request', ['model' => $model, 'prompt_length' => strlen($fullPrompt)]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(600)->post('https://api.openai.com/v1/responses', [
            'model' => $model,
            'input' => $fullPrompt,
            'reasoning' => ['effort' => 'low']
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            Log::error('GPT-5 API Error', ['status' => $response->status(), 'error' => $errorData]);
            throw new \Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? $response->status()));
        }

        $data = $response->json();

        // Extract content from Responses API output
        $content = '';

        // Try output_text first
        if (isset($data['output_text']) && is_string($data['output_text'])) {
            $content = $data['output_text'];
        }
        // Try text field if it's a string
        elseif (isset($data['text']) && is_string($data['text'])) {
            $content = $data['text'];
        }
        // Try output array
        elseif (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (isset($item['type']) && $item['type'] === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $c) {
                        if (($c['type'] ?? '') === 'output_text' || ($c['type'] ?? '') === 'text') {
                            $extracted = $c['text'] ?? $c['output_text'] ?? '';
                            if (is_string($extracted)) {
                                $content = $extracted;
                                break 2;
                            }
                        }
                    }
                } elseif (isset($item['text']) && is_string($item['text'])) {
                    $content = $item['text'];
                    break;
                }
            }
        }

        Log::info('GPT-5 Content Extracted', [
            'content_length' => strlen($content),
            'is_empty' => empty($content)
        ]);

        return $content;
    }

    /**
     * Call OpenAI Chat Completions API (GPT-4, GPT-3.5)
     */
    private function callOpenAIChatForEditing(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt
    ): string {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'max_tokens' => 4000,
            'temperature' => 0.7
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API Error: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }
}
