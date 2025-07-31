<?php

namespace App\Console\Commands;

use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Console\Command;

class DatabaseStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:status';

    /**
     * The console command description.
     */
    protected $description = 'Show database record counts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Database Status:');
        $this->line('================');
        $this->info('Protocols: ' . Protocol::count());
        $this->info('Threads: ' . Thread::count());
        
        return self::SUCCESS;
    }
}
