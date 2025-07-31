<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Protocol;
use App\Services\ReviewService;
use App\DTOs\ApiResponse;
use App\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Review Management Controller
 *
 * Handles all review and rating operations for research protocols including
 * creating, reading, updating, and deleting reviews. Supports comprehensive
 * rating systems, review analytics, and smart highlighting for enhanced
 * user experience across protocol discussions.
 *
 * Features:
 * - Complete CRUD operations for protocol reviews
 * - Star-based rating system (1-5 stars)
 * - Review analytics and statistics
 * - Author-based filtering and search
 * - Smart highlighting for cross-page review visibility
 * - Review voting and helpfulness tracking
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ReviewService
 * @see App\Models\Review
 * @see App\Models\Protocol
 * @see App\DTOs\ReviewDTO
 */
class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Display reviews for a protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Exception When fetching reviews fails due to server error
     */
    public function index(Request $request, int $protocolId): JsonResponse
    {
        try {
            $protocol = Protocol::findOrFail($protocolId);
            $reviews = $this->reviewService->getProtocolReviews($protocol, $request);

            // Transform reviews to array format
            $reviewsArray = $reviews->items();
            if (count($reviewsArray) > 0 && $reviewsArray[0] instanceof \App\DTOs\ReviewDTO) {
                $reviewsArray = array_map(function ($reviewDto) {
                    return $reviewDto->toArray();
                }, $reviewsArray);
            }

            // Create pagination DTO
            $paginationDTO = PaginationDTO::fromPaginator($reviews);

            return ApiResponse::successWithPagination(
                data: $reviewsArray,
                pagination: $paginationDTO->toArray(),
                message: 'Reviews fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404,
                data: 'The requested protocol does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch reviews',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Store a newly created review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When review creation fails due to server error
     */
    public function store(Request $request, int $protocolId): JsonResponse
    {
        try {
            $protocol = Protocol::findOrFail($protocolId);

            $validated = $request->validate([
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'feedback' => ['nullable', 'string'],
            ]);

            $reviewDto = $this->reviewService->createReview($protocol, $request->user(), $validated);

            return ApiResponse::success(
                data: $reviewDto->toArray(),
                message: 'Review created successfully.',
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404,
                data: 'The requested protocol does not exist.'
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create review',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Update an existing review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When review not found
     * @throws \Exception When review update fails due to server error
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $review = $this->reviewService->getReview($request->route('id'));

            $validated = $request->validate([
                'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
                'feedback' => ['sometimes', 'string'],
            ]);

            $reviewDto = $this->reviewService->updateReview($review, $request->user(), $validated);

            return ApiResponse::success(
                data: $reviewDto->toArray(),
                message: 'Review updated successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Review not found',
                statusCode: 404,
                data: 'The requested review does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            // Check if this is an authorization error
            if (str_contains($e->getMessage(), 'You can only update reviews that you created')) {
                return ApiResponse::error(
                    message: 'Unauthorized',
                    statusCode: 403,
                    data: $e->getMessage()
                )->toJsonResponse();
            }

            return ApiResponse::error(
                message: 'Failed to update review',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Remove the specified review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The ID of the review
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When review not found
     * @throws \Exception When review deletion fails due to server error
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $review = $this->reviewService->getReview($id);
            $this->reviewService->deleteReview($review, $request->user());

            return ApiResponse::success(
                data: null,
                message: 'Review deleted successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Review not found',
                statusCode: 404,
                data: 'The requested review does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            // Check if this is an authorization error
            if (str_contains($e->getMessage(), 'You can only delete reviews that you created')) {
                return ApiResponse::error(
                    message: 'Unauthorized',
                    statusCode: 403,
                    data: $e->getMessage()
                )->toJsonResponse();
            }

            return ApiResponse::error(
                message: 'Failed to delete review',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
