<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\Comment;
use App\Models\Review;
use App\Models\Vote;
use App\Models\User;
use App\DTOs\VoteDTO;

/**
 * Vote Service
 *
 * Handles voting logic for threads, comments, and reviews.
 * Provides methods for casting, updating, and removing votes, as well as aggregating vote statistics.
 *
 * Features:
 * - Vote on threads, comments, and reviews
 * - Handles vote toggling and updates
 * - Aggregates and returns vote statistics
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Thread
 * @see App\Models\Comment
 * @see App\Models\Review
 * @see App\Models\Vote
 * @see App\Models\User
 * @see App\DTOs\VoteDTO
 */
class VoteService
{
    /**
     * Vote on a thread.
     *
     * @param Thread $thread
     * @param User $user
     * @param string $type
     * @return array
     */
    public function voteOnThread(Thread $thread, User $user, string $type): array
    {
        // Check if user already voted on this thread
        $existingVote = $thread->votes()
            ->where('user_id', $user->getKey())
            ->first();

        $vote = null;
        $message = '';

        if ($existingVote) {
            if ($existingVote->type === $type) {
                // User is trying to vote the same way again - remove the vote
                $existingVote->delete();
                $message = 'Vote removed successfully';
            } else {
                // User is changing their vote
                $existingVote->update(['type' => $type]);
                $message = 'Vote updated successfully';
                $vote = VoteDTO::fromModel($existingVote);
            }
        } else {
            // Create new vote
            $newVote = $thread->votes()->create([
                'type' => $type,
                'user_id' => $user->getKey(),
            ]);
            $message = 'Voted successfully';
            $vote = VoteDTO::fromModel($newVote);
        }

        // Load votes with the thread to calculate counts efficiently
        $thread->load('votes');
        $votesCollection = collect($thread->votes);
        $upvotes = $votesCollection->where('type', 'upvote')->count();
        $downvotes = $votesCollection->where('type', 'downvote')->count();

        $responseData = [
            'thread' => [
                'id' => $thread->getKey(),
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]
        ];

        // Only include vote in response if it exists
        if ($vote !== null) {
            $responseData['vote'] = $vote->toArray();
        }

        return [
            'message' => $message,
            'data' => $responseData
        ];
    }

    /**
     * Vote on a comment.
     *
     * @param Comment $comment
     * @param User $user
     * @param string $type
     * @return array
     */
    public function voteOnComment(Comment $comment, User $user, string $type): array
    {
        // Check if user already voted on this comment
        $existingVote = $comment->votes()
            ->where('user_id', $user->getKey())
            ->first();

        $vote = null;
        $message = '';

        if ($existingVote) {
            if ($existingVote->type === $type) {
                // User is trying to vote the same way again - remove the vote
                $existingVote->delete();
                $message = 'Vote removed successfully';
            } else {
                // User is changing their vote
                $existingVote->update(['type' => $type]);
                $message = 'Vote updated successfully';
                $vote = VoteDTO::fromModel($existingVote);
            }
        } else {
            // Create new vote
            $newVote = $comment->votes()->create([
                'type' => $type,
                'user_id' => $user->getKey(),
            ]);
            $message = 'Voted successfully';
            $vote = VoteDTO::fromModel($newVote);
        }

        // Load votes with the comment to calculate counts efficiently
        $comment->load('votes');
        $votesCollection = collect($comment->votes);
        $upvotes = $votesCollection->where('type', 'upvote')->count();
        $downvotes = $votesCollection->where('type', 'downvote')->count();

        $responseData = [
            'comment' => [
                'id' => $comment->getKey(),
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'vote_score' => $upvotes - $downvotes,
            ]
        ];

        // Only include vote in response if it exists
        if ($vote !== null) {
            $responseData['vote'] = $vote->toArray();
        }

        return [
            'message' => $message,
            'data' => $responseData
        ];
    }

    /**
     * Vote on a review.
     *
     * @param Review $review
     * @param User $user
     * @param string $type
     * @return array
     */
    public function voteOnReview(Review $review, User $user, string $type): array
    {
        // Check if user already voted on this review
        $existingVote = Vote::where('user_id', $user->id)
            ->where('votable_type', Review::class)
            ->where('votable_id', $review->id)
            ->first();

        $message = '';

        if ($existingVote) {
            if ($existingVote->type === $type) {
                // User is trying to vote the same way again - remove the vote
                $existingVote->delete();
                $message = 'Vote removed successfully';
            } else {
                // User is changing their vote
                $existingVote->update(['type' => $type]);
                $message = 'Vote updated successfully';
            }
        } else {
            // Create new vote
            Vote::create([
                'user_id' => $user->id,
                'votable_type' => Review::class,
                'votable_id' => $review->id,
                'type' => $type,
            ]);
            $message = 'Vote recorded successfully';
        }

        // Refresh the review to get updated counts
        $review->refresh();

        return [
            'message' => $message,
            'data' => [
                'helpful_count' => $review->helpful_count,
                'not_helpful_count' => $review->not_helpful_count,
                'user_vote' => $review->hasUserVotedHelpful($user->id) ? 'helpful' : ($review->hasUserVotedNotHelpful($user->id) ? 'not_helpful' : null),
            ]
        ];
    }
}
