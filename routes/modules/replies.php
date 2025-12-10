<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReplyController;

// Authenticated Reply Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/comments/{comment}/replies', [ReplyController::class, 'store']);
    Route::post('/replies/{reply}/children', [ReplyController::class, 'storeChild']);
    Route::put('/replies/{reply}', [ReplyController::class, 'update']);
    Route::delete('/replies/{reply}', [ReplyController::class, 'destroy']);
});

// Public Reply Routes (read-only, no authentication required)
Route::get('/comments/{comment}/replies', [ReplyController::class, 'index']);
Route::get('/replies/{reply}/children', [ReplyController::class, 'indexChildren']);
Route::get('/replies/{reply}', [ReplyController::class, 'show']);



