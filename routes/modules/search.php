<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

// Public Search Routes
Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/suggestions', [SearchController::class, 'suggestions']);

