<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProtocolController;

// Authenticated Protocol Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/protocols', [ProtocolController::class, 'store']);
    Route::put('/protocols/{id}', [ProtocolController::class, 'update']);
    Route::delete('/protocols/{id}', [ProtocolController::class, 'destroy']);
});

// Public Protocol Routes (read-only, no authentication required)
Route::get('/protocols', [ProtocolController::class, 'index']);
Route::get('/protocols/featured', [ProtocolController::class, 'featured']);
Route::get('/protocols/filters', [ProtocolController::class, 'filters']);
Route::get('/protocols/{id}', [ProtocolController::class, 'show']);
Route::get('/protocols/{id}/stats', [ProtocolController::class, 'stats']);



