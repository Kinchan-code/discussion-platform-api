<?php

namespace App\Services;

use App\Models\Thread;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;


/**
 * Thread Management Service
 *
 * Handles thread listing, retrieval, creation, updating, deletion, and statistics for the platform.
 * Provides methods for paginated thread queries, filtering, sorting, trending, and efficient vote/comment aggregation.
 *
 * PERFORMANCE NOTE: Reverted to relationship-based queries for stability. JOIN optimization was attempted
 * but caused pagination issues and didn't improve performance on AWS RDS Free Tier infrastructure.
 *
 * Features:
 * - Paginated thread listing with filters and sorting (using stable relationship approach)
 * - Thread creation, update, and deletion
 * - Efficient vote and comment aggregation via withCount
 * - Trending and protocol-specific thread queries
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.2.0 (Reverted to stable approach)
 * @since 2025-07-31
 * @updated 2025-08-03 (Reverted JOIN optimization due to pagination issues)
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

        // OPTIMIZED FOR UI REQUIREMENTS - Only get what's needed: protocol ID, protocol name, vote score, comments count
        $query = Thread::select([
            'threads.*',
            'protocols.id as protocol_id_data',
            'protocols.title as protocol_title'
        ])
            ->leftJoin('protocols', 'threads.protocol_id', '=', 'protocols.id')
            ->withCount(['comments'])
            ->withCount([
                'votes as vote_score' => function ($q) {
                    $q->selectRaw('SUM(CASE WHEN type = "upvote" THEN 1 WHEN type = "downvote" THEN -1 ELSE 0 END)');
                }
            ]);

        if ($author) {
            $query->where('threads.author', $author);
        }

        if ($protocolId) {
            $query->where('threads.protocol_id', $protocolId);
        }

        switch ($sort) {
            case 'popular':
                $query->orderByDesc('vote_score')
                    ->orderByDesc('comments_count')
                    ->orderByDesc('threads.created_at');
                break;
            case 'rating':
                $query->orderByDesc('vote_score')
                    ->orderByDesc('threads.created_at');
                break;
            case 'recent':
            default:
                $query->orderByDesc('threads.created_at');
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
        $thread = Thread::select([
            'threads.*',
            'protocols.id as protocol_id_data',
            'protocols.title as protocol_title',
            'protocols.author as protocol_author'
        ])
            ->leftJoin('protocols', 'threads.protocol_id', '=', 'protocols.id')
            ->withCount(['comments'])
            ->withCount([
                'votes as vote_score' => function ($q) {
                    $q->selectRaw('SUM(CASE WHEN type = "upvote" THEN 1 WHEN type = "downvote" THEN -1 ELSE 0 END)');
                }
            ])
            ->findOrFail($id);

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

        return Thread::select([
            'threads.*',
            'protocols.id as protocol_id_data',
            'protocols.title as protocol_title',
            'protocols.author as protocol_author',
            DB::raw('(SELECT COUNT(*) FROM comments WHERE comments.thread_id = threads.id) as comments_count'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id) as votes_count'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id AND votes.type = "upvote") as upvotes'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id AND votes.type = "downvote") as downvotes'),
            DB::raw('(SELECT COALESCE(SUM(CASE WHEN votes.type = "upvote" THEN 1 WHEN votes.type = "downvote" THEN -1 ELSE 0 END), 0) FROM votes WHERE votes.thread_id = threads.id) as vote_score')
        ])
            ->leftJoin('protocols', 'threads.protocol_id', '=', 'protocols.id')
            ->where('threads.protocol_id', $protocolId)
            ->latest('threads.created_at')
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

        return Thread::select([
            'threads.*',
            'protocols.id as protocol_id_data',
            'protocols.title as protocol_title',
            'protocols.author as protocol_author',
            DB::raw('(SELECT COUNT(*) FROM comments WHERE comments.thread_id = threads.id) as comments_count'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id) as votes_count'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id AND votes.type = "upvote") as upvotes'),
            DB::raw('(SELECT COUNT(*) FROM votes WHERE votes.thread_id = threads.id AND votes.type = "downvote") as downvotes'),
            DB::raw('(SELECT COALESCE(SUM(CASE WHEN votes.type = "upvote" THEN 1 WHEN votes.type = "downvote" THEN -1 ELSE 0 END), 0) FROM votes WHERE votes.thread_id = threads.id) as vote_score')
        ])
            ->leftJoin('protocols', 'threads.protocol_id', '=', 'protocols.id')
            ->orderBy('threads.created_at', 'desc')
            ->paginate($perPage);
    }
}
