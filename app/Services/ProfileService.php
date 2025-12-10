<?php

namespace App\Services;

use App\Models\User;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Review;
use App\Http\Resources\ReplyResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ReviewResource;
use App\Http\Requests\ProfileRequest;
use App\Enums\VoteType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Profile Management Service
 *
 * Handles user profile data, updates, statistics, and activity for the platform.
 * Provides methods for retrieving and updating user profiles, statistics, comments, replies, and reviews.
 *
 * Features:
 * - User profile retrieval and update with validation
 * - User activity statistics and breakdowns
 * - Paginated and sorted retrieval of user comments, replies, and reviews
 * - Optimized vote and content counting using Eloquent relationships
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\User
 * @see App\Models\Protocol
 * @see App\Models\Thread
 * @see App\Models\Comment
 * @see App\Models\Review
 * @see App\Http\Resources\ProfileResource
 * @see App\Http\Resources\ReplyResource
 * @see App\Http\Resources\CommentResource
 * @see App\Http\Resources\ReviewResource
 */
class ProfileService
{
    /**
     * Get user profile.
     *
     * @param Request $request
     * @return User
     * @throws ValidationException
     */
    public function show(Request $request): User
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            return $user;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load the profile due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'profile' => [$message],
            ]);
        }
    }

    /**
     * Update user profile.
     *
     * @param ProfileRequest $request
     * @return User
     * @throws ValidationException
     */
    public function update(ProfileRequest $request): User
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();

                if (!$user) {
                    throw new \Exception('User not authenticated.');
                }

                $data = $request->validated();

                if (isset($data['new_password'])) {
                    if (!isset($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                        throw ValidationException::withMessages([
                            'current_password' => ['Current password is incorrect.']
                        ]);
                    }
                }

                if (isset($data['name'])) {
                    $user->name = $data['name'];
                }

                if (isset($data['email'])) {
                    $user->email = $data['email'];
                }

                if (isset($data['new_password'])) {
                    $user->password = Hash::make($data['new_password']);
                }

                $user->save();

                return $user;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t update the profile due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'profile' => [$message],
            ]);
        }
    }

    /**
     * Get user activity statistics with API endpoint references.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function statistics(Request $request): array
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            return [
                'total_protocols' => $this->getUserProtocolsCount($user),
                'total_threads' => $this->getUserThreadsCount($user),
                'total_comments' => $this->getUserCommentsCount($user),
                'total_replies' => $this->getUserRepliesCount($user),
                'total_reviews' => $this->getUserReviewsCount($user),
                'total_votes_received' => $this->getTotalVotesReceived($user->name),
                'member_since' => $user->created_at?->toISOString() ?? $user->created_at,
                'api_endpoints' => [
                    'description' => 'Use these endpoints to get user content with pagination and sorting',
                    'protocols' => "/api/protocols?author={$user->name}",
                    'threads' => "/api/threads?author={$user->name}",
                    'reviews' => "/api/protocols/1/reviews?author={$user->name}",
                    'replies' => "/api/profile/replies",
                ],
                'detailed_stats' => $this->getDetailedStatistics($user),
            ];
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load statistics due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'statistics' => [$message],
            ]);
        }
    }

    /**
     * Get user replies with pagination and sorting.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function indexReplies(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            $perPage = $request->get('per_page', 10);
            $sort = $request->get('sort', 'recent');

            $query = Reply::where('author', $user->name)
                ->with([
                    'comment.thread' => function ($query) {
                        $query->select('id', 'title', 'protocol_id');
                    },
                    'parent' => function ($query) {
                        $query->select('id', 'author', 'body', 'parent_id');
                    },
                    'votes'
                ])
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
                    'children as nested_replies_count'
                ]);

            // Load user's vote if authenticated
            if ($user) {
                $query->with(['votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }]);
            }

            // Apply sorting
            switch ($sort) {
                case 'popular':
                    $query->orderBy('vote_score', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'recent':
                default:
                    $query->latest();
                    break;
            }

            $replies = $query->paginate($perPage);

            // Transform replies using ReplyResource with profile-specific additions
            $replies->getCollection()->transform(function ($reply) use ($request) {
                $replyResource = new ReplyResource($reply);
                $replyArray = $replyResource->toArray($request);
                
                // Add profile-specific context
                $isNestedReply = $reply->parent && $reply->parent->parent_id !== null;
                $thread = $reply->comment->thread ?? null;
                
                $replyArray['thread'] = $thread ? [
                    'id' => $thread->id,
                    'title' => $thread->title,
                ] : null;
                
                $replyArray['reply_context'] = [
                    'is_nested_reply' => $isNestedReply,
                    'replying_to_author' => $reply->parent ? $reply->parent->author : null,
                    'replying_to_excerpt' => $reply->parent ? Str::limit($reply->parent->body, 100) : null,
                    'original_comment_id' => $isNestedReply ? ($reply->parent->comment_id ?? null) : $reply->comment_id,
                ];
                
                $threadId = $thread?->id ?? null;
                $replyArray['navigation'] = $threadId ? [
                    'thread_url' => "/threads/{$threadId}",
                    'api_url' => "/api/threads/{$threadId}/comments",
                ] : null;
                
                $replyArray['nested_replies_count'] = $reply->nested_replies_count ?? 0;
                
                return $replyArray;
            });

            return $replies;
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
     * Get user top-level comments with pagination and sorting.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function indexComments(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            $perPage = min($request->get('per_page', 10), 50);
            $sort = $request->get('sort', 'recent');

            $query = Comment::where('author', $user->name)
                ->with([
                    'thread' => function ($query) {
                        $query->select('id', 'title', 'protocol_id')->with('protocol:id,title');
                    }
                ])
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
                ]);

            // Load user's vote if authenticated
            if ($user) {
                $query->with(['votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }]);
            }

            // Apply sorting
            switch ($sort) {
                case 'popular':
                    $query->withCount(['votes as upvotes_count' => function ($q) {
                        $q->where('type', VoteType::UPVOTE->value);
                    }])->orderBy('upvotes_count', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'recent':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $comments = $query->paginate($perPage);

            // Transform comments using CommentResource
            $comments->getCollection()->transform(function ($comment) use ($request) {
                return (new CommentResource($comment))->toArray($request);
            });

            return $comments;
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
     * Get user reviews with pagination and sorting.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ValidationException
     */
    public function indexReviews(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            $perPage = min($request->get('per_page', 10), 50);
            $sort = $request->get('sort', 'recent');

            $query = Review::where('author', $user->name)
                ->with([
                    'protocol' => function ($query) {
                        $query->select('id', 'title', 'author', 'created_at');
                    }
                ]);

            // Load user's vote if authenticated
            if ($user) {
                $query->with(['votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }]);
            }

            // Apply sorting
            switch ($sort) {
                case 'rating_high':
                    $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
                    break;
                case 'rating_low':
                    $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'recent':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $reviews = $query->paginate($perPage);

            $reviews->getCollection()->transform(function ($review) use ($request) {
                return (new ReviewResource($review))->toArray($request);
            });

            return $reviews;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load reviews due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'reviews' => [$message],
            ]);
        }
    }

    /**
     * Get user protocols count.
     *
     * @param User $user
     * @return int
     */
    private function getUserProtocolsCount(User $user): int
    {
        return Protocol::where('author', $user->name)->count();
    }

    /**
     * Get user threads count.
     *
     * @param User $user
     * @return int
     */
    private function getUserThreadsCount(User $user): int
    {
        return Thread::where('author', $user->name)->count();
    }

    /**
     * Get user direct comments count (not replies).
     *
     * @param User $user
     * @return int
     */
    private function getUserCommentsCount(User $user): int
    {
        return Comment::where('author', $user->name)->count();
    }

    /**
     * Get user replies count.
     *
     * @param User $user
     * @return int
     */
    private function getUserRepliesCount(User $user): int
    {
        return Reply::where('author', $user->name)->count();
    }

    /**
     * Get user reviews count.
     *
     * @param User $user
     * @return int
     */
    private function getUserReviewsCount(User $user): int
    {
        return Review::where('author', $user->name)->count();
    }

    /**
     * Get detailed statistics breakdown by content type.
     *
     * @param User $user
     * @return array
     */
    private function getDetailedStatistics(User $user): array
    {
        $protocols = Protocol::where('author', $user->name)
            ->withCount(['reviews', 'threads'])
            ->get();

        $threads = Thread::where('author', $user->name)
            ->withCount(['comments', 'votes'])
            ->get();

        $comments = Comment::where('author', $user->name)
            ->withCount('votes')
            ->get();

        $reviews = Review::where('author', $user->name)->get();

        return [
            'protocols' => [
                'total' => $this->getUserProtocolsCount($user),
                'total_reviews_received' => $protocols->sum('reviews_count'),
                'total_threads_created' => $protocols->sum('threads_count'),
            ],
            'threads' => [
                'total' => $this->getUserThreadsCount($user),
                'total_comments_received' => $threads->sum('comments_count'),
                'total_votes_received' => $threads->sum('votes_count'),
            ],
            'comments' => [
                'direct_comments' => $this->getUserCommentsCount($user),
                'replies' => $this->getUserRepliesCount($user),
                'total_votes_received' => $comments->sum('votes_count'),
            ],
            'reviews' => [
                'total' => $this->getUserReviewsCount($user),
                'average_rating_given' => $reviews->avg('rating') ?? 0,
                'highest_rating_given' => $reviews->max('rating') ?? 0,
                'lowest_rating_given' => $reviews->min('rating') ?? 0,
            ],
        ];
    }

    /**
     * Get total votes received by a user across all their content using Eloquent relationships.
     *
     * @param string $username
     * @return int
     */
    private function getTotalVotesReceived(string $username): int
    {
        $protocolVotes = Protocol::where('author', $username)
            ->withCount('votes')
            ->get()
            ->sum('votes_count');

        $threadVotes = Thread::where('author', $username)
            ->withCount('votes')
            ->get()
            ->sum('votes_count');

        $commentVotes = Comment::where('author', $username)
            ->withCount('votes')
            ->get()
            ->sum('votes_count');

        $replyVotes = Reply::where('author', $username)
            ->withCount('votes')
            ->get()
            ->sum('votes_count');

        return $protocolVotes + $threadVotes + $commentVotes + $replyVotes;
    }

}
