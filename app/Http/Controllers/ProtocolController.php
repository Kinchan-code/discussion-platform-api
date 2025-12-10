<?php

namespace App\Http\Controllers;

use App\Models\Protocol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ProtocolService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ErrorResponseResource;
use App\Http\Resources\ProtocolResource;
use App\Http\Resources\PaginationResource;

/**
 * Protocol Management Controller
 *
 * Handles all protocol-related operations including creating, reading, updating,
 * and deleting research/discussion protocols. Protocols serve as the main
 * organizational structure for discussion threads and include metadata,
 * tags, reviews, and engagement statistics.
 *
 * Features:
 * - Complete CRUD operations for protocols
 * - Review and rating system integration
 * - Tag-based categorization and filtering
 * - Author-based filtering and search capabilities
 * - Protocol statistics and engagement metrics
 * - Public and private protocol visibility controls
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ProtocolService
 * @see App\Models\Protocol
 * @see App\Models\Review
 * @see App\Http\Resources\ProtocolResource
 */
class ProtocolController extends Controller
{
    protected ProtocolService $protocolService;

    public function __construct(ProtocolService $protocolService)
    {
        $this->protocolService = $protocolService;
    }

    /**
     * Retrieve a paginated list of all research protocols.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $protocols = $this->protocolService->index($request);

        $protocolResources = ProtocolResource::collection($protocols)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($protocols);

        return ApiResponseResource::successWithPagination(
            data: $protocolResources,
            pagination: $paginationResource->toArray($request),
            message: 'Protocols fetched successfully.'
        )->toJsonResponse();
    }


    /**
     * Retrieve protocol data for filtering and selection purposes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters(Request $request): JsonResponse
    {
        $protocols = Protocol::select('id', 'title')->get();

        return ApiResponseResource::success(
            data: $protocols,
            message: 'Protocols fetched successfully.'
        )->toJsonResponse();
    }


    /**
     * Retrieve detailed information for a specific protocol.
     *
     * @param  string  $id  The ID of the protocol to retrieve
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $protocol = $this->protocolService->show($id);

        return ApiResponseResource::success(
            data: (new ProtocolResource($protocol))->toArray($request),
            message: 'Protocol fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Create a new research protocol.
     *
     * @param  \App\Http\Requests\ProtocolRequest  $request
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function store(\App\Http\Requests\ProtocolRequest $request): JsonResponse
    {
        $protocol = $this->protocolService->store($request);

        return ApiResponseResource::success(
            data: (new ProtocolResource($protocol))->toArray($request),
            message: 'Protocol created successfully',
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Update an existing protocol.
     *
     * @param  \App\Http\Requests\ProtocolRequest  $request
     * @param  string  $id  The ID of the protocol to update
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function update(\App\Http\Requests\ProtocolRequest $request, string $id): JsonResponse
    {
        $protocol = $this->protocolService->update($id, $request);

        return ApiResponseResource::success(
            data: (new ProtocolResource($protocol))->toArray($request),
            message: 'Protocol updated successfully.'
        )->toJsonResponse();
    }

    /**
     * Delete a protocol permanently.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the protocol to delete
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->protocolService->destroy($id);

        return ApiResponseResource::success(
            message: 'Protocol deleted successfully.',
            data: null
        )->toJsonResponse();
    }

    /**
     * Retrieve comprehensive statistics for a specific protocol.
     *
     * @param  string  $id  The ID of the protocol to get statistics for
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(string $id, Request $request): JsonResponse
    {
        $stats = $this->protocolService->getProtocolStats($id);

        if (!$stats) {
            return ErrorResponseResource::toJsonResponse(
                ErrorResponseResource::fromMessage(
                    message: 'Protocol not found',
                    request: $request,
                    statusCode: 404
                )
            );
        }

        return ApiResponseResource::success(
            message: 'Protocol stats fetched successfully.',
            data: $stats
        )->toJsonResponse();
    }

    /**
     * Retrieve a curated collection of featured protocols.
     *
     * @param  \Illuminate\Http\Request  $request  HTTP request with optional pagination
     * @return \Illuminate\Http\JsonResponse
     * @unauthenticated
     */
    public function featured(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 3);

        $query = Protocol::withCount(['threads', 'reviews'])
            ->withAvg('reviews', 'rating');

        $protocols = $query->orderBy('reviews_avg_rating', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->orderBy('threads_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $protocolResources = ProtocolResource::collection($protocols)->toArray($request);
        $pagination = PaginationResource::fromPaginator($protocols);

        return ApiResponseResource::successWithPagination(
            data: $protocolResources,
            pagination: $pagination->toArray($request),
            message: 'Featured protocols fetched successfully.'
        )->toJsonResponse();
    }
}
