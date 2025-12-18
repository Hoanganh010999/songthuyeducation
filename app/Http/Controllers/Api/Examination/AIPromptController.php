<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\AIPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AIPromptController extends Controller
{
    /**
     * Get AI prompt(s) - single module or all prompts.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $module = $request->input('module');

            // If module is provided, return single prompt
            if ($module) {
                $prompt = AIPrompt::where('module', $module)
                    ->where('created_by', auth()->id())
                    ->first();

                if (!$prompt) {
                    return response()->json([
                        'success' => true,
                        'data' => null,
                        'message' => 'No prompt found for this module',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'module' => $prompt->module,
                        'prompt' => $prompt->prompt,
                    ],
                ]);
            }

            // If no module provided, return all prompts for current user
            $prompts = AIPrompt::where('created_by', auth()->id())
                ->get();

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
                'data' => $transformedPrompts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve prompt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store or update AI prompt.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|in:prompt_writing_grading,prompt_speaking_grading,prompt_ielts_listening,prompt_ielts_reading,prompt_ielts_writing,prompt_ielts_speaking,prompt_question_generation,prompt_lesson_plan_a,prompt_lesson_plan_b,prompt_lesson_plan_c,prompt_lesson_plan_d,prompt_lesson_plan_e,prompt_lesson_plan_f,prompt_lesson_plan_g,prompt_lesson_plan_h',
            'prompt' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $prompt = AIPrompt::updateOrCreate(
                [
                    'module' => $request->module,
                    'created_by' => auth()->id(),
                ],
                [
                    'prompt' => $request->prompt,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Prompt saved successfully',
                'data' => [
                    'module' => $prompt->module,
                    'prompt' => $prompt->prompt,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save prompt: ' . $e->getMessage(),
            ], 500);
        }
    }
}
