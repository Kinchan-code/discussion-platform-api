<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require base_path('routes/modules/auth.php');
    require base_path('routes/modules/tags.php');
    require base_path('routes/modules/profile.php');
    require base_path('routes/modules/votes.php');
    require base_path('routes/modules/protocols.php');
    require base_path('routes/modules/threads.php');
    require base_path('routes/modules/comments.php');
    require base_path('routes/modules/replies.php');
    require base_path('routes/modules/reviews.php');
    require base_path('routes/modules/stats.php');
    require base_path('routes/modules/search.php');
});

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
                'GET /profile/comments' => 'Get user\'s top-level comments across all threads (?sort=recent|popular|oldest) [AUTH]',
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
                'GET /threads/{thread}/comments' => 'Get comments (?author=username)',
                'POST /threads/{thread}/comments' => 'Create comment [AUTH]',
                'GET /comments/{comment}' => 'Get single comment',
                'PUT /comments/{comment}' => 'Update comment [AUTH]',
                'DELETE /comments/{comment}' => 'Delete comment [AUTH]'
            ],
            'replies' => [
                'GET /comments/{comment}/replies' => 'Get replies for a comment',
                'POST /comments/{comment}/replies' => 'Create reply to comment [AUTH]',
                'GET /replies/{reply}/children' => 'Get nested replies (replies to a reply)',
                'POST /replies/{reply}/children' => 'Create nested reply [AUTH]',
                'GET /replies/{reply}' => 'Get single reply',
                'PUT /replies/{reply}' => 'Update reply [AUTH]',
                'DELETE /replies/{reply}' => 'Delete reply [AUTH]'
            ],
            'reviews' => [
                'GET /protocols/{protocol}/reviews' => 'Get reviews (?author=username&highlight_review=id)',
                'POST /protocols/{protocol}/reviews' => 'Create review [AUTH]',
                'PUT /reviews/{id}' => 'Update review [AUTH]',
                'DELETE /reviews/{id}' => 'Delete review [AUTH]'
            ],
            'voting' => [
                'POST /votes' => 'Vote on any votable (thread, comment, reply, review) [AUTH]'
            ],
            'analytics' => [
                'GET /stats/dashboard' => 'Platform statistics'
            ],
            'search' => [
                'GET /search' => 'Global search (?q=query&type=protocol|thread&per_page=10&page=1)',
                'GET /search/suggestions' => 'Search suggestions for autocomplete (?q=query&limit=5)'
            ],
            'tags' => [
                'GET /tags/popular' => 'Popular tags'
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
                    'url' => 'POST /api/v1/threads/{thread}/comments',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my comment on the thread...'
                    ]
                ],
                'update' => [
                    'url' => 'PUT /api/v1/comments/{comment}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'Updated comment text here. Only the author can edit their own comment.'
                    ]
                ]
            ],
            'replies' => [
                'create_reply_to_comment' => [
                    'url' => 'POST /api/v1/comments/{comment}/replies',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my reply to your comment...'
                    ]
                ],
                'create_nested_reply' => [
                    'url' => 'POST /api/v1/replies/{reply}/children',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'This is my nested reply to your reply...'
                    ]
                ],
                'update' => [
                    'url' => 'PATCH /api/v1/replies/{reply}',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'body' => 'Updated reply text here. Only the author can edit their own reply.'
                    ]
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
                    'url' => 'POST /api/v1/votes',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'votable_id' => 1,
                        'votable_type' => 'thread',
                        'vote_type' => 'upvote'
                    ]
                ],
                'vote_comment' => [
                    'url' => 'POST /api/v1/votes',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'votable_id' => 1,
                        'votable_type' => 'comment',
                        'vote_type' => 'downvote'
                    ]
                ],
                'vote_review' => [
                    'url' => 'POST /api/v1/votes',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'votable_id' => 1,
                        'votable_type' => 'review',
                        'vote_type' => 'upvote'
                    ]
                ],
                'vote_reply' => [
                    'url' => 'POST /api/v1/votes',
                    'headers' => ['Authorization' => 'Bearer {token}'],
                    'body' => [
                        'votable_id' => 1,
                        'votable_type' => 'reply',
                        'vote_type' => 'upvote'
                    ]
                ]
            ],
            'search' => [
                'global_search' => [
                    'url' => 'GET /api/v1/search?q=protocol&type=protocol&per_page=10&page=1',
                    'description' => 'Search across all content types. Query params: q (required), type (optional: protocol|thread), per_page (default: 10, max: 50), page (default: 1)'
                ],
                'search_protocols_only' => [
                    'url' => 'GET /api/v1/search?q=blockchain&type=protocol',
                    'description' => 'Search only protocols'
                ],
                'search_threads_only' => [
                    'url' => 'GET /api/v1/search?q=discussion&type=thread',
                    'description' => 'Search only threads'
                ],
                'search_suggestions' => [
                    'url' => 'GET /api/v1/search/suggestions?q=prot&limit=5',
                    'description' => 'Get autocomplete suggestions. Query params: q (required, min 2 chars), limit (default: 5, max: 10)'
                ]
            ]
        ],
        'features' => [
            'author_filtering' => 'Add ?author=username to most GET endpoints for user-specific content',
            'pagination' => 'Use ?page=1&per_page=15 for paginated results',
            'sorting' => 'Use ?sort=recent|popular|rating for different sort orders',
            'service_architecture' => 'Clean separation with ProfileService handling business logic',
            'profile_content' => 'Get user comments separately with /profile/comments'
        ]
    ]);
});


