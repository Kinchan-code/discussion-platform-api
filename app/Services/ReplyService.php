<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Reply;
use App\Models\User;
use App\Enums\VoteType;
use App\Http\Requests\ReplyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Reply Management Service
 *
 * Handles all reply operations for comments, including creation, retrieval,
 * updating, and deletion. Supports nested replies and vote counting.
 *
 * Features:
 * - Comment reply management
 * - Nested reply support (reply to replies)
 * - Vote counting and engagement metrics
 * - Optimized pagination
 * - Authorization for reply actions
 *
 * @package App\Services
 */
class ReplyService
{
    /**
     * Get replies for a comment with pagination.
     *
     * @param string $commentId
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(string $commentId, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $comment = Comment::findOrFail($commentId);
            $query = $comment->replies()
                ->whereNull('parent_id')
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    },
                    'children'
                ])
                ->with(['user', 'children', 'replyTo:id,author,body'])
                ->withCount(['children as nested_replies_count']);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            $query->orderBy('created_at', 'desc');

            $perPage = min((int) $request->input('per_page', 20), 100);
            $currentPage = max((int) $request->input('page', 1), 1);

            return $query->paginate($perPage, ['*'], 'page', $currentPage);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load replies due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'replies' => [$message],
            ]);
        }
    }

    /**
     * Get a single reply by ID.
     *
     * @param string $id
     * @param Request $request
     * @return Reply
     */
    public function show(string $id, Request $request): Reply
    {
        try {
            $query = Reply::with(['user', 'children', 'children.user', 'comment', 'replyTo:id,author,body'])
                ->withCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    },
                    'children'
                ])
                ->withCount(['children as nested_replies_count']);

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
                : 'We couldn\'t load the reply due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reply' => [$message],
            ]);
        }
    }

    /**
     * Create a new reply for a comment.
     *
     * @param string $commentId
     * @param Request $request
     * @return Reply
     */
    public function store(string $commentId, ReplyRequest $request): Reply
    {
        try {
            return DB::transaction(function () use ($commentId, $request) {
                $comment = Comment::findOrFail($commentId);
                $data = $request->validated();

                $reply = $comment->replies()->create([
                    'parent_id' => null,
                    'reply_to_id' => null,
                    'body' => $data['body'],
                    'author' => Auth::user()->name,
                    'user_id' => Auth::id(),
                ]);

                $reply->loadCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    },
                    'children'
                ]);

                return $reply->load(['user', 'replyTo:id,author,body']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the reply due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reply' => [$message],
            ]);
        }
    }

    /**
     * Create a nested reply (reply to a reply).
     *
     * @param string $parentReplyId
     * @param Request $request
     * @return Reply
     */
    public function storeChild(string $parentReplyId, ReplyRequest $request): Reply
    {
        try {
            return DB::transaction(function () use ($parentReplyId, $request) {
                $parent = Reply::findOrFail($parentReplyId);
                $data = $request->validated();

                // Find the top-level reply for grouping (maintains 2-level UI structure)
                // If parent already has a parent, use that; otherwise, parent is top-level
                $topLevelReplyId = $parent->parent_id ?? $parent->id;

                $reply = Reply::create([
                    'comment_id' => $parent->comment_id,
                    'parent_id'  => $topLevelReplyId,  // UI grouping under top-level
                    'reply_to_id' => $parent->id,      // Actual reply context
                    'body' => $data['body'],
                    'author' => Auth::user()->name,
                    'user_id' => Auth::id(),
                ]);

                $reply->loadCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    },
                    'children'
                ]);

                return $reply->load(['user', 'replyTo:id,author,body']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t create the reply due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reply' => [$message],
            ]);
        }
    }

    /**
     * Get children replies for a parent reply.
     *
     * @param string $replyId
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function indexChildren(string $replyId, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $reply = Reply::findOrFail($replyId);

            $query = $reply->children()
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
                ->with(['user', 'replyTo:id,author,body']);

            // Load user's vote if authenticated, otherwise load empty collection
            $query->with(['votes' => function ($q) {
                if (Auth::guard('sanctum')->check()) {
                    $q->where('user_id', Auth::guard('sanctum')->id());
                } else {
                    $q->whereRaw('1 = 0'); // Load nothing for unauthenticated users
                }
            }]);

            $query->orderBy('created_at', 'desc');

            $perPage = min((int) $request->input('per_page', 20), 100);
            $currentPage = max((int) $request->input('page', 1), 1);

            return $query->paginate($perPage, ['*'], 'page', $currentPage);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load reply children due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'replies' => [$message],
            ]);
        }
    }

    /**
     * Update a reply if user is authorized.
     *
     * @param string $id
     * @param Request $request
     * @return Reply
     * @throws \Exception When user is not authorized
     */
    public function update(string $id, ReplyRequest $request): Reply
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $reply = Reply::findOrFail($id);

                if ($reply->user_id !== Auth::id()) {
                    throw new \Exception('You can only edit your own replies.');
                }

                $data = $request->validated();

                $reply->update($data);

                $reply->loadCount([
                    'votes as upvotes' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    },
                    'votes as downvotes' => function ($q) {
                        $q->where('type', VoteType::DOWNVOTE->value);
                    },
                    'votes as vote_score' => function ($q) {
                        $q->selectRaw("SUM(" . VoteType::sqlCaseExpression() . ")");
                    },
                    'children'
                ]);

                return $reply->fresh(['user', 'replyTo:id,author,body']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the reply due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reply' => [$message],
            ]);
        }
    }

    /**
     * Delete a reply if user is authorized.
     *
     * @param string $id
     * @return void
     * @throws \Exception When user is not authorized
     */
    public function destroy(string $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $reply = Reply::findOrFail($id);

                if ($reply->user_id !== Auth::id()) {
                    throw new \Exception('You can only delete your own replies.');
                }

                $reply->delete();
            });
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t delete the reply due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reply' => [$message],
            ]);
        }
    }
}

