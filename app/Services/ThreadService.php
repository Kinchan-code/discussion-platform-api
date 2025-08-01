<?php

namespace App\Services;

use App\Models\Thread;
use Illuminate\Pagination\LengthAwarePaginator;


/**
 * Thread Management Service
 *
 * Handles thread listing, retrieval, creation, updating, deletion, and statistics for the platform.
 * Provides methods for paginated thread queries, filtering, sorting, trending, and efficient vote/comment aggregation.
 *
 * Features:
 * - Paginated thread listing with filters and sorting
 * - Thread creation, update, and deletion
 * - Efficient vote and comment aggregation
 * - Trending and protocol-specific thread queries
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Thread
 */
class ThreadService
{
    /**
     * Get a paginated list of threads with optional filters and sorting.
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function listThreads($params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $sort = $params['sort'] ?? 'recent';
        $protocolId = $params['protocol_id'] ?? null;
        $author = $params['author'] ?? null;

        $query = Thread::with([
            'protocol' => function ($query) {
                $query->select('id', 'title', 'content', 'tags', 'author', 'created_at', 'updated_at');
            }
        ])
            ->withCount(['comments'])
            ->withCount([
                'votes as upvotes' => function ($q) {
                    $q->where('type', 'upvote');
                },
                'votes as downvotes' => function ($q) {
                    $q->where('type', 'downvote');
                },
            ]);

        if ($author) {
            $query->where('author', $author);
        }

        if ($protocolId) {
            $query->where('protocol_id', $protocolId);
        }

        switch ($sort) {
            case 'popular':
                $query->orderByDesc('upvotes')
                    ->orderByDesc('comments_count')
                    ->orderByDesc('created_at');
                break;
            case 'rating':
                $query->orderByDesc('upvotes')
                    ->orderByDesc('created_at');
                break;
            case 'recent':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $threads = $query->paginate($perPage);

        return $threads;
    }

    /**
     * Retrieve a thread by ID with related protocol and votes.
     *
     * @param int|string $id
     * @return Thread|null
     */
    public function getThread($id): ?Thread
    {
        $thread = Thread::with([
            'protocol' => function ($query) {
                $query->select('id', 'title', 'content', 'tags', 'author', 'created_at', 'updated_at'); // Include all fields needed by DTO
            },
            'votes' // Eager load votes for efficient counting
        ])
            ->withCount(['comments'])
            ->findOrFail($id);

        if ($thread && $thread->votes) {
            // Calculate vote counts efficiently
            $votesCollection = collect($thread->votes);
            $thread->setAttribute('upvotes', $votesCollection->where('type', 'upvote')->count());
            $thread->setAttribute('downvotes', $votesCollection->where('type', 'downvote')->count());
            $thread->setAttribute('votes_count', $thread->votes->count());
            $thread->setAttribute('vote_score', $thread->getAttribute('upvotes') - $thread->getAttribute('downvotes'));
        }

        return $thread;
    }

    /**
     * Get paginated threads for a specific protocol.
     *
     * @param int|string $protocolId
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function getThreadsByProtocol($protocolId, $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;

        return Thread::with(['protocol'])
            ->withCount(['comments', 'votes'])
            ->withCount(['votes as upvotes' => function ($query) {
                $query->where('type', 'upvote');
            }])
            ->withCount(['votes as downvotes' => function ($query) {
                $query->where('type', 'downvote');
            }])
            ->where('protocol_id', $protocolId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new thread.
     *
     * @param array $params
     * @param \App\Models\User $user
     * @return Thread
     */
    public function createThread($params, $user): Thread
    {

        return Thread::create([
            'protocol_id' => $params['protocol_id'],
            'title' => $params['title'],
            'body' => $params['body'],
            'author' => $user->name,
        ]);
    }

    /**
     * Update an existing thread.
     *
     * @param int|string $id
     * @param array $params
     * @return Thread|null
     */
    public function updateThread($id, $params): ?Thread
    {

        $thread = Thread::find($id);
        if (!$thread) {
            return null;
        }

        $thread->update($params);

        return $thread;
    }


    /**
     * Delete a thread by ID.
     *
     * @param int|string $id
     * @return Thread|null
     */
    public function deleteThread($id): ?Thread
    {
        $thread = Thread::find($id);
        if (!$thread) {
            return null;
        }

        $thread->delete();

        return $thread;
    }

    /**
     * Get statistics for a thread by ID.
     *
     * @param int $id
     * @return Thread|null
     */
    public function getThreadStatistics(int $id): ?Thread
    {
        return Thread::withCount(['comments', 'votes'])
            ->withCount(['votes as upvotes' => function ($query) {
                $query->where('type', 'upvote');
            }])
            ->withCount(['votes as downvotes' => function ($query) {
                $query->where('type', 'downvote');
            }])
            ->findOrFail($id);
    }

    /**
     * Get a paginated list of trending threads.
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function getTrendingThreads($params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 3;

        return Thread::with(['protocol'])
            ->withCount(['comments', 'votes'])
            ->withCount(['votes as upvotes' => function ($query) {
                $query->where('type', 'upvote');
            }])
            ->withCount(['votes as downvotes' => function ($query) {
                $query->where('type', 'downvote');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
