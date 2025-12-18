<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningJournal;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LearningJournalController extends Controller
{
    /**
     * Get journals for current student in a class
     */
    public function getMyJournals(Request $request, $classId)
    {
        try {
            $user = $request->user();

            $journals = LearningJournal::where('class_id', $classId)
                ->where('student_id', $user->id)
                ->orderBy('journal_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $journals
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error getting my journals', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading journals'
            ], 500);
        }
    }

    /**
     * Get journal for specific date
     */
    public function getJournalByDate(Request $request, $classId, $date)
    {
        try {
            $user = $request->user();

            // Parse the date to extract just the date part (YYYY-MM-DD)
            // Handle both formats: "2025-12-13" and "2025-12-13T17:00:00.000000Z"
            // IMPORTANT: Extract date part BEFORE parsing to avoid timezone issues
            if (strlen($date) > 10) {
                // If ISO format with time, extract just YYYY-MM-DD part
                $dateOnly = substr($date, 0, 10);
            } else {
                $dateOnly = $date;
            }

            Log::info('[LearningJournal] getJournalByDate', [
                'user_id' => $user->id,
                'class_id' => $classId,
                'date_received' => $date,
                'date_parsed' => $dateOnly,
            ]);

            $journal = LearningJournal::where('class_id', $classId)
                ->where('student_id', $user->id)
                ->where('journal_date', $dateOnly)
                ->first();

            Log::info('[LearningJournal] Found journal', [
                'journal_id' => $journal?->id,
                'has_content' => !empty($journal?->content),
                'content_length' => strlen($journal?->content ?? ''),
            ]);

            return response()->json([
                'success' => true,
                'data' => $journal
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error getting journal by date', [
                'class_id' => $classId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading journal'
            ], 500);
        }
    }

    /**
     * Save or update journal
     */
    public function saveJournal(Request $request, $classId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'journal_date' => 'required|date',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $branchId = $request->header('X-Branch-Id') ?? $user->branch_id ?? 0;

            // Parse the date to extract just the date part (YYYY-MM-DD)
            // IMPORTANT: Extract date part BEFORE parsing to avoid timezone issues
            $journalDate = $request->journal_date;
            if (strlen($journalDate) > 10) {
                // If ISO format with time, extract just YYYY-MM-DD part
                $dateOnly = substr($journalDate, 0, 10);
            } else {
                $dateOnly = $journalDate;
            }

            Log::info('[LearningJournal] saveJournal', [
                'user_id' => $user->id,
                'class_id' => $classId,
                'journal_date_received' => $request->journal_date,
                'journal_date_parsed' => $dateOnly,
                'content_length' => strlen($request->content ?? ''),
                'branch_id' => $branchId,
            ]);

            // Check if journal exists and is graded (cannot edit graded journals)
            $existingJournal = LearningJournal::where('class_id', $classId)
                ->where('student_id', $user->id)
                ->where('journal_date', $dateOnly)
                ->first();

            if ($existingJournal && $existingJournal->status === 'graded') {
                Log::warning('[LearningJournal] Cannot edit graded journal', [
                    'journal_id' => $existingJournal->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit graded journal'
                ], 403);
            }

            // Find existing journal or create new
            $journal = LearningJournal::updateOrCreate(
                [
                    'class_id' => $classId,
                    'student_id' => $user->id,
                    'journal_date' => $dateOnly,
                ],
                [
                    'content' => $request->content,
                    'status' => 'submitted',
                    'branch_id' => $branchId,
                ]
            );

            Log::info('[LearningJournal] Journal saved', [
                'journal_id' => $journal->id,
                'was_recently_created' => $journal->wasRecentlyCreated,
                'content_length' => strlen($journal->content ?? ''),
            ]);

            return response()->json([
                'success' => true,
                'data' => $journal,
                'message' => 'Journal saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error saving journal', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving journal'
            ], 500);
        }
    }

    /**
     * Get journals for a specific student (teacher view)
     */
    public function getStudentJournals(Request $request, $classId, $studentId)
    {
        try {
            $user = $request->user();

            // Check authorization - only teacher/admin can view
            $class = ClassModel::findOrFail($classId);
            $isTeacher = $class->homeroom_teacher_id === $user->id
                || $class->schedules()->where('teacher_id', $user->id)->exists();

            if (!($user->hasRole('admin') || $user->hasRole('super-admin')) && !$isTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $journals = LearningJournal::where('class_id', $classId)
                ->where('student_id', $studentId)
                ->with(['student', 'grader'])
                ->orderBy('journal_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $journals
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error getting student journals', [
                'class_id' => $classId,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading journals'
            ], 500);
        }
    }

    /**
     * Grade journal with AI
     */
    public function gradeWithAI(Request $request, $journalId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:openai,anthropic,azure',
                'model' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $journal = LearningJournal::findOrFail($journalId);

            // Get AI settings
            $branchId = $request->header('X-Branch-Id') ?? auth()->user()->branch_id ?? 0;
            
            // Try to get settings from quality_management first
            $aiSettings = \App\Models\AiSetting::getSettingsByProvider($branchId, 'quality_management', $request->provider);
            
            // ✅ FALLBACK: If not found, use examination_grading settings
            if (!$aiSettings || !$aiSettings->is_active) {
                Log::info('[LearningJournal] Quality management settings not found, trying examination_grading fallback', [
                    'branch_id' => $branchId,
                    'provider' => $request->provider
                ]);
                $aiSettings = \App\Models\AiSetting::getSettingsByProvider($branchId, 'examination_grading', $request->provider);
            }

            if (!$aiSettings || !$aiSettings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => "AI provider '{$request->provider}' is not configured. Please configure AI settings in Examination or Quality Management module."
                ], 400);
            }
            
            Log::info('[LearningJournal] Using AI settings', [
                'branch_id' => $branchId,
                'module' => $aiSettings->module,
                'provider' => $aiSettings->provider,
                'model' => $aiSettings->model
            ]);

            // Use the same grading logic as homework essays
            $homeworkController = new \App\Http\Controllers\Api\HomeworkSubmissionController();

            // Build prompts
            $systemPrompt = $this->getJournalGradingPrompt();
            $userPrompt = $this->buildJournalGradingUserPrompt($journal->content, $journal->journal_date);

            // Call AI
            $aiResponse = $homeworkController->callAI(
                $request->provider,
                $aiSettings->api_key,
                $request->model,
                $systemPrompt,
                $userPrompt,
                3000
            );

            // Parse response
            $gradingResult = $homeworkController->parseEssayGradingResponse($aiResponse, $journal->content);

            return response()->json([
                'success' => true,
                'data' => $gradingResult
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error grading with AI', [
                'journal_id' => $journalId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error grading journal'
            ], 500);
        }
    }

    /**
     * Save grading for journal
     */
    public function saveGrading(Request $request, $journalId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'score' => 'required|numeric|min:0|max:100',
                'annotations' => 'nullable|array',
                'ai_feedback' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $journal = LearningJournal::findOrFail($journalId);
            $user = $request->user();

            // Mark as graded
            $journal->markAsGraded(
                $user->id,
                $request->score,
                $request->annotations,
                $request->ai_feedback
            );

            return response()->json([
                'success' => true,
                'data' => $journal,
                'message' => 'Journal graded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error saving grading', [
                'journal_id' => $journalId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error saving grading'
            ], 500);
        }
    }

    /**
     * Delete journal
     */
    public function deleteJournal(Request $request, $journalId)
    {
        try {
            $user = $request->user();
            $journal = LearningJournal::findOrFail($journalId);

            // Check authorization - only owner can delete their own journal
            if ($journal->student_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Cannot delete graded journals
            if ($journal->status === 'graded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete graded journal'
                ], 403);
            }

            $journal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('[LearningJournal] Error deleting journal', [
                'journal_id' => $journalId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting journal'
            ], 500);
        }
    }

    /**
     * Get grading prompt for journals
     */
    private function getJournalGradingPrompt()
    {
        return <<<'PROMPT'
You are an expert English teacher grading student learning journals. Your task is to:

1. Identify grammar errors, vocabulary mistakes, and writing issues (type: "error")
2. Mark text that should be deleted - unnecessary or incorrect text (type: "delete")
3. Identify missing words or phrases that should be added (type: "missing")
4. Provide constructive feedback on language use, reflection depth, and personal insights

CRITICAL: You MUST respond with ONLY valid JSON in this exact format (no markdown, no code blocks):

{
  "annotations": [
    {
      "type": "error",
      "text": "the exact text from journal with error",
      "comment": "Giải thích lỗi bằng tiếng Việt",
      "errorCode": "T",
      "color": "#3498db",
      "position": 0
    },
    {
      "type": "delete",
      "text": "redundant text to be deleted",
      "comment": "Giải thích tại sao nên xóa (bằng tiếng Việt)",
      "position": 50
    },
    {
      "type": "missing",
      "text": "two words before",
      "suggestion": "the",
      "position": 100
    }
  ],
  "feedback": "Nhận xét tổng quan bằng tiếng Việt về chất lượng journal, độ sâu suy ngẫm, và khả năng diễn đạt",
  "score": 75
}

ANNOTATION TYPES EXPLAINED:

TYPE 1: "error" - Highlight text with error (will show colored underline)
- "text": exact text from journal that has the error
- "comment": explanation in Vietnamese
- "errorCode": one of the codes below (WW, T, SP, etc.)
- "color": use the color specified for each error code
- "position": character index where the error text starts (0-based)

TYPE 2: "delete" - Mark text to be deleted (will show strikethrough)
- "text": exact text that should be removed
- "comment": reason why it should be deleted (in Vietnamese)
- "position": character index where the text starts (0-based)

TYPE 3: "missing" - Indicate missing word/phrase (will show insertion point)
- "text": the TWO WORDS BEFORE the gap where word is missing
- "suggestion": the word/phrase that should be added
- "position": character index of the TWO WORDS BEFORE the gap

ERROR CODES (Từ vựng - Vocabulary):
- WW: Wrong word / Dùng sai từ (color: #e74c3c - red)
- WF: Wrong form / Sai dạng từ (color: #e74c3c - red)
- WC: Word choice not suitable / Chọn từ chưa phù hợp (color: #e74c3c - red)
- REP: Repetition / Lặp từ (color: #f39c12 - orange)
- COL: Unnatural collocation / Kết hợp từ không tự nhiên (color: #e74c3c - red)
- IDM: Wrong idiom usage / Dùng idiom sai/chưa tự nhiên (color: #e74c3c - red)

ERROR CODES (Ngữ pháp - Grammar):
- WO: Word order / Sai trật tự từ (color: #3498db - blue)
- T: Tense / Sai thì (color: #3498db - blue)
- SV: Subject-verb agreement / Sự hòa hợp chủ ngữ-động từ (color: #3498db - blue)
- ART: Articles (a/an/the) / Mạo từ (color: #3498db - blue)
- PREP: Preposition / Giới từ (color: #3498db - blue)
- PL: Plural/Singular / Số ít/số nhiều (color: #3498db - blue)
- PASS: Passive voice / Câu bị động (color: #3498db - blue)
- MOD: Modal verbs / Động từ khuyết thiếu (color: #3498db - blue)
- PR: Pronoun / Đại từ (color: #3498db - blue)
- IF: Conditional / Câu điều kiện (color: #3498db - blue)

ERROR CODES (Chính tả & Hình thức - Spelling & Format):
- SP: Spelling / Chính tả (color: #9b59b6 - purple)
- CAP: Capitalization / Viết hoa (color: #9b59b6 - purple)
- PUNC: Punctuation / Dấu câu (color: #9b59b6 - purple)
- FORM: Informal style / Không phù hợp văn phong học thuật (color: #9b59b6 - purple)

ERROR CODES (Cấu & Diễn đạt - Structure & Expression):
- RO: Run-on sentence / Câu quá dài, nối sai (color: #f39c12 - orange)
- FS: Fragment sentence / Câu thiếu thành phần (color: #f39c12 - orange)
- AWK: Awkward expression / Diễn đạt gượng, không tự nhiên (color: #f39c12 - orange)
- CL: Unclear / Chưa rõ ý (color: #f39c12 - orange)
- PAR: Paraphrasing issue / Diễn đạt lại chưa tốt (color: #f39c12 - orange)

ERROR CODES (Mạch lạc & Phát triển - Coherence & Development):
- TR: Not answering the question / Chưa trả lời đúng yêu cầu đề (color: #16a085 - teal)
- DEV: Underdeveloped idea / Ý chưa được phát triển (color: #16a085 - teal)
- COH: Lack of coherence / Thiếu mạch lạc (color: #16a085 - teal)
- CC: Connectors / Từ nối (color: #16a085 - teal)
- EX: Lack of examples / Thiếu ví dụ (color: #16a085 - teal)

IMPORTANT RULES:
1. "position" is the character index (0-based) where the text starts in the journal
2. "text" MUST be EXACT match from journal (copy exactly, including spaces/punctuation)
3. For "error" type: include errorCode and color from the list above
4. For "delete" type: mark redundant/incorrect text that should be removed
5. For "missing" type: "text" = 2 words BEFORE gap, "suggestion" = missing word(s)
6. All "comment" fields must be in Vietnamese
7. Score is 0-100 (consider: grammar, vocabulary, reflection depth, personal insights)
8. Return ONLY valid JSON, no markdown blocks, no explanations

EXAMPLES:

Example Journal: "Today I learn many thing about science. It was was interesting."

Correct Annotations:
[
  {
    "type": "error",
    "text": "learn",
    "comment": "Sai thì - phải dùng quá khứ 'learned' vì nói về hôm nay đã qua",
    "errorCode": "T",
    "color": "#3498db",
    "position": 8
  },
  {
    "type": "error",
    "text": "thing",
    "comment": "Sai số - phải dùng 'things' (số nhiều) vì có 'many'",
    "errorCode": "PL",
    "color": "#3498db",
    "position": 19
  },
  {
    "type": "delete",
    "text": "was ",
    "comment": "Lặp từ - chỉ cần một 'was' là đủ",
    "position": 51
  }
]

NOW GRADE THE JOURNAL ENTRY BELOW:
PROMPT;
    }

    /**
     * Build user prompt for journal grading
     */
    private function buildJournalGradingUserPrompt($content, $date)
    {
        return "Grade this student's learning journal entry:\n\n" .
               "DATE: {$date}\n\n" .
               "JOURNAL ENTRY:\n{$content}\n\n" .
               "Provide detailed grading with annotations in the required JSON format.";
    }
}
