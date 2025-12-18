<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\Examination\Assignment;
use App\Models\Examination\Submission;
use App\Models\Examination\SubmissionAnswer;
use App\Models\Examination\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AIGeneratorController extends Controller
{
    /**
     * JSON format templates for AI responses - automatically appended to user prompts
     */

    // GRADING JSON FORMATS
    private const JSON_FORMAT_WRITING_GRADING = '

🔥 CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks, or conversational responses. Output ONLY the raw JSON.

📋 REQUIRED JSON FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "band_score": 6.0,
  "task_achievement": {
    "score": 6.0,
    "feedback": "DETAILED feedback về Task Achievement (5-10 câu chi tiết):\n- Điểm mạnh: (liệt kê cụ thể 2-3 điểm với ví dụ)\n- Hạn chế: (liệt kê cụ thể 2-3 điểm với ví dụ từ bài viết)\n- Gợi ý cải thiện: (cụ thể, actionable)"
  },
  "coherence_cohesion": {
    "score": 6.0,
    "feedback": "DETAILED feedback về Coherence & Cohesion (5-10 câu chi tiết):\n- Điểm mạnh: (về bố cục, paragraphing, linking words)\n- Hạn chế: (chỉ ra lỗi cụ thể trong bài: referencing, cohesive devices)\n- Gợi ý: (cách cải thiện với ví dụ)"
  },
  "lexical_resource": {
    "score": 5.5,
    "feedback": "DETAILED feedback về Lexical Resource (5-10 câu chi tiết):\n- Điểm mạnh: (từ vựng học thuật đã dùng tốt)\n- Lỗi collocation/word choice: (liệt kê 3-5 lỗi cụ thể với correction: \"concerned problem\" → \"pressing issue\")\n- Gợi ý: (các cụm từ nên học và sử dụng)"
  },
  "grammatical_range": {
    "score": 5.5,
    "feedback": "DETAILED feedback về Grammatical Range & Accuracy (5-10 câu chi tiết):\n- Điểm mạnh: (cấu trúc phức đã dùng được)\n- Lỗi ngữ pháp: (liệt kê 3-5 lỗi cụ thể: S-V agreement, articles, prepositions với correction)\n- Gợi ý: (cấu trúc nên luyện tập)"
  },
  "overall_feedback": "Nhận xét tổng quan và lộ trình cải thiện (3-5 câu):\n- Tổng kết: Bài ở mức Band X vì...\n- Để lên Band Y: Ưu tiên cải thiện...\n- Hành động cụ thể: (2-3 bước ngắn hạn)"
}

⚠️ IMPORTANT JSON RULES:
- MUST provide DETAILED feedback for EACH criterion (task_achievement, coherence_cohesion, lexical_resource, grammatical_range)
- Each feedback should be 5-10 sentences, specific with examples from the essay
- List 3-5 specific errors with corrections (e.g., "take responsibility of" → "take responsibility for")
- Use \\n for line breaks in feedback
- If you need to use quotation marks in feedback, ESCAPE them with backslash: \\"word\\"
- Do not include any text before or after the JSON object

📊 STRICT SCORING RULES:
- All band scores MUST be numbers between 0-9 (use 0.5 increments: 5.0, 5.5, 6.0, etc.)
- "band_score" is the average of the 4 criteria scores, rounded to nearest 0.5
- BE CONSERVATIVE: Band 7+ requires consistent excellence; most responses are Band 5.5-6.5
- PENALIZE errors heavily: Each grammar error = -0.5 band in GRA; vocabulary errors = -0.5 in LR
- Task not fully addressed? Maximum Band 6 for TA
- Weak cohesion or paragraphing? Maximum Band 6 for CC
- DO NOT inflate scores - compare rigorously against official IELTS band descriptors

✅ OUTPUT CHECKLIST:
1. Valid JSON only (no markdown, no ```json)
2. All 4 criteria have both "score" and "feedback"
3. "overall_feedback" is included
4. Each feedback is in Vietnamese, specific, and actionable
5. Total response < 2000 characters';

    private const JSON_FORMAT_SPEAKING_GRADING = '

🔥 CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks, or conversational responses. Output ONLY the raw JSON.

📋 REQUIRED JSON FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "band_score": 5.5,
  "fluency_coherence": {
    "score": 6.0,
    "feedback": "DETAILED feedback về Fluency & Coherence (5-10 câu chi tiết):\n- Điểm mạnh: (về độ lưu loát, discourse markers)\n- Hạn chế: (hesitations, pauses, self-correction với timestamp nếu có)\n- Gợi ý: (cách cải thiện với ví dụ cụ thể)"
  },
  "lexical_resource": {
    "score": 5.5,
    "feedback": "DETAILED feedback về Lexical Resource (5-10 câu chi tiết):\n- Điểm mạnh: (từ vựng tốt đã sử dụng)\n- Hạn chế: (lặp từ, word choice errors với ví dụ cụ thể)\n- Gợi ý: (từ vựng/phrases nên học để nâng band)"
  },
  "grammatical_range": {
    "score": 5.5,
    "feedback": "DETAILED feedback về Grammatical Range & Accuracy (5-10 câu chi tiết):\n- Điểm mạnh: (cấu trúc phức đã dùng)\n- Lỗi: (liệt kê 3-5 lỗi ngữ pháp cụ thể với correction)\n- Gợi ý: (cấu trúc nên luyện)"
  },
  "pronunciation": {
    "score": 5.5,
    "feedback": "DETAILED feedback về Pronunciation (5-10 câu chi tiết, dựa trên Azure scores nếu có):\n- Điểm mạnh: (âm/từ phát âm tốt)\n- Hạn chế: (mispronunciation cụ thể, stress/intonation issues)\n- Gợi ý: (cách luyện phát âm)"
  },
  "overall_feedback": "Nhận xét tổng quan và lộ trình cải thiện (3-5 câu):\n- Tổng kết: Bài ở mức Band X vì...\n- Để lên Band Y: Ưu tiên cải thiện...\n- Hành động cụ thể: (2-3 bước)"
}

⚠️ IMPORTANT JSON RULES:
- MUST provide DETAILED feedback for EACH criterion (fluency_coherence, lexical_resource, grammatical_range, pronunciation)
- Each feedback should be 5-10 sentences, specific with examples
- Use \\n for line breaks in feedback
- If you need to use quotation marks in feedback, ESCAPE them with backslash: \\"word\\"
- Do not include any text before or after the JSON object

STRICT SCORING RULES FOR IELTS SPEAKING:

1. FLUENCY & COHERENCE (FC):
   - Band 7+: Speaks at length with few hesitations; clear coherence features
   - Band 6: Willing to speak at length but hesitations, repetition, self-correction may indicate problems
   - Band 5: Simple discourse markers; frequent hesitations; may lose coherence
   - PENALIZE: Long pauses (-0.5), repetition (-0.5), self-correction (-0.5), lack of discourse markers (-0.5)

2. LEXICAL RESOURCE (LR):
   - Band 7+: Flexible vocabulary with some less common items; good paraphrasing
   - Band 6: Adequate range but lacks flexibility; attempts paraphrasing with mixed success
   - Band 5: Limited vocabulary; may not convey precise meaning; basic word choice
   - PENALIZE: Repetitive vocabulary (-0.5), basic vocabulary only (-1), word choice errors (-0.5)

3. GRAMMATICAL RANGE & ACCURACY (GRA):
   - Band 7+: Wide range of structures with flexibility; frequent error-free sentences
   - Band 6: Mix of simple and complex forms with some errors that do not impede communication
   - Band 5: Produces basic sentence forms; subordinate clauses rare; frequent errors
   - PENALIZE: Subject-verb errors (-0.5), tense errors (-0.5), simple sentences only (-1)

4. PRONUNCIATION (P):
   - Band 7+: Easy to understand throughout; L1 accent has minimal effect
   - Band 6: Generally easy to understand; mispronunciation of individual words may reduce clarity
   - Band 5: Mispronunciation of individual words/sounds may reduce clarity at times
   - PENALIZE: Frequent mispronunciation (-0.5), unclear articulation (-0.5), stress/intonation issues (-0.5)

GRADING GUIDELINES:
- Band 7+: Strong performance with minor issues only (rare for most candidates)
- Band 6-6.5: Adequate but with noticeable limitations
- Band 5-5.5: Modest ability; frequent errors or limitations (MOST COMMON range)
- Band 4-4.5: Limited ability with serious problems
- BE REALISTIC: Most non-native speakers are Band 5.5-6.5
- DO NOT give Band 7+ unless performance is genuinely strong across ALL criteria

FEEDBACK REQUIREMENTS:
- "feedback" MUST be in VIETNAMESE (TIẾNG VIỆT), detailed (5-7 sentences minimum)
- For EACH criterion, list 2-3 SPECIFIC issues observed (bằng tiếng Việt)
- Include at least 5 concrete examples of errors or weaknesses
- Provide actionable improvement suggestions
- Start feedback with: "Dựa trên tiêu chuẩn chấm điểm IELTS Speaking..."
- Use Vietnamese for ALL feedback text

RULES:
- Band scores MUST be between 0-9 with 0.5 increments (e.g., 5.0, 5.5, 6.0, 6.5, 7.0)
- "band_score" is the average of 4 criteria, rounded to nearest 0.5
- BE CONSERVATIVE - compare strictly against official band descriptors
- Output ONLY the JSON object with these EXACT field names';

    // IELTS GENERATION JSON FORMATS
    private const JSON_FORMAT_IELTS_LISTENING = '

CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks (no ```json), or conversational responses. Output ONLY the raw JSON.

REQUIRED JSON OUTPUT FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "title": "Part title (e.g., \'A Conversation about University Accommodation\')",
  "audio_transcript": "Full transcript of the conversation/monologue...",
  "sections": [
    {
      "type": "form_completion",
      "startNumber": 1,
      "endNumber": 5,
      "instruction": "<p>Complete the <strong>form</strong> below. Write <strong>NO MORE THAN TWO WORDS AND/OR A NUMBER</strong> for each answer.</p>",
      "questions": [
        {"number": 1, "content": "Name:", "answer": "Sarah Johnson"},
        {"number": 2, "content": "Date of birth:", "answer": "15th March 1998"}
      ]
    },
    {
      "type": "multiple_choice",
      "startNumber": 6,
      "endNumber": 10,
      "instruction": "<p>Choose the <strong>correct letter</strong>, <strong>A, B or C</strong>.</p>",
      "questions": [
        {
          "number": 6,
          "content": "What is the main purpose?",
          "options": [
            {"label": "A", "content": "Option A"},
            {"label": "B", "content": "Option B"},
            {"label": "C", "content": "Option C"}
          ],
          "answer": "B"
        }
      ]
    }
  ]
}

RULES:
1. Follow IELTS Listening format (4 parts, 40 questions total)
2. Include diverse question types: form completion, multiple choice, matching, labeling, sentence completion
3. Answers must be directly from the transcript
4. Use "content" field for ALL question text (form labels, MCQ questions, etc.)
5. MUST include "instruction" field for each section with HTML formatting - use <strong> tags to highlight important words (e.g., question type, word limits, key instructions)
6. All field names MUST match the structure exactly (case-sensitive)
7. Output ONLY the JSON object with these EXACT field names, nothing else
8. NO markdown formatting, NO explanatory text before or after JSON';

    private const JSON_FORMAT_IELTS_READING = '

CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks (no ```json), or conversational responses. Output ONLY the raw JSON.

REQUIRED JSON OUTPUT FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "title": "Passage title",
  "content": "Full reading passage text (800-1000 words)...",
  "questions": [
    {
      "type": "true_false_ng",
      "startNumber": 1,
      "endNumber": 4,
      "questions": [
        {"number": 1, "statement": "Statement text", "answer": "TRUE"},
        {"number": 2, "statement": "Statement text", "answer": "FALSE"},
        {"number": 3, "statement": "Statement text", "answer": "NOT GIVEN"}
      ]
    },
    {
      "type": "multiple_choice",
      "startNumber": 5,
      "endNumber": 7,
      "questions": [
        {
          "number": 5,
          "question": "Question text?",
          "options": [
            {"label": "A", "content": "Option A"},
            {"label": "B", "content": "Option B"},
            {"label": "C", "content": "Option C"},
            {"label": "D", "content": "Option D"}
          ],
          "answer": "B"
        }
      ]
    },
    {
      "type": "matching_headings",
      "startNumber": 8,
      "endNumber": 11,
      "headings": [
        {"numeral": "i", "text": "Heading 1"},
        {"numeral": "ii", "text": "Heading 2"},
        {"numeral": "iii", "text": "Heading 3"},
        {"numeral": "iv", "text": "Heading 4 (distractor)"}
      ],
      "questions": [
        {"number": 8, "paragraphRef": "A", "answer": "ii"}
      ]
    }
  ]
}

RULES:
1. Passage should be 800-1000 words, academic style
2. Include diverse question types: True/False/NG, Multiple Choice, Matching Headings, Sentence Completion, etc.
3. For matching_headings: provide MORE headings than questions (distractors)
4. Total 13-14 questions per passage
5. All field names MUST match the structure exactly (case-sensitive)
6. Output ONLY the JSON object with these EXACT field names, nothing else
7. NO markdown formatting, NO explanatory text before or after JSON';

    private const JSON_FORMAT_IELTS_WRITING = '

CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks (no ```json), or conversational responses. Output ONLY the raw JSON.

REQUIRED JSON OUTPUT FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "task1": {
    "type": "academic_graph",
    "title": "Task 1 - Graph/Chart/Diagram Description",
    "prompt": "The graph below shows... Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.",
    "data_description": "Description of the visual data (graph, chart, table, etc.)",
    "sample_answer": "Sample band 7-8 answer (optional)",
    "band_descriptors": {
      "task_achievement": "Clear overview with key features",
      "coherence_cohesion": "Logical organization with clear progression",
      "lexical_resource": "Appropriate vocabulary for data description",
      "grammar": "Mix of simple and complex sentences"
    }
  },
  "task2": {
    "type": "opinion_essay",
    "title": "Task 2 - Essay",
    "prompt": "Some people believe that... To what extent do you agree or disagree? Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.",
    "topic_keywords": ["education", "technology", "society"],
    "sample_answer": "Sample band 7-8 answer (optional)",
    "band_descriptors": {
      "task_response": "Fully addresses all parts of the task",
      "coherence_cohesion": "Clear progression with appropriate paragraphing",
      "lexical_resource": "Wide range of vocabulary used naturally",
      "grammar": "Wide range of structures with flexibility"
    }
  }
}

RULES:
1. Task 1: 150 words minimum (graph, chart, table, diagram, or process)
2. Task 2: 250 words minimum (opinion, discussion, problem-solution, or two-part question)
3. Include band descriptors for assessment criteria
4. All field names MUST match the structure exactly (case-sensitive)
5. Output ONLY the JSON object with these EXACT field names, nothing else
6. NO markdown formatting, NO explanatory text before or after JSON';

    private const JSON_FORMAT_IELTS_SPEAKING = '

CRITICAL: Respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks (no ```json), or conversational responses. Output ONLY the raw JSON.

REQUIRED JSON OUTPUT FORMAT (MUST USE EXACTLY THIS STRUCTURE):
{
  "examiner_name": "Dr. Sarah Williams",
  "test_duration": "11-14 minutes",
  "parts": [
    {
      "part": 1,
      "title": "Introduction and Interview",
      "duration": "4-5 minutes",
      "topics": ["Home/Accommodation", "Work/Study", "Hobbies"],
      "questions": [
        "Let\'s talk about where you live. Do you live in a house or an apartment?",
        "What do you like most about living there?",
        "How long have you lived there?"
      ]
    },
    {
      "part": 2,
      "title": "Individual Long Turn",
      "duration": "3-4 minutes",
      "cue_card": {
        "topic": "Describe a memorable event in your life",
        "prompts": [
          "What the event was",
          "When and where it happened",
          "Who was involved",
          "Why it was memorable"
        ],
        "preparation_time": "1 minute",
        "speaking_time": "1-2 minutes"
      },
      "follow_up_questions": [
        "Do you often think about this event?",
        "Has this event influenced your life in any way?"
      ]
    },
    {
      "part": 3,
      "title": "Two-way Discussion",
      "duration": "4-5 minutes",
      "topic": "Events and Celebrations",
      "questions": [
        "Why do people celebrate special events?",
        "How have celebrations changed in your country over the years?",
        "Do you think technology has changed the way people celebrate?",
        "What role do traditions play in modern celebrations?"
      ]
    }
  ]
}

RULES:
1. Part 1: 4-5 familiar topics with 4-5 questions each
2. Part 2: Clear cue card with 1 minute prep, 1-2 minutes speaking
3. Part 3: Abstract discussion questions related to Part 2 topic
4. Include examiner name for personalization
5. All field names MUST match the structure exactly (case-sensitive)
6. Output ONLY the JSON object with these EXACT field names, nothing else
7. NO markdown formatting, NO explanatory text before or after JSON';

    /**
     * Get the appropriate JSON format template for a module
     */
    private function getJsonFormatTemplate(string $module): string
    {
        return match($module) {
            'prompt_writing_grading' => self::JSON_FORMAT_WRITING_GRADING,
            'prompt_speaking_grading' => self::JSON_FORMAT_SPEAKING_GRADING,
            'prompt_ielts_listening' => self::JSON_FORMAT_IELTS_LISTENING,
            'prompt_ielts_reading' => self::JSON_FORMAT_IELTS_READING,
            'prompt_ielts_writing' => self::JSON_FORMAT_IELTS_WRITING,
            'prompt_ielts_speaking' => self::JSON_FORMAT_IELTS_SPEAKING,
            default => '',
        };
    }

    /**
     * Load prompt from database and append JSON format
     */
    private function loadPromptWithFormat(string $module, string $fallbackInstructions): string
    {
        $userId = auth()->id();

        // Try to load from database
        $aiPrompt = \App\Models\Examination\AIPrompt::where('module', $module)
            ->where('created_by', $userId)
            ->first();

        // Use database prompt or fallback to default instructions
        $instructions = $aiPrompt ? $aiPrompt->prompt : $fallbackInstructions;

        // Append JSON format template
        $jsonFormat = $this->getJsonFormatTemplate($module);

        return $instructions . $jsonFormat;
    }

    /**
     * Upload image to storage
     */
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|image|max:10240', // 10MB max
                'type' => 'nullable|string'
            ]);

            $file = $request->file('file');
            $type = $request->input('type', 'general');

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "examination/{$type}/" . date('Y/m') . "/{$filename}";

            // Store file
            Storage::disk('public')->put($path, file_get_contents($file));

            $url = Storage::disk('public')->url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate image using AI (OpenAI DALL-E or similar)
     */
    public function generateImage(Request $request)
    {
        set_time_limit(300); // Allow up to 5 minutes for image generation

        try {
            $validated = $request->validate([
                'prompt' => 'required|string|max:4000',
                'provider' => 'required|string|in:openai,anthropic,azure',
                'api_key' => 'required|string',
                'visual_type' => 'nullable|string'
            ]);

            $prompt = $validated['prompt'];
            $provider = $validated['provider'];
            $apiKey = $validated['api_key'];
            $visualType = $validated['visual_type'] ?? 'chart';

            // Enhance prompt for better IELTS chart generation
            $enhancedPrompt = $this->enhanceImagePrompt($prompt, $visualType);

            if ($provider === 'openai') {
                $imageUrl = $this->generateWithDallE($enhancedPrompt, $apiKey);
            } else {
                // Anthropic doesn't support image generation directly
                // We could use a fallback or return error
                return response()->json([
                    'success' => false,
                    'message' => 'Anthropic không hỗ trợ tạo ảnh. Vui lòng sử dụng OpenAI.'
                ], 400);
            }

            if (!$imageUrl) {
                throw new \Exception('Failed to generate image');
            }

            // Download and save the generated image locally
            $savedPath = $this->downloadAndSaveImage($imageUrl, $visualType);

            return response()->json([
                'success' => true,
                'image_url' => Storage::disk('public')->url($savedPath),
                'image_path' => $savedPath,
                'original_url' => $imageUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Image generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo ảnh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate IELTS Writing Task prompt
     */
    public function generatePrompt(Request $request)
    {
        set_time_limit(120); // Allow up to 2 minutes for AI generation

        try {
            $validated = $request->validate([
                'type' => 'required|string|in:writing_task1,writing_task2',
                'visual_type' => 'nullable|string',
                'essay_type' => 'nullable|string|in:opinion,discussion,advantages,problem,twoPart',
                'description' => 'required|string',
                'provider' => 'nullable|string|in:openai,anthropic,azure',
                'api_key' => 'nullable|string'
            ]);

            // Get credentials from database or request
            $credentials = $this->getAiCredentials();
            $provider = $validated['provider'] ?? $credentials['provider'] ?? null;
            $apiKey = $validated['api_key'] ?? $credentials['api_key'] ?? null;

            if (!$provider || !$apiKey) {
                throw new \Exception('Chưa cấu hình AI. Vui lòng vào Thiết lập AI để cấu hình.');
            }

            $systemPrompt = $this->getPromptGeneratorSystem(
                $validated['type'],
                $validated['visual_type'] ?? null,
                $validated['essay_type'] ?? null
            );
            $userPrompt = "Generate an IELTS Writing Task prompt based on this description: {$validated['description']}";

            $response = $this->callAI(
                $provider,
                $apiKey,
                $systemPrompt,
                $userPrompt
            );

            return response()->json([
                'success' => true,
                'prompt' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Prompt generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate chart data using AI, then render with QuickChart
     * For bar_chart, line_graph, pie_chart, table
     */
    public function generateChartData(Request $request)
    {
        set_time_limit(180); // Allow up to 3 minutes for AI generation

        try {
            $validated = $request->validate([
                'visual_type' => 'required|string|in:bar_chart,line_graph,pie_chart,table',
                'topic' => 'required|string|max:500',
                'provider' => 'nullable|string|in:openai,anthropic,azure',
                'api_key' => 'nullable|string'
            ]);

            $visualType = $validated['visual_type'];
            $topic = $validated['topic'];

            // Get credentials from database or request
            $credentials = $this->getAiCredentials();
            $provider = $validated['provider'] ?? $credentials['provider'] ?? null;
            $apiKey = $validated['api_key'] ?? $credentials['api_key'] ?? null;

            if (!$provider || !$apiKey) {
                throw new \Exception('Chưa cấu hình AI. Vui lòng vào Thiết lập AI để cấu hình.');
            }

            // Get AI to generate chart data
            $systemPrompt = $this->getChartDataSystemPrompt($visualType);
            $userPrompt = "Create chart data for this topic: {$topic}";

            $response = $this->callAI(
                $provider,
                $apiKey,
                $systemPrompt,
                $userPrompt,
                1500
            );

            // Parse JSON from AI response
            $chartData = $this->parseChartDataFromAI($response, $visualType);

            if (!$chartData) {
                throw new \Exception('Could not parse chart data from AI response');
            }

            return response()->json([
                'success' => true,
                'chart_data' => $chartData,
                'raw_response' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate chart image using QuickChart.io from provided data
     */
    public function generateChart(Request $request)
    {
        // Extend PHP execution time for chart generation
        set_time_limit(120);

        try {
            $validated = $request->validate([
                'visual_type' => 'required|string|in:bar_chart,line_graph,pie_chart,table',
                'chart_data' => 'required|array'
            ]);

            $visualType = $validated['visual_type'];
            $chartData = $validated['chart_data'];

            Log::info('generateChart started', ['visual_type' => $visualType]);

            // Build QuickChart configuration
            $chartConfig = $this->buildQuickChartConfig($visualType, $chartData);

            Log::info('Calling QuickChart API...');

            // Generate image via QuickChart
            $imageUrl = $this->generateWithQuickChart($chartConfig);

            Log::info('QuickChart API completed', ['image_url' => $imageUrl ? 'success' : 'null']);

            if (!$imageUrl) {
                throw new \Exception('Failed to generate chart image');
            }

            // Check if QuickChart already saved the image (returns local storage URL)
            // In that case, extract the path directly instead of downloading again
            if (str_contains($imageUrl, '/storage/')) {
                // Extract path from local URL - get everything after /storage/
                if (preg_match('#/storage/(.+)$#', $imageUrl, $matches)) {
                    $savedPath = $matches[1];
                } else {
                    $savedPath = $imageUrl;
                }
                Log::info('Image already saved locally', ['path' => $savedPath]);
            } else {
                // External URL (like https://quickchart.io/...) - download and save locally
                $savedPath = $this->downloadAndSaveImage($imageUrl, $visualType);
            }

            return response()->json([
                'success' => true,
                'image_url' => Storage::disk('public')->url($savedPath),
                'image_path' => $savedPath,
                'chart_config' => $chartConfig
            ]);
        } catch (\Exception $e) {
            Log::error('Chart generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi tạo biểu đồ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system prompt for AI to generate chart data
     */
    private function getChartDataSystemPrompt(string $visualType): string
    {
        $examples = [
            'bar_chart' => '{
  "title": "Percentage of people using different transport in 4 cities",
  "subtitle": "Year 2023",
  "xAxisLabel": "Cities",
  "yAxisLabel": "Percentage (%)",
  "labels": ["London", "Paris", "Tokyo", "Beijing"],
  "datasets": [
    {"label": "Car", "data": [45, 38, 22, 52]},
    {"label": "Public Transport", "data": [35, 42, 58, 30]},
    {"label": "Bicycle", "data": [12, 15, 8, 10]}
  ]
}',
            'line_graph' => '{
  "title": "Internet usage by age group",
  "subtitle": "2010-2023",
  "xAxisLabel": "Year",
  "yAxisLabel": "Percentage (%)",
  "labels": ["2010", "2013", "2016", "2019", "2022", "2023"],
  "datasets": [
    {"label": "18-34 years", "data": [65, 75, 85, 92, 96, 98]},
    {"label": "35-54 years", "data": [45, 55, 68, 78, 85, 88]},
    {"label": "55+ years", "data": [15, 25, 38, 52, 65, 72]}
  ]
}',
            'pie_chart' => '{
  "title": "Household expenditure distribution",
  "charts": [
    {
      "subtitle": "1980",
      "data": [
        {"label": "Housing", "value": 25},
        {"label": "Food", "value": 35},
        {"label": "Transport", "value": 15},
        {"label": "Entertainment", "value": 10},
        {"label": "Others", "value": 15}
      ]
    },
    {
      "subtitle": "2020",
      "data": [
        {"label": "Housing", "value": 38},
        {"label": "Food", "value": 18},
        {"label": "Transport", "value": 20},
        {"label": "Entertainment", "value": 14},
        {"label": "Others", "value": 10}
      ]
    }
  ]
}',
            'table' => '{
  "title": "Number of visitors to tourist attractions (millions)",
  "headers": ["Country", "Attraction", "2019", "2020", "2021", "2022", "2023"],
  "rows": [
    ["France", "Eiffel Tower", "6.2", "1.5", "3.8", "5.9", "6.8"],
    ["USA", "Statue of Liberty", "4.5", "0.8", "2.1", "3.9", "4.2"],
    ["China", "Great Wall", "10.2", "2.3", "5.6", "7.8", "9.5"],
    ["UK", "Tower of London", "2.8", "0.4", "1.2", "2.4", "2.9"],
    ["Italy", "Colosseum", "7.5", "1.1", "3.2", "6.1", "7.2"]
  ]
}'
        ];

        $example = $examples[$visualType] ?? $examples['bar_chart'];

        return <<<PROMPT
You are a data generator for IELTS Writing Task 1 charts. Generate realistic, exam-appropriate data.

Rules:
1. Data should be realistic and make logical sense
2. Numbers should show clear trends or comparisons (for good Task 1 analysis)
3. Use appropriate units and ranges
4. Include 4-6 data points/categories typically
5. Return ONLY valid JSON, no markdown, no explanation

Output format for {$visualType}:
{$example}

IMPORTANT: Return ONLY the JSON object, nothing else. No ```json markers, no explanations.
PROMPT;
    }

    /**
     * Parse chart data from AI response
     */
    private function parseChartDataFromAI(string $response, string $visualType): ?array
    {
        // Clean up response - remove markdown code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse chart data JSON: ' . json_last_error_msg(), ['response' => $response]);
            return null;
        }

        return $data;
    }

    /**
     * Build QuickChart.io configuration from chart data
     */
    private function buildQuickChartConfig(string $visualType, array $chartData): array
    {
        // IELTS-style colors (grayscale)
        $colors = [
            'rgba(50, 50, 50, 0.8)',      // Dark gray
            'rgba(120, 120, 120, 0.8)',   // Medium gray
            'rgba(180, 180, 180, 0.8)',   // Light gray
            'rgba(80, 80, 80, 0.8)',      // Another gray
            'rgba(150, 150, 150, 0.8)',   // Another gray
        ];

        $borderColors = [
            'rgba(30, 30, 30, 1)',
            'rgba(100, 100, 100, 1)',
            'rgba(160, 160, 160, 1)',
            'rgba(60, 60, 60, 1)',
            'rgba(130, 130, 130, 1)',
        ];

        switch ($visualType) {
            case 'bar_chart':
                return $this->buildBarChartConfig($chartData, $colors, $borderColors);

            case 'line_graph':
                return $this->buildLineGraphConfig($chartData, $borderColors);

            case 'pie_chart':
                return $this->buildPieChartConfig($chartData, $colors, $borderColors);

            case 'table':
                return $this->buildTableConfig($chartData);

            default:
                throw new \Exception("Unsupported chart type: {$visualType}");
        }
    }

    private function buildBarChartConfig(array $data, array $colors, array $borderColors): array
    {
        $datasets = [];
        foreach ($data['datasets'] ?? [] as $i => $dataset) {
            $datasets[] = [
                'label' => $dataset['label'] ?? "Series " . ($i + 1),
                'data' => $dataset['data'] ?? [],
                'backgroundColor' => $colors[$i % count($colors)],
                'borderColor' => $borderColors[$i % count($borderColors)],
                'borderWidth' => 1
            ];
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $data['labels'] ?? [],
                'datasets' => $datasets
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => !empty($data['title']),
                        'text' => $data['title'] ?? '',
                        'font' => ['size' => 14, 'weight' => 'bold']
                    ],
                    'subtitle' => [
                        'display' => !empty($data['subtitle']),
                        'text' => $data['subtitle'] ?? ''
                    ]
                ],
                'scales' => [
                    'x' => [
                        'title' => [
                            'display' => !empty($data['xAxisLabel']),
                            'text' => $data['xAxisLabel'] ?? ''
                        ],
                        'grid' => ['color' => 'rgba(0,0,0,0.1)']
                    ],
                    'y' => [
                        'title' => [
                            'display' => !empty($data['yAxisLabel']),
                            'text' => $data['yAxisLabel'] ?? ''
                        ],
                        'grid' => ['color' => 'rgba(0,0,0,0.1)'],
                        'beginAtZero' => true
                    ]
                ]
            ]
        ];
    }

    private function buildLineGraphConfig(array $data, array $borderColors): array
    {
        $lineStyles = [
            [],                          // Solid
            [5, 5],                       // Dashed
            [2, 2],                       // Dotted
            [10, 5, 2, 5],               // Dash-dot
        ];

        $datasets = [];
        foreach ($data['datasets'] ?? [] as $i => $dataset) {
            $datasets[] = [
                'label' => $dataset['label'] ?? "Series " . ($i + 1),
                'data' => $dataset['data'] ?? [],
                'borderColor' => $borderColors[$i % count($borderColors)],
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'borderDash' => $lineStyles[$i % count($lineStyles)],
                'pointRadius' => 4,
                'pointBackgroundColor' => $borderColors[$i % count($borderColors)],
                'fill' => false,
                'tension' => 0.1
            ];
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $data['labels'] ?? [],
                'datasets' => $datasets
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => !empty($data['title']),
                        'text' => $data['title'] ?? '',
                        'font' => ['size' => 14, 'weight' => 'bold']
                    ],
                    'subtitle' => [
                        'display' => !empty($data['subtitle']),
                        'text' => $data['subtitle'] ?? ''
                    ]
                ],
                'scales' => [
                    'x' => [
                        'title' => [
                            'display' => !empty($data['xAxisLabel']),
                            'text' => $data['xAxisLabel'] ?? ''
                        ],
                        'grid' => ['color' => 'rgba(0,0,0,0.1)']
                    ],
                    'y' => [
                        'title' => [
                            'display' => !empty($data['yAxisLabel']),
                            'text' => $data['yAxisLabel'] ?? ''
                        ],
                        'grid' => ['color' => 'rgba(0,0,0,0.1)']
                    ]
                ]
            ]
        ];
    }

    private function buildPieChartConfig(array $data, array $colors, array $borderColors): array
    {
        // For IELTS, often have 2 pie charts side by side
        // QuickChart supports this via chart configuration
        if (isset($data['charts']) && count($data['charts']) > 1) {
            // Multiple pie charts - return as comparison
            $charts = [];
            foreach ($data['charts'] as $chartInfo) {
                $labels = array_column($chartInfo['data'] ?? [], 'label');
                $values = array_column($chartInfo['data'] ?? [], 'value');
                $charts[] = [
                    'subtitle' => $chartInfo['subtitle'] ?? '',
                    'labels' => $labels,
                    'data' => $values
                ];
            }

            // For multiple pies, we'll generate them separately and combine
            // For now, return the first one
            return [
                'type' => 'pie',
                'data' => [
                    'labels' => $charts[0]['labels'] ?? [],
                    'datasets' => [[
                        'data' => $charts[0]['data'] ?? [],
                        'backgroundColor' => array_slice($colors, 0, count($charts[0]['labels'] ?? [])),
                        'borderColor' => array_slice($borderColors, 0, count($charts[0]['labels'] ?? [])),
                        'borderWidth' => 1
                    ]]
                ],
                'options' => [
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => ($data['title'] ?? '') . ' - ' . ($charts[0]['subtitle'] ?? ''),
                            'font' => ['size' => 14, 'weight' => 'bold']
                        ],
                        'datalabels' => [
                            'display' => true,
                            'formatter' => 'function(value) { return value + "%"; }',
                            'color' => '#000'
                        ]
                    ]
                ],
                '_multiple_charts' => $charts // Store for frontend to handle
            ];
        }

        // Single pie chart
        $labels = array_column($data['data'] ?? [], 'label');
        $values = array_column($data['data'] ?? [], 'value');

        return [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                    'borderColor' => array_slice($borderColors, 0, count($labels)),
                    'borderWidth' => 1
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => !empty($data['title']),
                        'text' => $data['title'] ?? '',
                        'font' => ['size' => 14, 'weight' => 'bold']
                    ]
                ]
            ]
        ];
    }

    private function buildTableConfig(array $data): array
    {
        // Tables are special - we'll generate an HTML table and convert to image
        // For now, return the data structure
        return [
            'type' => 'table',
            'title' => $data['title'] ?? '',
            'headers' => $data['headers'] ?? [],
            'rows' => $data['rows'] ?? []
        ];
    }

    /**
     * Generate chart image using QuickChart.io
     */
    private function generateWithQuickChart(array $config): ?string
    {
        // Handle table separately (need to generate HTML image)
        if (($config['type'] ?? '') === 'table') {
            return $this->generateTableImage($config);
        }

        $chartConfig = json_encode($config);

        // QuickChart URL
        $url = 'https://quickchart.io/chart';

        Log::info('Sending request to QuickChart', ['config_length' => strlen($chartConfig)]);

        $response = Http::withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(60)->post($url, [
            'chart' => $chartConfig,
            'width' => 800,
            'height' => 600,
            'backgroundColor' => 'white',
            'format' => 'png'
        ]);

        Log::info('QuickChart response received', [
            'status' => $response->status(),
            'successful' => $response->successful()
        ]);

        if ($response->successful()) {
            // QuickChart returns the image directly or a URL
            $contentType = $response->header('Content-Type');

            if (str_contains($contentType, 'image')) {
                // Direct image - save it
                Log::info('QuickChart returned image directly');
                $filename = Str::uuid() . '.png';
                $path = "examination/ielts_writing/chart/" . date('Y/m') . "/{$filename}";
                Storage::disk('public')->put($path, $response->body());
                return Storage::disk('public')->url($path);
            }

            // URL response
            Log::info('QuickChart returned URL');
            return $response->json()['url'] ?? null;
        }

        // Fallback: Use GET request with encoded config
        Log::info('QuickChart POST failed, using GET fallback');
        $encodedConfig = urlencode($chartConfig);
        return "https://quickchart.io/chart?c={$encodedConfig}&w=800&h=600&bkg=white";
    }

    /**
     * Generate table as image (using QuickChart's table feature or HTML)
     */
    private function generateTableImage(array $config): string
    {
        // Build HTML table
        $title = $config['title'] ?? '';
        $headers = $config['headers'] ?? [];
        $rows = $config['rows'] ?? [];

        $html = "<table style='border-collapse:collapse;font-family:Arial,sans-serif;'>";

        if ($title) {
            $colspan = count($headers);
            $html .= "<caption style='font-weight:bold;font-size:14px;padding:10px;'>{$title}</caption>";
        }

        // Headers
        $html .= "<tr>";
        foreach ($headers as $header) {
            $html .= "<th style='border:1px solid #333;padding:8px 12px;background:#e0e0e0;font-weight:bold;'>{$header}</th>";
        }
        $html .= "</tr>";

        // Rows
        foreach ($rows as $row) {
            $html .= "<tr>";
            foreach ($row as $cell) {
                $html .= "<td style='border:1px solid #333;padding:8px 12px;text-align:center;'>{$cell}</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</table>";

        // Use QuickChart's HTML to image feature
        $response = Http::withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(30)->post('https://quickchart.io/graphviz', [
            'format' => 'png',
            'width' => 800,
            'graph' => 'digraph{node[shape=none];n[label=<' . $html . '>]}'
        ]);

        if ($response->successful() && str_contains($response->header('Content-Type'), 'image')) {
            $filename = Str::uuid() . '.png';
            $path = "examination/ielts_writing/table/" . date('Y/m') . "/{$filename}";
            Storage::disk('public')->put($path, $response->body());
            return Storage::disk('public')->url($path);
        }

        // Fallback - return a placeholder or error
        throw new \Exception('Failed to generate table image');
    }

    /**
     * Generate sample answer for IELTS Writing
     */
    public function generateSample(Request $request)
    {
        set_time_limit(180); // Allow up to 3 minutes for AI generation

        try {
            $validated = $request->validate([
                'type' => 'required|string|in:writing_task1,writing_task2',
                'prompt' => 'required|string',
                'visual_type' => 'nullable|string',
                'min_words' => 'nullable|integer|min:100|max:500',
                'provider' => 'nullable|string|in:openai,anthropic,azure',
                'api_key' => 'nullable|string'
            ]);

            // Get credentials from database or request
            $credentials = $this->getAiCredentials();
            $provider = $validated['provider'] ?? $credentials['provider'] ?? null;
            $apiKey = $validated['api_key'] ?? $credentials['api_key'] ?? null;

            if (!$provider || !$apiKey) {
                throw new \Exception('Chưa cấu hình AI. Vui lòng vào Thiết lập AI để cấu hình.');
            }

            $minWords = $validated['min_words'] ?? ($validated['type'] === 'writing_task1' ? 150 : 250);
            $systemPrompt = $this->getSampleGeneratorSystem($validated['type'], $validated['visual_type'], $minWords);
            $userPrompt = "Write a Band 8-9 sample answer for this IELTS task:\n\n{$validated['prompt']}";

            $response = $this->callAI(
                $provider,
                $apiKey,
                $systemPrompt,
                $userPrompt,
                2000 // More tokens for longer response
            );

            // Format as HTML paragraphs
            $formatted = '<p>' . implode('</p><p>', array_filter(explode("\n\n", trim($response)))) . '</p>';

            return response()->json([
                'success' => true,
                'sample' => $formatted
            ]);
        } catch (\Exception $e) {
            Log::error('Sample generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhance the image prompt for better results - IELTS authentic style
     * IELTS charts are typically: simple, minimalist, black/white/grayscale with maybe 1-2 accent colors
     * No decorative elements, no 3D effects, basic fonts, clear gridlines
     */
    private function enhanceImagePrompt(string $prompt, string $visualType): string
    {
        $typeSpecific = [
            'bar_chart' => 'Simple 2D bar chart. Plain rectangular bars (no 3D, no gradients). Black axis lines with gridlines. Bars should be solid single colors (gray, black, or maximum 2-3 simple colors like gray and dark gray). Clear numeric labels on Y-axis. Category labels on X-axis. Simple legend if comparing groups.',
            'line_graph' => 'Simple 2D line graph. Thin black lines connecting data points. Small circular markers at data points. Black axis lines with gridlines. Maximum 2-3 lines if comparing, using black, gray, and dotted styles to differentiate. Clear numeric labels on Y-axis. Time periods on X-axis.',
            'pie_chart' => 'Simple 2D pie chart (no 3D, no explosion effects). Segments in grayscale shades or simple patterns (solid, striped, dotted). Percentage labels next to or inside each segment. Simple legend with labels. Clean white background.',
            'table' => 'Simple data table with thin black borders. White background. Clear header row in light gray. Plain black text. No colors, no shading except header. Neat rows and columns with consistent spacing.',
            'map' => 'Simple black and white map diagram. Basic outlines for buildings, roads, paths. Simple geometric shapes (rectangles for buildings, lines for roads). Clear text labels. Compass direction indicator. No decorative elements, no colors except maybe light gray shading.',
            'process' => 'Simple flowchart/process diagram. Basic shapes (rectangles, circles, arrows). Black outlines on white background. Numbered steps. Simple arrows showing direction. Clear text labels. No decorative elements, no colors.'
        ];

        $baseStyle = "STYLE: Official IELTS exam diagram. White background. Black/gray only. NO colors, NO 3D, NO decorations. Simple academic style like Cambridge IELTS tests.";

        $typeHint = $typeSpecific[$visualType] ?? 'Create a simple, minimalist diagram suitable for an academic exam.';

        return "{$prompt}\n\n{$typeHint}\n\n{$baseStyle}";
    }

    /**
     * Generate image using OpenAI DALL-E
     */
    private function generateWithDallE(string $prompt, string $apiKey): ?string
    {
        // DALL-E 3 has a 4000 character limit for prompts
        if (strlen($prompt) > 4000) {
            $prompt = substr($prompt, 0, 3900) . '...';
            Log::warning('DALL-E prompt truncated to 4000 chars');
        }

        Log::info('DALL-E request', ['prompt_length' => strlen($prompt)]);

        // Retry logic for transient errors
        $maxRetries = 2;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json'
                ])
                ->withOptions([
                    'verify' => false, // Disable SSL verification for local dev (XAMPP issue)
                    'connect_timeout' => 60,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ],
                ])
                ->timeout(180) // Increase timeout for image generation
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'quality' => 'standard',
                    'response_format' => 'url'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'][0]['url'] ?? null;
                }

                $lastError = $response->json()['error']['message'] ?? $response->body();
                Log::error("DALL-E API error (attempt {$attempt}): " . $response->body());

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("DALL-E connection error (attempt {$attempt}): " . $e->getMessage());

                if ($attempt < $maxRetries) {
                    sleep(2); // Wait before retry
                }
            }
        }

        throw new \Exception('DALL-E API error after ' . $maxRetries . ' attempts: ' . $lastError);
    }

    /**
     * Download generated image and save locally
     */
    private function downloadAndSaveImage(string $url, string $visualType): string
    {
        Log::info('downloadAndSaveImage started', ['url' => substr($url, 0, 100)]);

        $imageContent = Http::withOptions([
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ],
        ])->timeout(90)->get($url)->body();

        Log::info('Image downloaded', ['size' => strlen($imageContent)]);

        $filename = Str::uuid() . '.png';
        $path = "examination/ielts_writing/{$visualType}/" . date('Y/m') . "/{$filename}";

        Storage::disk('public')->put($path, $imageContent);

        return $path;
    }

    /**
     * Get system prompt for prompt generation
     */
    private function getPromptGeneratorSystem(string $type, ?string $visualType, ?string $essayType = null): string
    {
        if ($type === 'writing_task1') {
            $visualTypeText = $visualType ? str_replace('_', ' ', $visualType) : 'chart';
            return <<<PROMPT
You are an IELTS Writing Task 1 question writer. Generate a professional exam question for the given {$visualTypeText}.

Format your response as HTML with proper paragraphs. Include:
1. Introduction describing what the {$visualTypeText} shows
2. Clear instruction about what candidates should do
3. Word count requirement (at least 150 words)

Example format:
<p>The {$visualTypeText} below shows [description of data].</p>
<p>Summarise the information by selecting and reporting the main features, and make comparisons where relevant.</p>
<p>Write at least 150 words.</p>
PROMPT;
        }

        // Task 2: Essay types with specific formats
        $essayTypePrompts = [
            'opinion' => [
                'instruction' => 'To what extent do you agree or disagree?',
                'format' => '<p>[A statement presenting a viewpoint or claim about the topic]</p>
<p>To what extent do you agree or disagree?</p>
<p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>
<p>Write at least 250 words.</p>'
            ],
            'discussion' => [
                'instruction' => 'Discuss both views and give your own opinion.',
                'format' => '<p>[Statement presenting two contrasting views on the topic]</p>
<p>Discuss both views and give your own opinion.</p>
<p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>
<p>Write at least 250 words.</p>'
            ],
            'advantages' => [
                'instruction' => 'Do the advantages outweigh the disadvantages?',
                'format' => '<p>[Statement about a trend, development, or phenomenon related to the topic]</p>
<p>What are the advantages and disadvantages of this?</p>
<p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>
<p>Write at least 250 words.</p>'
            ],
            'problem' => [
                'instruction' => 'What are the causes and solutions?',
                'format' => '<p>[Statement describing a problem or issue related to the topic]</p>
<p>What are the causes of this problem and what solutions can be suggested?</p>
<p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>
<p>Write at least 250 words.</p>'
            ],
            'twoPart' => [
                'instruction' => 'Answer both questions in your essay.',
                'format' => '<p>[Statement about the topic]</p>
<p>[First question about the topic]? [Second related question]?</p>
<p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p>
<p>Write at least 250 words.</p>'
            ],
        ];

        $essayConfig = $essayTypePrompts[$essayType] ?? $essayTypePrompts['opinion'];

        return <<<PROMPT
You are an IELTS Writing Task 2 question writer. Generate a professional essay question.

Essay type: {$essayConfig['instruction']}

Format your response as HTML with proper paragraphs. Follow this exact structure:
{$essayConfig['format']}

Important:
- Create a thought-provoking topic statement that is debatable
- Use formal, academic language
- Make sure the question is appropriate for IELTS test takers
- The topic should be general enough for anyone to write about (no specialized knowledge required)
PROMPT;
    }

    /**
     * Get system prompt for sample generation
     */
    private function getSampleGeneratorSystem(string $type, ?string $visualType, int $minWords): string
    {
        if ($type === 'writing_task1') {
            return <<<PROMPT
You are an IELTS examiner writing a Band 8-9 sample answer for Writing Task 1.

Write a model answer that:
- Is at least {$minWords} words
- Has clear paragraph structure (introduction, body paragraphs, optional conclusion)
- Uses appropriate academic vocabulary
- Includes relevant comparisons and key trends
- Uses varied sentence structures
- Has proper cohesive devices

Do NOT include any headings or labels. Just write the essay directly.
PROMPT;
        }

        return <<<PROMPT
You are an IELTS examiner writing a Band 8-9 sample answer for Writing Task 2.

Write a model essay that:
- Is at least {$minWords} words
- Has clear essay structure (introduction, body paragraphs, conclusion)
- Presents a clear position or discusses both views as required
- Uses sophisticated vocabulary and varied sentence structures
- Includes relevant examples to support arguments
- Has proper cohesive devices throughout

Do NOT include any headings or labels. Just write the essay directly.
PROMPT;
    }

    /**
     * Call AI API (OpenAI or Anthropic)
     */
    private function callAI(string $provider, string $apiKey, string $systemPrompt, string $userPrompt, int $maxTokens = 1000): string
    {
        // Get configured model from database
        $credentials = $this->getAiCredentials();
        $model = $credentials['model'] ?? 'gpt-5.1';

        if ($provider === 'openai') {
            // Check if using GPT-5 family (requires Responses API)
            $isGPT5Family = str_starts_with($model, 'gpt-5');

            if ($isGPT5Family) {
                return $this->callOpenAIResponsesGeneral($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
            }
            return $this->callOpenAI($apiKey, $model, $systemPrompt, $userPrompt, $maxTokens);
        }

        return $this->callAnthropic($apiKey, $credentials['model'] ?? 'claude-sonnet-4-20250514', $systemPrompt, $userPrompt, $maxTokens);
    }

    /**
     * Call OpenAI Responses API for GPT-5 models (general use)
     */
    private function callOpenAIResponsesGeneral(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens): string
    {
        set_time_limit(180); // Extend PHP timeout

        // Combine system prompt and user prompt for GPT-5.1 Responses API
        $fullPrompt = "System instructions:\n{$systemPrompt}\n\nUser request:\n{$userPrompt}";

        Log::info('GPT-5 Request', ['model' => $model, 'prompt_length' => strlen($fullPrompt)]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(150)->post('https://api.openai.com/v1/responses', [
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
        Log::info('GPT-5 General Response', ['response_keys' => array_keys($data)]);

        // Extract content from Responses API output with type checking
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

        return $content;
    }

    /**
     * Call OpenAI Chat Completions API for older models
     */
    private function callOpenAI(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'] ?? '';
        }

        throw new \Exception('OpenAI API error: ' . ($response->json()['error']['message'] ?? 'Unknown error'));
    }

    private function callAnthropic(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json()['content'] ?? [];
            return $content[0]['text'] ?? '';
        }

        throw new \Exception('Anthropic API error: ' . ($response->json()['error']['message'] ?? 'Unknown error'));
    }

    /**
     * Get AI settings for current user's branch
     */
    public function getAiSettings(Request $request)
    {
        try {
            $user = auth()->user();
            $branchId = $user->branch_id ?? 1; // Default to branch 1 if not set
            $module = $request->input('module', 'examination'); // Default to 'examination' for backward compatibility

            // Get all provider settings for this module
            $allSettings = AiSetting::getAllSettingsForModule($branchId, $module);

            if (empty($allSettings)) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Chưa cấu hình AI settings'
                ]);
            }

            // Format settings for each provider
            $formattedSettings = [];
            foreach ($allSettings as $provider => $setting) {
                $formattedSettings[$provider] = [
                    'provider' => $setting->provider,
                    'model' => $setting->model,
                    'has_api_key' => $setting->hasApiKey(),
                    'masked_api_key' => $setting->masked_api_key,
                    'settings' => $setting->settings,
                    'is_active' => $setting->is_active,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedSettings
            ]);
        } catch (\Exception $e) {
            Log::error('Get AI settings failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save AI settings for current user's branch
     */
    public function saveAiSettings(Request $request)
    {
        try {
            // DEBUG: Log incoming request data
            Log::info('💾 Save AI Settings Request', [
                'provider' => $request->input('provider'),
                'module' => $request->input('module'),
                'has_api_key' => !empty($request->input('api_key')),
                'model' => $request->input('model'),
                'all_data' => $request->all()
            ]);

            $validated = $request->validate([
                'provider' => 'required|string|in:openai,anthropic,azure',
                'api_key' => 'nullable|string|min:10',
                'model' => 'nullable|string',
                'settings' => 'nullable|array',
                'module' => 'nullable|string', // Allow module parameter
            ]);

            $user = auth()->user();
            $branchId = $user->branch_id ?? 1;
            $module = $validated['module'] ?? 'examination'; // Default to 'examination' for backward compatibility

            $setting = AiSetting::saveSettings($branchId, $module, $validated);

            Log::info("AI settings saved for branch {$branchId}, module {$module}, provider {$setting->provider}", [
                'provider' => $setting->provider,
                'has_api_key' => $setting->hasApiKey(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã lưu cấu hình AI thành công',
                'data' => [
                    'provider' => $setting->provider,
                    'model' => $setting->model,
                    'has_api_key' => $setting->hasApiKey(),
                    'masked_api_key' => $setting->masked_api_key,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Save AI settings failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI credentials from database for API calls
     */
    private function getAiCredentials(string $module = 'examination_generation'): ?array
    {
        $user = auth()->user();
        $branchId = $user->branch_id ?? 1;

        $setting = AiSetting::getSettings($branchId, $module);

        if (!$setting || !$setting->hasApiKey()) {
            return null;
        }

        return [
            'provider' => $setting->provider,
            'api_key' => $setting->api_key,
            'model' => $setting->model,
            'settings' => $setting->settings,
        ];
    }

    /**
     * Grade IELTS Writing answer with AI
     * Uses API credentials stored in database
     */
    public function gradeWithAI(Request $request)
    {
        set_time_limit(180); // Allow up to 3 minutes for AI grading

        try {
            $validated = $request->validate([
                'task' => 'required|string',
                'response' => 'required|string',
                'image_url' => 'nullable|string|url',  // Image URL for Task 1 charts/diagrams
                'provider' => 'nullable|string',
                'model' => 'nullable|string',
                'grading_prompt' => 'nullable|string',
            ]);

            // Get credentials from database (use grading module)
            // If provider is specified, use that specific provider
            $user = auth()->user();
            $branchId = $user->branch_id ?? 1;
            $requestedProvider = $validated['provider'] ?? null;

            if ($requestedProvider) {
                // Get settings for specific provider
                $setting = AiSetting::getSettingsByProvider($branchId, 'examination_grading', $requestedProvider);
            } else {
                // Fallback to first active provider
                $setting = AiSetting::getSettings($branchId, 'examination_grading');
            }

            if (!$setting || !$setting->hasApiKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình AI API Key cho provider này. Vui lòng vào Thiết lập AI để cấu hình.'
                ], 400);
            }

            $provider = $setting->provider;
            $apiKey = $setting->api_key;
            $model = $validated['model'] ?? $setting->model ?? 'gpt-5.1';

            // Default instructions (without JSON format - will be appended automatically)
            $defaultInstructions = <<<INSTRUCTIONS
You are a STRICT IELTS examiner with 15+ years of experience. Grade this IELTS Writing response using the OFFICIAL IELTS Band Descriptors with HIGH STANDARDS.

⚠️ CRITICAL GRADING RULES:
1. Be RIGOROUS and REALISTIC - DO NOT inflate scores
2. Band 7.0+ requires CONSISTENT excellence across ALL criteria
3. A SINGLE serious error can lower the band significantly
4. Common mistakes like repetition, off-topic content, poor cohesion, or basic vocabulary MUST be penalized
5. Task 1: Must describe ALL key features accurately with appropriate detail
6. Task 2: Must have clear position, well-developed arguments, and relevant examples

📊 EVALUATION CRITERIA (each 25%):

1. TASK ACHIEVEMENT/RESPONSE (TA/TR):
   - Band 7+: Fully addresses ALL parts of the task with well-developed, relevant ideas
   - Band 6: Addresses task but may be limited in development or relevance
   - Band 5: Only partially addresses task; ideas may be unclear or repetitive
   - PENALTIES: Off-topic (-1 band), Under word count (-0.5 to -1 band), Missing key features (-0.5 band)

2. COHERENCE & COHESION (CC):
   - Band 7+: Clear progression throughout; skillful use of cohesive devices; well-organized paragraphs
   - Band 6: Arranged coherently but cohesion may be faulty or mechanical
   - Band 5: Inadequate or inaccurate use of cohesive devices; may lack overall progression
   - PENALTIES: Poor paragraphing (-0.5 band), Overuse of linking words (-0.5 band), Unclear structure (-1 band)

3. LEXICAL RESOURCE (LR):
   - Band 7+: Wide range of vocabulary with FLEXIBILITY and PRECISION; rare minor errors
   - Band 6: Adequate range but lacks precision; some errors in word choice or spelling
   - Band 5: Limited range; noticeable errors; may impede meaning
   - PENALTIES: Repetitive vocabulary (-0.5 band), Word choice errors (-0.5 band per 3-4 errors), Basic vocabulary only (-1 band)

4. GRAMMATICAL RANGE & ACCURACY (GRA):
   - Band 7+: Wide variety of complex structures with good control; frequent error-free sentences
   - Band 6: Mix of simple and complex sentences with some accuracy; errors do not impede communication
   - Band 5: Limited range; frequent errors; may cause difficulty for reader
   - PENALTIES: Subject-verb agreement errors (-0.5 band), Tense errors (-0.5 band), Article errors (-0.5 band for multiple), Run-on sentences (-0.5 band)

⚠️ SCORING GUIDELINES:
- Band 9: Perfect or near-perfect (extremely rare)
- Band 8-8.5: Excellent with very minor slips
- Band 7-7.5: Good, competent, but not excellent (still has noticeable errors)
- Band 6-6.5: Adequate but clearly has limitations
- Band 5-5.5: Modest ability with frequent errors
- Below 5: Serious limitations

📋 GRADING PROCESS:
1. Count word count - penalize if significantly under requirement
2. Identify ALL errors (grammar, vocabulary, coherence, task response)
3. For EACH criterion, find specific weaknesses that lower the band
4. DO NOT give Band 7+ unless the work is genuinely strong across ALL areas
5. Compare against official band descriptors - be conservative

TASK:
{task}

STUDENT RESPONSE:
{response}

NOW GRADE STRICTLY - Focus on what prevents higher bands, not just strengths.
INSTRUCTIONS;

            // Load prompt from database (with JSON format auto-appended)
            $gradingPrompt = $validated['grading_prompt'] ??
                           $this->loadPromptWithFormat('prompt_writing_grading', $defaultInstructions);

            $prompt = str_replace(
                ['{task}', '{response}'],
                [$validated['task'], $validated['response']],
                $gradingPrompt
            );

            // Call AI based on provider and model
            $imageUrl = $validated['image_url'] ?? null;
            $result = $this->callGradingAI($provider, $apiKey, $model, $prompt, $imageUrl);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('AI grading failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chấm bằng AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call AI for grading with proper model handling
     */
    private function callGradingAI(string $provider, string $apiKey, string $model, string $prompt, ?string $imageUrl = null): array
    {
        if ($provider === 'openai') {
            // Check if using GPT-5 family (requires Responses API)
            $isGPT5Family = str_starts_with($model, 'gpt-5');

            if ($isGPT5Family) {
                return $this->callOpenAIResponses($apiKey, $model, $prompt, $imageUrl);
            } else {
                return $this->callOpenAIChatForGrading($apiKey, $model, $prompt, $imageUrl);
            }
        } else {
            return $this->callAnthropicForGrading($apiKey, $model, $prompt, $imageUrl);
        }
    }

    /**
     * Call OpenAI Responses API for GPT-5 models (grading)
     */
    private function callOpenAIResponses(string $apiKey, string $model, string $prompt, ?string $imageUrl = null): array
    {
        set_time_limit(180); // Extend PHP timeout

        Log::info('GPT-5 Grading Request', [
            'model' => $model, 
            'prompt_length' => strlen($prompt),
            'has_image' => !empty($imageUrl)
        ]);

        // Build content array
        $content = [
            ['type' => 'input_text', 'text' => $prompt]
        ];
        
        // Add image if provided (for Task 1 with charts/diagrams)
        if ($imageUrl) {
            // ✅ Convert image to base64 to avoid download issues
            try {
                // Download image from URL
                $imageData = file_get_contents($imageUrl);
                if ($imageData === false) {
                    throw new \Exception("Failed to download image from: {$imageUrl}");
                }
                
                // Detect mime type
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData);
                
                // Convert to base64
                $base64Image = base64_encode($imageData);
                $dataUri = "data:{$mimeType};base64,{$base64Image}";
                
                Log::info('✅ Image converted to base64', [
                    'original_url' => $imageUrl,
                    'mime_type' => $mimeType,
                    'size_bytes' => strlen($imageData),
                    'base64_length' => strlen($base64Image)
                ]);
                
                $content[] = ['type' => 'input_image', 'image_url' => $dataUri];
            } catch (\Exception $e) {
                Log::error('❌ Failed to convert image to base64', [
                    'error' => $e->getMessage(),
                    'image_url' => $imageUrl
                ]);
                // Try with URL as fallback
                $content[] = ['type' => 'input_image', 'image_url' => $imageUrl];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(180)->post('https://api.openai.com/v1/responses', [
            'model' => $model,
            'input' => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'reasoning' => [
                'effort' => 'medium'
            ],
            'max_output_tokens' => 6000  // Increased for detailed feedback with pronunciation data
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            Log::error('GPT-5 Grading API Error', ['status' => $response->status(), 'error' => $errorData]);
            throw new \Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? $response->status()));
        }

        $data = $response->json();
        Log::info('GPT-5 Grading Response', ['response_keys' => array_keys($data), 'full_response' => json_encode($data)]);

        // Extract content from Responses API output with type checking
        $content = '';

        // Responses API returns output array with role/content structure
        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (isset($item['role']) && $item['role'] === 'assistant' && isset($item['content'])) {
                    // Content is an array of content blocks
                    if (is_array($item['content'])) {
                        foreach ($item['content'] as $contentBlock) {
                            if (isset($contentBlock['type']) && $contentBlock['type'] === 'output_text' && isset($contentBlock['text'])) {
                                // Ensure text is actually a string
                                if (is_string($contentBlock['text'])) {
                                    $content = $contentBlock['text'];
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Fallback: try text field if it exists and is string
        if (!$content && isset($data['text']) && is_string($data['text'])) {
            $content = $data['text'];
        }

        // Fallback: try output_text field if it exists and is string
        if (!$content && isset($data['output_text']) && is_string($data['output_text'])) {
            $content = $data['output_text'];
        }

        if (empty($content)) {
            Log::error('Could not extract content from GPT-5 response', ['data' => $data]);
            throw new \Exception('Could not extract content from GPT-5 response');
        }

        return $this->parseGradingResponse($content);
    }

    /**
     * Call OpenAI Chat Completions API for grading
     */
    private function callOpenAIChatForGrading(string $apiKey, string $model, string $prompt, ?string $imageUrl = null): array
    {
        // Build message content
        $messageContent = $prompt;
        
        // If image URL provided and model supports vision (gpt-4-vision, gpt-4o, gpt-4-turbo)
        if ($imageUrl && (
            str_contains($model, 'vision') || 
            str_contains($model, 'gpt-4o') || 
            str_contains($model, 'gpt-4-turbo')
        )) {
            $messageContent = [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ];
        }
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $messageContent]],
            'temperature' => 0.3,
            'max_tokens' => 4000  // Increased for detailed feedback
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            throw new \Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? $response->status()));
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? '';
        return $this->parseGradingResponse($content);
    }

    /**
     * Call Anthropic API for grading
     */
    private function callAnthropicForGrading(string $apiKey, string $model, string $prompt, ?string $imageUrl = null): array
    {
        // Build message content
        $messageContent = $prompt;
        
        // If image URL provided, use Claude's vision format
        if ($imageUrl) {
            // Download image and convert to base64 for Claude
            try {
                $imageData = file_get_contents($imageUrl);
                $base64Image = base64_encode($imageData);
                
                // Detect image type from URL or content
                $imageType = 'image/jpeg'; // default
                if (str_contains($imageUrl, '.png')) {
                    $imageType = 'image/png';
                } elseif (str_contains($imageUrl, '.gif')) {
                    $imageType = 'image/gif';
                } elseif (str_contains($imageUrl, '.webp')) {
                    $imageType = 'image/webp';
                }
                
                $messageContent = [
                    ['type' => 'image', 'source' => [
                        'type' => 'base64',
                        'media_type' => $imageType,
                        'data' => $base64Image
                    ]],
                    ['type' => 'text', 'text' => $prompt]
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to load image for Claude vision, proceeding without image', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->withOptions([
            'verify' => false,
            'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
        ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4000,  // Increased for detailed feedback
            'messages' => [['role' => 'user', 'content' => $messageContent]]
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            throw new \Exception('Anthropic API error: ' . ($errorData['error']['message'] ?? $response->status()));
        }

        $content = $response->json()['content'][0]['text'] ?? '';
        return $this->parseGradingResponse($content);
    }

    /**
     * Parse grading response from AI
     */
    private function parseGradingResponse(string $content): array
    {
        if (empty($content)) {
            Log::error('AI returned empty response');
            throw new \Exception('AI returned empty response');
        }

        // Log the actual content for debugging
        Log::info('🤖 AI Response Content', ['content' => substr($content, 0, 1000), 'length' => strlen($content), 'full_content' => $content]);

        // Step 1: Try to remove markdown code blocks if present (```json ... ``` or ```...```)
        $cleaned = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', $content);

        // Step 2: Try multiple strategies to extract JSON
        $jsonString = null;
        
        // Strategy 1: Direct JSON extraction (most common)
        if (preg_match('/\{[\s\S]*\}/', $cleaned, $jsonMatch)) {
            $jsonString = $jsonMatch[0];
        }
        
        // Strategy 2: Look for JSON between specific markers
        if (!$jsonString && preg_match('/\{["\']band_score["\'][\s\S]*\}/', $cleaned, $jsonMatch)) {
            $jsonString = $jsonMatch[0];
        }
        
        // Strategy 3: Extract largest JSON object if multiple exist
        if (!$jsonString) {
            preg_match_all('/\{[^\{\}]*(?:\{[^\{\}]*\}[^\{\}]*)*\}/s', $cleaned, $allMatches);
            if (!empty($allMatches[0])) {
                // Find the longest JSON string (likely the complete one)
                $jsonString = collect($allMatches[0])->sortByDesc(function($item) {
                    return strlen($item);
                })->first();
            }
        }

        if (!$jsonString) {
            Log::error('❌ No JSON found in content', ['content' => substr($cleaned, 0, 500)]);
            throw new \Exception('Could not extract JSON from AI response');
        }
        
        // Fix potential encoding issues
        $jsonString = mb_convert_encoding($jsonString, 'UTF-8', 'UTF-8');
        
        // Log extracted JSON for debugging
        Log::info('📦 JSON extracted', ['json_length' => strlen($jsonString), 'json' => $jsonString]);

        // Try to parse JSON
        $parsed = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ JSON parse error', ['error' => json_last_error_msg(), 'json' => substr($jsonString, 0, 500)]);
            
            // Try to fix common JSON issues
            $fixedJson = $this->tryFixJsonIssues($jsonString);
            $parsed = json_decode($fixedJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON from AI: ' . json_last_error_msg());
            }
        }

        // ==============================================================
        // ROBUST FIELD MAPPING - Handle various AI response formats
        // ==============================================================
        
        Log::info('🔍 Raw parsed data', ['keys' => array_keys($parsed), 'full_data' => $parsed]);
        
        // Normalize field names - accept multiple variations
        $fieldMappings = [
            // Task Achievement / Response (different names in Task 1 vs Task 2)
            'task_achievement' => ['task_achievement', 'task_response', 'ta', 'tr', 'taskAchievement', 'taskResponse'],
            // Coherence & Cohesion
            'coherence_cohesion' => ['coherence_cohesion', 'coherence', 'cohesion', 'cc', 'coherenceCohesion'],
            // Lexical Resource
            'lexical_resource' => ['lexical_resource', 'lexical', 'vocabulary', 'lr', 'lexicalResource'],
            // Grammatical Range & Accuracy
            'grammatical_range' => ['grammatical_range', 'grammatical_accuracy', 'grammar', 'gra', 'gr', 'grammaticalRange', 'grammaticalAccuracy'],
            // Speaking specific
            'fluency_coherence' => ['fluency_coherence', 'fluency', 'fc', 'fluencyCoherence'],
            'pronunciation' => ['pronunciation', 'p'],
            // Common
            'band_score' => ['band_score', 'bandScore', 'overall_band', 'overall', 'score'],
            'feedback' => ['feedback', 'overall_feedback', 'comment', 'comments', 'analysis', 'detailed_feedback', 'detailedFeedback', 'overallFeedback'],
        ];

        $normalized = [];
        
        // Step 1: Map flat fields and detect nested format
        foreach ($fieldMappings as $standardField => $variations) {
            foreach ($variations as $variation) {
                if (isset($parsed[$variation])) {
                    $value = $parsed[$variation];
                    
                    // ✅ NEW: Handle nested format {score: X, feedback: "..."}
                    if (is_array($value) && isset($value['score'])) {
                        Log::info("🎯 Detected nested format for '{$standardField}'", ['value' => $value]);
                        $normalized[$standardField] = $value; // Keep as nested object
                    } else {
                        $normalized[$standardField] = $value;
                    }
                    
                    Log::info("✅ Mapped '{$variation}' -> '{$standardField}'", [
                        'value_type' => is_array($value) ? 'nested_object' : 'flat_value',
                        'value' => is_array($value) ? json_encode($value) : $value
                    ]);
                    break;
                }
            }
        }

        // Detect if it's Speaking or Writing based on fields present
        $isSpeaking = isset($normalized['fluency_coherence']) || isset($normalized['pronunciation']);

        Log::info('📊 Grading type detected', ['is_speaking' => $isSpeaking, 'normalized_keys' => array_keys($normalized)]);

        // Define required fields based on type
        if ($isSpeaking) {
            $requiredScores = ['fluency_coherence', 'lexical_resource', 'grammatical_range', 'pronunciation'];
        } else {
            $requiredScores = ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammatical_range'];
        }

        // ✅ NEW: Normalize nested format to consistent structure
        // Convert flat scores to nested format for consistency
        foreach ($requiredScores as $field) {
            if (isset($normalized[$field])) {
                $value = $normalized[$field];
                
                // If it's a flat numeric score, convert to nested format
                if (is_numeric($value)) {
                    Log::info("🔄 Converting flat score to nested format", ['field' => $field, 'score' => $value]);
                    $normalized[$field] = [
                        'score' => floatval($value),
                        'feedback' => null // Will be filled later if possible
                    ];
                }
                
                // If it's already nested, ensure it has both score and feedback
                if (is_array($value)) {
                    if (!isset($value['score'])) {
                        $normalized[$field]['score'] = $normalized['band_score'] ?? 5.0;
                        Log::warning("⚠️ Nested format missing 'score', using fallback", ['field' => $field]);
                    }
                    if (!isset($value['feedback'])) {
                        $normalized[$field]['feedback'] = null;
                    }
                }
            }
        }

        // Check for missing score fields
        $missingScores = array_diff($requiredScores, array_keys($normalized));
        
        if (!empty($missingScores)) {
            Log::warning('⚠️ Missing some score fields', ['missing' => $missingScores, 'available' => array_keys($normalized)]);
            
            // Try to use band_score as fallback for missing fields
            $fallbackScore = isset($normalized['band_score']) && is_numeric($normalized['band_score']) 
                ? floatval($normalized['band_score']) 
                : 5.0;
                
            foreach ($missingScores as $missingField) {
                $normalized[$missingField] = [
                    'score' => $fallbackScore,
                    'feedback' => null
                ];
                Log::info("🔄 Created missing field with fallback", ['field' => $missingField, 'score' => $fallbackScore]);
            }
        }

        // Ensure band_score exists (calculate if missing)
        if (!isset($normalized['band_score']) || !is_numeric($normalized['band_score'])) {
            $scoreSum = 0;
            $scoreCount = 0;
            foreach ($requiredScores as $field) {
                if (isset($normalized[$field])) {
                    $score = is_array($normalized[$field]) ? ($normalized[$field]['score'] ?? 0) : $normalized[$field];
                    if (is_numeric($score)) {
                        $scoreSum += floatval($score);
                        $scoreCount++;
                    }
                }
            }
            if ($scoreCount > 0) {
                $normalized['band_score'] = round($scoreSum / $scoreCount * 2) / 2; // Round to nearest 0.5
                Log::info("🔄 Calculated band_score from criteria", ['band_score' => $normalized['band_score']]);
            } else {
                $normalized['band_score'] = 5.0; // Fallback default
                Log::warning("⚠️ No scores found, using default band_score: 5.0");
            }
        }

        // ✅ NEW: Extract detailed feedback from overall feedback text if AI returned flat format
        if (isset($normalized['feedback']) && is_string($normalized['feedback']) && strlen($normalized['feedback']) > 100) {
            Log::info('📝 Attempting to extract criterion feedback from overall feedback text');
            
            // Extract feedback for each criterion from the big text
            $extractedFeedback = $this->extractCriterionFeedbackFromText($normalized['feedback'], $isSpeaking);
            
            foreach ($requiredScores as $field) {
                if (isset($normalized[$field]) && is_array($normalized[$field])) {
                    $currentFeedback = $normalized[$field]['feedback'] ?? null;
                    
                    // If current feedback is empty/fallback and we extracted something, use it
                    if (empty($currentFeedback) || strlen(trim($currentFeedback)) < 50) {
                        if (!empty($extractedFeedback[$field])) {
                            $normalized[$field]['feedback'] = $extractedFeedback[$field];
                            Log::info("✅ Extracted feedback for '{$field}'", ['length' => strlen($extractedFeedback[$field])]);
                        }
                    }
                }
            }
            
            // Extract overall summary (last paragraph)
            if (!empty($extractedFeedback['overall'])) {
                $normalized['overall_feedback'] = $extractedFeedback['overall'];
                Log::info("✅ Extracted overall feedback", ['length' => strlen($extractedFeedback['overall'])]);
            }
        }
        
        // ✅ Generate fallback feedback only if still missing after extraction
        $criterionFeedbackTemplates = [
            'task_achievement' => 'Cần phát triển ý tưởng đầy đủ hơn và trả lời chính xác yêu cầu đề bài.',
            'coherence_cohesion' => 'Cần cải thiện cách sử dụng từ nối và tổ chức đoạn văn rõ ràng hơn.',
            'lexical_resource' => 'Nên mở rộng vốn từ vựng và sử dụng từ ngữ đa dạng, chính xác hơn.',
            'grammatical_range' => 'Cần giảm lỗi ngữ pháp và thực hành sử dụng cấu trúc câu phức tạp hơn.',
            'fluency_coherence' => 'Nên luyện tập nói lưu loát hơn, giảm khoảng lặng và sử dụng từ nối tốt hơn.',
            'pronunciation' => 'Cần chú ý phát âm rõ ràng hơn và cải thiện ngữ điệu tự nhiên.',
        ];
        
        foreach ($requiredScores as $field) {
            if (isset($normalized[$field]) && is_array($normalized[$field])) {
                $feedback = $normalized[$field]['feedback'] ?? null;
                
                // If feedback is STILL empty or too short, generate fallback
                if (empty($feedback) || !is_string($feedback) || strlen(trim($feedback)) < 10) {
                    $score = $normalized[$field]['score'] ?? 5.0;
                    $template = $criterionFeedbackTemplates[$field] ?? 'Cần cải thiện tiêu chí này.';
                    
                    $normalized[$field]['feedback'] = "Điểm {$score}/9.0. {$template}";
                    Log::info("🔄 Generated fallback feedback for '{$field}'", ['score' => $score]);
                }
            }
        }
        
        // Handle overall feedback (optional, for backward compatibility)
        if (empty($normalized['feedback']) || !is_string($normalized['feedback']) || strlen(trim($normalized['feedback'])) < 10) {
            Log::info('⚠️ Overall feedback is empty, generating from criteria');
            
            // Collect all criterion feedback to create overall summary
            $overallParts = [];
            $overallParts[] = "📊 **Điểm tổng**: {$normalized['band_score']}/9.0\n";
            
            foreach ($requiredScores as $field) {
                if (isset($normalized[$field]) && is_array($normalized[$field])) {
                    $score = $normalized[$field]['score'] ?? 5.0;
                    $feedback = $normalized[$field]['feedback'] ?? '';
                    $fieldName = str_replace('_', ' ', ucwords($field, '_'));
                    
                    if (!empty($feedback)) {
                        $overallParts[] = "**{$fieldName}** ({$score}): {$feedback}";
                    }
                }
            }
            
            $normalized['feedback'] = implode("\n\n", $overallParts);
            Log::info("🔄 Generated overall feedback from criteria");
        }

        // Final validation
        $allRequiredFields = array_merge(['band_score', 'feedback'], $requiredScores);
        $stillMissing = array_diff($allRequiredFields, array_keys($normalized));
        
        if (!empty($stillMissing)) {
            Log::error('❌ Still missing required fields after normalization', [
                'missing' => $stillMissing,
                'normalized_keys' => array_keys($normalized),
                'original_keys' => array_keys($parsed)
            ]);
            throw new \Exception('AI response missing required fields: ' . implode(', ', $stillMissing));
        }

        Log::info('✅ Successfully parsed and normalized grading response', [
            'band_score' => $normalized['band_score'],
            'feedback_length' => strlen($normalized['feedback']),
            'is_speaking' => $isSpeaking
        ]);
        
        return $normalized;
    }

    /**
     * Extract criterion-specific feedback from overall feedback text
     * 
     * Handles AI responses that return all feedback in one big "feedback" field like:
     * "1) Task Achievement...\n2) Coherence...\n3) Lexical...\n4) Grammatical...\nTóm lại..."
     */
    private function extractCriterionFeedbackFromText(string $feedbackText, bool $isSpeaking = false): array
    {
        $extracted = [];
        
        // Define patterns for each criterion
        if ($isSpeaking) {
            $patterns = [
                'fluency_coherence' => '/(?:1\)|Fluency|FC)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'lexical_resource' => '/(?:2\)|Lexical|LR)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'grammatical_range' => '/(?:3\)|Grammatical|GRA?)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'pronunciation' => '/(?:4\)|Pronunciation|P)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
            ];
        } else {
            $patterns = [
                'task_achievement' => '/(?:1\)|Task\s+(?:Achievement|Response))[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'coherence_cohesion' => '/(?:2\)|Coherence)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'lexical_resource' => '/(?:3\)|Lexical)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
                'grammatical_range' => '/(?:4\)|Grammatical)[^\n]*(?:Band \d+(?:\.\d)?)[^\n]*\n([\s\S]*?)(?=(?:\n\d\)|\n[A-Z][a-z]+\s*\(Band|\nTóm lại|$))/i',
            ];
        }
        
        // Extract each criterion's feedback
        foreach ($patterns as $field => $pattern) {
            if (preg_match($pattern, $feedbackText, $matches)) {
                $extracted[$field] = trim($matches[1]);
                Log::info("🔍 Extracted '{$field}' feedback", ['length' => strlen($extracted[$field])]);
            } else {
                Log::warning("⚠️ Could not extract '{$field}' feedback from text");
            }
        }
        
        // Extract overall summary (last section starting with "Tóm lại", "Khuyến nghị", "Gợi ý cải thiện", etc.)
        if (preg_match('/(?:Tóm lại|Khuyến nghị|Gợi ý|Kết luận)[^\n]*\n([\s\S]+)$/i', $feedbackText, $matches)) {
            $extracted['overall'] = trim($matches[1]);
            Log::info("🔍 Extracted overall summary", ['length' => strlen($extracted['overall'])]);
        } else {
            // If no "Tóm lại" section, use the last paragraph
            $paragraphs = preg_split('/\n\n+/', $feedbackText);
            if (count($paragraphs) > 0) {
                $lastParagraph = trim(end($paragraphs));
                if (strlen($lastParagraph) > 50 && strlen($lastParagraph) < 500) {
                    $extracted['overall'] = $lastParagraph;
                    Log::info("🔍 Using last paragraph as overall summary", ['length' => strlen($extracted['overall'])]);
                }
            }
        }
        
        return $extracted;
    }
    
    /**
     * Try to fix common JSON formatting issues
     */
    private function tryFixJsonIssues(string $jsonString): string
    {
        Log::info('🔧 Attempting to fix JSON issues');
        
        // Fix 1: Replace single quotes with double quotes (common in feedback)
        // But be careful not to break JSON structure
        $fixed = $jsonString;
        
        // Fix 2: Escape unescaped quotes in string values
        // This is tricky and risky, so we'll just log for now
        
        // Fix 3: Remove trailing commas before closing braces
        $fixed = preg_replace('/,\s*(\}|\])/', '$1', $fixed);
        
        // Fix 4: Add missing commas between fields
        $fixed = preg_replace('/"\s*\n\s*"/', '",\n"', $fixed);
        
        Log::info('🔧 JSON fix applied', ['changes' => $fixed !== $jsonString]);
        
        return $fixed;
    }

    /**
     * Convert audio to Azure-compatible format: WAV PCM 16kHz 16-bit mono
     */
    private function convertAudioForAzure(string $inputPath): ?string
    {
        try {
            // Generate temp output path
            $outputPath = sys_get_temp_dir() . '/' . uniqid('azure_audio_') . '.wav';

            // Find FFmpeg executable - try multiple locations
            $ffmpegPaths = [
                'D:\\SpringBootProjects\\school-system\\ffmpeg-master-latest-win64-gpl\\bin\\ffmpeg.exe', // Local Windows
                '/usr/bin/ffmpeg', // Linux default
                '/usr/local/bin/ffmpeg', // Linux alternative
                'ffmpeg', // System PATH (fallback)
            ];

            $ffmpegBinary = null;
            foreach ($ffmpegPaths as $path) {
                // Check if it's a full path (contains / or \)
                if (strpos($path, '/') !== false || strpos($path, '\\') !== false) {
                    // For full paths, check if file exists
                    if (file_exists($path)) {
                        $ffmpegBinary = $path;
                        break;
                    }
                } else {
                    // For commands in PATH, test by running
                    $testOutput = [];
                    @exec("$path -version 2>&1", $testOutput, $returnCode);
                    if ($returnCode === 0 && !empty($testOutput)) {
                        $ffmpegBinary = $path;
                        break;
                    }
                }
            }

            // If no FFmpeg found, return null (will skip conversion)
            if (!$ffmpegBinary) {
                Log::error('❌ FFmpeg not found in any location');
                return null;
            }

            Log::info('✅ FFmpeg found', ['path' => $ffmpegBinary]);

            // FFmpeg command to convert to WAV PCM 16kHz 16-bit mono
            // Only escape binary if it contains spaces (full path)
            $escapedBinary = (strpos($ffmpegBinary, ' ') !== false)
                ? escapeshellarg($ffmpegBinary)
                : $ffmpegBinary;

            $ffmpegCmd = sprintf(
                '%s -i %s -ar 16000 -ac 1 -sample_fmt s16 -f wav %s 2>&1',
                $escapedBinary,
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            Log::info('🔄 Converting audio for Azure', [
                'input' => $inputPath,
                'output' => $outputPath,
                'command' => $ffmpegCmd
            ]);

            exec($ffmpegCmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                Log::error('❌ FFmpeg conversion failed', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output)
                ]);
                return null;
            }

            Log::info('✅ Audio converted successfully', [
                'output_size' => filesize($outputPath)
            ]);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('❌ Audio conversion exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transcribe audio using Azure Speech-to-Text
     * @param string $audioFilePath Path to audio file
     * @param array $azureSettings Azure settings (key, region, language)
     * @return array ['success' => bool, 'transcript' => string, 'error' => string]
     */
    private function transcribeAudioWithAzure(string $audioFilePath, array $azureSettings): array
    {
        Log::info('🔵 transcribeAudioWithAzure START', [
            'audio_path' => $audioFilePath,
            'settings_keys' => array_keys($azureSettings)
        ]);

        try {
            $azureKey = $azureSettings['key'] ?? null;
            $region = $azureSettings['region'] ?? 'southeastasia';
            $language = $azureSettings['language'] ?? 'en-US';

            Log::info('🔵 Checking Azure key', ['has_key' => !empty($azureKey)]);

            if (!$azureKey) {
                Log::warning('❌ No Azure key found');
                return ['success' => false, 'error' => 'Azure Speech API key not configured'];
            }

            // Azure Speech-to-Text endpoint
            $endpoint = "https://{$region}.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1";

            Log::info('🔵 Checking audio file exists', [
                'path' => $audioFilePath,
                'exists' => file_exists($audioFilePath)
            ]);

            // Read audio file
            if (!file_exists($audioFilePath)) {
                Log::warning('❌ Audio file not found');
                return ['success' => false, 'error' => 'Audio file not found'];
            }

            // Convert audio to WAV PCM 16kHz mono format required by Azure
            $convertedPath = $this->convertAudioForAzure($audioFilePath);
            if (!$convertedPath) {
                Log::error('❌ Failed to convert audio to Azure format');
                return ['success' => false, 'error' => 'Failed to convert audio format'];
            }

            $audioData = file_get_contents($convertedPath);

            // Clean up converted file
            if ($convertedPath !== $audioFilePath && file_exists($convertedPath)) {
                @unlink($convertedPath);
            }

            // Prepare request
            $url = $endpoint . '?' . http_build_query([
                'language' => $language,
                'format' => 'detailed'
            ]);

            Log::info('📡 Calling Azure Speech-to-Text API', [
                'endpoint' => $endpoint,
                'language' => $language,
                'audio_size' => strlen($audioData),
                'has_key' => !empty($azureKey)
            ]);

            // Pronunciation Assessment configuration for detailed pronunciation scoring
            // IMPORTANT: Azure requires PascalCase property names and STRING boolean values!
            $pronunciationConfig = json_encode([
                'ReferenceText' => '', // Empty for free-form speech
                'GradingSystem' => 'HundredMark',
                'Granularity' => 'Phoneme', // Get detailed phoneme-level scores
                'Dimension' => 'Comprehensive', // Include accuracy, fluency, completeness
                'EnableMiscue' => 'True', // Must be STRING "True", not boolean
                'EnableProsodyAssessment' => 'True', // Must be STRING "True", not boolean
            ]);

            // Base64 encode the JSON config as per Azure documentation
            $pronunciationHeader = base64_encode($pronunciationConfig);

            Log::info('🎤 Pronunciation Assessment Config', [
                'json' => $pronunciationConfig,
                'base64' => substr($pronunciationHeader, 0, 50) . '...'
            ]);

            $response = Http::timeout(180) // 3 minutes for large audio files
                ->connectTimeout(30) // 30 seconds for connection
                ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $azureKey,
                'Content-Type' => 'audio/wav',
                'Pronunciation-Assessment' => $pronunciationHeader, // Base64-encoded JSON
            ])->withBody($audioData, 'audio/wav')
              ->post($url);

            Log::info('📡 Azure API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 500)
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // Log full Azure response structure to debug
                Log::info('🔍 Full Azure Response Structure', [
                    'keys' => array_keys($result),
                    'has_NBest' => isset($result['NBest']),
                    'NBest_count' => isset($result['NBest']) ? count($result['NBest']) : 0,
                    'NBest_keys' => isset($result['NBest'][0]) ? array_keys($result['NBest'][0]) : [],
                    'has_PronunciationAssessment' => isset($result['NBest'][0]['PronunciationAssessment']),
                    'full_response' => json_encode($result, JSON_PRETTY_PRINT)
                ]);
                
                $transcript = $result['DisplayText'] ?? $result['NBest'][0]['Display'] ?? '';

                // Extract pronunciation assessment scores if available
                // Azure returns scores DIRECTLY in NBest[0], not in a nested PronunciationAssessment object
                $pronunciationScore = null;
                $wordLevelDetails = [];
                
                if (isset($result['NBest'][0])) {
                    $nBest = $result['NBest'][0];
                    
                    // Check if pronunciation scores are available
                    if (isset($nBest['PronScore'])) {
                    $pronunciationScore = [
                            'accuracy_score' => $nBest['AccuracyScore'] ?? null,
                            'fluency_score' => $nBest['FluencyScore'] ?? null,
                            'completeness_score' => $nBest['CompletenessScore'] ?? null,
                            'prosody_score' => $nBest['ProsodyScore'] ?? null,
                            'pronunciation_score' => $nBest['PronScore'] ?? null, // Overall pronunciation score (0-100)
                    ];

                    Log::info('🎤 Pronunciation Assessment Scores', $pronunciationScore);
                    }
                }
                
                // Extract word-level pronunciation details for detailed feedback
                if (isset($result['NBest'][0]['Words']) && is_array($result['NBest'][0]['Words'])) {
                    $problematicWords = [];
                    foreach ($result['NBest'][0]['Words'] as $wordData) {
                        // Azure returns Word and AccuracyScore directly in the word object
                        $wordScore = $wordData['AccuracyScore'] ?? 100;
                        $wordText = $wordData['Word'] ?? '';
                        $errorType = $wordData['ErrorType'] ?? null; // May be null for correct words
                        
                        // Collect words with pronunciation issues (score < 70)
                        if ($wordScore < 70 && $wordText) {
                            $problematicWords[] = [
                                'word' => $wordText,
                                'accuracy' => $wordScore,
                                'error_type' => $errorType ?? 'Mispronunciation'
                            ];
                        }
                    }
                    
                    // Store top 10 most problematic words (sorted by lowest score)
                    if (!empty($problematicWords)) {
                        usort($problematicWords, function($a, $b) {
                            return $a['accuracy'] <=> $b['accuracy'];
                        });
                        $wordLevelDetails = array_slice($problematicWords, 0, 10);
                        
                        Log::info('🎤 Problematic Words Found', [
                            'count' => count($wordLevelDetails),
                            'words' => array_column($wordLevelDetails, 'word'),
                            'scores' => array_column($wordLevelDetails, 'accuracy')
                        ]);
                    }
                }
                
                if ($pronunciationScore && !empty($wordLevelDetails)) {
                    $pronunciationScore['problematic_words'] = $wordLevelDetails;
                }

                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'confidence' => $result['NBest'][0]['Confidence'] ?? null,
                    'pronunciation_assessment' => $pronunciationScore
                ];
            } else {
                Log::error('Azure Speech-to-Text failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to transcribe audio: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Audio transcription error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Grade IELTS Speaking answer with AI
     * Handles audio file, transcribes it, then grades with AI
     */
    public function gradeSpeakingWithAI(Request $request)
    {
        set_time_limit(300); // Allow up to 5 minutes for transcription + grading

        try {
            Log::info('🎤 Speaking AI Grading Request Started', [
                'all_inputs' => array_keys($request->all())
            ]);

            $validated = $request->validate([
                'audio_file_path' => 'required|string', // Path to existing audio file
                'question' => 'nullable|string',
                'provider' => 'nullable|string',
                'model' => 'nullable|string',
                'grading_prompt' => 'nullable|string',
            ]);

            Log::info('✅ Validation passed', ['audio_path' => $validated['audio_file_path']]);

            // Get credentials from database
            $user = auth()->user();
            $branchId = $user->branch_id ?? 1;
            $requestedProvider = $validated['provider'] ?? null;

            if ($requestedProvider) {
                $setting = AiSetting::getSettingsByProvider($branchId, 'examination_grading', $requestedProvider);
            } else {
                $setting = AiSetting::getSettings($branchId, 'examination_grading');
            }

            if (!$setting || !$setting->hasApiKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình AI API Key. Vui lòng vào Thiết lập AI để cấu hình.'
                ], 400);
            }

            Log::info('✅ Credentials retrieved for AI grading', ['provider' => $setting->provider]);

            // Get Azure settings for Speech-to-Text transcription
            $azureSetting = AiSetting::getSettingsByProvider($branchId, 'examination_grading', 'azure');

            if (!$azureSetting || !$azureSetting->hasApiKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Azure Speech-to-Text chưa được cấu hình. Vui lòng vào Thiết lập AI để cấu hình Azure.'
                ], 400);
            }

            Log::info('✅ Azure credentials retrieved for transcription', [
                'has_azure_key' => $azureSetting->hasApiKey()
            ]);

            // Use existing audio file (stored in storage/app/public/)
            $audioFilePath = $validated['audio_file_path'];
            $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $audioFilePath);

            Log::info('📁 Audio file path', [
                'relative_path' => $audioFilePath,
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath),
                'is_readable' => is_readable($fullPath)
            ]);

            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audio file not found at: ' . $audioFilePath
                ], 404);
            }

            // Step 1: Transcribe audio with Azure Speech-to-Text
            // Get Azure key from Azure provider settings (not from OpenAI/Anthropic provider)
            $azureKey = $azureSetting->api_key ?? $azureSetting->settings['azure_key'] ?? null;

            // Mask the key for logging (show first 4 and last 4 chars)
            $maskedKey = '';
            if ($azureKey) {
                $keyLength = strlen($azureKey);
                if ($keyLength > 8) {
                    $maskedKey = substr($azureKey, 0, 4) . '...' . substr($azureKey, -4);
                } else {
                    $maskedKey = str_repeat('*', $keyLength);
                }
            }

            $azureSettings = [
                'key' => $azureKey,
                'region' => $azureSetting->settings['azure_region'] ?? 'southeastasia',
                'language' => $azureSetting->settings['speaking_language'] ?? 'en-US'
            ];

            Log::info('🔑 Azure settings prepared', [
                'has_key' => !empty($azureKey),
                'key_length' => strlen($azureKey ?? ''),
                'masked_key' => $maskedKey,
                'region' => $azureSettings['region'],
                'language' => $azureSettings['language']
            ]);

            Log::info('🎙️ Starting Azure transcription...');
            $transcriptionResult = $this->transcribeAudioWithAzure($fullPath, $azureSettings);
            Log::info('🎙️ Transcription completed', ['success' => $transcriptionResult['success']]);

            // No cleanup needed - using original file, not temp file

            if (!$transcriptionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi chuyển đổi giọng nói: ' . $transcriptionResult['error']
                ], 500);
            }

            $transcript = $transcriptionResult['transcript'];

            Log::info('📝 Transcript extracted', ['length' => strlen($transcript)]);

            // Step 2: Grade transcript with AI
            $provider = $setting->provider;
            $apiKey = $setting->api_key;
            $model = $validated['model'] ?? $setting->model ?? 'gpt-5.1';

            Log::info('🤖 AI grading settings', [
                'provider' => $provider,
                'model' => $model,
                'has_api_key' => !empty($apiKey)
            ]);

            // Build speaking grading prompt with Azure pronunciation data
            $pronunciationData = $transcriptionResult['pronunciation_assessment'] ?? null;
            $pronunciationInfo = '';
            if ($pronunciationData) {
                $pronunciationInfo = "\n\n📊 AZURE PRONUNCIATION ASSESSMENT (Tham khảo để chấm điểm Pronunciation):\n"
                    . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                    . "ĐIỂM TỔNG QUAN:\n"
                    . "- Pronunciation Score (Tổng): " . ($pronunciationData['pronunciation_score'] ?? 'N/A') . "/100\n"
                    . "- Accuracy (Độ chính xác): " . ($pronunciationData['accuracy_score'] ?? 'N/A') . "/100\n"
                    . "- Fluency (Độ trôi chảy): " . ($pronunciationData['fluency_score'] ?? 'N/A') . "/100\n"
                    . "- Completeness (Độ hoàn chỉnh): " . ($pronunciationData['completeness_score'] ?? 'N/A') . "/100\n"
                    . "- Prosody (Ngữ điệu): " . ($pronunciationData['prosody_score'] ?? 'N/A') . "/100\n";
                
                // Add word-level pronunciation issues if available
                if (isset($pronunciationData['problematic_words']) && !empty($pronunciationData['problematic_words'])) {
                    $pronunciationInfo .= "\n⚠️ CÁC TỪ PHÁT ÂM SAI (Accuracy < 60/100):\n";
                    foreach ($pronunciationData['problematic_words'] as $wordData) {
                        $word = $wordData['word'] ?? '';
                        $accuracy = $wordData['accuracy'] ?? 0;
                        $errorType = $wordData['error_type'] ?? 'Unknown';
                        
                        $pronunciationInfo .= sprintf(
                            "  • \"%s\" - %d/100 (%s)\n",
                            $word,
                            round($accuracy),
                            $errorType === 'Mispronunciation' ? 'Phát âm sai' : 
                            ($errorType === 'Omission' ? 'Bỏ sót' : 
                            ($errorType === 'Insertion' ? 'Thêm vào' : $errorType))
                        );
                    }
                    $pronunciationInfo .= "\n💡 Sử dụng thông tin này để đánh giá tiêu chí Pronunciation một cách chính xác.\n";
                } else {
                    $pronunciationInfo .= "\n✅ Không phát hiện lỗi phát âm nghiêm trọng.\n";
                }
                
                $pronunciationInfo .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                $pronunciationInfo .= "🎯 Hướng dẫn chấm Pronunciation dựa trên Azure scores:\n";
                $pronunciationInfo .= "  • < 60: Band 4-5 (Many pronunciation issues)\n";
                $pronunciationInfo .= "  • 60-70: Band 5-6 (Some issues affecting clarity)\n";
                $pronunciationInfo .= "  • 70-85: Band 6-7 (Generally clear with minor issues)\n";
                $pronunciationInfo .= "  • > 85: Band 7-8 (Very clear pronunciation)\n";
                $pronunciationInfo .= "\n";
            }

            // Default instructions (without JSON format - will be appended automatically)
            $defaultInstructions = <<<INSTRUCTIONS
You are a STRICT IELTS examiner with 15+ years of experience. Grade this IELTS Speaking response using the OFFICIAL IELTS Speaking Band Descriptors with HIGH STANDARDS.

⚠️ CRITICAL GRADING RULES:
1. Be RIGOROUS and REALISTIC - DO NOT inflate scores
2. Band 7.0+ requires CONSISTENT excellence across ALL 4 criteria
3. Common issues like hesitations, repetition, simple vocabulary, or grammar errors MUST be penalized
4. Azure pronunciation scores are REFERENCE ONLY - apply strict IELTS band descriptors
5. Most candidates are Band 5.5-6.5; Band 7+ is for truly strong performers only

📊 EVALUATION CRITERIA (each 25%):

1. FLUENCY & COHERENCE (FC):
   - Band 7+: Speaks at length without noticeable effort; minimal hesitation; clear coherence
   - Band 6: Willing to speak but with hesitation, repetition, self-correction
   - Band 5: Simple discourse markers; frequent hesitations; may lose coherence
   - PENALTIES: Long pauses (-0.5 band), frequent repetition (-0.5 band), self-correction (-0.5 band), unclear ideas (-1 band)

2. LEXICAL RESOURCE (LR):
   - Band 7+: Flexible use of vocabulary with some less common items; good paraphrasing
   - Band 6: Adequate range but lacks flexibility; attempts paraphrasing with mixed success
   - Band 5: Limited vocabulary; may not convey precise meaning; basic word choice
   - PENALTIES: Repetitive vocabulary (-0.5 band), basic vocabulary only (-1 band), word choice errors (-0.5 band per 2-3 errors)

3. GRAMMATICAL RANGE & ACCURACY (GRA):
   - Band 7+: Wide range of structures with flexibility; frequent error-free sentences
   - Band 6: Mix of simple and complex forms; errors do not impede communication
   - Band 5: Basic sentence forms; subordinate clauses rare; frequent errors
   - PENALTIES: Subject-verb errors (-0.5 band), tense errors (-0.5 band), only simple sentences (-1 band), 3+ serious errors (-1 band)

4. PRONUNCIATION (P):
   - Band 7+: Easy to understand throughout; L1 accent has minimal effect on clarity
   - Band 6: Generally understood; mispronunciation may reduce clarity occasionally
   - Band 5: Mispronunciation reduces clarity at times; may cause some difficulty
   - PENALTIES: Frequent mispronunciation (-0.5 band), unclear articulation (-0.5 band), poor stress/intonation (-0.5 band)
   - USE Azure pronunciation assessment as PRIMARY REFERENCE:
     * Azure Overall Score <60: Maximum Band 5
     * Azure Overall Score 60-70: Band 5-6
     * Azure Overall Score 70-85: Band 6-7
     * Azure Overall Score >85: Band 7-8
   - If Azure lists specific mispronounced words, MENTION them in feedback (bằng tiếng Việt)
   - Prosody score <70 indicates poor intonation/stress patterns (penalize CC and Pronunciation)

⚠️ SCORING GUIDELINES:
- Band 7-8: Strong performance with minor issues (rare - requires excellence across ALL criteria)
- Band 6-6.5: Adequate but with clear limitations (MOST COMMON for competent speakers)
- Band 5-5.5: Modest ability; frequent errors or limitations
- Band 4-4.5: Limited ability with serious problems

📋 GRADING PROCESS:
1. Count hesitations, pauses, repetitions, self-corrections
2. Identify ALL grammar errors and vocabulary limitations
3. Assess pronunciation issues (use Azure scores as reference, not absolute)
4. For EACH criterion, list 2-3 specific weaknesses
5. DO NOT give Band 7+ unless truly excellent across ALL areas
6. Compare strictly against official IELTS band descriptors

QUESTION:
{question}

STUDENT TRANSCRIPT:
{transcript}{pronunciation_info}

CRITICAL FEEDBACK REQUIREMENTS:
⚠️ MANDATORY FORMAT: Structure feedback with clear sections separated by double newlines (\\n\\n):

Về Fluency & Coherence:
[Analysis in Vietnamese - 2-3 sentences with specific examples]

Về Lexical Resource:
[Analysis in Vietnamese - 2-3 sentences with specific examples]

Về Grammatical Range & Accuracy:
[Analysis in Vietnamese - 2-3 sentences with specific examples]

Về Pronunciation:
[Analysis in Vietnamese - 2-3 sentences, MUST reference Azure scores and list mispronounced words]

Để cải thiện:
[Overall summary and specific recommendations in Vietnamese - 1-2 sentences]

CONTENT REQUIREMENTS:
- Provide detailed analysis in VIETNAMESE (TIẾNG VIỆT) for EACH criterion
- Include at least 5 concrete examples of errors or weaknesses
- DO NOT quote full sentences - reference briefly (ví dụ: "dùng 'go' quá nhiều lần")
- For PRONUNCIATION criterion:
  * Base your band score PRIMARILY on Azure pronunciation assessment data
  * If Azure lists mispronounced words, MENTION THEM SPECIFICALLY (ví dụ: "Các từ phát âm chưa chuẩn: 'computer' (45/100), 'technology' (52/100)")
  * Reference Azure overall score (ví dụ: "Điểm phát âm Azure là 68/100, tương đương Band 5.5-6")
  * Explain HOW the mispronunciation affects clarity
- Focus on what PREVENTS higher bands, not just strengths
- Use VIETNAMESE for ALL feedback text

NOW GRADE STRICTLY - Be conservative and realistic.
INSTRUCTIONS;

            // Load prompt from database (with JSON format auto-appended)
            // Only use custom prompt if explicitly provided in request
            $gradingPrompt = $validated['grading_prompt'] ??
                           $this->loadPromptWithFormat('prompt_speaking_grading', $defaultInstructions);

            // Replace placeholders
            $finalPrompt = str_replace(
                ['{question}', '{transcript}', '{pronunciation_info}'],
                [$validated['question'] ?? 'No specific question provided', $transcript, $pronunciationInfo],
                $gradingPrompt
            );

            Log::info('🚀 Calling AI for grading...');
            // Call AI for grading
            $result = $this->callGradingAI($provider, $apiKey, $model, $finalPrompt);
            Log::info('✅ AI grading completed', ['has_result' => !empty($result)]);

            return response()->json([
                'success' => true,
                'data' => array_merge($result, [
                    'transcript' => $transcript,
                    'confidence' => $transcriptionResult['confidence'] ?? null
                ])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Speaking AI grading failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chấm Speaking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate IELTS Listening/Reading content with AI
     * Uses API credentials stored in database
     */
    public function generateIELTSContent(Request $request)
    {
        set_time_limit(300); // Allow up to 5 minutes for IELTS content generation

        try {
            $validated = $request->validate([
                'skill' => 'required|string|in:listening,reading,writing,speaking',
                'prompt' => 'required|string',
                'model' => 'nullable|string',
            ]);

            // Get credentials from database
            $credentials = $this->getAiCredentials();

            if (!$credentials || !$credentials['api_key']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình AI API Key. Vui lòng vào Thiết lập AI để cấu hình.'
                ], 400);
            }

            $provider = $credentials['provider'];
            $apiKey = $credentials['api_key'];
            $model = $validated['model'] ?? $credentials['model'] ?? 'gpt-5.1';

            // Map skill to module name for JSON format template
            $moduleMap = [
                'listening' => 'prompt_ielts_listening',
                'reading' => 'prompt_ielts_reading',
                'writing' => 'prompt_ielts_writing',
                'speaking' => 'prompt_ielts_speaking',
            ];

            $module = $moduleMap[$validated['skill']] ?? '';

            // The prompt from frontend already contains user instructions
            // Append JSON format template automatically
            $fullPrompt = $validated['prompt'];
            if ($module) {
                $jsonFormat = $this->getJsonFormatTemplate($module);
                $fullPrompt = $validated['prompt'] . $jsonFormat;
            }

            // Call AI based on provider and model
            $content = $this->callAIForContent($provider, $apiKey, $model, $fullPrompt);

            // Try to parse JSON from response (support both object {...} and array [...])
            $result = null;
            // Match both JSON object and JSON array
            if (preg_match('/[\{\[][\s\S]*[\}\]]/', $content, $matches)) {
                Log::info('JSON regex matched', [
                    'matched_length' => strlen($matches[0]),
                    'first_100_chars' => substr($matches[0], 0, 100),
                    'starts_with' => substr($matches[0], 0, 1) // [ or {
                ]);

                $result = json_decode($matches[0], true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode failed', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                        'json_preview' => substr($matches[0], 0, 500)
                    ]);
                } else {
                    // Check if result is sequential array (multi-part) or associative array (single object)
                    $isMultiPart = isset($result[0]) && is_array($result[0]);
                    
                    Log::info('JSON decoded successfully', [
                        'is_multi_part' => $isMultiPart,
                        'count' => $isMultiPart ? count($result) : 1,
                        'first_keys' => $isMultiPart ? (isset($result[0]) ? array_keys($result[0]) : []) : array_keys($result)
                    ]);
                }
            } else {
                Log::error('JSON regex did not match', [
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 200)
                ]);
            }

            // Map field names for frontend compatibility
            if ($result && is_array($result)) {
                // Check if it's a multi-part response (array of objects)
                $isMultiPart = isset($result[0]) && is_array($result[0]);
                
                if ($isMultiPart) {
                    // Multi-part: map each part's fields
                    foreach ($result as $index => &$part) {
                        // Listening: Map audio_transcript -> transcript
                        if (isset($part['audio_transcript']) && !isset($part['transcript'])) {
                            $part['transcript'] = $part['audio_transcript'];
                            unset($part['audio_transcript']);
                        }

                        // Listening: Map sections -> questionGroups
                        if (isset($part['sections']) && !isset($part['questionGroups'])) {
                            $part['questionGroups'] = $part['sections'];
                            unset($part['sections']);
                        }

                        // Reading: Map questions -> questionGroups
                        if (isset($part['questions']) && !isset($part['questionGroups']) && is_array($part['questions'])) {
                            $part['questionGroups'] = $part['questions'];
                            unset($part['questions']);
                        }
                    }
                    unset($part); // Break reference
                    
                    Log::info('Mapped multi-part result for frontend', [
                        'skill' => $validated['skill'],
                        'parts_count' => count($result)
                    ]);
                } else {
                    // Single part: map fields directly
                // Listening: Map audio_transcript -> transcript
                if (isset($result['audio_transcript']) && !isset($result['transcript'])) {
                    $result['transcript'] = $result['audio_transcript'];
                    unset($result['audio_transcript']);
                }

                // Listening: Map sections -> questionGroups
                if (isset($result['sections']) && !isset($result['questionGroups'])) {
                    $result['questionGroups'] = $result['sections'];
                    unset($result['sections']);
                }

                    // Reading: Map questions -> questionGroups
                if (isset($result['questions']) && !isset($result['questionGroups']) && is_array($result['questions'])) {
                    $result['questionGroups'] = $result['questions'];
                    unset($result['questions']);
                }

                    Log::info('Mapped single-part result for frontend', [
                    'skill' => $validated['skill'],
                    'has_transcript' => isset($result['transcript']),
                    'has_questionGroups' => isset($result['questionGroups']),
                    'has_title' => isset($result['title']),
                    'has_content' => isset($result['content'])
                ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'raw' => $content
            ]);
        } catch (\Exception $e) {
            Log::error('IELTS content generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo nội dung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call AI for content generation
     */
    private function callAIForContent(string $provider, string $apiKey, string $model, string $prompt): string
    {
        set_time_limit(300); // Extend PHP timeout for content generation

        if ($provider === 'openai') {
            $isGPT5Family = str_starts_with($model, 'gpt-5');

            if ($isGPT5Family) {
                Log::info('GPT-5 Content Request', ['model' => $model, 'prompt_length' => strlen($prompt)]);

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json'
                ])->withOptions([
                    'verify' => false,
                    'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
                ])->timeout(240)->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'input' => $prompt,
                    'reasoning' => ['effort' => 'medium']
                ]);

                if (!$response->successful()) {
                    $errorData = $response->json();
                    Log::error('GPT-5 Content API Error', ['status' => $response->status(), 'error' => $errorData]);
                    throw new \Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? $response->status()));
                }

                $data = $response->json();
                Log::info('GPT-5 Content Response', ['response_keys' => array_keys($data)]);

                // DEBUG: Log the actual structure of text and output
                if (isset($data['text'])) {
                    Log::info('GPT-5 text field', [
                        'type' => gettype($data['text']),
                        'is_string' => is_string($data['text']),
                        'value' => is_string($data['text']) ? substr($data['text'], 0, 200) : json_encode($data['text'])
                    ]);
                }
                if (isset($data['output'])) {
                    Log::info('GPT-5 output field', [
                        'type' => gettype($data['output']),
                        'structure' => json_encode($data['output'], JSON_PRETTY_PRINT)
                    ]);
                }

                // Try different keys in order of priority, ensuring we only get strings
                $content = '';

                if (isset($data['output_text']) && is_string($data['output_text'])) {
                    $content = $data['output_text'];
                    Log::info('Extracted content from output_text', ['length' => strlen($content)]);
                } elseif (isset($data['text']) && is_string($data['text'])) {
                    $content = $data['text'];
                    Log::info('Extracted content from text (string)', ['length' => strlen($content)]);
                }

                // If still no content, try parsing from output array
                if (!$content && isset($data['output']) && is_array($data['output'])) {
                    foreach ($data['output'] as $item) {
                        if (isset($item['type']) && $item['type'] === 'message' && isset($item['content'])) {
                            foreach ($item['content'] as $c) {
                                if (($c['type'] ?? '') === 'output_text' || ($c['type'] ?? '') === 'text') {
                                    $content = $c['text'] ?? $c['output_text'] ?? '';
                                    Log::info('Extracted content from output array (message)', ['length' => strlen($content)]);
                                    break 2;
                                }
                            }
                        } elseif (isset($item['text'])) {
                            $content = $item['text'];
                            Log::info('Extracted content from output array (text)', ['length' => strlen($content)]);
                            break;
                        }
                    }
                }

                // If text is array, try to extract from it
                if (!$content && isset($data['text']) && is_array($data['text'])) {
                    foreach ($data['text'] as $textItem) {
                        if (is_string($textItem)) {
                            $content = $textItem;
                            Log::info('Extracted content from text array (string item)', ['length' => strlen($content)]);
                            break;
                        } elseif (is_array($textItem) && isset($textItem['text'])) {
                            $content = $textItem['text'];
                            Log::info('Extracted content from text array (nested text)', ['length' => strlen($content)]);
                            break;
                        }
                    }
                }

                Log::info('Final extracted content', [
                    'empty' => empty($content),
                    'length' => strlen($content),
                    'preview' => substr($content, 0, 200)
                ]);

                return $content;
            } else {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json'
                ])->withOptions([
                    'verify' => false,
                    'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
                ])->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens' => 8000
                ]);

                if (!$response->successful()) {
                    $errorData = $response->json();
                    throw new \Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? $response->status()));
                }

                return $response->json()['choices'][0]['message']['content'] ?? '';
            }
        } else {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ])->withOptions([
                'verify' => false,
                'curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
            ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 8000,
                'messages' => [['role' => 'user', 'content' => $prompt]]
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                throw new \Exception('Anthropic API error: ' . ($errorData['error']['message'] ?? $response->status()));
            }

            return $response->json()['content'][0]['text'] ?? '';
        }
    }

    /**
     * Generate IELTS Speaking Test with AI
     * Creates full 3-part test with questions and examiner name
     */
    public function generateSpeakingTest(Request $request)
    {
        set_time_limit(300); // Allow up to 5 minutes for speaking test generation

        try {
            $validated = $request->validate([
                'topic' => 'required|string|max:500',
                'examiner_name' => 'nullable|string|max:100'
            ]);

            // Get credentials from database
            $credentials = $this->getAiCredentials();

            if (!$credentials || !$credentials['api_key']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình AI API Key. Vui lòng vào Thiết lập AI để cấu hình.'
                ], 400);
            }

            $provider = $credentials['provider'];
            $apiKey = $credentials['api_key'];
            $model = $credentials['model'] ?? 'gpt-5.1';

            // Build system prompt for speaking test generation
            $systemPrompt = $this->getSpeakingTestGeneratorPrompt();

            // Build user prompt
            $userPrompt = <<<PROMPT
Topic: {$validated['topic']}
Examiner name: {$this->generateExaminerName($validated['examiner_name'] ?? null)}

Generate a complete IELTS Speaking test based on the topic above.
PROMPT;

            // Call AI
            $content = $this->callAIForContent($provider, $apiKey, $model, $systemPrompt . "\n\n" . $userPrompt);

            // Try to parse JSON from response
            $result = null;
            if (preg_match('/\{[\s\S]*\}/s', $content, $matches)) {
                $result = json_decode($matches[0], true);
            }

            if (!$result) {
                throw new \Exception('AI did not return valid JSON format');
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Speaking test generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo đề Speaking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system prompt for speaking test generation
     */
    private function getSpeakingTestGeneratorPrompt(): string
    {
        // Instructions only - JSON format will be appended from constants
        $instructions = <<<INSTRUCTIONS
You are an IELTS Speaking test creator. Generate a complete IELTS Speaking test with 3 parts based on the given topic.

IMPORTANT NOTE: The system will automatically insert natural transition phrases between questions (like "Thank you", "I see", "Good, let's move on", etc.) to signal when moving to the next question. This creates a realistic IELTS test experience where the examiner acknowledges the candidate's answer before proceeding.

Guidelines:
- Part 1: 4-5 personal questions (work, study, daily life, hobbies related to topic)
- Part 2: Cue card with 4 bullet points + 2 follow-up questions
- Part 3: 4-5 abstract discussion questions requiring deeper analysis
- All questions must be related to the main topic
- Questions should be natural, realistic IELTS-style questions
- Use proper British English
- DO NOT include transition phrases in question content - they will be added automatically by the system
- Always generate a random examiner name (Western names like Sarah Johnson, David Brown, Emma Wilson, etc)
INSTRUCTIONS;

        // Append JSON format template
        return $instructions . self::JSON_FORMAT_IELTS_SPEAKING;
    }

    /**
     * Generate or use provided examiner name
     */
    private function generateExaminerName(?string $providedName): string
    {
        if (!empty($providedName)) {
            return $providedName;
        }

        $firstNames = ['Sarah', 'Michael', 'Emma', 'David', 'Jessica', 'James', 'Sophie', 'Daniel', 'Rachel', 'Thomas'];
        $lastNames = ['Johnson', 'Smith', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Taylor'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Azure Text-to-Speech API
     * Converts text to speech using Azure Speech Service
     */
    public function azureTTS(Request $request)
    {
        try {
            $validated = $request->validate([
                'text' => 'required|string|max:3000',
                'voice' => 'nullable|string',
                'rate' => 'nullable|numeric|between:0.5,2',
                'pitch' => 'nullable|numeric|between:0.5,2'
            ]);

            $text = $validated['text'];
            $voice = $validated['voice'] ?? 'en-GB-SoniaNeural'; // Default British English female voice
            $rate = $validated['rate'] ?? 0.9;
            $pitch = $validated['pitch'] ?? 1.0;

            // Get Azure credentials from settings - specifically request Azure provider
            $user = auth()->user();
            $branchId = $user->branch_id ?? 1;

            $azureSetting = AiSetting::getSettingsByProvider($branchId, 'examination_grading', 'azure');

            if (!$azureSetting || !$azureSetting->hasApiKey()) {
                Log::warning('Azure TTS: No Azure settings found', [
                    'branch_id' => $branchId,
                    'module' => 'examination_grading'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Azure TTS is not configured. Please configure Azure settings in Examination Settings.'
                ], 400);
            }

            $credentials = [
                'provider' => $azureSetting->provider,
                'api_key' => $azureSetting->api_key,
                'model' => $azureSetting->model,
                'settings' => $azureSetting->settings,
            ];

            // For Azure, get key from api_key (decrypted from api_key_encrypted)
            // Fallback to settings.azure_key for backwards compatibility
            $apiKey = $credentials['api_key'] ?? $credentials['settings']['azure_key'] ?? null;

            if (empty($apiKey)) {
                Log::error('Azure TTS: No API key found', [
                    'has_api_key_encrypted' => !empty($credentials['api_key']),
                    'has_settings_azure_key' => !empty($credentials['settings']['azure_key'])
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Azure API Key is not configured.'
                ], 400);
            }

            // Get endpoint from settings or build from region
            $customEndpoint = $credentials['settings']['azure_endpoint'] ?? null;
            $region = $credentials['settings']['azure_region'] ?? 'eastus';

            // Build SSML (Speech Synthesis Markup Language)
            $ratePercent = ($rate - 1) * 100;
            $pitchPercent = ($pitch - 1) * 100;
            $ssml = "<speak version='1.0' xml:lang='en-GB'>
                <voice name='{$voice}'>
                    <prosody rate='{$ratePercent}%' pitch='{$pitchPercent}%'>
                        {$text}
                    </prosody>
                </voice>
            </speak>";

            // Determine endpoint
            // For TTS, always use the regional TTS endpoint even with multi-service keys
            if ($customEndpoint) {
                // Extract region from custom endpoint if it's a Cognitive Services endpoint
                // Example: https://eastasia.api.cognitive.microsoft.com/ → eastasia
                if (preg_match('/https:\/\/([^.]+)\.api\.cognitive\.microsoft\.com/', $customEndpoint, $matches)) {
                    $extractedRegion = $matches[1];
                    $endpoint = "https://{$extractedRegion}.tts.speech.microsoft.com/cognitiveservices/v1";
                    Log::info('🔊 Azure TTS: Using regional TTS endpoint', [
                        'custom_endpoint' => $customEndpoint,
                        'extracted_region' => $extractedRegion,
                        'tts_endpoint' => $endpoint
                    ]);
                } else if (strpos($customEndpoint, '.tts.speech.microsoft.com') !== false) {
                    // Already a TTS endpoint
                    $baseUrl = rtrim($customEndpoint, '/');
                    $endpoint = $baseUrl . '/cognitiveservices/v1';
                } else {
                    // Unknown format, try to use as-is
                    $baseUrl = rtrim($customEndpoint, '/');
                    $endpoint = $baseUrl . '/cognitiveservices/v1';
                }
            } else {
                // Default: Build Speech Service endpoint from region
                $endpoint = "https://{$region}.tts.speech.microsoft.com/cognitiveservices/v1";
            }

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $apiKey,
                'Content-Type' => 'application/ssml+xml',
                'X-Microsoft-OutputFormat' => 'audio-24khz-48kbitrate-mono-mp3',
                'User-Agent' => 'SchoolManagementSystem'
            ])->withBody($ssml, 'application/ssml+xml')
              ->post($endpoint);

            if (!$response->successful()) {
                Log::error('Azure TTS API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate speech. Please check Azure configuration.'
                ], 500);
            }

            // Return audio as base64
            $audioData = base64_encode($response->body());

            return response()->json([
                'success' => true,
                'audio' => $audioData,
                'format' => 'mp3',
                'voice' => $voice
            ]);

        } catch (\Exception $e) {
            Log::error('Azure TTS error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Azure TTS error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit IELTS Speaking Test with recordings
     * Creates Submission record and saves audio recordings
     */
    public function submitSpeakingTest(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_id' => 'required|integer',
                'practice_test_id' => 'nullable|integer',
                'total_duration' => 'required|integer',
                'recordings' => 'required|array',
                'recordings.*.part' => 'required|integer',
                'recordings.*.question_index' => 'nullable|integer',
                'recordings.*.duration' => 'required|integer',
                'recordings.*.audio' => 'required|file|mimes:wav,mp3,webm,ogg',
            ]);

            $user = auth()->user();
            $testId = $validated['test_id'];
            $practiceTestId = $validated['practice_test_id'] ?? null;
            $totalDuration = $validated['total_duration'];
            $recordings = $request->file('recordings');

            DB::beginTransaction();

            // Find or create "Self Practice" assignment for this test
            $test = Test::findOrFail($testId);
            // Create individual assignment for this user
            $assignment = Assignment::firstOrCreate(
                [
                    'test_id' => $testId,
                    'title' => 'Self Practice - ' . $test->title,
                    'assign_type' => 'user', // Individual assignment
                ],
                [
                    'assigned_by' => $user->id,
                    'start_date' => now(),
                    'due_date' => now()->addYears(10),
                    'status' => 'active',
                    'max_attempts' => 999,
                ]
            );

            // Create assignment target to link user with assignment
            \App\Models\Examination\AssignmentTarget::firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'targetable_type' => 'App\Models\User',
                    'targetable_id' => $user->id,
                ]
            );

            // Create or find existing in-progress submission
            $submission = Submission::firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'status' => Submission::STATUS_IN_PROGRESS,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'practice_test_id' => $practiceTestId,
                    'started_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Create directory for this submission
            $submissionDir = 'speaking-recordings/' . $user->id . '/' . $submission->id;
            $savedFiles = [];

            // Save each recording as SubmissionAnswer
            foreach ($recordings as $index => $recording) {
                if (isset($recording['audio'])) {
                    $audioFile = $recording['audio'];
                    $part = $request->input("recordings.{$index}.part");
                    $questionIndex = $request->input("recordings.{$index}.question_index");
                    $duration = $request->input("recordings.{$index}.duration");

                    // Generate filename
                    $filename = "part{$part}_q{$questionIndex}_" . time() . '.' . $audioFile->getClientOriginalExtension();

                    // Save file
                    $path = $audioFile->storeAs($submissionDir, $filename, 'public');

                    // Create SubmissionAnswer record
                    $answer = SubmissionAnswer::create([
                        'submission_id' => $submission->id,
                        'question_id' => null, // Speaking test doesn't have question records
                        'test_question_id' => null,
                        'audio_file_path' => $path,
                        'audio_duration' => $duration,
                        'answer' => [
                            'part' => $part,
                            'question_index' => $questionIndex,
                            'type' => 'audio',
                        ],
                        'max_points' => 9.0, // IELTS band score
                        'answered_at' => now(),
                    ]);

                    $savedFiles[] = [
                        'answer_id' => $answer->id,
                        'part' => $part,
                        'question_index' => $questionIndex,
                        'duration' => $duration,
                        'filename' => $filename,
                        'path' => $path,
                        'url' => asset('storage/' . $path)
                    ];

                    Log::info("Saved speaking recording: {$filename}", [
                        'user_id' => $user->id,
                        'submission_id' => $submission->id,
                        'answer_id' => $answer->id,
                    ]);
                }
            }

            // Update submission as submitted
            $submission->update([
                'submitted_at' => now(),
                'time_spent' => $totalDuration,
                'status' => Submission::STATUS_SUBMITTED,
                'max_score' => 9.0, // IELTS band score
            ]);

            DB::commit();

            Log::info('Speaking test submitted successfully', [
                'user_id' => $user->id,
                'submission_id' => $submission->id,
                'recordings_count' => count($savedFiles),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Speaking test submitted successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'submission_uuid' => $submission->uuid,
                    'assignment_id' => $assignment->id,
                    'recordings' => $savedFiles,
                    'total_recordings' => count($savedFiles),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Speaking test submission failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit speaking test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate questions using AI
     * Creates multiple questions based on topic and parameters
     */
    public function generateQuestions(Request $request)
    {
        set_time_limit(180); // Allow up to 3 minutes for AI generation

        try {
            $validated = $request->validate([
                'topic' => 'required|string|max:1000',
                'subject_id' => 'nullable|exists:exam_subjects,id',
                'subject_category_id' => 'nullable|exists:exam_subject_categories,id',
                'skill' => 'nullable|in:listening,reading,writing,speaking,grammar,vocabulary,general',
                'type' => 'required|in:multiple_choice,multiple_response,true_false,true_false_ng,fill_blanks,matching,matching_headings,matching_features,matching_sentence_endings,short_answer,sentence_completion,summary_completion,note_completion,table_completion,ordering,essay,audio_response',
                'difficulty' => 'required|in:easy,medium,hard,expert',
                'count' => 'required|integer|min:1|max:20',
            ]);

            // Get credentials from database
            $credentials = $this->getAiCredentials();
            if (!$credentials || !$credentials['api_key']) {
                throw new \Exception('Chưa cấu hình AI. Vui lòng vào Thiết lập AI để cấu hình.');
            }

            $provider = $credentials['provider'];
            $apiKey = $credentials['api_key'];

            // Build AI prompt with strict JSON format
            $systemPrompt = $this->getQuestionGenerationSystemPrompt($validated['type']);
            $userPrompt = $this->buildQuestionGenerationPrompt($validated);

            // Call AI
            $response = $this->callAI(
                $provider,
                $apiKey,
                $systemPrompt,
                $userPrompt,
                3000
            );

            // Parse JSON response
            $questions = $this->parseQuestionsFromAI($response, $validated['type']);

            if (!$questions || empty($questions)) {
                throw new \Exception('Không thể phân tích dữ liệu từ AI. Vui lòng thử lại.');
            }

            return response()->json([
                'success' => true,
                'questions' => $questions,
                'raw_response' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('AI Question Generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system prompt for question generation with strict JSON format
     */
    private function getQuestionGenerationSystemPrompt(string $type): string
    {
        $jsonFormat = match($type) {
            'multiple_choice', 'multiple_response' => '
{
  "questions": [
    {
      "title": "Question text here",
      "explanation": "Detailed explanation in Vietnamese why the answer is correct",
      "options": [
        {"text": "Option A text", "is_correct": false},
        {"text": "Option B text", "is_correct": true},
        {"text": "Option C text", "is_correct": false},
        {"text": "Option D text", "is_correct": false}
      ]
    }
  ]
}',
            'true_false' => '
{
  "questions": [
    {
      "title": "Statement to evaluate as true or false",
      "explanation": "Explanation in Vietnamese",
      "options": [
        {"text": "True", "is_correct": true},
        {"text": "False", "is_correct": false}
      ]
    }
  ]
}',
            'true_false_ng' => '
{
  "questions": [
    {
      "title": "Statement to evaluate as True/False/Not Given",
      "explanation": "Explanation in Vietnamese",
      "options": [
        {"text": "True", "is_correct": false},
        {"text": "False", "is_correct": false},
        {"text": "Not Given", "is_correct": true}
      ]
    }
  ]
}',
            'fill_blanks' => '
{
  "questions": [
    {
      "title": "Complete sentence with [blank] markers. Example: The [blank] is on the [blank].",
      "explanation": "Explanation in Vietnamese",
      "blank_answers": ["first answer", "second answer"]
    }
  ]
}',
            'matching', 'matching_headings', 'matching_features', 'matching_sentence_endings' => '
{
  "questions": [
    {
      "title": "Match the following items",
      "explanation": "Explanation in Vietnamese",
      "matching_pairs": [
        {"left": "Item 1", "right": "Match 1"},
        {"left": "Item 2", "right": "Match 2"},
        {"left": "Item 3", "right": "Match 3"}
      ]
    }
  ]
}',
            'short_answer', 'sentence_completion', 'summary_completion', 'note_completion' => '
{
  "questions": [
    {
      "title": "Question requiring a short answer",
      "explanation": "Explanation in Vietnamese",
      "sample_answer": "Expected answer"
    }
  ]
}',
            'table_completion' => '
{
  "questions": [
    {
      "title": "Complete the table below",
      "explanation": "Explanation in Vietnamese",
      "settings": {
        "tableData": {
          "headers": ["Column 1", "Column 2", "Column 3"],
          "rows": [
            [
              {"content": "Fixed value", "isBlank": false},
              {"content": "", "isBlank": true, "blankIndex": 1, "correctAnswer": "Answer 1"},
              {"content": "Another value", "isBlank": false}
            ],
            [
              {"content": "Row 2 value", "isBlank": false},
              {"content": "", "isBlank": true, "blankIndex": 2, "correctAnswer": "Answer 2"},
              {"content": "Row 2 end", "isBlank": false}
            ]
          ]
        }
      },
      "correct_answer": ["Answer 1", "Answer 2"]
    }
  ]
}',
            'ordering' => '
{
  "questions": [
    {
      "title": "Put the following items in the correct order",
      "explanation": "Explanation in Vietnamese with correct order",
      "options": [
        {"text": "Step 1", "is_correct": false},
        {"text": "Step 2", "is_correct": false},
        {"text": "Step 3", "is_correct": false},
        {"text": "Step 4", "is_correct": false}
      ]
    }
  ]
}',
            'essay', 'audio_response' => '
{
  "questions": [
    {
      "title": "Essay question or prompt",
      "content": "Additional context or reading passage if needed",
      "explanation": "Grading rubric and criteria in Vietnamese",
      "sample_answer": "Sample response or key points"
    }
  ]
}',
            default => '{}'
        };

        return "You are an expert educational content creator specializing in creating high-quality examination questions.

CRITICAL: You MUST respond ONLY with a valid JSON object. Do not include any explanatory text, markdown code blocks, or conversational responses. Output ONLY the raw JSON.

REQUIRED JSON FORMAT:
{$jsonFormat}

REQUIREMENTS:
1. Create engaging, clear, and pedagogically sound questions
2. Ensure questions test understanding, not just memorization
3. All explanations MUST be in Vietnamese (tiếng Việt)
4. For multiple choice: Include 4 options with only one correct answer (or multiple for multiple_response type)
5. For fill blanks: Use [blank] markers in the question text
6. Questions should be appropriate for the specified difficulty level
7. Output ONLY the JSON object with these EXACT field names, nothing else
8. NO markdown formatting, NO explanatory text before or after JSON";
    }

    /**
     * Build the user prompt for question generation
     */
    private function buildQuestionGenerationPrompt(array $params): string
    {
        $topic = $params['topic'];
        $type = $params['type'];
        $difficulty = $params['difficulty'];
        $count = $params['count'];
        $skill = $params['skill'] ?? 'general';

        // Get subject and category info if provided
        $subjectName = null;
        $categoryName = null;

        if (!empty($params['subject_id'])) {
            $subject = \App\Models\ExamSubject::find($params['subject_id']);
            $subjectName = $subject?->name;
        }

        if (!empty($params['subject_category_id'])) {
            $category = \App\Models\ExamSubjectCategory::find($params['subject_category_id']);
            $categoryName = $category?->name;
        }

        $difficultyMap = [
            'easy' => 'dễ (easy)',
            'medium' => 'trung bình (medium)',
            'hard' => 'khó (hard)',
            'expert' => 'chuyên gia (expert)'
        ];

        $skillMap = [
            'listening' => 'Listening (Nghe hiểu)',
            'reading' => 'Reading (Đọc hiểu)',
            'writing' => 'Writing (Viết)',
            'speaking' => 'Speaking (Nói)',
            'grammar' => 'Grammar (Ngữ pháp)',
            'vocabulary' => 'Vocabulary (Từ vựng)',
            'math' => 'Math (Toán học)',
            'science' => 'Science (Khoa học)',
            'general' => 'General (Tổng hợp)'
        ];

        $typeMap = [
            'multiple_choice' => 'trắc nghiệm một đáp án (multiple choice)',
            'multiple_response' => 'trắc nghiệm nhiều đáp án (multiple response)',
            'true_false' => 'đúng/sai (true/false)',
            'true_false_ng' => 'đúng/sai/không xác định (true/false/not given)',
            'fill_blanks' => 'điền vào chỗ trống (fill in the blanks)',
            'matching' => 'nối cột (matching)',
            'matching_headings' => 'nối tiêu đề (matching headings)',
            'matching_features' => 'nối đặc điểm (matching features)',
            'matching_sentence_endings' => 'nối kết câu (matching sentence endings)',
            'short_answer' => 'trả lời ngắn (short answer)',
            'sentence_completion' => 'hoàn thành câu (sentence completion)',
            'summary_completion' => 'hoàn thành tóm tắt (summary completion)',
            'note_completion' => 'hoàn thành ghi chú (note completion)',
            'table_completion' => 'hoàn thành bảng (table completion)',
            'ordering' => 'sắp xếp thứ tự (ordering)',
            'essay' => 'viết luận (essay)',
            'audio_response' => 'trả lời bằng audio (audio response)'
        ];

        $prompt = "Generate {$count} {$typeMap[$type]} questions about: {$topic}

";

        // Add subject context if available
        if ($subjectName) {
            $prompt .= "Subject (Môn học): {$subjectName}\n";
        }

        if ($categoryName) {
            $prompt .= "Category (Phân loại): {$categoryName}\n";
        }

        $prompt .= "Skill Focus: {$skillMap[$skill]}
Difficulty Level: {$difficultyMap[$difficulty]}

CRITICAL REQUIREMENTS:
1. Questions MUST be relevant to the subject and topic above
2. Test understanding and application, not just memorization
3. Questions should be clear, accurate, and pedagogically sound
4. Appropriate difficulty for {$difficulty} level learners
5. All explanations MUST be in Vietnamese (tiếng Việt)
6. Follow the JSON format EXACTLY as specified in the system prompt

";

        // Add type-specific guidance
        $typeGuidance = match($type) {
            'multiple_choice', 'multiple_response' => "
ADDITIONAL GUIDANCE for Multiple Choice:
- Create EXACTLY 4 options (A, B, C, D)
- Only ONE correct answer (or multiple for multiple_response)
- Distractors should be plausible but clearly incorrect
- Avoid 'all of the above' or 'none of the above' options
- Question stem should be clear and complete",

            'fill_blanks' => "
ADDITIONAL GUIDANCE for Fill in the Blanks:
- Use EXACTLY [blank] markers (not ___ or other formats)
- Place blanks on KEY concepts, not trivial words
- Provide answers in blank_answers array in exact order
- Context should make answers somewhat determinable
- Avoid blanking articles (a, an, the) or prepositions",

            'matching' => "
ADDITIONAL GUIDANCE for Matching:
- Create 4-6 matching pairs
- Items should be clearly related
- Avoid ambiguous matches
- Left column: prompts/questions
- Right column: answers/matches",

            'table_completion' => "
ADDITIONAL GUIDANCE for Table Completion:
- Create simple 2-3 column tables
- Mark cells as blank where students fill answers
- Provide correct answers for blank cells
- Table should test understanding, not just memory",

            'ordering' => "
ADDITIONAL GUIDANCE for Ordering:
- Create 4-6 items to be ordered
- Items should have clear logical sequence
- Explain the correct order in the explanation field
- Consider chronological, process, or importance ordering",

            'essay', 'audio_response' => "
ADDITIONAL GUIDANCE for Essay/Audio Response:
- Provide clear prompt/question
- Include grading rubric in explanation
- Suggest key points in sample_answer
- Specify expected length or duration if relevant",

            default => ""
        };

        $prompt .= $typeGuidance;

        return $prompt;
    }

    /**
     * Parse questions from AI response
     */
    private function parseQuestionsFromAI(string $response, string $type): ?array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);

        // Try to extract JSON if wrapped in text
        if (preg_match('/\{.*"questions".*\}/s', $response, $matches)) {
            $response = $matches[0];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['questions'])) {
            Log::error('Failed to parse questions JSON', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500)
            ]);
            return null;
        }

        return $data['questions'];
    }

    /**
     * Upload audio file to local storage
     */
    public function uploadAudio(Request $request)
    {
        try {
            // Validate file (accept all audio files, just check size)
            $request->validate([
                'file' => 'required|file|max:51200', // Max 50MB
            ]);

            $file = $request->file('file');
            
            // Check if it's likely an audio file by extension
            $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'webm', 'aac', 'flac', 'wma'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $allowedExtensions)) {
                Log::warning('⚠️ Non-audio file uploaded', [
                    'extension' => $extension,
                    'mime' => $file->getMimeType()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn file audio (mp3, wav, ogg, m4a, webm...)'
                ], 422);
            }
            
            Log::info('🎵 Audio upload attempt', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
            ]);
            
            // Store in public/ielts-audio directory
            $path = $file->store('ielts-audio', 'public');
            
            // Generate full URL
            $url = asset('storage/' . $path);

            Log::info('Audio file uploaded', [
                'path' => $path,
                'url' => $url,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $path,
                    'url' => $url,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Audio upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi upload audio: ' . $e->getMessage()
            ], 500);
        }
    }
}
