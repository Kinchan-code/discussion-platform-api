<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Tag Management Service
 *
 * Handles tag analytics and aggregation for the discussion platform.
 * Provides methods for retrieving popular tags.
 *
 * Features:
 * - Aggregates and ranks tags from protocols
 * - Provides popular tag analytics for UI and API
 * - Optimized for performance and scalability
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Protocol
 * @see App\Models\Tag
 */
class TagService
{
    /**
     * Retrieve the most popular tags from protocols.
     *
     * @return array
     * @throws ValidationException
     */
    public function index(): array
    {
        try {
            return Tag::withCount('protocols')
                ->orderBy('protocols_count', 'desc')
                ->take(6)
                ->get()
                ->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'tag' => $tag->tag,
                        'count' => $tag->protocols_count ?? null,
                    ];
                })
                ->toArray();
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load tags due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'tags' => [$message],
            ]);
        }
    }

}
