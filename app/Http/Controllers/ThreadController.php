<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ThreadService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\PaginationResource;

/**
 * Thread Management Controller
 *
 * Handles all thread-related operations within discussion protocols including
 * creating, reading, updating, and deleting threads. Threads are discussion
 * topics that belong to protocols and contain comments and replies.
 *
 * Features:
 * - Complete CRUD operations for discussion threads
 * - Protocol-based thread organization and filtering
 * - Author-based filtering and search capabilities
 * - Thread statistics and engagement metrics
 * - Optimized pagination for performance
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ThreadService
 * @see App\Models\Thread
 * @see App\Models\Protocol
 * @see App\Http\Resources\ThreadResource
 */
class ThreadController extends Controller
{
    protected ThreadService $threadService;

    public function __construct(ThreadService $threadService)
    {
        $this->threadService = $threadService;
    }

    /**
     * Retrieve a paginated list of all discussion threads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $threads = $this->threadService->index($request);

        $threadResources = ThreadResource::collection($threads)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($threads);

        return ApiResponseResource::successWithPagination(
            data: $threadResources,
            pagination: $paginationResource->toArray($request),
            message: 'Threads fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Display the specified thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $thread = $this->threadService->show($id, $request);

        return ApiResponseResource::success(
            data: (new ThreadResource($thread))->toArray($request),
            message: 'Thread fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Get threads by protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     */
    public function byProtocol(Request $request, string $protocolId): JsonResponse
    {
        $threads = $this->threadService->getThreadsByProtocol($protocolId, $request);

        $threadResources = ThreadResource::collection($threads)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($threads);

        return ApiResponseResource::successWithPagination(
            data: $threadResources,
            pagination: $paginationResource->toArray($request),
            message: 'Threads fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Store a newly created thread.
     *
     * @param  \App\Http\Requests\ThreadRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(\App\Http\Requests\ThreadRequest $request): JsonResponse
    {
        $thread = $this->threadService->store($request);

        return ApiResponseResource::success(
            data: (new ThreadResource($thread))->toArray($request),
            message: 'Thread created successfully.',
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Update the specified thread.
     *
     * @param  \App\Http\Requests\ThreadRequest  $request
     * @param  string  $id  The ID of the thread to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(\App\Http\Requests\ThreadRequest $request, string $id): JsonResponse
    {
        $thread = $this->threadService->update($id, $request);

        return ApiResponseResource::success(
            data: (new ThreadResource($thread))->toArray($request),
            message: 'Thread updated successfully.'
        )->toJsonResponse();
    }

    /**
     * Remove the specified thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->threadService->destroy($id);

        return ApiResponseResource::success(
            message: 'Thread deleted successfully.',
            data: null
        )->toJsonResponse();
    }

    /**
     * Get thread statistics.
     *
     * @param  string  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(string $id): JsonResponse
    {
        $thread = $this->threadService->getThreadStatistics($id);

        $threadStats = [
            'thread_id' => $thread->id,
            'total_comments' => $thread->comments_count ?? 0,
            'total_votes' => $thread->votes_count ?? 0,
            'upvotes' => $thread->upvotes ?? 0,
            'downvotes' => $thread->downvotes ?? 0,
            'vote_score' => $thread->vote_score ?? 0,
            'engagement_score' => ($thread->comments_count ?? 0) + ($thread->votes_count ?? 0),
        ];

        return ApiResponseResource::success(
            $threadStats,
            message: 'Thread stats fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Get trending threads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trending(Request $request): JsonResponse
    {
        $threads = $this->threadService->getTrendingThreads($request);

        $threadResources = ThreadResource::collection($threads)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($threads);

        return ApiResponseResource::successWithPagination(
            data: $threadResources,
            pagination: $paginationResource->toArray($request),
            message: 'Trending threads fetched successfully.',
        )->toJsonResponse();
    }
}
