<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Thread;
use App\Enums\VoteType;
use App\Http\Requests\CommentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Comment Management Service
 *
 * Handles all comment operations for threads, including creation, retrieval,
 * updating, and deletion. Supports vote counting and optimized pagination
 * for large discussion threads.
 *
 * Features:
 * - Thread comment management
 * - Vote counting and engagement metrics
 * - Optimized pagination and sorting
 * - Authorization for comment actions
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\Comment
 * @see App\Models\Thread
 * @see App\Models\User
 * @see App\Http\Resources\CommentResource
 * @see App\Http\Resources\ThreadResource
 */
class CommentService
{
    /**
     * Get comments with pagination and filtering.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $threadId = $request->route('thread');
            $thread = Thread::findOrFail($threadId);

            $query = $thread->comments()
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    }
                ])
                ->with(['replies'])
                ->withCount(['replies as replies_count']);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            $query->orderBy('created_at', 'desc');

            if ($request->has('author')) {
                $author = $request->get('author');
                if ($author === 'current_user') {
                    if (!Auth::check()) {
                        throw new \Exception('Authentication required for current_user filter.');
                    }
                    $author = Auth::user()->name;
                }
                $query->where('author', $author);
            }

            $perPage = min((int) $request->input('per_page', 20), 100);
            $currentPage = max((int) $request->input('page', 1), 1);

            return $query->paginate($perPage, ['*'], 'page', $currentPage);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load comments due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'comments' => [$message],
            ]);
        }
    }

    /**
     * Store a new comment.
     *
     * @param CommentRequest $request
     * @param int $threadId
     * @return Comment
     * @throws ValidationException
     */
    public function store(CommentRequest $request, string $threadId): Comment
    {
        try {
            return DB::transaction(function () use ($request, $threadId) {
                $data = $request->validated();

                $thread = Thread::findOrFail($threadId);
                $user = Auth::user();

                $comment = $thread->comments()->create([
                    'body' => $data['body'],
                    'author' => $user->name,
                ]);

                $comment->loadCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    }
                ]);

                return $comment->load('thread');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the comment due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'comment' => [$message],
            ]);
        }
    }

    /**
     * Get a single comment by ID.
     *
     * @param string $id
     * @param Request $request
     * @return Comment
     * @throws ValidationException
     */
    public function show(string $id, Request $request): Comment
    {
        try {
            $query = Comment::with(['replies', 'thread'])
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    }
                ])
                ->withCount(['replies as replies_count']);

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
                : 'We couldn\'t load the comment due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'comment' => [$message],
            ]);
        }
    }

    /**
     * Update a comment.
     *
     * @param string $id
     * @param \Illuminate\Http\Request $request
     * @return Comment
     * @throws ValidationException
     */
    public function update(string $id, CommentRequest $request): Comment
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $comment = Comment::findOrFail($id);
                $user = Auth::user();
                $data = $request->validated();

                if ($comment->author !== $user->name) {
                    throw new \Exception('You can only edit your own comments.');
                }

                $comment->update([
                    'body' => $data['body'],
                ]);

                $comment->refresh();

                $comment->loadCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    }
                ]);

                return $comment;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the comment due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'comment' => [$message],
            ]);
        }
    }

    /**
     * Delete a comment by ID.
     *
     * @param string $id
     * @return void
     * @throws ValidationException
     */
    public function destroy(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $comment = Comment::findOrFail($id);
                $user = Auth::user();

                if ($comment->author !== $user->name) {
                    throw new \Exception('You can only delete your own comments.');
                }

                $comment->delete();
            });
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t delete the comment due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'comment' => [$message],
            ]);
        }
    }
}
