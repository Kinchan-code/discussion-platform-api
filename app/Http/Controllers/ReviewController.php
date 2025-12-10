<?php

namespace App\Http\Controllers;

use App\Services\ReviewService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Review Management Controller
 *
 * Handles all review and rating operations for research protocols including
 * creating, reading, updating, and deleting reviews.
 *
 * Features:
 * - Complete CRUD operations for protocol reviews
 * - Star-based rating system (1-5 stars)
 * - Author-based filtering and search
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ReviewService
 * @see App\Models\Review
 * @see App\Models\Protocol
 * @see App\Http\Resources\ReviewResource
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
     * @param  string  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $protocolId): JsonResponse
    {
        $paginator = $this->reviewService->index($protocolId, $request);

        $reviewResources = ReviewResource::collection($paginator)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($paginator);

        return ApiResponseResource::successWithPagination(
            data: $reviewResources,
            pagination: $paginationResource->toArray(),
            message: 'Reviews fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Store a newly created review.
     *
     * @param  \App\Http\Requests\ReviewRequest  $request
     * @param  string  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(\App\Http\Requests\ReviewRequest $request, string $protocolId): JsonResponse
    {
        $review = $this->reviewService->store($protocolId, $request);
        $reviewResource = new ReviewResource($review);

        return ApiResponseResource::success(
            message: 'Review created successfully.',
            data: $reviewResource->toArray($request),
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Update an existing review.
     *
     * @param  \App\Http\Requests\ReviewRequest  $request
     * @param  string  $id  The ID of the review
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(\App\Http\Requests\ReviewRequest $request, string $id): JsonResponse
    {
        $review = $this->reviewService->update($id, $request);
        $reviewResource = new ReviewResource($review);

        return ApiResponseResource::success(
            message: 'Review updated successfully.',
            data: $reviewResource->toArray($request)
        )->toJsonResponse();
    }

    /**
     * Show a single review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the review
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $review = $this->reviewService->show($id, $request);
        $reviewResource = new ReviewResource($review);

        return ApiResponseResource::success(
            message: 'Review fetched successfully.',
            data: $reviewResource->toArray($request)
        )->toJsonResponse();
    }

    /**
     * Remove the specified review.
     *
     * @param  string  $id  The ID of the review
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $this->reviewService->destroy($id);

        return ApiResponseResource::success(
            message: 'Review deleted successfully.',
            data: null
        )->toJsonResponse();
    }
}
