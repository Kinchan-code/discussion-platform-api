<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use App\DTOs\ApiResponse;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Comment Management Controller
 *
 * Handles all comment-related operations including creating, reading, updating,
 * and deleting comments and replies within discussion threads. Supports nested
 * reply structures, voting, and smart highlighting for enhanced user experience.
 *
 * Features:
 * - Complete CRUD operations for comments and replies
 * - Nested reply support with flattened structure
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
 * @see App\DTOs\ApiResponse
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
     * @param  int  $threadId  The ID of the thread to fetch comments for
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Exception When fetching comments fails due to server error
     * @authenticated
     */
    public function index(Request $request, int $threadId): JsonResponse
    {
        try {
            $thread = Thread::findOrFail($threadId);
            $result = $this->commentService->getThreadComments($thread, $request);



            return ApiResponse::successWithPagination(
                data: $result['comments'],
                pagination: $result['pagination'],
                message: 'Comments fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch comments',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Create a new top-level comment for a thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $threadId  The ID of the thread to create the comment in
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When comment creation fails due to server error
     * @authenticated
     */
    public function store(Request $request, int $threadId): JsonResponse
    {
        try {
            $thread = Thread::findOrFail($threadId);

            $validated = $request->validate([
                'body' => ['required', 'string'],
            ]);

            $commentDto = $this->commentService->createComment($thread, $request->user(), $validated);

            return ApiResponse::success(
                message: 'Comment created successfully.',
                data: $commentDto->toArray(),
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'The requested thread does not exist.',
                statusCode: 404
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create comment',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }


    /**
     * Retrieve all replies for a specific comment with nested structure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $commentId  The ID of the comment to fetch replies for
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Exception When fetching replies fails due to server error
     * @authenticated
     */
    public function replies(Request $request, int $commentId): JsonResponse
    {
        try {
            $comment = Comment::findOrFail($commentId);
            $result = $this->commentService->getCommentReplies($comment, $request);

            return ApiResponse::successWithPagination(
                data: $result['replies'],
                pagination: $result['pagination'],
                message: 'Replies fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Comment not found',
                statusCode: 404,
                data: 'The requested comment does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch replies',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve nested replies for a specific reply with pagination support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $replyId  The ID of the parent reply to fetch nested replies for
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When reply not found
     * @throws \InvalidArgumentException When target is not a reply (is top-level comment)
     * @throws \Exception When fetching nested replies fails due to server error
     * @authenticated
     */
    public function nestedReplies(Request $request, int $replyId): JsonResponse
    {
        try {
            $parentReply = Comment::findOrFail($replyId);
            $result = $this->commentService->getNestedReplies($parentReply, $request);

            return ApiResponse::successWithPagination(
                data: $result['nested_replies'],
                pagination: $result['pagination'],
                message: 'Nested replies fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Reply not found',
                statusCode: 404,
                data: "No reply found with ID {$replyId}."
            )->toJsonResponse();
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                message: 'Invalid target',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch nested replies',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Create a reply to a top-level comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $commentId  The ID of the top-level comment to reply to
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \InvalidArgumentException When target is not a top-level comment
     * @throws \Exception When reply creation fails due to server error
     * @authenticated
     */
    public function replyToComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $targetComment = Comment::findOrFail($commentId);

            $validated = $request->validate([
                'body' => ['required', 'string'],
            ]);

            $replyDTO = $this->commentService->createReplyToComment($targetComment, $request->user(), $validated);

            return ApiResponse::success(
                data: $replyDTO->toArray(),
                message: 'Reply to comment created successfully.',
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Comment not found',
                statusCode: 404,
                data: "No comment found with ID {$commentId}."
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create reply',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Create a nested reply to an existing reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $replyId  The ID of the reply to create a nested reply to
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When reply not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \InvalidArgumentException When target is not a reply (is top-level comment)
     * @throws \Exception When nested reply creation fails due to server error
     * @authenticated
     */
    public function replyToReply(Request $request, int $replyId): JsonResponse
    {
        try {
            $targetReply = Comment::findOrFail($replyId);

            $validated = $request->validate([
                'body' => ['required', 'string'],
            ]);

            $nestedReplyDTO = $this->commentService->createReplyToReply($targetReply, $request->user(), $validated);

            return ApiResponse::success(
                data: $nestedReplyDTO->toArray(),
                message: "Reply to {$targetReply->author}'s reply created successfully.",
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Reply not found',
                statusCode: 404,
                data: "No reply found with ID {$replyId}."
            )->toJsonResponse();
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                message: 'Invalid target',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create reply to reply',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Delete a comment or reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The ID of the comment or reply to delete
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Exception When user is not authorized or deletion fails due to server error
     * @authenticated
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $comment = Comment::findOrFail($id);
            $this->commentService->deleteComment($comment, $request->user());

            return ApiResponse::success(
                message: 'Comment deleted successfully.',
                data: null,
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Comment not found',
                statusCode: 404,
                data: 'The requested comment does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'You can only delete your own comments') ? 403 : 500;
            $error = $statusCode === 403 ? 'Unauthorized' : 'Failed to delete comment';

            return ApiResponse::error(
                message: $error,
                statusCode: $statusCode,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Update an existing comment or reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The ID of the comment or reply to update
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When user is not authorized or update fails due to server error
     * @authenticated
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Optimized: Only load the comment we need, service will handle relationships
            $comment = Comment::findOrFail($id);

            $validated = $request->validate([
                'body' => ['required', 'string', 'max:10000'], // Add reasonable limit
            ]);

            $commentDto = $this->commentService->updateComment($comment, $request->user(), $validated);

            return ApiResponse::success(
                message: 'Comment updated successfully.',
                data: $commentDto->toArray(),
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Comment not found',
                statusCode: 404,
                data: 'The requested comment does not exist.'
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'You can only edit your own comments') ? 403 : 500;
            $error = $statusCode === 403 ? 'Unauthorized' : 'Failed to update comment';

            return ApiResponse::error(
                message: $error,
                statusCode: $statusCode,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
