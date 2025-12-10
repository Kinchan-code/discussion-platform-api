<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThreadController;

// Authenticated Thread Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/threads', [ThreadController::class, 'store']);
    Route::put('/threads/{id}', [ThreadController::class, 'update']);
    Route::delete('/threads/{id}', [ThreadController::class, 'destroy']);
});

// Public Thread Routes (read-only, no authentication required)
Route::get('/threads', [ThreadController::class, 'index']);
Route::get('/threads/trending', [ThreadController::class, 'trending']);
Route::get('/threads/{id}', [ThreadController::class, 'show']);
Route::get('/threads/{id}/stats', [ThreadController::class, 'stats']);
Route::get('/protocols/{protocol}/threads', [ThreadController::class, 'byProtocol']);



