<?php

namespace App\Services;

use App\DTOs\TagDTO;
use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Support\Facades\Artisan;

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
        Artisan::call('scout:flush', ['model' => 'App\Models\Protocol']);
        Artisan::call('scout:flush', ['model' => 'App\Models\Thread']);

        Artisan::call('scout:import', ['model' => 'App\Models\Protocol']);
        Artisan::call('scout:import', ['model' => 'App\Models\Thread']);

        return [
            'protocols_indexed' => Protocol::count(),
            'threads_indexed' => Thread::count(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
