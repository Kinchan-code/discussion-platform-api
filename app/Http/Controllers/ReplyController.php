<?php

namespace App\Http\Controllers;

use App\Services\ReplyService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ReplyResource;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\ReplyRequest;
/**
 * Reply Management Controller
 *
 * Handles all reply-related operations including creating, reading, updating,
 * and deleting replies to comments. Supports nested replies and vote counting.
 *
 * Features:
 * - Complete CRUD operations for replies
 * - Nested reply support (reply to replies)
 * - Optimized performance with eager loading
 * - Comprehensive pagination
 *
 * @package App\Http\Controllers
 */
class ReplyController extends Controller
{
    protected ReplyService $replyService;

    public function __construct(ReplyService $replyService)
    {
        $this->replyService = $replyService;
    }

    /**
     * Retrieve all replies for a specific comment with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $commentId  The ID of the comment to fetch replies for
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $commentId): JsonResponse
    {
        $paginator = $this->replyService->index($commentId, $request);

        $replyResources = ReplyResource::collection($paginator)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($paginator);

        return ApiResponseResource::successWithPagination(
            data: $replyResources,
            pagination: $paginationResource->toArray(),
            message: 'Replies fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Create a new reply for a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $commentId  The ID of the comment to reply to
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ReplyRequest $request, string $commentId): JsonResponse
    {
        $reply = $this->replyService->store($commentId, $request);
        $resource = new ReplyResource($reply);

        return ApiResponseResource::success(
            message: 'Reply created successfully.',
            data: $resource->toArray($request),
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Show a single reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the reply to show
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $reply = $this->replyService->show($id, $request);
        $resource = new ReplyResource($reply);

        return ApiResponseResource::success(
            message: 'Reply fetched successfully.',
            data: $resource->toArray($request),
        )->toJsonResponse();
    }

    /**
     * Update an existing reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the reply to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ReplyRequest $request, string $id): JsonResponse
    {
        $updatedReply = $this->replyService->update($id, $request);
        $resource = new ReplyResource($updatedReply);

        return ApiResponseResource::success(
            message: 'Reply updated successfully.',
            data: $resource->toArray($request),
        )->toJsonResponse();
    }

    /**
     * Delete a reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the reply to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->replyService->destroy($id);

        return ApiResponseResource::success(
            message: 'Reply deleted successfully.',
            data: null,
        )->toJsonResponse();
    }

    /**
     * Create a nested reply (reply to a reply).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $replyId  The ID of the reply to reply to
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeChild(ReplyRequest $request, string $replyId): JsonResponse
    {
        $reply = $this->replyService->storeChild($replyId, $request);
        $resource = new ReplyResource($reply);

        return ApiResponseResource::success(
            message: 'Reply created successfully.',
            data: $resource->toArray($request),
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Get children replies for a parent reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $replyId  The ID of the parent reply
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexChildren(Request $request, string $replyId): JsonResponse
    {
        $paginator = $this->replyService->indexChildren($replyId, $request);

        $replyResources = ReplyResource::collection($paginator)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($paginator);

        return ApiResponseResource::successWithPagination(
            data: $replyResources,
            pagination: $paginationResource->toArray(),
            message: 'Reply children fetched successfully.'
        )->toJsonResponse();
    }
}

