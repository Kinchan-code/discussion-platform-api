<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;

// Authenticated Comment Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/threads/{thread}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

// Public Comment Routes (read-only, no authentication required)
Route::get('/threads/{thread}/comments', [CommentController::class, 'index']);
Route::get('/comments/{comment}', [CommentController::class, 'show']);



