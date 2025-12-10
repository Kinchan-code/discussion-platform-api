<?php

namespace App\Http\Controllers;

use App\Services\VoteService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\VoteResource;
use App\Http\Requests\VoteRequest;
use Illuminate\Http\JsonResponse;

/**
 * Vote Management Controller
 *
 * Handles all voting operations for threads, comments, reviews, protocols, and replies.
 * Uses a unified endpoint that accepts votable_type and votable_id.
 *
 * Features:
 * - Unified voting endpoint for all votable types
 * - Upvote and downvote functionality
 * - Vote removal and change capabilities
 * - Returns null when vote is removed
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 2.0.0
 * @since 2025-07-31
 *
 * @see App\Services\VoteService
 * @see App\Models\Vote
 */
class VoteController extends Controller
{
    protected VoteService $voteService;

    public function __construct(VoteService $voteService)
    {
        $this->voteService = $voteService;
    }

    /**
     * Store a vote (create, update, or remove).
     *
     * @param VoteRequest $request
     * @return JsonResponse
     */
    public function store(VoteRequest $request): JsonResponse
    {
        $vote = $this->voteService->store($request);

        if ($vote === null) {
            return ApiResponseResource::success(
                message: 'Vote removed successfully.',
                data: null
            )->toJsonResponse();
        }

        $voteResource = new VoteResource($vote);

        return ApiResponseResource::success(
            message: 'Vote saved successfully.',
            data: $voteResource->toArray($request)
        )->toJsonResponse();
    }
}
