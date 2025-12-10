<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Review;
use App\Models\Tag;
use App\Http\Requests\ProtocolRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $perPage = $request->input('per_page', 15);
            $sort = $request->input('sort', 'recent');
            $tags = $request->input('tags');
            $author = $request->input('author');

            // Handle 'current_user' special case for authenticated requests
            if ($author === 'current_user') {
                if (!$request->user()) {
                    throw new \Exception('Authentication required for current_user filter.');
                }
                $author = $request->user()->name;
            }

            $query = Protocol::with(['tags'])
                ->withCount(['reviews', 'threads'])
                ->withAvg('reviews', 'rating');

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
                $query->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('tag', $tags);
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

            $protocols = $query->paginate($perPage);
            
            // Fix for eager loading issue: fetch tags with protocols_count for each protocol
            // Note: setRelation works but property access doesn't, so we use getRelationValue in resources
            $protocols->getCollection()->each(function ($protocol) {
                $tags = $protocol->tags()->withCount('protocols')->get();
                $protocol->setRelation('tags', $tags);
            });
            
            return $protocols;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load protocols due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'protocols' => [$message],
            ]);
        }
    }

    /**
     * Get a single protocol with counts.
     *
     * @param string $id
     * @return Protocol
     * @throws ValidationException
     */
    public function show(string $id): Protocol
    {
        try {
            $protocol = Protocol::withCount(['reviews', 'threads'])
                ->withAvg('reviews', 'rating')
                ->findOrFail($id);
            
            // Fetch tags with protocols_count due to eager loading issue with belongsToMany
            $tags = $protocol->tags()->withCount('protocols')->get();
            $protocol->setRelation('tags', $tags);
            
            return $protocol;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load the protocol due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'protocol' => [$message],
            ]);
        }
    }

    /**
     * Create a new protocol.
     *
     * @param ProtocolRequest $request
     * @return Protocol
     * @throws ValidationException
     */
    public function store(ProtocolRequest $request): Protocol
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $user = $request->user();

                $protocol = Protocol::create([
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'author' => $user ? $user->name : 'Anonymous',
                ]);

                // Sync tags
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagIds = collect($data['tags'])->map(function ($tagName) {
                        return Tag::firstOrCreate(['tag' => trim($tagName)])->id;
                    })->toArray();
                    $protocol->tags()->sync($tagIds);
                }

                // Load relationships for search indexing
                $protocol->load(['tags', 'reviews', 'threads']);
                $protocol->loadCount(['reviews', 'threads']);

                return $protocol;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the protocol due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'protocol' => [$message],
            ]);
        }
    }

    /**
     * Update an existing protocol.
     *
     * @param string $id
     * @param ProtocolRequest $request
     * @return Protocol
     * @throws ValidationException
     */
    public function update(string $id, ProtocolRequest $request): Protocol
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $protocol = Protocol::findOrFail($id);
                $user = $request->user();

                if ($protocol->author !== $user->name) {
                    throw new \Exception('You can only update protocols that you created.');
                }

                $data = $request->validated();

                $protocol->fill([
                    'title' => $data['title'] ?? $protocol->title,
                    'content' => $data['content'] ?? $protocol->content,
                ]);
                $protocol->save();

                // Sync tags if provided
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagIds = collect($data['tags'])->map(function ($tagName) {
                        return Tag::firstOrCreate(['tag' => trim($tagName)])->id;
                    })->toArray();
                    $protocol->tags()->sync($tagIds);
                }

                $protocol->load(['tags']);
                $protocol->loadCount(['reviews', 'threads']);

                return $protocol;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the protocol due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'protocol' => [$message],
            ]);
        }
    }

    /**
     * Delete a protocol.
     *
     * @param string $id
     * @return void
     * @throws ValidationException
     */
    public function destroy(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $protocol = Protocol::findOrFail($id);
                $user = Auth::user();

                if ($protocol->author !== $user->name) {
                    throw new \Exception('You can only delete protocols that you created.');
                }

                $protocol->delete();
            });
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t delete the protocol due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'protocol' => [$message],
            ]);
        }
    }

    /**
     * Get protocol stats (counts, average, distribution).
     *
     * @param mixed $id
     * @return array|null
     */
    public function getProtocolStats($id): ?array
    {
        $protocol = Protocol::with(['tags'])
            ->withCount(['reviews', 'threads'])
            ->withAvg('reviews', 'rating')
            ->with(['reviews' => function ($query) {
                $query->select('protocol_id', 'rating');
            }])
            ->find($id);

        if (!$protocol) return null;

        $reviewsCount = $protocol->getAttribute('reviews_count') ?? 0;
        $threadsCount = $protocol->getAttribute('threads_count') ?? 0;
        $averageRating = round($protocol->getAttribute('reviews_avg_rating') ?? 0.0, 2);
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
