<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\Examination\AIPrompt;
use App\Models\LessonPlanSession;
use App\Models\LessonPlanBlock;
use App\Models\LessonPlanStage;
use App\Models\LessonPlanProcedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QualityAIController extends Controller
{
    /**
     * Get AI settings for Quality Management module
     */
    public function getAiSettings(Request $request): JsonResponse
    {
        try {
            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            // Get module from request, default to quality_management
            $requestedModule = $request->input('module') ?? 'quality_management';
            
            Log::info('[QualityAI] Getting AI settings', [
                'branch_id' => $branchId,
                'requested_module' => $requestedModule
            ]);

            // Get all AI settings for requested module
            $settings = AiSetting::getAllSettingsForModule($branchId, $requestedModule);

            // ✅ FALLBACK: If no settings found for requested module, try examination_grading
            if (empty($settings) && $requestedModule !== 'examination_grading') {
                Log::info('[QualityAI] No settings found for module, trying examination_grading fallback', [
                    'requested_module' => $requestedModule
                ]);
                $settings = AiSetting::getAllSettingsForModule($branchId, 'examination_grading');
                $actualModule = 'examination_grading';
            } else {
                $actualModule = $requestedModule;
            }

            $response = [];
            foreach ($settings as $setting) {
                $response[$setting->provider] = [
                    'provider' => $setting->provider,
                    'model' => $setting->model,
                    'has_api_key' => !empty($setting->api_key_encrypted),
                    'masked_api_key' => $setting->api_key ? 'sk-...' . substr($setting->api_key, -4) : null,
                    'is_active' => $setting->is_active,
                    'settings' => $setting->settings,
                ];
            }

            Log::info('[QualityAI] Returning AI settings', [
                'module_used' => $actualModule,
                'providers' => array_keys($response)
            ]);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting Quality AI settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get AI settings'
            ], 500);
        }
    }

    /**
     * Save AI settings for Quality Management module
     */
    public function saveAiSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|in:openai,anthropic,azure',
                'model' => 'nullable|string',
                'api_key' => 'required|string|min:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            Log::info('Saving Quality AI Settings', [
                'branch_id' => $branchId,
                'provider' => $request->provider,
                'model' => $request->model,
                'has_settings' => isset($request->settings)
            ]);

            $setting = AiSetting::saveSettings($branchId, 'quality_management', [
                'provider' => $request->provider,
                'model' => $request->model,
                'api_key' => $request->api_key,
                'settings' => $request->settings ?? [],
                'is_active' => true,
            ]);

            Log::info('Quality AI Settings Saved', [
                'setting_id' => $setting->id,
                'branch_id' => $setting->branch_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI settings saved successfully',
                'setting' => [
                    'provider' => $setting->provider,
                    'model' => $setting->model,
                    'has_api_key' => true,
                    'masked_api_key' => 'sk-...' . substr($setting->api_key, -4),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving Quality AI settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save AI settings'
            ], 500);
        }
    }

    /**
     * Get all AI prompts for current user
     */
    public function getPrompts(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get specific module or all modules
            $module = $request->input('module');

            Log::info('Getting AI Prompts', [
                'user_id' => $userId,
                'module' => $module
            ]);

            $query = AIPrompt::where('created_by', $userId);

            if ($module) {
                $query->where('module', $module);
            }

            $prompts = $query->get();

            Log::info('AI Prompts Retrieved', [
                'count' => $prompts->count(),
                'modules' => $prompts->pluck('module')->toArray()
            ]);

            // Transform prompts to include identifier for frontend compatibility
            $transformedPrompts = $prompts->map(function ($prompt) {
                // Convert module name to identifier format
                // e.g., "prompt_lesson_plan_e" -> "lesson_shape_E"
                $identifier = $prompt->module;
                if (preg_match('/prompt_lesson_plan_([a-h])/i', $prompt->module, $matches)) {
                    $identifier = 'lesson_shape_' . strtoupper($matches[1]);
                }

                return [
                    'id' => $prompt->id,
                    'module' => $prompt->module,
                    'identifier' => $identifier,
                    'prompt' => $prompt->prompt,
                    'created_at' => $prompt->created_at,
                    'updated_at' => $prompt->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedPrompts
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting AI prompts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get AI prompts'
            ], 500);
        }
    }

    /**
     * Save or update AI prompt
     */
    public function savePrompt(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'module' => 'required|string',
                'prompt' => 'required|string|min:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $userId = Auth::id();
            // Get branch_id from request first, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            Log::info('Saving AI Prompt', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'module' => $request->module,
                'prompt_length' => strlen($request->prompt)
            ]);

            // Update or create prompt (now including branch_id in unique constraint)
            $prompt = AIPrompt::updateOrCreate(
                [
                    'module' => $request->module,
                    'branch_id' => $branchId,
                    'created_by' => $userId,
                ],
                [
                    'prompt' => $request->prompt,
                ]
            );

            Log::info('AI Prompt Saved', [
                'prompt_id' => $prompt->id,
                'branch_id' => $branchId,
                'was_recently_created' => $prompt->wasRecentlyCreated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prompt saved successfully',
                'prompt' => $prompt
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving AI prompt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save AI prompt'
            ], 500);
        }
    }

    /**
     * Generate lesson plan using AI
     */
    public function generateLessonPlan(Request $request): JsonResponse
    {
        try {
            // Increase PHP execution time for AI generation (can take 2-3 minutes)
            set_time_limit(300); // 5 minutes

            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:lesson_plan_sessions,id',
                'lesson_shape' => 'required|in:A,B,C,D,E,F,G,H',
                'student_age' => 'nullable|integer|min:1|max:100',
                'number_of_students' => 'nullable|integer|min:1|max:1000',
                'additional_context' => 'nullable|string',
                'provider' => 'nullable|string|in:openai,anthropic',
                'model' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Get session data to use in prompt
            $session = LessonPlanSession::findOrFail($request->session_id);

            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;
            $userId = Auth::id();

            // Always get AI settings (for API key)
            $aiSettings = AiSetting::getSettings($branchId, 'quality_management');

            if (!$aiSettings || !$aiSettings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI is not configured. Please set up AI settings first.'
                ], 400);
            }

            // Use custom provider/model if provided, otherwise from settings
            $provider = $request->input('provider') ?: $aiSettings->provider;
            $model = $request->input('model') ?: $aiSettings->model;
            $apiKey = $aiSettings->api_key; // Always from settings

            // Get custom prompt or use default
            $promptModule = 'prompt_lesson_plan_' . strtolower($request->lesson_shape);
            $systemPrompt = $this->loadPromptWithFormat($promptModule, $request->lesson_shape, $branchId);

            // Merge session data with request data for prompt building
            $promptData = array_merge([
                'lesson_title' => $session->lesson_title,
                'teacher_name' => $session->teacher_name,
                'level' => $session->level,
                'duration' => $session->duration_minutes ?? 50,
                'lesson_focus' => $session->lesson_focus,
                'communicative_outcome' => $session->communicative_outcome,
                'linguistic_aim' => $session->linguistic_aim,
                'productive_subskills_focus' => $session->productive_subskills_focus,
                'receptive_subskills_focus' => $session->receptive_subskills_focus,
            ], $request->all());

            // Build user prompt with context
            $userPrompt = $this->buildLessonPlanPrompt($promptData);

            // Log complete prompt details before calling AI
            Log::info('=== AI LESSON PLAN GENERATION - PROMPT DETAILS ===', [
                'session_id' => $request->session_id,
                'lesson_shape' => $request->lesson_shape,
                'lesson_title' => $session->lesson_title,
                'provider' => $provider,
                'model' => $model,
                'custom_provider_used' => $request->has('provider'),
                'custom_model_used' => $request->has('model'),
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
            ]);

            Log::info('SYSTEM PROMPT (Custom or Default + JSON Template)', [
                'content' => $systemPrompt
            ]);

            Log::info('USER PROMPT (Session Data + Requirements)', [
                'content' => $userPrompt
            ]);

            Log::info('=== END PROMPT DETAILS ===');

            // Call AI API with higher max tokens for complex lesson plans
            $response = $this->callAI(
                $provider,
                $apiKey,
                $model,
                $systemPrompt,
                $userPrompt,
                16000 // High limit for complex TECP lesson plans - stay under 15000
            );

            Log::info('AI Response Received', [
                'response_length' => strlen($response),
                'starts_with' => substr($response, 0, 50),
                'ends_with' => substr($response, -50)
            ]);

            // Debug: Save full response to file for inspection
            $debugFile = storage_path('logs/ai_response_debug_' . time() . '.json');
            file_put_contents($debugFile, $response);
            Log::info('Full response saved to: ' . $debugFile);

            // Parse JSON response - strip markdown code blocks if present
            $cleanedResponse = $response;

            // Remove markdown code blocks using simple string operations
            if (str_starts_with($cleanedResponse, '```json')) {
                $cleanedResponse = substr($cleanedResponse, 7);
            } elseif (str_starts_with($cleanedResponse, '```')) {
                $cleanedResponse = substr($cleanedResponse, 3);
            }
            $cleanedResponse = trim($cleanedResponse);
            if (str_ends_with($cleanedResponse, '```')) {
                $cleanedResponse = substr($cleanedResponse, 0, -3);
            }
            $cleanedResponse = trim($cleanedResponse);
            if (false && preg_match('/```(?:json)?\s*(.*?)\s*```/s', $response, $matches)) {
                $cleanedResponse = trim($matches[1]);
                Log::info('Stripped markdown code blocks from response', [
                    'cleaned_length' => strlen($cleanedResponse)
                ]);
            }

            // Also try to extract JSON if it's embedded in other text
            if (!str_starts_with(trim($cleanedResponse), '{')) {
                // Try to find JSON object
                if (preg_match('/\{.*\}/s', $cleanedResponse, $matches)) {
                    $cleanedResponse = $matches[0];
                    Log::info('Extracted JSON from text response', [
                        'extracted_length' => strlen($cleanedResponse)
                    ]);
                }
            }

            // Check if JSON looks complete
            $trimmed = trim($cleanedResponse);
            $looksComplete = str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}');

            Log::info('JSON Validation Check', [
                'starts_with_brace' => str_starts_with($trimmed, '{'),
                'ends_with_brace' => str_ends_with($trimmed, '}'),
                'looks_complete' => $looksComplete,
                'last_100_chars' => substr($trimmed, -100)
            ]);

            if (!$looksComplete) {
                Log::warning('JSON appears incomplete - may be truncated by max_tokens limit');
            }

            $lessonPlanData = json_decode($cleanedResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error', [
                    'error' => json_last_error_msg(),
                    'response_length' => strlen($response),
                    'cleaned_length' => strlen($cleanedResponse),
                    'looks_complete' => $looksComplete,
                    'original_first_500' => substr($response, 0, 500),
                    'original_last_500' => substr($response, -500),
                    'cleaned_first_500' => substr($cleanedResponse, 0, 500),
                    'cleaned_last_500' => substr($cleanedResponse, -500)
                ]);
                throw new \Exception('Invalid JSON response from AI - Response may be truncated. Try reducing lesson complexity or contact support.');
            }

            Log::info('Lesson Plan Data Parsed', [
                'has_lesson_plan_key' => isset($lessonPlanData['lesson_plan']),
                'stages_count' => isset($lessonPlanData['lesson_plan']['stages']) ? count($lessonPlanData['lesson_plan']['stages']) : 0,
                'first_stage_preview' => isset($lessonPlanData['lesson_plan']['stages'][0]) ? json_encode($lessonPlanData['lesson_plan']['stages'][0]) : 'no stages'
            ]);

            // Save lesson plan to database
            $session = LessonPlanSession::findOrFail($request->session_id);
            $blocks = $this->saveLessonPlan($session, $lessonPlanData);

            Log::info('Lesson Plan Saved', [
                'blocks_count' => count($blocks),
                'first_block_stages' => isset($blocks[0]->stages) ? $blocks[0]->stages->count() : 0
            ]);

            // Transform blocks to match frontend expected structure (blocks with nested stages)
            $transformedBlocks = collect($blocks)->map(function($block) {
                return [
                    'id' => $block->id,
                    'block_number' => $block->block_number,
                    'block_title' => $block->block_title,
                    'block_description' => $block->block_description,
                    'stages' => $block->stages->map(function($stage) {
                        return [
                            'id' => $stage->id,
                            'stage_number' => $stage->stage_number,
                            'stage_name' => $stage->stage_name,
                            'stage_aim' => $stage->stage_aim,
                            'total_timing' => $stage->total_timing,
                            'procedure' => $stage->procedure ? [
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
                                'task_problems' => $stage->procedure->task_problems,
                            ] : null
                        ];
                    })->toArray()
                ];
            })->toArray();

            Log::info('Transformed Blocks', [
                'blocks_count' => count($transformedBlocks),
                'first_block_stages_count' => count($transformedBlocks[0]['stages'] ?? []),
                'first_stage_has_procedure' => isset($transformedBlocks[0]['stages'][0]['procedure']) ? 'yes' : 'no'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson plan generated successfully',
                'lesson_plan' => $transformedBlocks,
                'raw_response' => $lessonPlanData
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating lesson plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate lesson plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load prompt with JSON format template
     */
    private function loadPromptWithFormat(string $module, string $lessonShape, ?int $branchId = null): string
    {
        $userId = Auth::id();

        // Get branch_id if not provided
        if ($branchId === null) {
            $branchId = Auth::user()->branch_id ?? 0;
        }

        Log::info('Loading Lesson Plan Prompt', [
            'module' => $module,
            'branch_id' => $branchId,
            'user_id' => $userId
        ]);

        // Try to load custom prompt from database with priority order:
        // 1. Branch-specific prompt (branch_id = X AND created_by = user)
        // 2. Global prompt (branch_id IS NULL AND created_by = user)
        // 3. Default prompt

        // First try branch-specific prompt
        $aiPrompt = AIPrompt::where('module', $module)
            ->where('branch_id', $branchId)
            ->where('created_by', $userId)
            ->first();

        // If not found, try global prompt (branch_id = NULL)
        if (!$aiPrompt) {
            $aiPrompt = AIPrompt::where('module', $module)
                ->whereNull('branch_id')
                ->where('created_by', $userId)
                ->first();
        }

        Log::info('Lesson Plan Prompt Loaded', [
            'found_custom' => $aiPrompt ? 'yes' : 'no (using default)',
            'prompt_type' => $aiPrompt ? ($aiPrompt->branch_id ? 'branch-specific' : 'global') : 'default',
            'prompt_length' => $aiPrompt ? strlen($aiPrompt->prompt) : 0
        ]);

        $instructions = $aiPrompt ? $aiPrompt->prompt : $this->getDefaultLessonShapePrompt($lessonShape);

        // Append JSON format template
        $jsonFormat = $this->getJsonFormatTemplate($lessonShape);

        return $instructions . "\n\n" . $jsonFormat;
    }

    /**
     * Get default prompt for lesson shape
     */
    private function getDefaultLessonShapePrompt(string $shape): string
    {
        $prompts = [
            'A' => 'You are an expert ESL lesson planner. Create a Text-Based Presentation lesson plan following TECP methodology. Focus on using authentic texts to present new language naturally.',
            'B' => 'You are an expert ESL lesson planner. Create a Language Practice lesson plan following TECP methodology. Design activities that provide controlled and freer practice of target language.',
            'C' => 'You are an expert ESL lesson planner. Create a Test-Teach-Test lesson plan following TECP methodology. Start with a diagnostic task, teach based on needs, then test again.',
            'D' => 'You are an expert ESL lesson planner. Create a Situational Presentation (PPP) lesson plan following TECP methodology. Present language in context, provide controlled practice, then production.',
            'E' => 'You are an expert ESL lesson planner. Create a Receptive Skills lesson plan following TECP methodology. Focus on listening or reading comprehension with pre/while/post stages.',
            'F' => 'You are an expert ESL lesson planner. Create a Productive Skills lesson plan following TECP methodology. Focus on speaking or writing with clear preparation and execution stages.',
            'G' => 'You are an expert ESL lesson planner. Create a Dogme ELT lesson plan following TECP methodology. Use a conversation-driven approach with emergent language focus.',
            'H' => 'You are an expert ESL lesson planner. Create a Task-Based Learning lesson plan following TECP methodology. Use the Pre-task, Task, Language Focus framework.',
        ];

        return $prompts[$shape] ?? $prompts['D'];
    }

    /**
     * Get JSON format template
     */
    private function getJsonFormatTemplate(string $lessonShape): string
    {
        $template = <<<'JSONTEMPLATE'
CRITICAL: You MUST respond with ONLY valid JSON in this exact format (no markdown, no code blocks, no extra text):

{
  "lesson_plan": {
    "title": "Lesson title",
    "shape": "LESSON_SHAPE_PLACEHOLDER",
    "duration": 50,
    "level": "Intermediate",
    "lesson_aims": {
      "communicative_outcome": "By the end of the lesson, students will be better able to...",
      "linguistic_aim": "Students will have a better understanding of...",
      "productive_subskills": "fluency and accuracy in speaking",
      "receptive_subskills": "listening for specific information"
    },
    "stages": [
      {
        "stage_number": 1,
        "stage_title": "Main Stage Title",
        "stage_description": "Description of what happens in this stage",
        "procedures": [
          {
            "procedure_number": 1,
            "procedure_name": "Warmer / Lead-in",
            "procedure_aim": "To activate prior knowledge and generate interest in the topic",
            "total_timing": 5,
            "procedure": {
              "stage_content": "Brief overview of what teacher and students do",
              "instructions": "Clear step-by-step instructions for students",
              "icqs": "Instruction checking questions to verify understanding",
              "instruction_timing": 1,
              "instruction_interaction": "T-Ss",
              "task_completion": "What students actually do during the task",
              "monitoring_points": "What the teacher monitors and looks for",
              "task_timing": 3,
              "task_interaction": "SS-Ss",
              "feedback": "How feedback is given and what is focused on",
              "feedback_timing": 1,
              "feedback_interaction": "T-Ss",
              "learner_problems": [
                {"problem": "Students might not understand vocabulary", "solution": "Pre-teach key words with visuals"}
              ],
              "task_problems": [
                {"problem": "Task might be too easy", "solution": "Have extension questions ready"}
              ]
            }
          }
        ]
      }
    ]
  }
}

FORMATTING RULES:
- Return ONLY the JSON object above
- NO markdown code blocks
- NO explanatory text before or after JSON
- All timing in minutes (integer)
- Interaction must be EXACTLY ONE of: T-Ss, Ss-T, SS-Ss, Ss-text, T-S (NOT multiple values, NOT comma-separated)
- Include 3-5 procedures minimum depending on lesson shape
- Total timing across all procedures should match requested duration
- IMPORTANT: Keep your response under 15000 tokens to ensure completeness. Be detailed and thorough in all sections.
JSONTEMPLATE;

        return str_replace('LESSON_SHAPE_PLACEHOLDER', $lessonShape, $template);
    }

    /**
     * Build lesson plan prompt from request data and session info
     */
    private function buildLessonPlanPrompt(array $data): string
    {
        $additionalContext = $data['additional_context'] ?? '';

        // Build detailed prompt from session data
        $prompt = "Generate a complete TECP lesson plan with the following specifications:\n\n";

        // Basic Information
        $prompt .= "=== BASIC INFORMATION ===\n";
        $prompt .= "Lesson Title: " . ($data['lesson_title'] ?? 'N/A') . "\n";
        $prompt .= "Level: " . ($data['level'] ?? 'Intermediate') . "\n";
        $prompt .= "Duration: " . ($data['duration'] ?? 50) . " minutes\n";
        $prompt .= "Lesson Focus: " . ($data['lesson_focus'] ?? 'N/A') . "\n";
        $prompt .= "Lesson Shape: " . ($data['lesson_shape'] ?? 'D') . "\n";

        // Student Information
        if (!empty($data['student_age'])) {
            $prompt .= "Student Age: {$data['student_age']} years old\n";
        }
        if (!empty($data['number_of_students'])) {
            $prompt .= "Number of Students: {$data['number_of_students']}\n";
        }
        $prompt .= "\n";

        // Lesson Aims (if provided in session)
        if (!empty($data['communicative_outcome']) || !empty($data['linguistic_aim'])) {
            $prompt .= "=== LESSON AIMS ===\n";
            if (!empty($data['communicative_outcome'])) {
                $prompt .= "Communicative Outcome: {$data['communicative_outcome']}\n";
            }
            if (!empty($data['linguistic_aim'])) {
                $prompt .= "Linguistic Aim: {$data['linguistic_aim']}\n";
            }
            if (!empty($data['productive_subskills_focus'])) {
                $prompt .= "Productive Sub-skills Focus: {$data['productive_subskills_focus']}\n";
            }
            if (!empty($data['receptive_subskills_focus'])) {
                $prompt .= "Receptive Sub-skills Focus: {$data['receptive_subskills_focus']}\n";
            }
            $prompt .= "\n";
        }

        // Personal Aims (if provided)
        if (!empty($data['teaching_aspects_to_improve']) || !empty($data['improvement_methods'])) {
            $prompt .= "=== PERSONAL AIMS (Teacher Development Focus) ===\n";
            if (!empty($data['teaching_aspects_to_improve'])) {
                $prompt .= "Aspects to Improve: {$data['teaching_aspects_to_improve']}\n";
            }
            if (!empty($data['improvement_methods'])) {
                $prompt .= "Improvement Methods: {$data['improvement_methods']}\n";
            }
            $prompt .= "\n";
        }

        // Language Analysis Sheet (if provided)
        if (!empty($data['language_area']) || !empty($data['examples_of_language']) || !empty($data['context'])) {
            $prompt .= "=== LANGUAGE ANALYSIS (For Language-Focused Lessons) ===\n";
            if (!empty($data['language_area'])) {
                $prompt .= "Language Area: {$data['language_area']}\n";
            }
            if (!empty($data['examples_of_language'])) {
                $prompt .= "Example Sentences/Language: {$data['examples_of_language']}\n";
            }
            if (!empty($data['context'])) {
                $prompt .= "Context: {$data['context']}\n";
            }
            if (!empty($data['concept_checking_methods'])) {
                $prompt .= "Concept Checking Methods: {$data['concept_checking_methods']}\n";
            }
            if (!empty($data['concept_checking_in_lesson'])) {
                $prompt .= "How to Check in Lesson: {$data['concept_checking_in_lesson']}\n";
            }
            $prompt .= "\n";
        }

        // Additional Context from teacher
        if (!empty($additionalContext)) {
            $prompt .= "=== ADDITIONAL CONTEXT FROM TEACHER ===\n";
            $prompt .= $additionalContext . "\n\n";
        }

        // Instructions
        $prompt .= "=== REQUIREMENTS ===\n";
        $prompt .= "Please create a detailed lesson plan following the specified lesson shape structure. Include:\n";
        $prompt .= "1. Clear lesson aims - MUST align with and incorporate the lesson aims provided above (communicative outcome, linguistic aim, sub-skills)\n";
        $prompt .= "2. Appropriate stages for lesson shape {$data['lesson_shape']}\n";
        $prompt .= "3. Detailed procedures for each stage with realistic timing\n";
        $prompt .= "4. ICQs (Instruction Checking Questions) - if concept checking methods are provided, incorporate them\n";
        $prompt .= "5. Monitoring points and anticipated problems with solutions\n";
        $prompt .= "6. Appropriate interaction patterns (T-Ss, Ss-T, SS-Ss, Ss-text, T-S)\n";
        $prompt .= "7. If language analysis is provided, ensure the lesson effectively teaches that language area with the example sentences\n";
        $prompt .= "8. If personal aims are provided, design procedures that help the teacher practice those teaching skills\n";
        $prompt .= "9. Make the plan practical, detailed, and ready to use in class\n\n";

        $prompt .= "CRITICAL: The lesson plan MUST be aligned with all the information provided above. ";
        $prompt .= "Do not create generic content - use the specific aims, language, context, and requirements given.\n";

        return $prompt;
    }

    /**
     * Call AI API (OpenAI or Anthropic)
     */
    private function callAI(
        string $provider,
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens = 2000
    ): string {
        if ($provider === 'openai') {
            // Check if it's GPT-5 family (uses Responses API)
            $isGPT5 = str_starts_with($model, 'gpt-5');

            if ($isGPT5) {
                return $this->callOpenAIResponses($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
            }

            return $this->callOpenAIChat($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
        }

        return $this->callAnthropic($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
    }

    /**
     * Call OpenAI Chat Completions API (GPT-4, GPT-3.5)
     */
    private function callOpenAIChat(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens
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
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object']
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API Error: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Call OpenAI Responses API (GPT-5.1, o1 models)
     */
    private function callOpenAIResponses(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens
    ): string {
        // Combine system prompt and user prompt for GPT-5.1 Responses API
        $fullPrompt = "System instructions:\n{$systemPrompt}\n\nUser request:\n{$userPrompt}";

        Log::info('GPT-5 Lesson Plan Request', ['model' => $model, 'prompt_length' => strlen($fullPrompt)]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(180)->post('https://api.openai.com/v1/responses', [
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
        Log::info('GPT-5 Response', [
            'response_keys' => array_keys($data),
            'status' => $data['status'] ?? null,
            'output_type' => isset($data['output']) ? gettype($data['output']) : 'not set',
            'text_type' => isset($data['text']) ? gettype($data['text']) : 'not set',
            'output_first_item' => isset($data['output'][0]) ? json_encode($data['output'][0]) : 'no first item'
        ]);

        // Extract content from Responses API output with type checking (robust extraction)
        $content = '';

        // Try output_text first (must be string)
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
        // If text is array, try to extract from it
        if (!$content && isset($data['text']) && is_array($data['text'])) {
            foreach ($data['text'] as $textItem) {
                if (is_string($textItem)) {
                    $content = $textItem;
                    break;
                } elseif (is_array($textItem) && isset($textItem['text']) && is_string($textItem['text'])) {
                    $content = $textItem['text'];
                    break;
                }
            }
        }

        Log::info('GPT-5 Content Extracted', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 500),
            'is_empty' => empty($content)
        ]);

        return $content;
    }

    /**
     * Call Anthropic Claude API
     */
    private function callAnthropic(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens
    ): string {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->timeout(600)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API Error: ' . $response->body());
        }

        $data = $response->json();
        return $data['content'][0]['text'] ?? '';
    }

    /**
     * Save generated lesson plan to database
     */
    private function saveLessonPlan(LessonPlanSession $session, array $data): array
    {
        $lessonPlanData = $data['lesson_plan'];

        // Update session with lesson aims if provided
        if (isset($lessonPlanData['lesson_aims'])) {
            $aims = $lessonPlanData['lesson_aims'];
            $session->update([
                'communicative_outcome' => $aims['communicative_outcome'] ?? null,
                'linguistic_aim' => $aims['linguistic_aim'] ?? null,
                'productive_subskills_focus' => $aims['productive_subskills'] ?? null,
                'receptive_subskills_focus' => $aims['receptive_subskills'] ?? null,
            ]);
        }

        // Delete all existing blocks for this session (cascades to stages and procedures)
        // This ensures each new generation replaces the old one completely
        LessonPlanBlock::where('lesson_plan_session_id', $session->id)->delete();

        Log::info('Deleted existing blocks for session', ['session_id' => $session->id]);

        $savedStages = [];

        // Save stages and procedures (updated terminology)
        foreach ($lessonPlanData['stages'] as $stageIndex => $stageData) {
            $stage = LessonPlanBlock::create([
                'lesson_plan_session_id' => $session->id,
                'block_number' => $stageData['stage_number'] ?? ($stageIndex + 1),
                'block_title' => $stageData['stage_title'] ?? null,
                'block_description' => $stageData['stage_description'] ?? null,
                'sort_order' => $stageIndex,
            ]);

            $savedProcedures = [];

            // Handle both 'procedures' (array) and 'procedure' (single object) from AI
            $proceduresData = [];
            if (isset($stageData['procedures']) && is_array($stageData['procedures'])) {
                $proceduresData = $stageData['procedures'];
            } elseif (isset($stageData['procedure']) && is_array($stageData['procedure'])) {
                // If AI returned 'procedure' as single object, wrap it in array
                $proceduresData = [$stageData['procedure']];
            }

            foreach ($proceduresData as $procedureIndex => $procedureData) {
                Log::info('Saving Procedure', [
                    'procedure_name' => $procedureData['procedure_name'] ?? 'N/A',
                    'total_timing' => $procedureData['total_timing'] ?? 0,
                    'has_nested_procedure' => isset($procedureData['procedure']),
                    'procedure_keys' => isset($procedureData['procedure']) ? array_keys($procedureData['procedure']) : []
                ]);

                $procedure = LessonPlanStage::create([
                    'lesson_plan_block_id' => $stage->id,
                    'stage_number' => $procedureData['procedure_number'] ?? ($procedureIndex + 1),
                    'stage_name' => $procedureData['procedure_name'],
                    'stage_aim' => $procedureData['procedure_aim'] ?? null,
                    'total_timing' => $procedureData['total_timing'] ?? 0,
                    'sort_order' => $procedureIndex,
                ]);

                // Save procedure details if provided
                // Handle both nested 'procedure' object and flat structure
                $proc = null;
                if (isset($procedureData['procedure']) && is_array($procedureData['procedure'])) {
                    // Case 1: Nested structure - procedure details in 'procedure' key
                    $proc = $procedureData['procedure'];
                } elseif (isset($procedureData['instructions']) || isset($procedureData['stage_content'])) {
                    // Case 2: Flat structure - procedure details are directly in procedureData
                    $proc = $procedureData;
                }

                if ($proc) {
                    Log::info('Saving Procedure Details', [
                        'stage_content_length' => strlen($proc['stage_content'] ?? ''),
                        'instructions_length' => strlen($proc['instructions'] ?? ''),
                        'icqs_length' => strlen($proc['icqs'] ?? '')
                    ]);

                    // Helper function to extract first interaction pattern if multiple provided
                    $extractFirstInteraction = function($interaction, $default) {
                        if (!$interaction) return $default;
                        // If contains comma, take only the first pattern
                        $patterns = array_map('trim', explode(',', $interaction));
                        return $patterns[0] ?? $default;
                    };

                    $procedureDetails = LessonPlanProcedure::create([
                        'lesson_plan_stage_id' => $procedure->id,
                        'stage_content' => $proc['stage_content'] ?? null,
                        'instructions' => $proc['instructions'] ?? null,
                        'icqs' => $proc['icqs'] ?? null,
                        'instruction_timing' => $proc['instruction_timing'] ?? 0,
                        'instruction_interaction' => $extractFirstInteraction($proc['instruction_interaction'] ?? null, 'T-Ss'),
                        'task_completion' => $proc['task_completion'] ?? null,
                        'monitoring_points' => $proc['monitoring_points'] ?? null,
                        'task_timing' => $proc['task_timing'] ?? 0,
                        'task_interaction' => $extractFirstInteraction($proc['task_interaction'] ?? null, 'SS-Ss'),
                        'feedback' => $proc['feedback'] ?? null,
                        'feedback_timing' => $proc['feedback_timing'] ?? 0,
                        'feedback_interaction' => $extractFirstInteraction($proc['feedback_interaction'] ?? null, 'T-Ss'),
                        'learner_problems' => $proc['learner_problems'] ?? [],
                        'task_problems' => $proc['task_problems'] ?? [],
                        'sort_order' => 0,
                    ]);

                    $savedProcedures[] = $procedure->load('procedure');
                } else {
                    Log::warning('No procedure details found for stage', [
                        'stage_name' => $procedureData['procedure_name'] ?? 'N/A',
                        'has_procedure_key' => isset($procedureData['procedure']),
                        'has_instructions_key' => isset($procedureData['instructions']),
                        'procedure_data_keys' => array_keys($procedureData)
                    ]);
                    $savedProcedures[] = $procedure;
                }
            }

            // Load stages with their procedures correctly
            $stage->load('stages.procedure');
            $savedStages[] = $stage;
        }

        Log::info('Lesson Plan Structure', [
            'total_blocks' => count($savedStages),
            'first_block_stages_count' => $savedStages[0]->stages->count() ?? 0,
            'first_stage_has_procedure' => isset($savedStages[0]->stages[0]->procedure) ? 'yes' : 'no'
        ]);

        return $savedStages;
    }

    /**
     * Get material generation prompt
     */
    public function getMaterialPrompt(Request $request): JsonResponse
    {
        try {
            // Get branch_id from request first, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            Log::info('Loading Material Prompt', [
                'branch_id' => $branchId,
                'user_branch_id' => Auth::user()->branch_id ?? null
            ]);

            $prompt = DB::table('ai_prompts')
                ->where('module', 'quality_management')
                ->where('branch_id', $branchId)
                ->where('prompt_type', 'material_generation')
                ->first();

            Log::info('Material Prompt Loaded', [
                'found' => $prompt ? 'yes' : 'no',
                'has_description' => $prompt && $prompt->description ? 'yes' : 'no'
            ]);

            return response()->json([
                'success' => true,
                'data' => $prompt ? [
                    'description' => $prompt->description,
                    'json_format' => $prompt->json_format
                ] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading material prompt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load material prompt'
            ], 500);
        }
    }

    /**
     * Save material generation prompt
     */
    public function saveMaterialPrompt(Request $request): JsonResponse
    {
        try {
            // Get branch_id from request first, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            Log::info('Saving Material Prompt', [
                'branch_id' => $branchId,
                'user_branch_id' => Auth::user()->branch_id ?? null,
                'user_id' => Auth::id(),
                'prompt_type' => $request->prompt_type
            ]);

            $validator = Validator::make($request->all(), [
                'prompt_type' => 'required|string',
                'description' => 'nullable|string',
                'json_format' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if record exists
            $existing = DB::table('ai_prompts')
                ->where('module', 'quality_management')
                ->where('branch_id', $branchId)
                ->where('prompt_type', $request->prompt_type)
                ->first();

            if ($existing) {
                // Update existing
                Log::info('Updating existing material prompt', ['id' => $existing->id]);
                DB::table('ai_prompts')
                    ->where('id', $existing->id)
                    ->update([
                        'description' => $request->description,
                        'json_format' => $request->json_format,
                        'prompt' => $request->description ?? '', // Keep old 'prompt' column for compatibility
                        'updated_at' => now()
                    ]);
            } else {
                // Insert new
                Log::info('Inserting new material prompt', ['branch_id' => $branchId]);
                DB::table('ai_prompts')->insert([
                    'module' => 'quality_management',
                    'branch_id' => $branchId,
                    'prompt_type' => $request->prompt_type,
                    'description' => $request->description,
                    'json_format' => $request->json_format,
                    'prompt' => $request->description ?? '', // Keep old 'prompt' column for compatibility
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            Log::info('Material prompt saved successfully');

            return response()->json([
                'success' => true,
                'message' => 'Material prompt saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving material prompt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save material prompt'
            ], 500);
        }
    }

    /**
     * Generate teaching material with AI
     */
    public function generateMaterial(Request $request, $sessionId): JsonResponse
    {
        try {
            // Increase PHP execution time for AI generation
            set_time_limit(300); // 5 minutes

            // Validate request - provider and model are nullable, will use settings if not provided
            $validator = Validator::make($request->all(), [
                'provider' => 'nullable|string|in:openai,anthropic',
                'model' => 'nullable|string',
                'additional_context' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Load session with all data
            $session = LessonPlanSession::with([
                'blocks.stages.procedure'
            ])->findOrFail($sessionId);

            // Get branch_id from request first, then from header, then from user, then default to 0
            $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id') ?? Auth::user()->branch_id ?? 0;

            // Always get AI settings (for API key)
            $aiSettings = AiSetting::getSettings($branchId, 'quality_management');

            if (!$aiSettings || !$aiSettings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI is not configured. Please set up AI settings first.'
                ], 400);
            }

            // Use custom provider/model if provided, otherwise from settings
            $provider = $request->input('provider') ?: $aiSettings->provider;
            $model = $request->input('model') ?: $aiSettings->model;
            $apiKey = $aiSettings->api_key; // Always from settings

            // If custom provider is specified, get its API key
            if ($request->input('provider') && $request->input('provider') !== $aiSettings->provider) {
                $customSettings = AiSetting::getSettingsByProvider($branchId, 'quality_management', $request->input('provider'));
                if (!$customSettings || !$customSettings->api_key) {
                    return response()->json([
                        'success' => false,
                        'message' => "API key not configured for {$request->input('provider')}. Please configure it in Quality Settings first."
                    ], 400);
                }
                $apiKey = $customSettings->api_key;
            }

            // Load material generation prompt
            $promptData = DB::table('ai_prompts')
                ->where('module', 'quality_management')
                ->where('branch_id', $branchId)
                ->where('prompt_type', 'material_generation')
                ->first();

            $customPrompt = $promptData->description ?? '';
            $jsonFormat = $promptData->json_format ?? $this->getDefaultMaterialJsonFormat();
            $additionalContext = $request->input('additional_context');

            // Build system prompt (instructions only)
            $systemPrompt = $this->buildMaterialSystemPrompt($customPrompt);

            // Build user prompt (lesson data + JSON format requirements)
            $userPrompt = $this->buildMaterialUserPrompt($session, $jsonFormat, $additionalContext);

            // Log the prompts being sent
            Log::info('Material Generation - Sending Prompts', [
                'provider' => $provider,
                'model' => $model,
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
                'system_prompt' => $systemPrompt,
                'user_prompt' => $userPrompt
            ]);

            // Call AI with selected provider and model
            $response = $this->callAI(
                $provider,
                $apiKey,
                $model,
                $systemPrompt,
                $userPrompt,
                12000 // max tokens for material generation - increased to support longer reading texts
            );

            // Log the full raw AI response
            Log::info('Material Generation - Raw AI Response', [
                'provider' => $provider,
                'model' => $model,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
                'full_response' => $response // Save full response for debugging
            ]);

            // Parse JSON response - strip markdown code blocks if present
            $cleanedResponse = $response;

            // Remove markdown code blocks using simple string operations
            if (str_starts_with($cleanedResponse, '```json')) {
                $cleanedResponse = substr($cleanedResponse, 7);
            } elseif (str_starts_with($cleanedResponse, '```')) {
                $cleanedResponse = substr($cleanedResponse, 3);
            }
            $cleanedResponse = trim($cleanedResponse);
            if (str_ends_with($cleanedResponse, '```')) {
                $cleanedResponse = substr($cleanedResponse, 0, -3);
            }
            $cleanedResponse = trim($cleanedResponse);
            if (false && preg_match('/```(?:json)?\s*(.*?)\s*```/s', $response, $matches)) {
                $cleanedResponse = trim($matches[1]);
                Log::info('Material Generation - Stripped markdown blocks', [
                    'cleaned_length' => strlen($cleanedResponse)
                ]);
            }

            // Also try to extract JSON if it's embedded in other text
            if (!str_starts_with(trim($cleanedResponse), '{')) {
                // Try to find JSON object
                if (preg_match('/\{.*\}/s', $cleanedResponse, $matches)) {
                    $cleanedResponse = $matches[0];
                    Log::info('Material Generation - Extracted JSON from text', [
                        'extracted_length' => strlen($cleanedResponse)
                    ]);
                }
            }

            // Parse JSON
            $material = json_decode($cleanedResponse, true);

            if (!$material || !isset($material['title']) || !isset($material['content'])) {
                // Save full response to file for debugging
                $debugFile = storage_path('logs/material_debug_' . time() . '.json');
                file_put_contents($debugFile, $cleanedResponse);

                Log::error('Material Generation - Invalid response format', [
                    'cleaned_response_length' => strlen($cleanedResponse),
                    'cleaned_response_preview' => substr($cleanedResponse, 0, 500),
                    'cleaned_response_end' => substr($cleanedResponse, -500),
                    'json_error' => json_last_error_msg(),
                    'json_error_code' => json_last_error(),
                    'material_is_null' => is_null($material),
                    'has_title' => isset($material['title']),
                    'has_content' => isset($material['content']),
                    'debug_file' => $debugFile
                ]);
                throw new \Exception('Invalid AI response format: ' . json_last_error_msg() . ' (saved to ' . basename($debugFile) . ')');
            }

            // Log the parsed material
            Log::info('Material Generation - Parsed Successfully', [
                'title' => $material['title'],
                'description_length' => strlen($material['description'] ?? ''),
                'content_length' => strlen($material['content']),
                'content_preview' => substr(strip_tags($material['content']), 0, 200)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $material['title'],
                    'description' => $material['description'] ?? '',
                    'content' => $material['content']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating material: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build system prompt for material generation (instructions + JSON format)
     */
    private function buildMaterialSystemPrompt($customPrompt): string
    {
        $prompt = "You are an expert ESL teacher creating comprehensive teaching materials following CELTA lesson plan structure.\n\n";

        $prompt .= "YOUR ROLE:\n";
        $prompt .= "Create student handout materials that align with the CELTA framework, providing complete content for all activities while leaving appropriate spaces for student work during the lesson.\n\n";

        $prompt .= "CONTENT LENGTH GUIDELINES:\n";
        $prompt .= "Create focused, effective materials. Follow these STRICT guidelines:\n";
        $prompt .= "   - Reading texts: Maximum 500 words total (1-2 focused passages)\n";
        $prompt .= "   - Vocabulary: 10-12 key words with clear but concise definitions\n";
        $prompt .= "   - Grammar explanations: 1 well-structured table with 4-5 rules and examples\n";
        $prompt .= "   - Exercises: 2-3 IELTS-style exercise types, 6-8 items each\n";
        $prompt .= "   - Answer key: Complete but concise answers\n";
        $prompt .= "   - CRITICAL: Stay within limits to avoid truncation\n\n";

        $prompt .= "CONTENT QUALITY FOCUS:\n";
        $prompt .= "   - CREATE engaging reading texts that are focused and effective\n";
        $prompt .= "   - INCLUDE essential vocabulary for comprehension\n";
        $prompt .= "   - PROVIDE clear, concise explanations\n";
        $prompt .= "   - DESIGN effective IELTS-style exercises\n";
        $prompt .= "   - PRIORITIZE quality over quantity - keep materials focused\n\n";

        $prompt .= "⭐ MANDATORY: IELTS-STYLE QUESTIONS REQUIREMENT ⭐\n\n";
        $prompt .= "CRITICAL: ALL reading/listening comprehension exercises MUST use authentic IELTS question formats.\n";
        $prompt .= "This is NON-NEGOTIABLE - students are preparing for IELTS exams.\n\n";

        $prompt .= "REQUIRED IELTS QUESTION TYPES (use 2-3 types per text):\n\n";

        $prompt .= "1. MULTIPLE CHOICE (A/B/C/D):\n";
        $prompt .= "   Format: Question stem with 4 options (A, B, C, D)\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>1. What is the main idea of the passage?</strong></p>\n";
        $prompt .= "   <p>A) Tourism is harmful to the environment<br>\n";
        $prompt .= "   B) Sustainable tourism benefits local communities<br>\n";
        $prompt .= "   C) Hotels should reduce their prices<br>\n";
        $prompt .= "   D) Governments must ban tourism</p>\n";
        $prompt .= "   <p>Your answer: _______</p>\n\n";

        $prompt .= "2. TRUE / FALSE / NOT GIVEN:\n";
        $prompt .= "   Format: Statements that students mark as T, F, or NG based on the text\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>Read the statements. Write TRUE, FALSE, or NOT GIVEN:</strong></p>\n";
        $prompt .= "   <table style='width:100%; border-collapse: collapse;'>\n";
        $prompt .= "   <tr><td style='border: 1px solid #333; padding: 8px;'>1. The author visited Paris last year.</td>\n";
        $prompt .= "   <td style='border: 1px solid #333; padding: 8px;'>_______</td></tr>\n";
        $prompt .= "   <tr><td style='border: 1px solid #333; padding: 8px;'>2. The Eiffel Tower is 300 meters tall.</td>\n";
        $prompt .= "   <td style='border: 1px solid #333; padding: 8px;'>_______</td></tr>\n";
        $prompt .= "   </table>\n\n";

        $prompt .= "3. SENTENCE COMPLETION:\n";
        $prompt .= "   Format: Incomplete sentences that students complete using words from the text\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>Complete the sentences below. Use NO MORE THAN TWO WORDS from the passage.</strong></p>\n";
        $prompt .= "   <p>1. The researchers discovered that climate change affects _____________ and _____________.</p>\n";
        $prompt .= "   <p>2. According to the study, temperatures have increased by _____________ degrees.</p>\n\n";

        $prompt .= "4. MATCHING HEADINGS:\n";
        $prompt .= "   Format: List of headings (i-viii) that students match to paragraphs\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>Match each paragraph (A-D) with the correct heading (i-vii):</strong></p>\n";
        $prompt .= "   <p><strong>Headings:</strong></p>\n";
        $prompt .= "   <p>i. The benefits of exercise<br>ii. Common health problems<br>iii. How to start a fitness routine</p>\n";
        $prompt .= "   <table style='width:100%; border-collapse: collapse;'>\n";
        $prompt .= "   <tr><td style='border: 1px solid #333; padding: 8px;'>Paragraph A</td>\n";
        $prompt .= "   <td style='border: 1px solid #333; padding: 8px;'>_______</td></tr>\n";
        $prompt .= "   </table>\n\n";

        $prompt .= "5. SHORT ANSWER QUESTIONS:\n";
        $prompt .= "   Format: Questions requiring brief answers (1-3 words) from the text\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>Answer the questions below. Write NO MORE THAN THREE WORDS.</strong></p>\n";
        $prompt .= "   <p>1. When did the study begin? _______________________</p>\n";
        $prompt .= "   <p>2. Who conducted the research? _______________________</p>\n\n";

        $prompt .= "6. TABLE/SUMMARY/NOTE COMPLETION:\n";
        $prompt .= "   Format: Partially completed table/summary with gaps to fill\n";
        $prompt .= "   Example:\n";
        $prompt .= "   <p><strong>Complete the table using information from the passage:</strong></p>\n";
        $prompt .= "   <table style='width:100%; border-collapse: collapse;'>\n";
        $prompt .= "   <tr style='background-color: #f0f0f0;'><th style='border: 1px solid #333; padding: 8px;'>Country</th>\n";
        $prompt .= "   <th style='border: 1px solid #333; padding: 8px;'>Population</th></tr>\n";
        $prompt .= "   <tr><td style='border: 1px solid #333; padding: 8px;'>Japan</td>\n";
        $prompt .= "   <td style='border: 1px solid #333; padding: 8px;'>___________</td></tr>\n";
        $prompt .= "   </table>\n\n";

        $prompt .= "IELTS QUESTION IMPLEMENTATION RULES:\n";
        $prompt .= "   ✓ ALWAYS use these formats for reading/listening comprehension\n";
        $prompt .= "   ✓ Include word limits where appropriate (e.g., 'NO MORE THAN TWO WORDS')\n";
        $prompt .= "   ✓ Use authentic IELTS instructions (e.g., 'Choose the correct letter, A, B, C or D')\n";
        $prompt .= "   ✓ Mix 2-3 question types per text to simulate real IELTS variety\n";
        $prompt .= "   ✓ Focus on skills: main ideas, specific details, inference, writer's opinion\n";
        $prompt .= "   ✓ Use academic/semi-formal language appropriate for IELTS\n";
        $prompt .= "   ✗ DO NOT use simple 'Answer these questions' without IELTS format\n";
        $prompt .= "   ✗ DO NOT create generic comprehension questions\n";
        $prompt .= "   ✗ DO NOT skip the authentic IELTS instructions\n\n";

        $prompt .= "LANGUAGE CONCEPTS (Essential for Lesson Design):\n\n";

        $prompt .= "1. Language Clarifiers:\n";
        $prompt .= "   - Words/phrases NOT the learning objectives but may block comprehension\n";
        $prompt .= "   - Must be pre-taught before the main text/audio\n";
        $prompt .= "   - Use simple tasks (matching, gap-fills) - NOT deep analysis\n";
        $prompt .= "   - Example: Pre-teach 'expedition', 'trek' before a reading about mountain climbing\n\n";

        $prompt .= "2. Target Language:\n";
        $prompt .= "   - The MAIN language focus of the lesson (grammar, lexical items, functional expressions, discourse features)\n";
        $prompt .= "   - Should be DISCOVERED by learners during the While stage through the text/audio\n";
        $prompt .= "   - DO NOT teach at the beginning - use guided discovery instead\n";
        $prompt .= "   - Highlight using questions that guide learners to notice patterns\n";
        $prompt .= "   - Example: Students discover 'must have + past participle' from context, then you highlight it\n\n";

        $prompt .= "3. Functional Language:\n";
        $prompt .= "   - Expressions needed for the final communicative task\n";
        $prompt .= "   - Examples: expressing probability, giving advice, justifying opinions, requesting, disagreeing politely\n";
        $prompt .= "   - Provide these phrases in the post-task section to support the communicative outcome\n\n";

        $prompt .= "4. Communicative Outcome:\n";
        $prompt .= "   - A final REAL communication task (NOT just a language exercise)\n";
        $prompt .= "   - Must use authentic, real-world language in meaningful context\n";
        $prompt .= "   - Must include an audience (listener/reader) and response/feedback\n";
        $prompt .= "   - Example: 'Discuss with a partner: What must have happened in this situation? Give reasons.'\n\n";

        $prompt .= "TEACHING PHILOSOPHY: Learning By Discovery (LBD):\n\n";
        $prompt .= "CRITICAL: Your materials must support LBD methodology:\n";
        $prompt .= "   - Teacher does NOT explain target language upfront\n";
        $prompt .= "   - Students discover meaning, form, and use through context, questions, and examples\n";
        $prompt .= "   - Materials should include:\n";
        $prompt .= "     • Questions that guide learners to notice language patterns\n";
        $prompt .= "     • Tasks that help students work out meaning from context\n";
        $prompt .= "     • Exercises that encourage learners to figure out rules themselves\n";
        $prompt .= "   - Teacher highlights language AFTER students attempt to interpret it\n";
        $prompt .= "   - Provide minimal clarification only after discovery\n\n";

        $prompt .= "REMEMBER: You are creating a STUDENT HANDOUT\n";
        $prompt .= "   - Students will write on this handout during the lesson\n";
        $prompt .= "   - Provide COMPLETE content (texts, definitions, explanations)\n";
        $prompt .= "   - Leave BLANK SPACES for exercises where students write answers\n";
        $prompt .= "   - Follow the lesson plan flow provided\n";
        $prompt .= "   - Match materials to each stage's aims and timing\n\n";

        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. CREATE STUDENT HANDOUT FORMAT:\n";
        $prompt .= "   - Provide COMPLETE reading texts, dialogues, listening scripts, and reference materials\n";
        $prompt .= "   - Include DETAILED vocabulary tables with definitions and example sentences\n";
        $prompt .= "   - Create FULL grammar explanations and charts\n";
        $prompt .= "   - Leave BLANK SPACES in exercises where students need to write answers (gap-fills, matching, sentence completion)\n";
        $prompt .= "   - Include empty note sections for student work during activities\n";
        $prompt .= "   - This is intentional - blanks allow active learning!\n\n";

        $prompt .= "2. PROVIDE DETAILED CONTENT FOR PROCEDURES:\n";
        $prompt .= "   - For each block/stage in the lesson plan, create corresponding materials\n";
        $prompt .= "   - If the procedure mentions vocabulary teaching → create vocabulary tables with words, definitions, examples\n";
        $prompt .= "   - If the procedure mentions reading → write complete, level-appropriate reading texts (check word count for level)\n";
        $prompt .= "   - If the procedure mentions grammar practice → create grammar charts and practice exercises\n";
        $prompt .= "   - If the procedure mentions speaking activities → provide discussion questions, prompts, useful phrases\n";
        $prompt .= "   - Match your materials to the timing and aims specified in each stage\n\n";

        $prompt .= "3. FOLLOW CELTA STRUCTURE:\n";
        $prompt .= "   - Organize materials by lesson blocks (Introduction, Presentation, Practice, Production, etc.)\n";
        $prompt .= "   - Include Pre-, While-, and Post- sections for reading/listening activities\n";
        $prompt .= "   - Provide controlled practice before freer practice\n";
        $prompt .= "   - Include guided discovery activities for grammar/vocabulary\n\n";

        $prompt .= "4. MATCH CONTENT TO LEVEL:\n";
        $prompt .= "   - Beginner (A1): Simple sentences, 250-300 word texts, basic vocabulary, present tenses\n";
        $prompt .= "   - Elementary (A2): Compound sentences, 300-350 word texts, everyday vocabulary, past tenses\n";
        $prompt .= "   - Intermediate (B1): Complex sentences, 350-400 word texts, abstract vocabulary, all tenses\n";
        $prompt .= "   - Upper-Intermediate (B2): Advanced structures, 400-450 word texts, idiomatic expressions, academic language\n";
        $prompt .= "   - Advanced (C1): Sophisticated structures, 450-500 word texts maximum, specialized vocabulary, nuanced expressions\n";
        $prompt .= "   - NOTE: Keep texts focused and within limits to avoid truncation\n\n";

        $prompt .= "5. INCLUDE CONCISE ANSWER KEYS:\n";
        $prompt .= "   - Add a brief ANSWER KEY section at the end for teachers\n";
        $prompt .= "   - Provide short answers for ALL exercises, gap-fills, and questions\n";
        $prompt .= "   - Keep answers concise - just the answer, no lengthy explanations\n";
        $prompt .= "   - Model answers for open-ended questions should be 1-2 sentences maximum\n\n";

        $prompt .= "6. USE PROPER HTML FORMATTING:\n";
        $prompt .= "   - Use simple HTML tags: <h1-h6>, <p>, <strong>, <em>, <ul>, <ol>, <li>, <table>\n";
        $prompt .= "   - Apply inline styles for formatting (borders, padding, margins)\n";
        $prompt .= "   - Do NOT use CSS classes or <style> blocks\n";
        $prompt .= "   - Create clear visual hierarchy with headings and spacing\n";
        $prompt .= "   - Use tables for structured information (vocabulary, grammar rules, exercises)\n\n";

        if ($customPrompt) {
            $prompt .= "EXAMPLE FORMAT TO FOLLOW:\n";
            $prompt .= "Below is a complete example of student handout materials. Study its structure carefully:\n\n";
            $prompt .= "--- EXAMPLE STARTS ---\n{$customPrompt}\n--- EXAMPLE ENDS ---\n\n";
            $prompt .= "IMPORTANT NOTES ABOUT THE EXAMPLE:\n";
            $prompt .= "- The example is about a DIFFERENT topic than your task\n";
            $prompt .= "- Notice how it provides COMPLETE content (reading texts, vocabulary definitions, grammar explanations)\n";
            $prompt .= "- Notice how it includes BLANK SPACES for student exercises (gap-fills, matching, notes)\n";
            $prompt .= "- Notice the ANSWER KEY section at the end for teachers\n";
            $prompt .= "- Create NEW materials about the actual lesson topic provided in the lesson plan data\n";
            $prompt .= "- Follow the SAME comprehensive structure and level of detail\n";
            $prompt .= "- Match the duration and activities specified in the lesson plan\n\n";
        }

        return $prompt;
    }

    /**
     * Build user prompt for material generation (lesson data + JSON format requirements)
     */
    private function buildMaterialUserPrompt($session, $jsonFormat, $additionalContext = null): string
    {
        // Prepare lesson plan data (excluding problems/solutions)
        $lessonData = [
            'lesson_title' => $session->lesson_title,
            'lesson_focus' => $session->lesson_focus,
            'level' => $session->level,
            'duration_minutes' => $session->duration_minutes,
            'communicative_outcome' => $session->communicative_outcome,
            'linguistic_aim' => $session->linguistic_aim,
            'blocks' => []
        ];

        foreach ($session->blocks as $block) {
            $blockData = [
                'block_number' => $block->block_number,
                'block_title' => $block->block_title,
                'block_description' => $block->block_description,
                'stages' => []
            ];

            foreach ($block->stages as $stage) {
                $stageData = [
                    'stage_number' => $stage->stage_number,
                    'stage_name' => $stage->stage_name,
                    'stage_aim' => $stage->stage_aim,
                    'total_timing' => $stage->total_timing
                ];

                if ($stage->procedure) {
                    $stageData['procedure'] = [
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
                        'feedback_interaction' => $stage->procedure->feedback_interaction
                    ];
                }

                $blockData['stages'][] = $stageData;
            }

            $lessonData['blocks'][] = $blockData;
        }

        $lessonDataJson = json_encode($lessonData, JSON_PRETTY_PRINT);

        // Build user prompt with lesson data and requirements
        $prompt = "";

        // Add additional context at the BEGINNING if provided
        if ($additionalContext) {
            $prompt .= "ADDITIONAL REQUIREMENTS:\n{$additionalContext}\n\n";
        }

        $prompt .= "Generate teaching material based on the following lesson plan:\n\n";
        $prompt .= "LESSON PLAN DATA:\n{$lessonDataJson}\n\n";

        $prompt .= "IMPORTANT: Return ONLY valid JSON in this exact format:\n{$jsonFormat}\n\n";
        $prompt .= "Your response must be pure JSON with no additional text or explanation.";

        return $prompt;
    }

    /**
     * Get default material JSON format
     */
    private function getDefaultMaterialJsonFormat(): string
    {
        return json_encode([
            "title" => "string - Material title",
            "description" => "string - Brief description of the material",
            "content" => "HTML string - Main content with formatting (can include <h1>, <p>, <ul>, <ol>, <table>, <img>, etc.)"
        ], JSON_PRETTY_PRINT);
    }
}
