<?php

namespace App\Services;

use App\Models\Thread;
use App\Http\Requests\ThreadRequest;
use App\Enums\VoteType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;


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
 * - Paginated thread listing with filters and sorting using Eloquent relationships
 * - Thread creation, update, and deletion
 * - Efficient vote and comment aggregation via withCount
 * - Trending and protocol-specific thread queries
 * - Uses eager loading (with) for protocol relationship to avoid N+1 queries
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 2.0.0
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
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $perPage = $request->input('per_page', 15);
            $sort = $request->input('sort', 'recent');
            $protocolId = $request->input('protocol_id');
            $author = $request->input('author');

            // Handle 'current_user' special case for authenticated requests
            if ($author === 'current_user') {
                if (!Auth::guard('sanctum')->check()) {
                    throw new \Exception('Authentication required for current_user filter.');
                }
                $author = Auth::guard('sanctum')->user()->name;
            }

            $query = Thread::with(['protocol'])
                ->withCount(['comments'])
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', \App\Enums\VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', \App\Enums\VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . \App\Enums\VoteType::sqlCaseExpression() . ")");
                    }
                ]);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            if ($author) {
                $query->where('author', $author);
            }

            if ($protocolId) {
                $query->where('protocol_id', $protocolId);
            }

            switch ($sort) {
                case 'popular':
                    $query->orderByDesc('vote_score')
                        ->orderByDesc('comments_count')
                        ->orderByDesc('created_at');
                    break;
                case 'rating':
                    $query->orderByDesc('vote_score')
                        ->orderByDesc('created_at');
                    break;
                case 'recent':
                default:
                    $query->orderByDesc('created_at');
                    break;
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load threads due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'threads' => [$message],
            ]);
        }
    }

    /**
     * Retrieve a thread by ID with related protocol and votes.
     *
     * @param string $id
     * @param Request $request
     * @return Thread
     * @throws ValidationException
     */
    public function show(string $id, Request $request): Thread
    {
        try {
            $query = Thread::with(['protocol'])
                ->withCount(['comments'])
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', \App\Enums\VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', \App\Enums\VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . \App\Enums\VoteType::sqlCaseExpression() . ")");
                    }
                ]);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);
            
            return $query->findOrFail($id);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load the thread due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'thread' => [$message],
            ]);
        }
    }

    /**
     * Get paginated threads for a specific protocol.
     *
     * @param string $protocolId
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getThreadsByProtocol(string $protocolId, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $request->input('per_page', 15);

        $query = Thread::where('protocol_id', $protocolId)
            ->with(['protocol'])
            ->withCount(['comments'])
            ->withCount([
                'votes as upvotes' => function ($q) {
                    $q->where('type', \App\Enums\VoteType::UPVOTE->value);
                },
                'votes as downvotes' => function ($q) {
                    $q->where('type', \App\Enums\VoteType::DOWNVOTE->value);
                },
                'votes as vote_score' => function ($q) {
                    $q->selectRaw("SUM(" . \App\Enums\VoteType::sqlCaseExpression() . ")");
                }
            ]);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Create a new thread.
     *
     * @param ThreadRequest $request
     * @return Thread
     * @throws ValidationException
     */
    public function store(ThreadRequest $request): Thread
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $user = Auth::guard('sanctum')->user();

                return Thread::create([
                    'protocol_id' => $data['protocol_id'],
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'author' => $user->name,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the thread due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'thread' => [$message],
            ]);
        }
    }

    /**
     * Update an existing thread.
     *
     * @param string $id
     * @param ThreadRequest $request
     * @return Thread
     * @throws ValidationException
     */
    public function update(string $id, ThreadRequest $request): Thread
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $thread = Thread::findOrFail($id);
                $user = Auth::guard('sanctum')->user();

                if ($thread->author !== $user->name) {
                    throw new \Exception('You can only update threads that you created.');
                }

                $data = $request->validated();

                $thread->update([
                    'protocol_id' => $data['protocol_id'] ?? $thread->protocol_id,
                    'title' => $data['title'] ?? $thread->title,
                    'body' => $data['body'] ?? $thread->body,
                ]);

                return $thread;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the thread due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'thread' => [$message],
            ]);
        }
    }


    /**
     * Delete a thread by ID.
     *
     * @param string $id
     * @return void
     * @throws ValidationException
     */
    public function destroy(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $thread = Thread::findOrFail($id);
                $user = Auth::guard('sanctum')->user();

                if ($thread->author !== $user->name) {
                    throw new \Exception('You can only delete threads that you created.');
                }

                $thread->delete();
            });
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t delete the thread due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'thread' => [$message],
            ]);
        }
    }

    /**
     * Get statistics for a thread by ID.
     *
     * @param int $id
     * @return Thread|null
     */
    public function getThreadStatistics(string $id): ?Thread
    {
        return Thread::withCount(['comments', 'votes'])
            ->withCount(['votes as upvotes' => function ($query) {
                $query->where('type', 'upvote');
            }])
            ->withCount(['votes as downvotes' => function ($query) {
                $query->where('type', 'downvote');
            }])
            ->withCount([
                'votes as vote_score' => function ($q) {
                    $q->selectRaw("SUM(" . \App\Enums\VoteType::sqlCaseExpression() . ")");
                }
            ])
            ->findOrFail($id);
    }

    /**
     * Get a paginated list of trending threads.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTrendingThreads(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $request->input('per_page', 3);

        $query = Thread::with(['protocol'])
            ->withCount(['comments'])
            ->withCount([
                'votes as vote_score' => function ($q) {
                    $q->selectRaw("SUM(" . \App\Enums\VoteType::sqlCaseExpression() . ")");
                }
            ]);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
