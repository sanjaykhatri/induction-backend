<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InductionController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\Admin\InductionController as AdminInductionController;
use App\Http\Controllers\Api\Admin\ChapterController;
use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\VideoCompletionController;
use App\Http\Controllers\Api\UserProgressController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/admin/login', [AuthController::class, 'adminLogin']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// User routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/inductions/active', [InductionController::class, 'active']);
    Route::get('/inductions/{induction}/completed', [InductionController::class, 'getCompleted']);
    Route::post('/inductions/{induction}/start', [InductionController::class, 'start']);
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
    Route::get('/submissions/{submission}/last-unanswered', [SubmissionController::class, 'getLastUnanswered']);
    Route::post('/submissions/{submission}/answers', [SubmissionController::class, 'submitAnswers']);
    Route::post('/submissions/{submission}/complete', [SubmissionController::class, 'complete']);

    // Video completion tracking
    Route::post('/chapters/{chapter}/video/progress', [VideoCompletionController::class, 'updateProgress']);
    Route::post('/chapters/{chapter}/video/complete', [VideoCompletionController::class, 'markCompleted']);
    Route::get('/chapters/{chapter}/video/completion', [VideoCompletionController::class, 'checkCompletion']);

    // User progress
    Route::get('/progress', [UserProgressController::class, 'index']);
    Route::get('/progress/submissions/{submission}', [UserProgressController::class, 'show']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Inductions management
    Route::apiResource('inductions', AdminInductionController::class);
    Route::post('inductions/{induction}/reorder', [AdminInductionController::class, 'reorder']);
    Route::post('inductions/import', [\App\Http\Controllers\Api\Admin\InductionImportController::class, 'import']);

    // Chapters management
    Route::get('inductions/{induction}/chapters', [ChapterController::class, 'index']);
    Route::post('inductions/{induction}/chapters', [ChapterController::class, 'store']);
    Route::match(['PUT', 'POST'], 'chapters/{chapter}', [ChapterController::class, 'update']); // Accept both PUT and POST for method spoofing
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy']);
    Route::post('chapters/{chapter}/reorder', [ChapterController::class, 'reorder']);

    // Questions management
    Route::get('chapters/{chapter}/questions', [QuestionController::class, 'index']);
    Route::post('chapters/{chapter}/questions', [QuestionController::class, 'store']);
    Route::put('questions/{question}', [QuestionController::class, 'update']);
    Route::delete('questions/{question}', [QuestionController::class, 'destroy']);
    Route::post('questions/{question}/reorder', [QuestionController::class, 'reorder']);

    // Submissions management
    Route::get('submissions', [AdminSubmissionController::class, 'index']);
    Route::get('submissions/{submission}', [AdminSubmissionController::class, 'show']);

    // Admin management
    Route::get('admins', [AdminController::class, 'index']);
    Route::post('admins', [AdminController::class, 'store']);
    Route::put('admins/{user}', [AdminController::class, 'update']);
    Route::delete('admins/{user}', [AdminController::class, 'destroy']);
});

