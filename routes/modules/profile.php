<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Authenticated Profile Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/profile/statistics', [ProfileController::class, 'statistics']);
    Route::get('/profile/replies', [ProfileController::class, 'replies']);
    Route::get('/profile/comments', [ProfileController::class, 'comments']);
    Route::get('/profile/reviews', [ProfileController::class, 'reviews']);
});

