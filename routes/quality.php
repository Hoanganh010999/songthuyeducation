<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Quality\QualityAIController;
use App\Http\Controllers\Api\Quality\SessionController;
use App\Http\Controllers\Api\Examination\AIPromptController;

/*
|--------------------------------------------------------------------------
| Quality Management Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('quality')->middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AI Settings Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/ai-settings', [QualityAIController::class, 'getAiSettings']);
    Route::post('/ai-settings', [QualityAIController::class, 'saveAiSettings']);

    /*
    |--------------------------------------------------------------------------
    | AI Prompts Routes (Custom prompts for lesson shapes)
    |--------------------------------------------------------------------------
    */
    Route::get('/ai-prompts', [AIPromptController::class, 'show']);
    Route::post('/ai-prompts', [AIPromptController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Lesson Plan Generation Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/generate-lesson-plan', [QualityAIController::class, 'generateLessonPlan']);

    /*
    |--------------------------------------------------------------------------
    | Session & Lesson Plan Editor Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/sessions/{id}', [SessionController::class, 'show']);
    Route::post('/sessions/{id}/lesson-plan', [SessionController::class, 'saveLessonPlan']);

});
