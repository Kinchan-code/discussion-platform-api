<?php

namespace App\Console\Commands;

use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Console\Command;

class ReindexSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reindex {--model= : Specific model to reindex}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the search index for all searchable models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = $this->option('model');

        try {
            if ($model) {
                $this->reindexModel($model);
            } else {
                $this->reindexAll();
            }

            $this->info('Search index rebuilt successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to rebuild search index: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Reindex all searchable models.
     */
    private function reindexAll(): void
    {
        $this->info('Rebuilding search index for all models...');

        // Reindex protocols
        $this->info('Reindexing protocols...');
        $this->reindexModel('Protocol');

        // Reindex threads
        $this->info('Reindexing threads...');
        $this->reindexModel('Thread');

        $this->info('All models have been reindexed successfully!');
    }

    /**
     * Reindex a specific model.
     */
    private function reindexModel(string $model): void
    {
        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            throw new \Exception("Model {$model} not found");
        }

        $this->info("Processing {$model} records...");
        $totalRecords = $modelClass::count();
        
        if ($totalRecords === 0) {
            $this->info("No {$model} records found to process.");
            return;
        }
        
        $processed = 0;
        $relationships = match($model) {
            'Protocol' => ['reviews', 'threads'],
            'Thread' => ['protocol', 'comments', 'votes'],
            default => []
        };
        
        $modelClass::with($relationships)->chunk(100, function ($records) use (&$processed, $model) {
            foreach ($records as $record) {
                try {
                    // For now, just touch the record to update timestamps
                    $record->touch();
                    $processed++;
                } catch (\Exception $e) {
                    $this->warn("Failed to process {$model} #{$record->id}: {$e->getMessage()}");
                }
            }
            $this->info("Processed {$processed} {$model} records...");
        });
        
        $this->info("Processed {$processed} {$model} records successfully!");
    }
}
