<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeworkExerciseController;
use App\Http\Controllers\Api\HomeworkSubmissionController;

/*
|--------------------------------------------------------------------------
| Homework Exercise API Routes
|--------------------------------------------------------------------------
|
| Routes for the Homework Exercise Bank module.
| These routes handle CRUD operations for exercises with branch filtering.
|
*/

Route::middleware(['auth:sanctum'])->prefix('homework')->group(function () {

    // Exercise Management (Exercise Bank)
    Route::prefix('exercises')->group(function () {
        Route::get('/', [HomeworkExerciseController::class, 'index']); // List exercises (branch-filtered)
        Route::post('/', [HomeworkExerciseController::class, 'store']); // Create exercise
        Route::get('/statistics', [HomeworkExerciseController::class, 'statistics']); // Get statistics
        Route::get('/{id}', [HomeworkExerciseController::class, 'show']); // View single exercise
        Route::put('/{id}', [HomeworkExerciseController::class, 'update']); // Update exercise
        Route::delete('/{id}', [HomeworkExerciseController::class, 'destroy']); // Delete exercise
    });

    // AI Generation
    Route::post('/generate-with-ai', [HomeworkExerciseController::class, 'generateWithAI']); // Generate homework with AI

    // Homework Bank (Templates for syllabus/quality control)
    Route::prefix('bank')->group(function () {
        Route::get('/', [HomeworkExerciseController::class, 'indexBank']); // List homework banks
        Route::post('/', [HomeworkExerciseController::class, 'storeBank']); // Create homework bank
        Route::get('/{id}', [HomeworkExerciseController::class, 'showBank']); // View single homework bank
        Route::put('/{id}', [HomeworkExerciseController::class, 'updateBank']); // Update homework bank
        Route::delete('/{id}', [HomeworkExerciseController::class, 'destroyBank']); // Delete homework bank
    });
});
