<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Protocol Management Service
 *
 * Handles protocol listing, creation, retrieval, statistics, and rating distribution for the platform.
 * Provides methods for paginated protocol queries, filtering, sorting, and statistics aggregation.
 *
 * Features:
 * - Paginated protocol listing with filters and sorting
 * - Protocol creation and attribute management
 * - Protocol statistics and rating distribution
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Protocol
 * @see App\Models\Review
 */
class ProtocolService
{
    /**
     * Get a paginated list of protocols with counts and optional filters/sorting.
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function listProtocols($params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $sort = $params['sort'] ?? 'recent';
        $tags = $params['tags'] ?? null;
        $author = $params['author'] ?? null;

        $query = Protocol::withCount(['reviews', 'threads']);

        // Author filtering
        if ($author) {
            $query->where('author', $author);
        }

        // Tag filtering
        if ($tags) {
            if (is_string($tags)) {
                $tags = explode(',', $tags);
            }
            $tags = array_map('trim', $tags);
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->whereJsonContains('tags', $tag);
                }
            });
        }

        // Sorting
        switch ($sort) {
            case 'popular':
                $query->orderBy('threads_count', 'desc')
                    ->orderBy('reviews_count', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'rating':
                $query->withAvg('reviews', 'rating')
                    ->orderBy('reviews_avg_rating', 'desc')
                    ->orderBy('reviews_count', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }


        return $query->paginate($perPage);
    }

    /**
     * Get a single protocol with counts.
     *
     * @param mixed $id
     * @return Protocol
     */
    public function getProtocol($id): Protocol
    {
        return Protocol::withCount(['reviews', 'threads'])->findOrFail($id);
    }

    /**
     * Create a new protocol.
     *
     * @param array $data Protocol data (title, content, tags)
     * @param mixed $user User creating the protocol
     * @return Protocol
     */
    public function createProtocol(array $data, $user = null): Protocol
    {
        $protocol = Protocol::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'tags' => $data['tags'] ?? [],
            'author' => optional($user)->name ?? 'Anonymous',
        ]);

        // Load count relationships for search indexing
        $protocol->load(['reviews', 'threads']);
        $protocol->loadCount(['reviews', 'threads']);

        return $protocol;
    }

    /**
     * Get protocol stats (counts, average, distribution).
     *
     * @param mixed $id
     * @return array|null
     */
    public function getProtocolStats($id): ?array
    {
        $protocol = Protocol::withCount(['reviews', 'threads'])
            ->with(['reviews' => function ($query) {
                $query->select('protocol_id', 'rating');
            }])
            ->find($id);

        if (!$protocol) return null;

        $reviewsCount = $protocol->getAttribute('reviews_count') ?? 0;
        $threadsCount = $protocol->getAttribute('threads_count') ?? 0;
        $averageRating = round($protocol->getAverageRatingAttribute(), 2);
        $distribution = $this->getRatingDistribution($id, $protocol->reviews);

        return [
            'protocol_id' => $protocol->getKey(),
            'total_reviews' => $reviewsCount,
            'total_threads' => $threadsCount,
            'average_rating' => $averageRating,
            'rating_distribution' => $distribution,
        ];
    }

    /**
     * Get rating distribution for a protocol.
     *
     * @param mixed $protocolId
     * @param mixed $reviews
     * @return array
     */
    public function getRatingDistribution($protocolId, $reviews = null): array
    {
        // Use preloaded reviews if available, otherwise query database
        if ($reviews) {
            $reviewCounts = collect($reviews)->countBy('rating');
        } else {
            $reviewCounts = Review::where('protocol_id', $protocolId)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating');
        }

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviewCounts->get($i, 0);
        }
        return $distribution;
    }
}
