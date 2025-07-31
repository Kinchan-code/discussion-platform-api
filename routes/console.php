<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Production-ready commands can be added here
// Examples:
// - Database maintenance commands
// - Cache management commands  
// - User management commands
// - Data migration commands

/*
|--------------------------------------------------------------------------
| Development/Testing Commands (Remove in Production)
|--------------------------------------------------------------------------
|
| The following commands are for development and testing purposes only.
| Consider removing them before deploying to production, or wrap them
| in environment checks: if (app()->environment(['local', 'development']))
|
*/

// Uncomment for development/testing only:
/*
if (app()->environment(['local', 'development', 'testing'])) {
    
    // Add your test commands here when needed
    // Artisan::command('test:performance', function () { ... });
    // Artisan::command('test:review-highlighting', function () { ... });
    // etc.
    
}
*/
