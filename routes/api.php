<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpeakingAttemptController;
use App\Http\Controllers\Api\SpeakingQuestionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/speaking/questions', [SpeakingQuestionController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/speaking/submit', [SpeakingAttemptController::class, 'store'])->middleware('throttle:speaking-submit');
    Route::get('/speaking/attempts', [SpeakingAttemptController::class, 'index']);
    Route::get('/speaking/attempts/{attempt}', [SpeakingAttemptController::class, 'show']);
});
