<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Protocol;
use App\Models\User;
use App\DTOs\ReviewDTO;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Review Management Service
 *
 * Handles review listing, creation, deletion, retrieval, and smart highlighting for protocols.
 * Provides methods for paginated review queries, filtering, and optimized highlighting logic.
 *
 * Features:
 * - Paginated review listing with filters and highlighting
 * - Review creation and deletion with authorization
 * - Efficient review retrieval and highlight location calculation
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Review
 * @see App\Models\Protocol
 * @see App\Models\User
 * @see App\DTOs\ReviewDTO
 */

class ReviewService
{
    /**
     * Get paginated reviews for a protocol with filtering and smart highlighting support.
     *
     * @param Protocol $protocol
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getProtocolReviews(Protocol $protocol, Request $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 10);
        $author = $request->get('author');
        $highlightReviewId = $request->get('highlight_review');

        // Use the protocol ID directly to leverage database indexes
        $reviewsQuery = Review::where('protocol_id', $protocol->id)
            ->latest('created_at');

        // Add author filter support
        if ($author) {
            // Handle 'current_user' special case
            if ($author === 'current_user') {
                if (!$request->user()) {
                    throw new \Exception('Authentication required for current_user filter.');
                }
                $author = $request->user()->name;
            }

            $reviewsQuery->where('author', $author);
        }

        // Handle smart highlighting logic
        if ($highlightReviewId) {
            return $this->getReviewsWithSmartHighlighting($reviewsQuery, $request, $perPage, $highlightReviewId);
        }

        // Standard pagination when no highlighting
        $reviews = $reviewsQuery->paginate($perPage);

        // Transform each review to DTO
        $reviews->getCollection()->transform(function ($review) use ($highlightReviewId) {
            $reviewDto = ReviewDTO::fromModel($review);
            $reviewArray = $reviewDto->toArray();
            $reviewArray['is_highlighted'] = false; // No highlighting in standard pagination
            return $reviewArray;
        });

        return $reviews;
    }

    /**
     * Create a new review for a protocol.
     *
     * @param Protocol $protocol
     * @param User $user
     * @param array $data
     * @return ReviewDTO
     */
    public function createReview(Protocol $protocol, User $user, array $data): ReviewDTO
    {
        $review = $protocol->reviews()->create([
            'rating' => $data['rating'],
            'feedback' => $data['feedback'] ?? null,
            'author' => $user->name,
        ]);

        return ReviewDTO::fromModel($review);
    }

    /**
     * Update an existing review if user is authorized.
     *
     * @param Review $review
     * @param User $user
     * @param array $data
     * @return ReviewDTO
     * @throws \Exception When user is not the author
     */
    public function updateReview(Review $review, User $user, array $data): ReviewDTO
    {
        // Check if the authenticated user is the author
        if ($review->author !== $user->name) {
            throw new \Exception('You can only update reviews that you created.');
        }

        // Only allow updating certain fields
        $fieldsToUpdate = [
            'rating' => $data['rating'] ?? $review->rating,
            'feedback' => $data['feedback'] ?? $review->feedback,
        ];
        $review->fill($fieldsToUpdate);

        // Only save if something actually changed
        if ($review->isDirty()) {
            $review->save();
        }

        return ReviewDTO::fromModel($review);
    }

    /**
     * Delete a review if user is authorized.
     *
     * @param Review $review
     * @param User $user
     * @return void
     * @throws \Exception When user is not the author
     */
    public function deleteReview(Review $review, User $user): void
    {
        // Check if the authenticated user is the author
        if ($review->author !== $user->name) {
            throw new \Exception('You can only delete reviews that you created.');
        }

        $review->delete();
    }

    /**
     * Get a single review by ID.
     *
     * @param int $id
     * @return Review
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getReview(int $id): Review
    {
        return Review::findOrFail($id);
    }

    /**
     * Get reviews with smart highlighting that ensures highlighted items are visible.
     *
     * @param $reviewsQuery
     * @param Request $request
     * @param int $perPage
     * @param $highlightReviewId
     * @return LengthAwarePaginator
     */
    private function getReviewsWithSmartHighlighting($reviewsQuery, Request $request, int $perPage, $highlightReviewId): LengthAwarePaginator
    {
        $currentPage = $request->get('page', 1);

        $highlightedReview = $this->findHighlightedReview($reviewsQuery, $highlightReviewId);

        if (!$highlightedReview) {
            return $this->executePaginationWithTransform($reviewsQuery, $perPage, $highlightReviewId, false);
        }

        $highlightLocation = $this->calculateReviewHighlightLocation($reviewsQuery, $highlightedReview, $perPage);
        $naturalPage = $highlightLocation['natural_page'] ?? 1;

        if ($naturalPage == $currentPage) {
            return $this->executePaginationWithTransform($reviewsQuery, $perPage, $highlightReviewId, true);
        }

        $reviews = $reviewsQuery->paginate($perPage);

        $transformedItems = [];
        foreach ($reviews->items() as $review) {
            $reviewDto = ReviewDTO::fromModel($review);
            $reviewArray = $reviewDto->toArray();
            $reviewArray['is_highlighted'] = false;
            $transformedItems[] = $reviewArray;
        }

        $highlightedDto = ReviewDTO::fromModel($highlightedReview);
        $highlightedArray = $highlightedDto->toArray();
        $highlightedArray['is_highlighted'] = true;

        $allItems = array_merge([$highlightedArray], $transformedItems);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $allItems,
            $reviews->total(),
            $reviews->perPage(),
            $reviews->currentPage(),
            [
                'path' => $request->url(),
                'pageName' => 'page',
                'highlight_info' => [
                    'found_on_different_page' => true,
                    'natural_location' => $highlightLocation,
                    'message' => 'Highlighted review included from different page for visibility'
                ]
            ]
        );
    }

    /**
     * Execute pagination with efficient transformation.
     *
     * @param $reviewsQuery
     * @param int $perPage
     * @param $highlightReviewId
     * @param bool $hasHighlight
     * @return LengthAwarePaginator
     */
    private function executePaginationWithTransform($reviewsQuery, int $perPage, $highlightReviewId, bool $hasHighlight): LengthAwarePaginator
    {
        $reviews = $reviewsQuery->paginate($perPage);

        // Single-pass transformation for better performance
        $reviews->getCollection()->transform(function ($review) use ($highlightReviewId, $hasHighlight) {
            $reviewDto = ReviewDTO::fromModel($review);
            $reviewArray = $reviewDto->toArray();
            $reviewArray['is_highlighted'] = $hasHighlight && $review->id == $highlightReviewId;
            return $reviewArray;
        });

        return $reviews;
    }

    /**
     * Find highlighted review by ID within the query scope.
     *
     * @param $baseQuery
     * @param $reviewId
     * @return mixed
     */
    private function findHighlightedReview($baseQuery, $reviewId)
    {
        // Clone the base query to maintain filters and sorting
        $query = clone $baseQuery;

        // Only select fields needed for the review (optimization)
        return $query->select(['id', 'protocol_id', 'rating', 'feedback', 'author', 'created_at', 'updated_at'])
            ->where('id', $reviewId)
            ->first();
    }

    /**
     * Calculate where the highlighted review would naturally appear.
     *
     * @param $reviewsQuery
     * @param $highlightedItem
     * @param int $perPage
     * @return array|null
     */
    private function calculateReviewHighlightLocation($reviewsQuery, $highlightedItem, int $perPage): ?array
    {
        if (!$highlightedItem) return null;

        // Use more efficient counting query - only count, don't fetch data
        $query = clone $reviewsQuery;
        $countBefore = $query->where('created_at', '>', $highlightedItem->created_at)
            ->count();

        $naturalPage = intval(ceil(($countBefore + 1) / $perPage));
        $positionInPage = ($countBefore % $perPage) + 1;

        return [
            'natural_page' => $naturalPage,
            'position_in_page' => $positionInPage,
            'url_to_natural_location' => "?page={$naturalPage}",
        ];
    }
}
