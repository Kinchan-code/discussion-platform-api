<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Protocol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ThreadService;
use App\DTOs\ApiResponse;
use App\DTOs\ThreadDTO;
use App\DTOs\PaginationDTO;
use Illuminate\Support\Facades\Auth;

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
 * - Optimized pagination with protocol tag transformation
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ThreadService
 * @see App\Models\Thread
 * @see App\Models\Protocol
 * @see App\DTOs\ThreadDTO
 */
class ThreadController extends Controller
{
    protected ThreadService $threadService;

    public function __construct(ThreadService $threadService)
    {
        $this->threadService = $threadService;
    }

    private function transformProtocolTags($threads)
    {
        if ($threads instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $threads->getCollection()->transform(function ($thread) {
                if ($thread->protocol && $thread->protocol->tags) {
                    $thread->protocol->tags = collect($thread->protocol->tags)->map(function ($tag, $index) {
                        return [
                            'id' => $index + 1,
                            'tag' => $tag
                        ];
                    })->toArray();
                }
                return $thread;
            });
        } else {
            // Handle single thread
            if ($threads->protocol && $threads->protocol->tags) {
                $threads->protocol->tags = collect($threads->protocol->tags)->map(function ($tag, $index) {
                    return [
                        'id' => $index + 1,
                        'tag' => $tag
                    ];
                })->toArray();
            }
        }

        return $threads;
    }
    /**
     * Retrieve a paginated list of all discussion threads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching threads fails due to server error
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $service = new ThreadService();

            // Add author filter support
            $filters = $request->all();

            // Handle 'current_user' special case for authenticated requests
            if ($request->has('author') && $request->get('author') === 'current_user') {
                if (!$request->user()) {
                    return ApiResponse::error(
                        message: 'Authentication required for current_user filter',
                        statusCode: 401
                    )->toJsonResponse();
                }
                $filters['author'] = $request->user()->name;
            }

            $threads = $service->listThreads($filters);

            // Transform threads to DTOs
            $threadDTOs = $threads->getCollection()->transform(function ($thread) {
                return ThreadDTO::fromModel($thread)->toArray();
            })->toArray();

            $paginationDTO = PaginationDTO::fromPaginator($threads);

            return ApiResponse::successWithPagination(
                data: $threadDTOs,
                pagination: $paginationDTO->toArray(),
                message: 'Threads fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch threads',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Display the specified thread.
     *
     * @param  int  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Exception When fetching thread fails due to server error
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = new ThreadService();
            $thread = $service->getThread($id);

            $threadDTOs = ThreadDTO::fromModel($thread)->toArray();

            return ApiResponse::success(
                data: $threadDTOs,
                message: 'Thread fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Thread not found',
                statusCode: 404,
                data: 'The requested thread does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch thread',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Get threads by protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $protocolId  The ID of the protocol
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Exception When fetching threads fails due to server error
     */
    public function byProtocol(Request $request, int $protocolId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);

            $threads = $this->threadService->getThreadsByProtocol($protocolId, [
                'per_page' => $perPage
            ]);

            $threadDTOs = $threads->getCollection()->map(function ($thread) {
                return ThreadDTO::fromModel($thread);
            });

            $paginationDTO = PaginationDTO::fromPaginator($threads);

            return ApiResponse::successWithPagination(
                data: $threadDTOs->toArray(),
                pagination: $paginationDTO->toArray(),
                message: 'Threads fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch threads',
                statusCode: 500
            )->toJsonResponse();
        }
    }

    /**
     * Store a newly created thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When thread creation fails due to server error
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'protocol_id' => ['required', 'exists:protocols,id'],
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
            ]);

            if (!Auth::user()) {
                return ApiResponse::error(
                    message: 'Unauthorized',
                    statusCode: 401
                )->toJsonResponse();
            }

            $service = new ThreadService();
            $thread = $service->createThread($request->all(), Auth::user());

            return ApiResponse::success(
                data: ThreadDTO::fromModel($thread)->toArray(),
                message: 'Thread created successfully.',
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create thread',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Update the specified thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Thread  $thread  The thread instance to update
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When thread update fails due to server error
     */
    public function update(Request $request, Thread $thread): JsonResponse
    {
        try {
            // Check if the authenticated user is the author
            if ($thread->author !== $request->user()->name) {
                return response()->json([
                    'status_code' => 403,
                    'error' => 'Unauthorized',
                    'message' => 'You can only update threads that you created.',
                ], 403);
            }

            $request->validate([
                'protocol_id' => ['sometimes', 'required', 'exists:protocols,id'],
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'body' => ['sometimes', 'required', 'string'],
            ]);

            $service = new ThreadService();
            $thread = $service->updateThread($thread->id, $request->only(['protocol_id', 'title', 'body']));

            return ApiResponse::success(
                data: ThreadDTO::fromModel($thread)->toArray(),
                message: 'Thread updated successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Thread not found',
                statusCode: 404,
                data: 'The requested thread does not exist.'
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to update thread',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Remove the specified thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Exception When thread deletion fails due to server error
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $thread = Thread::findOrFail($id);

            // Check if the authenticated user is the author
            if ($thread->author !== $request->user()->name) {
                return response()->json([
                    'status_code' => 403,
                    'error' => 'Unauthorized',
                    'message' => 'You can only delete threads that you created.',
                ], 403);
            }

            $service = new ThreadService();
            $thread = $service->deleteThread($id);

            return ApiResponse::success(
                data: ThreadDTO::fromModel($thread)->toArray(),
                message: 'Thread deleted successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Thread not found',
                statusCode: 404,
                data: 'The requested thread does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to delete thread',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Get thread statistics.
     *
     * @param  int  $id  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Exception When fetching thread statistics fails due to server error
     */
    public function stats(int $id): JsonResponse
    {
        try {
            $service = new ThreadService();
            $thread = $service->getThreadStatistics($id);

            $threadStats = [
                'thread_id' => $thread->id,
                'total_comments' => $thread->comments()->count(),
                'total_votes' => $thread->votes()->count(),
                'upvotes' => $thread->getUpvotesAttribute(),
                'downvotes' => $thread->getDownvotesAttribute(),
                'vote_score' => $thread->getVoteScoreAttribute(),
                'engagement_score' => $thread->comments()->count() + $thread->votes()->count(),
            ];

            return ApiResponse::success(
                $threadStats,
                message: 'Thread stats fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Thread not found',
                statusCode: 404,
                data: 'The requested thread does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch thread stats',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Get trending threads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching trending threads fails due to server error
     */
    public function trending(Request $request): JsonResponse
    {
        try {
            $service = new ThreadService();
            $threads = $service->getTrendingThreads($request->all());

            // Transform threads to DTOs
            $threadDTOs = $threads->getCollection()->transform(function ($thread) {
                return ThreadDTO::fromModel($thread)->toArray();
            })->toArray();

            $paginationDTO = PaginationDTO::fromPaginator($threads);

            return ApiResponse::successWithPagination(
                data: $threadDTOs,
                pagination: $paginationDTO->toArray(),
                message: 'Trending threads fetched successfully.',
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch trending threads',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
