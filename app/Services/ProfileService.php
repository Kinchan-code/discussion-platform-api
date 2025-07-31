<?php

namespace App\Services;

use App\Models\User;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\Comment;
use App\Models\Review;
use App\DTOs\ProfileDTO;
use App\DTOs\ProfileStatisticsDTO;
use App\DTOs\ProfileReplyDTO;
use App\DTOs\ProfileCommentDTO;
use App\DTOs\ReviewDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Profile Management Service
 *
 * Handles user profile data, updates, statistics, activity, and review highlighting for the platform.
 * Provides methods for retrieving and updating user profiles, statistics, comments, replies, and reviews.
 *
 * Features:
 * - User profile retrieval and update with validation
 * - User activity statistics and breakdowns
 * - Paginated and sorted retrieval of user comments, replies, and reviews
 * - Smart highlighting for reviews
 * - Optimized vote and content counting
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
 * @see App\DTOs\ProfileDTO
 * @see App\DTOs\ProfileStatisticsDTO
 * @see App\DTOs\ProfileReplyDTO
 * @see App\DTOs\ProfileCommentDTO
 * @see App\DTOs\ReviewDTO
 */
class ProfileService
{
    /**
     * Get user profile data as DTO.
     *
     * @param User $user
     * @return ProfileDTO
     */
    public function getUserProfile(User $user): ProfileDTO
    {
        return new ProfileDTO($user);
    }

    /**
     * Update user profile with validation.
     *
     * @param User $user
     * @param array $data
     * @return ProfileDTO
     * @throws ValidationException When current password is incorrect
     */
    public function updateUserProfile(User $user, array $data): ProfileDTO
    {
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

        return new ProfileDTO($user);
    }

    /**
     * Get user activity statistics with API endpoint references.
     *
     * @param User $user
     * @return ProfileStatisticsDTO
     */
    public function getUserStatistics(User $user): ProfileStatisticsDTO
    {
        $statsData = [
            'total_protocols' => $this->getUserProtocolsCount($user),
            'total_threads' => $this->getUserThreadsCount($user),
            'total_comments' => $this->getUserCommentsCount($user),
            'total_replies' => $this->getUserRepliesCount($user),
            'total_reviews' => $this->getUserReviewsCount($user),
            'total_votes_received' => $this->getTotalVotesReceived($user->name),
            'detailed_stats' => $this->getDetailedStatistics($user),
        ];

        return new ProfileStatisticsDTO($user, $statsData);
    }

    /**
     * Get user replies with pagination and sorting.
     *
     * @param User $user
     * @param int $perPage
     * @param string $sort
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserReplies(User $user, int $perPage = 10, string $sort = 'recent'): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Comment::where('author', $user->name)
            ->whereNotNull('parent_id') // Only replies
            ->with([
                'thread' => function ($query) {
                    $query->select('id', 'title', 'protocol_id'); // Only load needed fields
                },
                'parent' => function ($query) {
                    $query->select('id', 'author', 'body', 'parent_id');
                },
                'votes' // Eager load votes for efficient counting
            ])
            ->withCount(['children as nested_replies_count']);

        // Apply sorting with performance optimizations
        switch ($sort) {
            case 'popular':
                // Use our performance indexes for vote counting
                $query->select('comments.*')
                    ->leftJoin('votes', function ($join) {
                        $join->on('comments.id', '=', 'votes.votable_id')
                            ->where('votes.votable_type', '=', 'App\\Models\\Comment')
                            ->where('votes.type', '=', 'upvote');
                    })
                    ->groupBy('comments.id')
                    ->orderByRaw('COUNT(votes.id) DESC');
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

        // Transform replies using DTO with optimized vote counting
        $replies->getCollection()->transform(function ($reply) {
            $votesCollection = collect($reply->votes);
            $reply->upvotes = $votesCollection->where('type', 'upvote')->count();
            $reply->downvotes = $votesCollection->where('type', 'downvote')->count();
            $reply->vote_score = $reply->upvotes - $reply->downvotes;

            return (new ProfileReplyDTO($reply))->toArray();
        });

        return $replies;
    }

    /**
     * Get user top-level comments with pagination and sorting.
     *
     * @param User $user
     * @param int $perPage
     * @param string $sort
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserComments(User $user, int $perPage = 10, string $sort = 'recent'): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Comment::where('author', $user->name)
            ->whereNull('parent_id') // Only top-level comments
            ->with([
                'thread' => function ($query) {
                    $query->select('id', 'title', 'protocol_id')->with('protocol:id,title'); // Only load needed fields
                },
                'votes' // Eager load all votes for efficient counting
            ])
            ->withCount(['children as replies_count']);

        // Apply sorting with optimized queries
        switch ($sort) {
            case 'popular':
                // Use our performance indexes for vote counting
                $query->select('comments.*')
                    ->leftJoin('votes', function ($join) {
                        $join->on('comments.id', '=', 'votes.votable_id')
                            ->where('votes.votable_type', '=', 'App\\Models\\Comment')
                            ->where('votes.type', '=', 'upvote');
                    })
                    ->groupBy('comments.id')
                    ->orderByRaw('COUNT(votes.id) DESC');
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

        // Transform comments using DTO with optimized vote counting
        $comments->getCollection()->transform(function ($comment) {
            $votesCollection = collect($comment->votes);
            $comment->upvotes = $votesCollection->where('type', 'upvote')->count();
            $comment->downvotes = $votesCollection->where('type', 'downvote')->count();
            $comment->vote_score = $comment->upvotes - $comment->downvotes;

            return (new ProfileCommentDTO($comment))->toArray();
        });

        return $comments;
    }

    /**
     * Get user reviews with pagination, sorting, and smart highlighting support.
     *
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserReviews(User $user, Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = min($request->get('per_page', 10), 50);
        $sort = $request->get('sort', 'recent');
        $highlightReviewId = $request->get('highlight_review');

        $query = Review::where('author', $user->name)
            ->with([
                'protocol' => function ($query) {
                    $query->select('id', 'title', 'author', 'created_at');
                }
            ]);

        // Apply sorting with optimized queries
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

        if ($highlightReviewId) {
            return $this->getReviewsWithSmartHighlighting($query, $request, $perPage, $highlightReviewId);
        }

        $reviews = $query->paginate($perPage);

        $reviews->getCollection()->transform(function ($review) {
            $reviewDto = ReviewDTO::fromModel($review);
            $reviewArray = $reviewDto->toArray();

            $reviewArray['is_highlighted'] = false;

            $reviewArray['context'] = [
                'source' => 'profile_reviews',
                'protocol_title' => $review->protocol?->title ?? 'Unknown Protocol',
                'protocol_author' => $review->protocol?->author ?? 'Unknown Author',
                'protocol_created_at' => $review->protocol?->created_at?->toISOString() ?? '',
            ];

            return $reviewArray;
        });

        return $reviews;
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
        return Comment::where('author', $user->name)->whereNull('parent_id')->count();
    }

    /**
     * Get user replies count.
     *
     * @param User $user
     * @return int
     */
    private function getUserRepliesCount(User $user): int
    {
        return Comment::where('author', $user->name)->whereNotNull('parent_id')->count();
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
        return [
            'protocols' => [
                'total' => $this->getUserProtocolsCount($user),
                'total_reviews_received' => Protocol::where('author', $user->name)
                    ->withCount('reviews')->get()->sum('reviews_count'),
                'total_threads_created' => Protocol::where('author', $user->name)
                    ->withCount('threads')->get()->sum('threads_count'),
            ],
            'threads' => [
                'total' => $this->getUserThreadsCount($user),
                'total_comments_received' => Thread::where('author', $user->name)
                    ->withCount('comments')->get()->sum('comments_count'),
                'total_votes_received' => Thread::where('author', $user->name)
                    ->withCount('votes')->get()->sum('votes_count'),
            ],
            'comments' => [
                'direct_comments' => $this->getUserCommentsCount($user),
                'replies' => $this->getUserRepliesCount($user),
                'total_votes_received' => Comment::where('author', $user->name)
                    ->withCount('votes')->get()->sum('votes_count'),
            ],
            'reviews' => [
                'total' => $this->getUserReviewsCount($user),
                'average_rating_given' => Review::where('author', $user->name)->avg('rating') ?? 0,
                'highest_rating_given' => Review::where('author', $user->name)->max('rating') ?? 0,
                'lowest_rating_given' => Review::where('author', $user->name)->min('rating') ?? 0,
            ],
        ];
    }

    /**
     * Get total votes received by a user across all their content using optimized queries.
     *
     * @param string $username
     * @return int
     */
    private function getTotalVotesReceived(string $username): int
    {
        $protocolVotes = DB::table('votes')
            ->join('protocols', 'votes.votable_id', '=', 'protocols.id')
            ->where('votes.votable_type', 'App\\Models\\Protocol')
            ->where('protocols.author', $username)
            ->count();

        $threadVotes = DB::table('votes')
            ->join('threads', 'votes.votable_id', '=', 'threads.id')
            ->where('votes.votable_type', 'App\\Models\\Thread')
            ->where('threads.author', $username)
            ->count();

        $commentVotes = DB::table('votes')
            ->join('comments', 'votes.votable_id', '=', 'comments.id')
            ->where('votes.votable_type', 'App\\Models\\Comment')
            ->where('comments.author', $username)
            ->count();

        return $protocolVotes + $threadVotes + $commentVotes;
    }

    /**
     * Get reviews with smart highlighting that ensures highlighted items are visible.
     *
     * @param $reviewsQuery
     * @param Request $request
     * @param int $perPage
     * @param $highlightReviewId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function getReviewsWithSmartHighlighting($reviewsQuery, Request $request, int $perPage, $highlightReviewId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $currentPage = $request->get('page', 1);

        // First, try normal pagination
        $reviews = $reviewsQuery->paginate($perPage);
        $foundHighlight = false;
        $highlightLocation = null;
        $highlightedItem = null;

        // Check if highlighted review is in current page
        foreach ($reviews->items() as $review) {
            if ($review->id == $highlightReviewId) {
                $foundHighlight = true;
                break;
            }
        }

        // If highlight not found, find it and include it in results
        $extraItem = null;
        if (!$foundHighlight) {
            // Find the highlighted review
            $highlightedItem = Review::where('author', $request->user()->name)
                ->where('id', $highlightReviewId)
                ->with([
                    'protocol' => function ($query) {
                        $query->select('id', 'title', 'author', 'created_at');
                    }
                ])
                ->first();

            if ($highlightedItem) {
                // Calculate where it would naturally appear
                $highlightLocation = $this->calculateReviewHighlightLocation($reviewsQuery, $highlightedItem, $perPage);

                // If it's not on current page, we'll add it as extra item
                if ($highlightLocation && $highlightLocation['natural_page'] != $currentPage) {
                    $extraItem = $highlightedItem;
                }
            }
        }

        // Transform reviews with highlighting
        $reviews->getCollection()->transform(function ($review) use ($highlightReviewId) {
            $reviewDto = ReviewDTO::fromModel($review);
            $reviewArray = $reviewDto->toArray();

            // Add highlighting flag
            $reviewArray['is_highlighted'] = $review->id == $highlightReviewId;

            // Add navigation context for profile reviews
            $reviewArray['context'] = [
                'source' => 'profile_reviews',
                'protocol_title' => $review->protocol?->title ?? 'Unknown Protocol',
                'protocol_author' => $review->protocol?->author ?? 'Unknown Author',
                'protocol_created_at' => $review->protocol?->created_at?->toISOString() ?? '',
            ];

            return $reviewArray;
        });

        // Add extra highlighted item if needed
        if ($extraItem) {
            $extraReviewDto = ReviewDTO::fromModel($extraItem);
            $extraReviewArray = $extraReviewDto->toArray();
            $extraReviewArray['is_highlighted'] = true;
            $extraReviewArray['context'] = [
                'source' => 'profile_reviews',
                'protocol_title' => $extraItem->protocol?->title ?? 'Unknown Protocol',
                'protocol_author' => $extraItem->protocol?->author ?? 'Unknown Author',
                'protocol_created_at' => $extraItem->protocol?->created_at?->toISOString() ?? '',
                'highlight_info' => [
                    'found_on_different_page' => true,
                    'natural_page' => $highlightLocation['natural_page'] ?? null,
                    'natural_position' => $highlightLocation['position_in_page'] ?? null,
                ]
            ];

            // Add to the end of current page results
            $currentItems = $reviews->items();
            $currentItems[] = $extraReviewArray;
            $reviews = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                $reviews->total() + 1, // Increase total by 1 for the extra item
                $reviews->perPage(),
                $reviews->currentPage(),
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );
        }

        return $reviews;
    }

    /**
     * Calculate where the highlighted review would naturally appear.
     *
     * @param $reviewsQuery
     * @param $highlightedItem
     * @param int $perPage
     * @return array|null
     */
    private function calculateReviewHighlightLocation($reviewsQuery, $highlightedItem, int $perPage): ?array
    {
        if (!$highlightedItem) return null;

        // Count reviews that would appear before this one with current sorting
        $query = clone $reviewsQuery;
        $countBefore = $query->where('id', '<', $highlightedItem->id)->count();

        $naturalPage = intval(ceil(($countBefore + 1) / $perPage));
        $positionInPage = ($countBefore % $perPage) + 1;

        return [
            'natural_page' => $naturalPage,
            'position_in_page' => $positionInPage,
            'count_before' => $countBefore,
        ];
    }
}
