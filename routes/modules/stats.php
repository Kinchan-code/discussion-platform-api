<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatsController;

// Public Stats Routes
Route::get('/stats/dashboard', [StatsController::class, 'dashboard']);

