<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

// Authenticated Review Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/protocols/{protocol}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

// Public Review Routes (read-only, no authentication required)
Route::get('/protocols/{protocol}/reviews', [ReviewController::class, 'index']);



