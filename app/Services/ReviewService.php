<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Protocol;
use App\Http\Requests\ReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Review Management Service
 *
 * Handles review listing, creation, deletion, retrieval, and updates for protocols.
 * Provides methods for paginated review queries and filtering.
 *
 * Features:
 * - Paginated review listing with filters
 * - Review creation, update, and deletion with authorization
 * - Optimized queries using Eloquent relationships
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Review
 * @see App\Models\Protocol
 * @see App\Models\User
 * @see App\Http\Resources\ReviewResource
 */

class ReviewService
{
    /**
     * Get paginated reviews for a protocol with filtering.
     *
     * @param string $protocolId
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function index(string $protocolId, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $protocol = Protocol::findOrFail($protocolId);
            $perPage = min($request->get('per_page', 10), 50);
            $author = $request->get('author');

            $query = $protocol->reviews()
                ->with(['protocol'])
                ->latest('created_at');

            // Add author filter support
            if ($author) {
                // Handle 'current_user' special case
                if ($author === 'current_user') {
                    if (!Auth::check()) {
                        throw new \Exception('Authentication required for current_user filter.');
                    }
                    $author = Auth::user()->name;
                }

                $query->where('author', $author);
            }

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load reviews due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reviews' => [$message],
            ]);
        }
    }

    /**
     * Create a new review for a protocol.
     *
     * @param string $protocolId
     * @param ReviewRequest $request
     * @return Review
     * @throws ValidationException
     */
    public function store(string $protocolId, ReviewRequest $request): Review
    {
        try {
            return DB::transaction(function () use ($protocolId, $request) {
                $protocol = Protocol::findOrFail($protocolId);
                $user = Auth::user();
                $data = $request->validated();

                $review = $protocol->reviews()->create([
                    'rating' => $data['rating'],
                    'feedback' => $data['feedback'] ?? null,
                    'author' => $user->name,
                ]);

                $review->load(['protocol']);

                return $review;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the review due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'review' => [$message],
            ]);
        }
    }

    /**
     * Update an existing review.
     *
     * @param string $id
     * @param ReviewRequest $request
     * @return Review
     * @throws ValidationException
     */
    public function update(string $id, ReviewRequest $request): Review
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $review = Review::findOrFail($id);
                $user = Auth::user();

                if ($review->author !== $user->name) {
                    throw new \Exception('You can only update reviews that you created.');
                }

                $data = $request->validated();

                $review->fill([
                    'rating' => $data['rating'] ?? $review->rating,
                    'feedback' => $data['feedback'] ?? $review->feedback,
                ]);
                $review->save();

                $review->load(['protocol']);

                return $review;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the review due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'review' => [$message],
            ]);
        }
    }

    /**
     * Get a single review by ID.
     *
     * @param string $id
     * @param Request $request
     * @return Review
     * @throws ValidationException
     */
    public function show(string $id, Request $request): Review
    {
        try {
            $query = Review::with(['protocol']);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            return $query->findOrFail($id);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load the review due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'review' => [$message],
            ]);
        }
    }

    /**
     * Delete a review.
     *
     * @param string $id
     * @return void
     * @throws ValidationException
     */
    public function destroy(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $review = Review::findOrFail($id);
                $user = Auth::user();

                if ($review->author !== $user->name) {
                    throw new \Exception('You can only delete reviews that you created.');
                }

                $review->delete();
            });
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t delete the review due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'review' => [$message],
            ]);
        }
    }

}
