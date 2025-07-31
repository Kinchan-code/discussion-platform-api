<?php

namespace App\Console\Commands;

use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Console\Command;

class CreateSampleData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sample:create {--count=10 : Number of sample records to create}';

    /**
     * The console command description.
     */
    protected $description = 'Create sample data for testing search functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        
        $this->info("Creating {$count} sample protocols and threads...");
        
        try {
            // Create sample protocols
            $this->info('Creating protocols...');
            for ($i = 1; $i <= $count; $i++) {
                $protocol = Protocol::create([
                    'title' => "Sample Protocol {$i}",
                    'content' => "This is the content for sample protocol {$i}. It contains information about medical procedures and guidelines for healthcare professionals. The protocol covers various aspects including preparation, execution, and follow-up procedures.",
                    'tags' => ['sample', 'protocol', 'medical', 'healthcare'],
                    'author' => "Dr. Sample Author {$i}",
                    'rating' => rand(30, 50) / 10, // Random rating between 3.0 and 5.0
                ]);
                
                $this->line("Created Protocol: {$protocol->title}");
                
                // Create 2-3 threads for each protocol
                $threadsCount = rand(2, 3);
                for ($j = 1; $j <= $threadsCount; $j++) {
                    $thread = Thread::create([
                        'protocol_id' => $protocol->id,
                        'title' => "Discussion Thread {$j} for Protocol {$i}",
                        'body' => "This is a discussion thread about protocol {$i}. Healthcare professionals can discuss implementation details, share experiences, and ask questions about the protocol procedures. This thread #{$j} focuses on specific aspects of the protocol.",
                    ]);
                    
                    $this->line("  Created Thread: {$thread->title}");
                }
            }
            
            $this->info('Sample data created successfully!');
            $this->info("Created {$count} protocols and " . Thread::count() . " threads total.");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create sample data: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
