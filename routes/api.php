<?php
// filepath: c:\Users\Simplevia\Documents\Tian\Upskill\discussion-platform-api\routes\api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TagController;

// API Documentation
Route::get('/', function () {
    return response()->json([
        'message' => 'Discussion Platform API',
        'version' => '1.0.0',
        'base_url' => '/api',
        'authentication' => 'Bearer token required for protected endpoints',
        'endpoints' => [
            'auth' => [
                'POST /register' => 'Register new user',
                'POST /login' => 'Login user',
                'POST /logout' => 'Logout user [AUTH]'
            ],
            'profile' => [
                'GET /profile' => 'Get current user profile [AUTH]',
                'PUT /profile' => 'Update current user profile [AUTH]',
                'GET /profile/statistics' => 'Get user activity statistics [AUTH]',
                'GET /profile/replies' => 'Get user\'s reply history across all threads (?sort=recent|popular|oldest&highlight_reply=id) [AUTH]',
                'GET /profile/comments' => 'Get user\'s top-level comments across all threads (?sort=recent|popular|oldest&highlight_comment=id) [AUTH]',
                'GET /profile/reviews' => 'Get user\'s reviews across all protocols (?sort=recent|oldest|rating_high|rating_low&highlight_review=id) [AUTH]'
            ],
            'protocols' => [
                'GET /protocols' => 'List protocols (?author=username&sort=recent|popular&tags=tag1,tag2)',
                'GET /protocols/{id}' => 'Get specific protocol',
                'GET /protocols/featured' => 'Get featured protocols',
                'GET /protocols/filters' => 'Get protocol filters',
                'GET /protocols/{id}/stats' => 'Get protocol statistics',
                'POST /protocols' => 'Create protocol [AUTH]',
                'PUT /protocols/{id}' => 'Update protocol [AUTH]',
                'DELETE /protocols/{id}' => 'Delete protocol [AUTH]'
            ],
            'threads' => [
                'GET /threads' => 'List threads (?author=username&protocol_id=1&sort=recent|popular)',
                'GET /threads/{id}' => 'Get specific thread',
                'GET /threads/trending' => 'Get trending threads',
                'GET /threads/{id}/stats' => 'Get thread statistics',
                'GET /protocols/{protocol}/threads' => 'Get threads by protocol',
                'POST /threads' => 'Create thread [AUTH]',
                'PUT /threads/{id}' => 'Update thread [AUTH]',
                'DELETE /threads/{id}' => 'Delete thread [AUTH]'
            ],
            'comments' => [
                'GET /threads/{thread}/comments' => 'Get comments (?author=username&highlight_comment=123&highlight_reply=456) [Smart Highlighting]',
                'GET /comments/{comment}/replies' => 'Get comment replies',
                'GET /replies/{reply}/nested' => 'Get nested replies for specific reply (progressive loading)',
                'POST /threads/{thread}/comments' => 'Create comment [AUTH]',
                'POST /comments/{comment}/reply' => 'Reply to comment [AUTH]',
                'POST /replies/{reply}/reply' => 'Reply to reply [AUTH]',
                'PUT /comments/{comment}' => 'Update comment [AUTH]',
                'DELETE /comments/{comment}' => 'Delete comment [AUTH]'
            ],
            'reviews' => [
                'GET /protocols/{protocol}/reviews' => 'Get reviews (?author=username&highlight_review=id)',
                'POST /protocols/{protocol}/reviews' => 'Create review [AUTH]',
                'PUT /reviews/{id}' => 'Update review [AUTH]',
                'DELETE /reviews/{id}' => 'Delete review [AUTH]'
            ],
            'voting' => [
                'POST /threads/{thread}/vote' => 'Vote on thread [AUTH]',
                'POST /comments/{comment}/vote' => 'Vote on comment [AUTH]',
                'POST /reviews/{review}/vote' => 'Vote on review [AUTH]'
            ],
            'analytics' => [
                'GET /stats/dashboard' => 'Platform statistics'
            ],
            'tags' => [
                'GET /tags/popular' => 'Popular tags',
                'POST /tags/reindex' => 'Rebuild search index [AUTH]'
            ]
        ],
        'sample_requests' => [
            'auth' => [
                'register' => [
                    'url' => 'POST /api/register',
                    'body' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'password' => 'password123',
                        'password_confirmation' => 'password123'
                    ]
                ],
                'login' => [
                    'url' => 'POST /api/login',
                    'body' => [
                        'email' => 'john@example.com',
                        'password' => 'password123'
                    ]
                ]
            ],
            'profile' => [
                'update' => [
                    'url' => 'PUT /api/profile',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'name' => 'John Smith',
                        'email' => 'johnsmith@example.com',
                        'current_password' => 'oldpassword123',
                        'new_password' => 'newpassword123'
                    ]
                ],
                'reviews_with_highlight' => [
                    'url' => 'GET /api/profile/reviews?highlight_review=123&per_page=5',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'description' => 'Get user reviews with specific review highlighted (always visible even if on different page)'
                ]
            ],
            'protocols' => [
                'create' => [
                    'url' => 'POST /api/protocols',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'title' => 'Advanced Trading Protocol',
                        'content' => 'Detailed protocol description...',
                        'tags' => ['trading', 'advanced', 'strategy']
                    ]
                ],
                'update' => [
                    'url' => 'PUT /api/protocols/{id}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'title' => 'Updated Trading Protocol',
                        'content' => 'Updated protocol description...',
                        'tags' => ['trading', 'updated']
                    ]
                ]
            ],
            'threads' => [
                'create' => [
                    'url' => 'POST /api/threads',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'title' => 'Discussion about Protocol Implementation',
                        'body' => 'I have questions about implementing this protocol...',
                        'protocol_id' => 1
                    ]
                ],
                'update' => [
                    'url' => 'PUT /api/threads/{id}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'title' => 'Updated Discussion Title',
                        'body' => 'Updated discussion content...'
                    ]
                ]
            ],
            'comments' => [
                'create' => [
                    'url' => 'POST /api/threads/{thread}/comments',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my comment on the thread...'
                    ]
                ],
                'reply_to_comment' => [
                    'url' => 'POST /api/comments/{comment}/reply',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my reply to your comment...'
                    ]
                ],
                'reply_to_reply' => [
                    'url' => 'POST /api/replies/{reply}/reply',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my nested reply...'
                    ]
                ],
                'update' => [
                    'url' => 'PUT /api/comments/{comment}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'Updated comment text here. This works for both comments and replies.'
                    ],
                    'description' => 'Update any comment or reply. Only the author can edit their own content.'
                ]
            ],
            'reviews' => [
                'create' => [
                    'url' => 'POST /api/protocols/{protocol}/reviews',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'rating' => 4,
                        'feedback' => 'Great protocol with clear instructions. Works well in practice.'
                    ]
                ],
                'update' => [
                    'url' => 'PUT /api/reviews/{id}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'rating' => 5,
                        'feedback' => 'Updated review feedback. Protocol is excellent!'
                    ]
                ],
            ],
            'voting' => [
                'vote_thread' => [
                    'url' => 'POST /api/threads/{thread}/vote',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'type' => 'upvote'
                    ]
                ],
                'vote_comment' => [
                    'url' => 'POST /api/comments/{comment}/vote',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'type' => 'downvote'
                    ]
                ],
                'vote_review' => [
                    'url' => 'POST /api/reviews/{review}/vote',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'type' => 'upvote'
                    ]
                ]
            ]
        ],
        'features' => [
            'author_filtering' => 'Add ?author=username to most GET endpoints for user-specific content',
            'pagination' => 'Use ?page=1&per_page=15 for paginated results',
            'sorting' => 'Use ?sort=recent|popular|rating for different sort orders',
            'smart_highlighting' => 'Highlighted comments/replies automatically included even if on different pages',
            'deep_linking' => 'Use ?highlight_comment=id or ?highlight_reply=id for smart highlighting',
            'service_architecture' => 'Clean separation with ProfileService handling business logic',
            'profile_content' => 'Get user comments and replies separately with /profile/comments and /profile/replies'
        ]
    ]);
});

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Tag Routes (public)
Route::get('/tags/popular', [TagController::class, 'popularTags']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']); // Get current user

    // Profile Routes (simplified - only unique endpoints)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/profile/statistics', [ProfileController::class, 'statistics']);
    Route::get('/profile/replies', [ProfileController::class, 'replies']); // Only unique endpoint
    Route::get('/profile/comments', [ProfileController::class, 'comments']); // Get user's top-level comments
    Route::get('/profile/reviews', [ProfileController::class, 'reviews']); // Get user's reviews across all protocols

    // Search reindex
    Route::post('/tags/reindex', [TagController::class, 'reindex']);

    // Main Resources (protected)
    Route::apiResource('protocols', ProtocolController::class)->except(['index', 'show']);
    Route::apiResource('threads', ThreadController::class)->except(['index', 'show']);

    // Nested Routes (protected)
    Route::post('/protocols/{protocol}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    Route::post('/threads/{thread}/comments', [CommentController::class, 'store']);
    Route::post('/comments/{comment}/reply', [CommentController::class, 'replyToComment']);
    Route::post('/replies/{reply}/reply', [CommentController::class, 'replyToReply']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/threads/{thread}/vote', [VoteController::class, 'voteOnThread']);
    Route::post('/comments/{comment}/vote', [VoteController::class, 'voteOnComment']);
    Route::post('/reviews/{review}/vote', [VoteController::class, 'voteOnReview']);
});

// Public Routes (read-only, no authentication required)
// These support author filtering for user content
Route::get('/protocols', [ProtocolController::class, 'index']);
Route::get('/protocols/featured', [ProtocolController::class, 'featured']);
Route::get('/protocols/filters', [ProtocolController::class, 'filters']);
Route::get('/protocols/{id}', [ProtocolController::class, 'show']);
Route::get('/protocols/{id}/stats', [ProtocolController::class, 'stats']);
Route::get('/threads', [ThreadController::class, 'index']);
Route::get('/threads/trending', [ThreadController::class, 'trending']);
Route::get('/threads/{id}', [ThreadController::class, 'show']);
Route::get('/threads/{id}/stats', [ThreadController::class, 'stats']);
Route::get('/protocols/{protocol}/threads', [ThreadController::class, 'byProtocol']);
Route::get('/protocols/{protocol}/reviews', [ReviewController::class, 'index']);
Route::get('/threads/{thread}/comments', [CommentController::class, 'index']);
Route::get('/comments/{comment}/replies', [CommentController::class, 'replies']);
Route::get('/replies/{reply}/nested', [CommentController::class, 'nestedReplies']);

// Public stats route
Route::get('/stats/dashboard', [StatsController::class, 'dashboard']);
