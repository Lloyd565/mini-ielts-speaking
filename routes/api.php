<?php

use App\Http\Controllers\Api\SpeakingAttemptController;
use App\Http\Controllers\Api\SpeakingQuestionController;
use Illuminate\Support\Facades\Route;

Route::get('/speaking/questions', [SpeakingQuestionController::class, 'index']);
Route::post('/speaking/submit', [SpeakingAttemptController::class, 'store'])->middleware('throttle:speaking-submit');
Route::get('/speaking/attempts', [SpeakingAttemptController::class, 'index']);
Route::get('/speaking/attempts/{attempt}', [SpeakingAttemptController::class, 'show']);
