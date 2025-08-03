<?php

namespace App\Services;

use App\DTOs\TagDTO;
use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Support\Facades\Log;

/**
 * Tag Management Service
 *
 * Handles tag analytics, aggregation, and search index management for the discussion platform.
 * Provides methods for retrieving popular tags and reindexing Protocol and Thread models for search.
 *
 * Features:
 * - Aggregates and ranks tags from protocols
 * - Provides popular tag analytics for UI and API
 * - Manages reindexing of Protocol and Thread models with Laravel Scout
 * - Optimized for performance and scalability
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Protocol
 * @see App\Models\Thread
 * @see App\DTOs\TagDTO
 */
class TagService
{
    /**
     * Retrieve the most popular tags from protocols.
     *
     * @return array<TagDTO>
     */
    public function getPopularTags(): array
    {
        $tags = Protocol::whereNotNull('tags')
            ->select('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(6)
            ->map(function ($count, $tag) {
                return new TagDTO(
                    id: $tag,
                    tag: $tag,
                    count: $count
                );
            })
            ->values()
            ->toArray();

        return $tags;
    }

    /**
     * Reindex Protocol and Thread models for search.
     *
     * @return array
     */
    public function reindexSearchModels(): array
    {
        Log::info('Starting search reindex process...');

        // Remove all existing records from search index
        Log::info('Removing all Protocol records from search index...');
        Protocol::removeAllFromSearch();

        Log::info('Removing all Thread records from search index...');
        Thread::removeAllFromSearch();

        // Give search engine time to process deletions
        sleep(2);

        // Re-add all current records to search index
        Log::info('Adding all Protocol records to search index...');
        Protocol::makeAllSearchable();

        Log::info('Adding all Thread records to search index...');
        Thread::makeAllSearchable();

        $protocolCount = Protocol::count();
        $threadCount = Thread::count();

        Log::info("Reindexing complete: {$protocolCount} protocols, {$threadCount} threads");

        return [
            'protocols_indexed' => $protocolCount,
            'threads_indexed' => $threadCount,
            'timestamp' => now()->toISOString(),
        ];
    }
}
