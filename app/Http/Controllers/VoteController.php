<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Comment;
use App\Models\Vote;
use App\Models\Review;
use App\Services\VoteService;
use App\DTOs\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vote Management Controller
 *
 * Handles all voting operations for threads, comments, and reviews within
 * the discussion platform. Supports upvotes, downvotes, and vote management
 * with user authentication and vote tracking capabilities.
 *
 * Features:
 * - Vote on threads, comments, and reviews
 * - Upvote and downvote functionality
 * - Vote removal and change capabilities
 * - User vote tracking and analytics
 * - Duplicate vote prevention
 *
 * @package App\Http\Controllers
 * @author Your Name
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\VoteService
 * @see App\Models\Vote
 * @see App\Models\Thread
 * @see App\Models\Comment
 */
class VoteController extends Controller
{
    protected VoteService $voteService;

    public function __construct(VoteService $voteService)
    {
        $this->voteService = $voteService;
    }
    /**
     * Vote on a thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $threadId  The ID of the thread
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When thread not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When voting fails due to server error
     */
    public function voteOnThread(Request $request, int $threadId): JsonResponse
    {
        try {
            $thread = Thread::findOrFail($threadId);

            $validated = $request->validate([
                'type' => ['required', 'string', 'in:upvote,downvote'],
            ]);

            $result = $this->voteService->voteOnThread($thread, $request->user(), $validated['type']);

            $response = ApiResponse::success($result['data'], $result['message']);
            return response()->json($response->toArray(), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = ApiResponse::error('The requested thread does not exist.', 404);
            return response()->json($response->toArray(), 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = ApiResponse::error($e->getMessage(), 422, $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            return response()->json($response->toArray(), 500);
        }
    }

    /**
     * Vote on a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $commentId  The ID of the comment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When comment not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When voting fails due to server error
     */
    public function voteOnComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $comment = Comment::findOrFail($commentId);

            $validated = $request->validate([
                'type' => ['required', 'string', 'in:upvote,downvote'],
            ]);

            $result = $this->voteService->voteOnComment($comment, $request->user(), $validated['type']);

            $response = ApiResponse::success($result['data'], $result['message']);
            return response()->json($response->toArray(), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = ApiResponse::error('The requested comment does not exist.', 404);
            return response()->json($response->toArray(), 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = ApiResponse::error($e->getMessage(), 422, $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            return response()->json($response->toArray(), 500);
        }
    }


    /**
     * Vote on a review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $reviewId  The ID of the review
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When review not found
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When voting fails due to server error
     */
    public function voteOnReview(Request $request, int $reviewId): JsonResponse
    {
        try {
            $review = Review::findOrFail($reviewId);
            $user = $request->user();

            $validated = $request->validate([
                'type' => ['required', 'string', 'in:upvote,downvote'],
            ]);

            $result = $this->voteService->voteOnReview($review, $user, $validated['type']);

            $response = ApiResponse::success($result['data'], $result['message']);
            return response()->json($response->toArray(), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = ApiResponse::error('The requested review does not exist.', 404);
            return response()->json($response->toArray(), 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = ApiResponse::error($e->getMessage(), 422, $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            return response()->json($response->toArray(), 500);
        }
    }
}
