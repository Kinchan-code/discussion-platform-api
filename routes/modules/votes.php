<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VoteController;

// Authenticated Vote Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/votes', [VoteController::class, 'store']);
});

