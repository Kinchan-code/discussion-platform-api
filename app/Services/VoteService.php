<?php

namespace App\Services;

use App\Models\Vote;
use App\Models\Thread;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Review;
use App\Enums\VoteType;
use App\Http\Requests\VoteRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Vote Service
 *
 * Handles voting logic for any votable model (threads, comments, reviews, protocols, replies).
 * Uses polymorphic relationships to provide a unified voting interface.
 *
 * Features:
 * - Unified voting for any votable model
 * - Handles vote toggling and updates
 * - DB transactions for data integrity
 * - Comprehensive error handling
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 2.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Vote
 * @see App\Models\User
 * @see App\Http\Resources\VoteResource
 */
class VoteService
{
    /**
     * Store a vote (create, update, or remove).
     *
     * @param VoteRequest $request
     * @return Vote|null
     * @throws ValidationException
     */
    public function store(VoteRequest $request): ?Vote
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();

                $type = match ($data['votable_type']) {
                    'thread' => Thread::class,
                    'comment' => Comment::class,
                    'reply' => Reply::class,
                    'review' => Review::class,
                    default => abort(400, 'Invalid votable type'),
                };

                // Load votable with necessary relationships
                $votable = match ($type) {
                    Comment::class => $type::with('thread')->findOrFail($data['votable_id']),
                    Reply::class => $type::with('comment.thread')->findOrFail($data['votable_id']),
                    Review::class => $type::with('protocol')->findOrFail($data['votable_id']),
                    default => $type::findOrFail($data['votable_id']),
                };

                $voteType = $data['vote_type'];

                $existingVote = $votable->votes()->where('user_id', Auth::id())->first();

                // If user votes the same way again, remove the vote
                if ($existingVote && $existingVote->type === $voteType) {
                    $existingVote->delete();
                    return null;
                }

                // If user is changing their vote, update it
                if ($existingVote && $existingVote->type !== $voteType) {
                    $existingVote->update([
                        'type' => $voteType,
                    ]);
                    return $existingVote->fresh()->load(['user']);
                }

                // Create new vote
                $vote = $votable->votes()->create([
                    'type' => $voteType,
                    'user_id' => Auth::id(),
                ])->load(['user']);

                return $vote;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t process your vote due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'vote' => [$message],
            ]);
        }
    }
}
