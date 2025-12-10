<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;

// Public Tag Routes
Route::get('/tags/popular', [TagController::class, 'popularTags']);



