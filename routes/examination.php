<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Examination\QuestionController;
use App\Http\Controllers\Api\Examination\QuestionTagController;
use App\Http\Controllers\Api\Examination\TestController;
use App\Http\Controllers\Api\Examination\AssignmentController;
use App\Http\Controllers\Api\Examination\SubmissionController;
use App\Http\Controllers\Api\Examination\ExamSubjectController;
use App\Http\Controllers\Api\Examination\AIGeneratorController;
use App\Http\Controllers\Api\Examination\AIPromptController;
use App\Http\Controllers\Api\Examination\PracticeTestController;

/*
|--------------------------------------------------------------------------
| Examination Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('examination')->middleware(['auth:sanctum'])->group(function () {

    // Question types (no auth required for reading)
    Route::get('question-types', [QuestionController::class, 'types']);
    Route::get('test-types', [TestController::class, 'types']);

    /*
    |--------------------------------------------------------------------------
    | Subject Routes (Môn học)
    |--------------------------------------------------------------------------
    */
    Route::prefix('subjects')->group(function () {
        Route::get('/', [ExamSubjectController::class, 'index']);
        Route::post('/', [ExamSubjectController::class, 'store']);
        Route::get('/{id}', [ExamSubjectController::class, 'show']);
        Route::put('/{id}', [ExamSubjectController::class, 'update']);
        Route::delete('/{id}', [ExamSubjectController::class, 'destroy']);

        // Categories within a subject
        Route::get('/{id}/categories', [ExamSubjectController::class, 'categories']);
        Route::post('/{id}/categories', [ExamSubjectController::class, 'addCategory']);
        Route::put('/{subjectId}/categories/{categoryId}', [ExamSubjectController::class, 'updateCategory']);
        Route::delete('/{subjectId}/categories/{categoryId}', [ExamSubjectController::class, 'deleteCategory']);
    });

    /*
    |--------------------------------------------------------------------------
    | Question Bank Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('questions')->group(function () {
        Route::get('/', [QuestionController::class, 'index']);
        Route::post('/', [QuestionController::class, 'store']);
        Route::post('/import', [QuestionController::class, 'import']);
        Route::get('/{id}', [QuestionController::class, 'show']);
        Route::put('/{id}', [QuestionController::class, 'update']);
        Route::delete('/{id}', [QuestionController::class, 'destroy']);
        Route::post('/{id}/duplicate', [QuestionController::class, 'duplicate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Question Tags Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('question-tags')->group(function () {
        Route::get('/', [QuestionTagController::class, 'index']);
        Route::post('/', [QuestionTagController::class, 'store']);
        Route::get('/{id}', [QuestionTagController::class, 'show']);
        Route::put('/{id}', [QuestionTagController::class, 'update']);
        Route::delete('/{id}', [QuestionTagController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | AI Prompts Routes (Module-based prompts for AI grading/generation)
    |--------------------------------------------------------------------------
    */
    Route::get('/ai-prompts', [AIPromptController::class, 'show']);
    Route::post('/ai-prompts', [AIPromptController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Test Bank Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('tests')->group(function () {
        Route::get('/', [TestController::class, 'index']);
        Route::post('/', [TestController::class, 'store']);
        Route::get('/{id}', [TestController::class, 'show']);
        Route::put('/{id}', [TestController::class, 'update']);
        Route::delete('/{id}', [TestController::class, 'destroy']);
        Route::post('/{id}/duplicate', [TestController::class, 'duplicate']);
        Route::get('/{id}/preview', [TestController::class, 'preview']);

        // Sections
        Route::post('/{id}/sections', [TestController::class, 'addSection']);

        // Questions management
        Route::post('/{id}/questions', [TestController::class, 'addQuestions']);
        Route::delete('/{testId}/questions/{questionId}', [TestController::class, 'removeQuestion']);
        Route::put('/{id}/questions/reorder', [TestController::class, 'reorderQuestions']);
    });

    /*
    |--------------------------------------------------------------------------
    | Assignment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('assignments')->group(function () {
        Route::get('/', [AssignmentController::class, 'index']);
        Route::post('/', [AssignmentController::class, 'store']);
        Route::get('/my', [AssignmentController::class, 'myAssignments']); // Student view
        Route::get('/{id}', [AssignmentController::class, 'show']);
        Route::put('/{id}', [AssignmentController::class, 'update']);
        Route::delete('/{id}', [AssignmentController::class, 'destroy']);
        Route::post('/{id}/assign', [AssignmentController::class, 'assign']);
        Route::get('/{id}/submissions', [AssignmentController::class, 'submissions']);
        Route::get('/{id}/statistics', [AssignmentController::class, 'statistics']);
    });

    /*
    |--------------------------------------------------------------------------
    | Submission Routes (Test Taking)
    |--------------------------------------------------------------------------
    */
    Route::prefix('submissions')->group(function () {
        // Grading list & stats (for teachers)
        Route::get('/', [SubmissionController::class, 'index']);
        Route::get('/pending-count', [SubmissionController::class, 'pendingCount']);
        Route::get('/{id}', [SubmissionController::class, 'show'])->where('id', '[0-9]+');

        // Test taking (for students)
        Route::post('/start', [SubmissionController::class, 'start']);
        Route::post('/{id}/answer', [SubmissionController::class, 'saveAnswer']);
        Route::post('/{id}/audio-response', [SubmissionController::class, 'saveAudioResponse']);
        Route::post('/{id}/submit', [SubmissionController::class, 'submit']);
        Route::get('/{id}/result', [SubmissionController::class, 'result']);
        Route::get('/{id}/remaining-time', [SubmissionController::class, 'remainingTime']);
        Route::post('/{id}/activity', [SubmissionController::class, 'logActivity']);

        // Grading
        Route::post('/{id}/answers/{answerId}/grade', [SubmissionController::class, 'gradeAnswer']);
        Route::post('/{id}/feedback', [SubmissionController::class, 'addFeedback']);

        // Publishing
        Route::post('/{id}/publish', [SubmissionController::class, 'publish']);
        Route::post('/{id}/unpublish', [SubmissionController::class, 'unpublish']);
        Route::post('/bulk-publish', [SubmissionController::class, 'bulkPublish']);
        
        // Special View Toggle (Allow sharing with users who have special_view permission)
        Route::post('/{id}/toggle-special-view', [SubmissionController::class, 'toggleSpecialView']);
    });

    /*
    |--------------------------------------------------------------------------
    | Practice Test Routes (IELTS Full Tests)
    |--------------------------------------------------------------------------
    */
    Route::prefix('practice-tests')->group(function () {
        Route::get('/', [PracticeTestController::class, 'index']);
        Route::post('/', [PracticeTestController::class, 'store']);
        Route::get('/available-tests', [PracticeTestController::class, 'availableTests']);
        Route::get('/{id}', [PracticeTestController::class, 'show']);
        Route::put('/{id}', [PracticeTestController::class, 'update']);
        Route::delete('/{id}', [PracticeTestController::class, 'destroy']);
        Route::post('/{id}/duplicate', [PracticeTestController::class, 'duplicate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Practice Test Submissions (Direct submission without assignment)
    |--------------------------------------------------------------------------
    */
    Route::post('/submissions', [PracticeTestController::class, 'submit']);

    /*
    |--------------------------------------------------------------------------
    | AI Generator Routes (Image generation, Writing prompts, Samples)
    |--------------------------------------------------------------------------
    */
    Route::post('/generate-image', [AIGeneratorController::class, 'generateImage']);
    Route::post('/generate-prompt', [AIGeneratorController::class, 'generatePrompt']);
    Route::post('/generate-sample', [AIGeneratorController::class, 'generateSample']);

    // QuickChart-based chart generation (AI generates data, QuickChart renders image)
    Route::post('/generate-chart-data', [AIGeneratorController::class, 'generateChartData']);
    Route::post('/generate-chart', [AIGeneratorController::class, 'generateChart']);

    // AI Settings management
    Route::get('/ai-settings', [AIGeneratorController::class, 'getAiSettings']);
    Route::post('/ai-settings', [AIGeneratorController::class, 'saveAiSettings']);

    // AI Grading (uses API key from database)
    Route::post('/grade-with-ai', [AIGeneratorController::class, 'gradeWithAI']);
    Route::post('/grade-speaking-with-ai', [AIGeneratorController::class, 'gradeSpeakingWithAI']);

    // IELTS Content Generation (listening/reading)
    Route::post('/generate-ielts-content', [AIGeneratorController::class, 'generateIELTSContent']);

    // IELTS Speaking Test Generation (AI creates full 3-part test with examiner name)
    Route::post('/generate-speaking-test', [AIGeneratorController::class, 'generateSpeakingTest']);

    // Azure Text-to-Speech for Speaking tests
    Route::post('/azure-tts', [AIGeneratorController::class, 'azureTTS']);

    // Submit IELTS Speaking Test recordings
    Route::post('/speaking-submissions', [AIGeneratorController::class, 'submitSpeakingTest']);

    // AI Question Generation
    Route::post('/generate-questions', [AIGeneratorController::class, 'generateQuestions']);

    // Upload routes
    Route::post('/upload/image', [AIGeneratorController::class, 'uploadImage']);
    Route::post('/upload/audio', [AIGeneratorController::class, 'uploadAudio']);

});
