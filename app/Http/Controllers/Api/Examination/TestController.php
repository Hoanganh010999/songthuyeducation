<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Store a new test
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|string|in:ielts,cambridge,toeic,custom,placement,quiz,practice',
                'subtype' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:draft,active,archived',
                'sections' => 'nullable|string', // JSON string of sections data
            ]);

            // Parse sections to get content for settings
            $sectionsData = null;
            if (!empty($validated['sections'])) {
                $sectionsData = json_decode($validated['sections'], true);
            }

            // Build settings based on subtype
            $settings = [];
            if ($sectionsData) {
                $subtype = $validated['subtype'];

                // Process each section independently (support for multi-skill tests with subtype='full')
                // Use namespaced keys to avoid collisions between skills
                if (isset($sectionsData['reading']['passages'])) {
                    $settings['reading'] = [
                        'passages' => array_map(function ($passage) {
                        $passageData = [
                            'title' => $passage['title'] ?? '',
                            'content' => $passage['content'] ?? '',
                        ];

                        // Save questionGroups if present (new format with instructions)
                        if (isset($passage['questionGroups']) && is_array($passage['questionGroups'])) {
                            $passageData['questionGroups'] = array_map(function ($group) {
                                return [
                                    'id' => $group['id'] ?? null,
                                    'type' => $group['type'] ?? 'short_answer',
                                    'startNumber' => $group['startNumber'] ?? 1,
                                    'endNumber' => $group['endNumber'] ?? 1,
                                    'wordLimit' => $group['wordLimit'] ?? 3,
                                    'instruction' => $group['instruction'] ?? null, // Custom edited instruction
                                    'headings' => $group['headings'] ?? [],
                                    'features' => $group['features'] ?? [],
                                    'featureType' => $group['featureType'] ?? '',
                                    'questions' => array_map(function ($q) {
                                        return [
                                            'id' => $q['id'] ?? null,
                                            'number' => $q['number'] ?? 0,
                                            'statement' => $q['statement'] ?? '',
                                            'sentence' => $q['sentence'] ?? '',
                                            'question' => $q['question'] ?? '',
                                            'paragraphRef' => $q['paragraphRef'] ?? '',
                                            'information' => $q['information'] ?? '',
                                            'sentenceStart' => $q['sentenceStart'] ?? '',
                                            'note' => $q['note'] ?? '',
                                            'options' => $q['options'] ?? [],
                                            'answer' => $q['answer'] ?? $q['correctAnswer'] ?? null,
                                        ];
                                    }, $group['questions'] ?? [])
                                ];
                            }, $passage['questionGroups']);
                        }

                        // Also save flat questions for backward compatibility
                        if (isset($passage['questions']) && is_array($passage['questions'])) {
                            $passageData['questions'] = array_map(function ($q) {
                                return [
                                    'number' => $q['number'] ?? 0,
                                    'type' => $q['type'] ?? 'short_answer',
                                    'question' => $q['content'] ?? $q['question'] ?? '',
                                    'answer' => $q['correctAnswer'] ?? $q['answer'] ?? null,
                                    'options' => $q['options'] ?? [],
                                ];
                            }, $passage['questions']);
                        }

                        return $passageData;
                        }, $sectionsData['reading']['passages'])
                    ];
                }

                if (isset($sectionsData['listening']['parts'])) {
                    $settings['listening'] = [
                        'parts' => array_map(function ($part) {
                        $partData = [
                            'id' => $part['id'] ?? null,
                            'title' => $part['title'] ?? '',
                            'audio' => $part['audio'] ?? null,
                            'transcript' => $part['transcript'] ?? '', // Audio transcript
                        ];

                        // Save questionGroups if present (new format with instructions)
                        if (isset($part['questionGroups']) && is_array($part['questionGroups'])) {
                            $partData['questionGroups'] = array_map(function ($group) {
                                $groupData = [
                                    'id' => $group['id'] ?? null,
                                    'type' => $group['type'] ?? 'multiple_choice',
                                    'startNumber' => $group['startNumber'] ?? 1,
                                    'endNumber' => $group['endNumber'] ?? 1,
                                    'wordLimit' => $group['wordLimit'] ?? 3,
                                    'instruction' => $group['instruction'] ?? null,
                                    'questions' => array_map(function ($q) {
                                        return [
                                            'id' => $q['id'] ?? null,
                                            'number' => $q['number'] ?? 0,
                                            'content' => $q['content'] ?? '',
                                            'options' => $q['options'] ?? [],
                                            'answer' => $q['answer'] ?? $q['correctAnswer'] ?? null,
                                        ];
                                    }, $group['questions'] ?? [])
                                ];
                                
                                // For labeling types: save diagram image and features
                                if (isset($group['diagramImage'])) {
                                    $groupData['diagramImage'] = $group['diagramImage'];
                                }
                                if (isset($group['diagramDescription'])) {
                                    $groupData['diagramDescription'] = $group['diagramDescription'];
                                }
                                if (isset($group['features'])) {
                                    $groupData['features'] = $group['features'];
                                }
                                
                                // For matching types
                                if (isset($group['featureType'])) {
                                    $groupData['featureType'] = $group['featureType'];
                                }
                                
                                return $groupData;
                            }, $part['questionGroups']);
                        }

                        // Also save flat questions for backward compatibility
                        if (isset($part['questions']) && is_array($part['questions'])) {
                            $partData['questions'] = array_map(function ($q) {
                                return [
                                    'number' => $q['number'] ?? 0,
                                    'type' => $q['type'] ?? 'multiple_choice',
                                    'content' => $q['content'] ?? '',
                                    'options' => $q['options'] ?? [],
                                    'answer' => $q['correctAnswer'] ?? $q['answer'] ?? null,
                                ];
                            }, $part['questions']);
                        }

                        return $partData;
                        }, $sectionsData['listening']['parts'])
                    ];
                }

                if (isset($sectionsData['writing']['tasks'])) {
                    $settings['writing'] = [
                        'tasks' => array_map(function ($task) {
                        return [
                            'id' => $task['id'] ?? null,
                            'title' => $task['title'] ?? '',
                            'prompt' => $task['prompt'] ?? '',
                            'minWords' => $task['minWords'] ?? ($task['id'] == 1 ? 150 : 250),
                            'timeLimit' => $task['timeLimit'] ?? ($task['id'] == 1 ? 20 : 40),
                            'criteria' => $task['criteria'] ?? ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy'],
                            'sampleAnswer' => $task['sampleAnswer'] ?? null,
                            // Task 1 specific fields
                            'visualType' => $task['visualType'] ?? 'bar_chart',
                            'imageUrl' => $task['imageUrl'] ?? null,
                            'imageSource' => $task['imageSource'] ?? null,
                            'imagePath' => $task['imagePath'] ?? null,
                            'imagePrompt' => $task['imagePrompt'] ?? null,
                            'chartData' => $task['chartData'] ?? null,
                        ];
                        }, $sectionsData['writing']['tasks'])
                    ];
                }

                if (isset($sectionsData['speaking']['parts'])) {
                    $settings['speaking'] = [
                        'parts' => $sectionsData['speaking']['parts']
                    ];
                    // Save examiner script for Azure Speech integration
                    if (isset($sectionsData['speaking']['script'])) {
                        $settings['speaking']['script'] = $sectionsData['speaking']['script'];
                    }
                }
            }

            // Calculate total questions (from questionGroups or flat questions)
            $totalQuestions = 0;

            // Count from Reading passages
            if (isset($settings['reading']['passages'])) {
                foreach ($settings['reading']['passages'] as $passage) {
                    if (isset($passage['questionGroups']) && is_array($passage['questionGroups'])) {
                        foreach ($passage['questionGroups'] as $group) {
                            $totalQuestions += count($group['questions'] ?? []);
                        }
                    } elseif (isset($passage['questions'])) {
                        $totalQuestions += count($passage['questions']);
                    }
                }
            }

            // Count from Listening parts
            if (isset($settings['listening']['parts'])) {
                foreach ($settings['listening']['parts'] as $part) {
                    if (isset($part['questionGroups']) && is_array($part['questionGroups'])) {
                        foreach ($part['questionGroups'] as $group) {
                            $totalQuestions += count($group['questions'] ?? []);
                        }
                    } elseif (isset($part['questions'])) {
                        $totalQuestions += count($part['questions']);
                    }
                }
            }

            // Count from Writing tasks
            if (isset($settings['writing']['tasks'])) {
                $totalQuestions += count($settings['writing']['tasks']);
            }

            // Count from Speaking parts
            if (isset($settings['speaking']['parts'])) {
                foreach ($settings['speaking']['parts'] as $part) {
                    if (isset($part['questions'])) {
                        $totalQuestions += count($part['questions']);
                    } elseif (isset($part['cueCard'])) {
                        // Speaking Part 2 has cue card
                        $totalQuestions += 1;
                    }
                }
            }

            $test = Test::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'subtype' => $validated['subtype'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'settings' => $settings,
                'total_points' => $totalQuestions,
                'created_by' => auth()->id(),
            ]);

            Log::info("Test created: {$test->title} (ID: {$test->id})", [
                'type' => $test->type,
                'subtype' => $test->subtype,
                'questions' => $totalQuestions,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đề thi đã được tạo thành công',
                'data' => $test
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Failed to create test: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đề thi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing test
     */
    public function update(Request $request, $id)
    {
        try {
            $test = Test::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'subtype' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:draft,active,archived',
                'sections' => 'nullable|string',
            ]);

            // Parse sections to get content for settings
            $sectionsData = null;
            if (!empty($validated['sections'])) {
                $sectionsData = json_decode($validated['sections'], true);
                
                // Log the incoming data for debugging
                if (isset($sectionsData['listening']['parts'])) {
                    foreach ($sectionsData['listening']['parts'] as $pIdx => $part) {
                        if (isset($part['questionGroups'])) {
                            foreach ($part['questionGroups'] as $gIdx => $group) {
                                if ($group['type'] === 'labeling') {
                                    Log::info('📥 Received labeling group from frontend', [
                                        'part' => $pIdx,
                                        'group' => $gIdx,
                                        'has_diagramImage' => isset($group['diagramImage']),
                                        'diagramImage' => $group['diagramImage'] ?? null,
                                        'has_features' => isset($group['features']),
                                        'features_count' => isset($group['features']) ? count($group['features']) : 0,
                                        'features' => $group['features'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // Build settings based on subtype
            $settings = $test->settings ?? [];
            $subtype = $validated['subtype'] ?? $test->subtype;

            if ($sectionsData) {
                // Process each section independently (support for multi-skill tests with subtype='full')
                // Use namespaced keys to avoid collisions between skills
                if (isset($sectionsData['reading']['passages'])) {
                    $settings['reading'] = [
                        'passages' => array_map(function ($passage) {
                        $passageData = [
                            'title' => $passage['title'] ?? '',
                            'content' => $passage['content'] ?? '',
                        ];

                        // Save questionGroups if present (new format with instructions)
                        if (isset($passage['questionGroups']) && is_array($passage['questionGroups'])) {
                            $passageData['questionGroups'] = array_map(function ($group) {
                                return [
                                    'id' => $group['id'] ?? null,
                                    'type' => $group['type'] ?? 'short_answer',
                                    'startNumber' => $group['startNumber'] ?? 1,
                                    'endNumber' => $group['endNumber'] ?? 1,
                                    'wordLimit' => $group['wordLimit'] ?? 3,
                                    'instruction' => $group['instruction'] ?? null,
                                    'headings' => $group['headings'] ?? [],
                                    'features' => $group['features'] ?? [],
                                    'featureType' => $group['featureType'] ?? '',
                                    'questions' => array_map(function ($q) {
                                        return [
                                            'id' => $q['id'] ?? null,
                                            'number' => $q['number'] ?? 0,
                                            'statement' => $q['statement'] ?? '',
                                            'sentence' => $q['sentence'] ?? '',
                                            'question' => $q['question'] ?? '',
                                            'paragraphRef' => $q['paragraphRef'] ?? '',
                                            'information' => $q['information'] ?? '',
                                            'sentenceStart' => $q['sentenceStart'] ?? '',
                                            'note' => $q['note'] ?? '',
                                            'options' => $q['options'] ?? [],
                                            'answer' => $q['answer'] ?? $q['correctAnswer'] ?? null,
                                        ];
                                    }, $group['questions'] ?? [])
                                ];
                            }, $passage['questionGroups']);
                        }

                        // Also save flat questions for backward compatibility
                        if (isset($passage['questions']) && is_array($passage['questions'])) {
                            $passageData['questions'] = array_map(function ($q) {
                                return [
                                    'number' => $q['number'] ?? 0,
                                    'type' => $q['type'] ?? 'short_answer',
                                    'question' => $q['content'] ?? $q['question'] ?? '',
                                    'answer' => $q['correctAnswer'] ?? $q['answer'] ?? null,
                                    'options' => $q['options'] ?? [],
                                ];
                            }, $passage['questions']);
                        }

                        return $passageData;
                        }, $sectionsData['reading']['passages'])
                    ];
                }

                if (isset($sectionsData['listening']['parts'])) {
                    $settings['listening'] = [
                        'parts' => array_map(function ($part) {
                        $partData = [
                            'id' => $part['id'] ?? null,
                            'title' => $part['title'] ?? '',
                            'audio' => $part['audio'] ?? null,
                            'transcript' => $part['transcript'] ?? '', // Audio transcript
                        ];

                        // Save questionGroups if present (new format with instructions)
                        if (isset($part['questionGroups']) && is_array($part['questionGroups'])) {
                            $partData['questionGroups'] = array_map(function ($group) {
                                $groupData = [
                                    'id' => $group['id'] ?? null,
                                    'type' => $group['type'] ?? 'multiple_choice',
                                    'startNumber' => $group['startNumber'] ?? 1,
                                    'endNumber' => $group['endNumber'] ?? 1,
                                    'wordLimit' => $group['wordLimit'] ?? 3,
                                    'instruction' => $group['instruction'] ?? null,
                                    'questions' => array_map(function ($q) {
                                        return [
                                            'id' => $q['id'] ?? null,
                                            'number' => $q['number'] ?? 0,
                                            'content' => $q['content'] ?? '',
                                            'options' => $q['options'] ?? [],
                                            'answer' => $q['answer'] ?? $q['correctAnswer'] ?? null,
                                        ];
                                    }, $group['questions'] ?? [])
                                ];
                                
                                // For labeling types: save diagram image and features
                                if (isset($group['diagramImage'])) {
                                    $groupData['diagramImage'] = $group['diagramImage'];
                                }
                                if (isset($group['diagramDescription'])) {
                                    $groupData['diagramDescription'] = $group['diagramDescription'];
                                }
                                if (isset($group['features'])) {
                                    $groupData['features'] = $group['features'];
                                }
                                
                                // For matching types
                                if (isset($group['featureType'])) {
                                    $groupData['featureType'] = $group['featureType'];
                                }
                                
                                return $groupData;
                            }, $part['questionGroups']);
                        }

                        // Also save flat questions for backward compatibility
                        if (isset($part['questions']) && is_array($part['questions'])) {
                            $partData['questions'] = array_map(function ($q) {
                                return [
                                    'number' => $q['number'] ?? 0,
                                    'type' => $q['type'] ?? 'multiple_choice',
                                    'content' => $q['content'] ?? '',
                                    'options' => $q['options'] ?? [],
                                    'answer' => $q['correctAnswer'] ?? $q['answer'] ?? null,
                                ];
                            }, $part['questions']);
                        }

                        return $partData;
                        }, $sectionsData['listening']['parts'])
                    ];
                }

                if (isset($sectionsData['writing']['tasks'])) {
                    $settings['writing'] = [
                        'tasks' => array_map(function ($task) {
                        return [
                            'id' => $task['id'] ?? null,
                            'title' => $task['title'] ?? '',
                            'prompt' => $task['prompt'] ?? '',
                            'minWords' => $task['minWords'] ?? ($task['id'] == 1 ? 150 : 250),
                            'timeLimit' => $task['timeLimit'] ?? ($task['id'] == 1 ? 20 : 40),
                            'criteria' => $task['criteria'] ?? ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy'],
                            'sampleAnswer' => $task['sampleAnswer'] ?? null,
                            // Task 1 specific fields
                            'visualType' => $task['visualType'] ?? 'bar_chart',
                            'imageUrl' => $task['imageUrl'] ?? null,
                            'imageSource' => $task['imageSource'] ?? null,
                            'imagePath' => $task['imagePath'] ?? null,
                            'imagePrompt' => $task['imagePrompt'] ?? null,
                            'chartData' => $task['chartData'] ?? null,
                        ];
                        }, $sectionsData['writing']['tasks'])
                    ];
                }

                if (isset($sectionsData['speaking']['parts'])) {
                    $settings['speaking'] = [
                        'parts' => $sectionsData['speaking']['parts']
                    ];
                    // Save examiner script for Azure Speech integration
                    if (isset($sectionsData['speaking']['script'])) {
                        $settings['speaking']['script'] = $sectionsData['speaking']['script'];
                    }
                }
            }

            // Calculate total questions (from questionGroups or flat questions)
            $totalQuestions = 0;

            // Count from Reading passages
            if (isset($settings['reading']['passages'])) {
                foreach ($settings['reading']['passages'] as $passage) {
                    if (isset($passage['questionGroups']) && is_array($passage['questionGroups'])) {
                        foreach ($passage['questionGroups'] as $group) {
                            $totalQuestions += count($group['questions'] ?? []);
                        }
                    } elseif (isset($passage['questions'])) {
                        $totalQuestions += count($passage['questions']);
                    }
                }
            }

            // Count from Listening parts
            if (isset($settings['listening']['parts'])) {
                foreach ($settings['listening']['parts'] as $part) {
                    if (isset($part['questionGroups']) && is_array($part['questionGroups'])) {
                        foreach ($part['questionGroups'] as $group) {
                            $totalQuestions += count($group['questions'] ?? []);
                        }
                    } elseif (isset($part['questions'])) {
                        $totalQuestions += count($part['questions']);
                    }
                }
            }

            // Count from Writing tasks
            if (isset($settings['writing']['tasks'])) {
                $totalQuestions += count($settings['writing']['tasks']);
            }

            // Count from Speaking parts
            if (isset($settings['speaking']['parts'])) {
                foreach ($settings['speaking']['parts'] as $part) {
                    if (isset($part['questions'])) {
                        $totalQuestions += count($part['questions']);
                    } elseif (isset($part['cueCard'])) {
                        // Speaking Part 2 has cue card
                        $totalQuestions += 1;
                    }
                }
            }

            $test->update([
                'title' => $validated['title'] ?? $test->title,
                'description' => $validated['description'] ?? $test->description,
                'subtype' => $subtype,
                'status' => $validated['status'] ?? $test->status,
                'settings' => $settings,
                'total_points' => $totalQuestions ?: $test->total_points,
            ]);

            Log::info("Test updated: {$test->title} (ID: {$test->id})", [
                'type' => $test->type,
                'subtype' => $test->subtype,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đề thi đã được cập nhật thành công',
                'data' => $test
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Failed to update test {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật đề thi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a test
     */
    public function destroy($id)
    {
        try {
            $test = Test::findOrFail($id);

            // Check if test has assignments
            $assignmentCount = $test->assignments()->count();
            if ($assignmentCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa đề thi này vì đã có {$assignmentCount} bài tập được giao cho học sinh. Vui lòng xóa các bài tập trước."
                ], 422);
            }

            // Check if test is used in practice tests
            $practiceTests = \DB::table('practice_tests')
                ->where(function($query) use ($id) {
                    $query->where('reading_test_id', $id)
                        ->orWhere('writing_test_id', $id)
                        ->orWhere('listening_test_id', $id)
                        ->orWhere('speaking_test_id', $id);
                })
                ->whereNull('deleted_at')
                ->count();

            if ($practiceTests > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa đề thi này vì đang được sử dụng trong {$practiceTests} bộ đề luyện tập IELTS. Vui lòng xóa khỏi các bộ đề trước."
                ], 422);
            }

            // If no constraints, proceed with deletion
            $test->delete();

            Log::info("Test deleted: {$test->title} (ID: {$test->id})");

            return response()->json([
                'success' => true,
                'message' => 'Đề thi đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete test {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa đề thi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single test by ID with all related data
     */
    public function show($id)
    {
        try {
            $test = Test::with([
                'creator',
                'sections.passage',
                'sections.audioTrack',
                'sections.testQuestions.question.options',
            ])->findOrFail($id);

            // Decode settings if it's JSON string
            if ($test->settings && is_string($test->settings)) {
                $test->settings = json_decode($test->settings, true);
            }

            // Backward compatibility: Convert old flat structure to new namespaced structure
            if ($test->settings && is_array($test->settings)) {
                $settings = $test->settings;

                // Convert old flat 'passages' to 'reading.passages'
                if (isset($settings['passages']) && !isset($settings['reading'])) {
                    $settings['reading'] = ['passages' => $settings['passages']];
                    unset($settings['passages']);
                }

                // Convert old flat 'tasks' to 'writing.tasks'
                if (isset($settings['tasks']) && !isset($settings['writing'])) {
                    $settings['writing'] = ['tasks' => $settings['tasks']];
                    unset($settings['tasks']);
                }

                // Convert old flat 'parts' to either 'listening.parts' or 'speaking.parts' based on subtype
                if (isset($settings['parts']) && !isset($settings['listening']) && !isset($settings['speaking'])) {
                    if ($test->subtype === 'listening') {
                        $settings['listening'] = ['parts' => $settings['parts']];
                    } elseif ($test->subtype === 'speaking') {
                        $settings['speaking'] = ['parts' => $settings['parts']];
                    }
                    unset($settings['parts']);
                }

                // Also convert 'script' to 'speaking.script'
                if (isset($settings['script']) && !isset($settings['speaking']['script'])) {
                    if (!isset($settings['speaking'])) {
                        $settings['speaking'] = [];
                    }
                    $settings['speaking']['script'] = $settings['script'];
                    unset($settings['script']);
                }

                $test->settings = $settings;
            }

            // Build structured data for frontend
            $testData = $test->toArray();

            // Build settings object for reading tests
            // IELTSTestBuilder expects data in settings.passages format
            if ($test->subtype === 'reading') {
                // Check if data is stored in sections (old way) or settings (new way)
                if ($test->sections->count() > 0) {
                    // Build from sections relationships (old way)
                    $passages = $test->sections->map(function ($section) {
                        return [
                            'id' => $section->id,
                            'title' => $section->passage->title ?? $section->title,
                            'content' => $section->passage->content ?? '',
                            'questions' => $section->testQuestions->map(function ($tq) {
                                $q = $tq->question;
                                $content = is_string($q->content) ? json_decode($q->content, true) : $q->content;
                                $questionText = is_array($content) ? ($content['text'] ?? '') : (string) $content;

                                return [
                                    'id' => $q->id,
                                    'number' => $tq->sort_order,
                                    'type' => $q->type,
                                    'question' => $questionText,
                                    'statement' => $questionText,
                                    'answer' => $q->correct_answer,
                                    'options' => $q->options->map(function ($opt) {
                                        return [
                                            'label' => $opt->label,
                                            'content' => $opt->content,
                                            'is_correct' => $opt->is_correct,
                                        ];
                                    })->toArray(),
                                ];
                            })->toArray(),
                        ];
                    })->toArray();

                    $testData['passages'] = $passages;
                    if (!isset($testData['settings']) || !is_array($testData['settings'])) {
                        $testData['settings'] = [];
                    }
                    $testData['settings']['passages'] = $passages;
                } elseif (isset($testData['settings']['passages'])) {
                    // Data is stored directly in settings.passages (new way)
                    // Just ensure it's available at top level too
                    $testData['passages'] = $testData['settings']['passages'];
                }
            }

            Log::info("Test loaded: {$test->title} (ID: {$test->id})", [
                'type' => $test->type,
                'subtype' => $test->subtype,
                'sections' => $test->sections->count(),
                'questions' => $test->testQuestions()->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $testData
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load test {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tests list with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Test::withCount('testQuestions');

            // Filter by type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Filter by subtype (also accept 'skill' param for frontend compatibility)
            if ($request->filled('subtype')) {
                $query->where('subtype', $request->subtype);
            } elseif ($request->filled('skill')) {
                // Frontend sends 'skill' (reading, listening, etc.) which maps to 'subtype'
                $query->where('subtype', $request->skill);
            }

            // Filter by search
            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                $query->where('status', 'active');
            }

            $tests = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $tests
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load tests: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load tests'
            ], 500);
        }
    }
}
