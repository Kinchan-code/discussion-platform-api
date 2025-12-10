<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\CommentRequest;
/**
 * Comment Management Controller
 *
 * Handles all comment-related operations including creating, reading, updating,
 * and deleting comments within discussion threads. Supports voting and smart
 * highlighting for enhanced user experience.
 *
 * Features:
 * - Complete CRUD operations for comments
 * - Smart highlighting for cross-page comment visibility
 * - Optimized performance with eager loading
 * - Comprehensive pagination and filtering
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\CommentService
 * @see App\Models\Comment
 * @see App\Models\Thread
 * @see App\Http\Resources\ApiResponseResource
 */
class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }
    /**
     * Retrieve all comments for a specific thread with pagination and filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $threadId  The ID of the thread to fetch comments for
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Exception When fetching comments fails due to server error
     * @authenticated
     */
    public function index(Request $request, string $threadId): JsonResponse
    {
        $paginator = $this->commentService->index($request);

        $commentResources = CommentResource::collection($paginator)->toArray($request);
        $paginationResource = PaginationResource::fromPaginator($paginator);

        return ApiResponseResource::successWithPagination(
            data: $commentResources,
            pagination: $paginationResource->toArray(),
            message: 'Comments fetched successfully.'
        )->toJsonResponse();
    }

    /**
     * Create a new top-level comment for a thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $threadId  The ID of the thread to create the comment in
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When comment creation fails due to server error
     * @authenticated
     */
    public function store(CommentRequest $request, string $thread): JsonResponse
    {
        $comment = $this->commentService->store($request, $thread);

        $resource = new CommentResource($comment);

        return ApiResponseResource::success(
            message: 'Comment created successfully.',
            data: $resource->toArray($request),
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Show a single comment.
     *
     * @param  string  $id  The ID of the comment to show
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $comment = $this->commentService->show($id, $request);
        $resource = new CommentResource($comment);

        return ApiResponseResource::success(
            message: 'Comment fetched successfully.',
            data: $resource->toArray($request),
        )->toJsonResponse();
    }

    /**
     * Update an existing comment or reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the comment or reply to update
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When user is not authorized or update fails due to server error
     * @authenticated
     */
    public function update(CommentRequest $request, string $id): JsonResponse
    {
        $updatedComment = $this->commentService->update($id, $request);

        $resource = new CommentResource($updatedComment);

        return ApiResponseResource::success(
            message: 'Comment updated successfully.',
            data: $resource->toArray($request),
        )->toJsonResponse();
    }

    /**
     * Delete a comment or reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  The ID of the comment or reply to delete
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Exception When user is not authorized or deletion fails due to server error
     * @authenticated
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->commentService->destroy($id);

        return ApiResponseResource::success(
            message: 'Comment deleted successfully.',
            data: null,
        )->toJsonResponse();
    }
}
