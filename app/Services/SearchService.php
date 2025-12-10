<?php

namespace App\Services;

use App\Models\Protocol;
use App\Models\Thread;
use App\Models\Tag;
use App\Enums\VoteType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Search Service
 *
 * Handles global search functionality across multiple models (Protocols, Threads).
 * Provides full-text search capabilities with highlighting support.
 *
 * Features:
 * - Multi-model search (Protocols, Threads)
 * - Full-text search with relevance scoring
 * - Result highlighting
 * - Pagination support
 * - Filtering by type
 *
 * @package App\Services
 */
class SearchService
{
    /**
     * Perform a global search across all searchable models.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function search(Request $request): array
    {
        try {
            $query = $request->get('q', '');
            $type = $request->get('type'); // 'protocol', 'thread', or null for all
            $perPage = min((int) $request->get('per_page', 10), 50);
            $page = max((int) $request->get('page', 1), 1);

            if (empty(trim($query))) {
                return [
                    'protocols' => [
                        'results' => [],
                        'total' => 0,
                        'page' => $page,
                        'per_page' => $perPage,
                    ],
                    'threads' => [
                        'results' => [],
                        'total' => 0,
                        'page' => $page,
                        'per_page' => $perPage,
                    ],
                ];
            }

            $results = [];

            // Search protocols
            if ($type === null || $type === 'protocol') {
                $protocolResults = $this->searchProtocols($query, $perPage, $page);
                $results['protocols'] = $protocolResults;
            } else {
                $results['protocols'] = [
                    'results' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }

            // Search threads
            if ($type === null || $type === 'thread') {
                $threadResults = $this->searchThreads($query, $perPage, $page);
                $results['threads'] = $threadResults;
            } else {
                $results['threads'] = [
                    'results' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }

            return $results;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t perform the search due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'search' => [$message],
            ]);
        }
    }

    /**
     * Search protocols.
     *
     * @param string $query
     * @param int $perPage
     * @param int $page
     * @return array
     */
    protected function searchProtocols(string $query, int $perPage, int $page): array
    {
        $searchTerms = $this->extractSearchTerms($query);
        $queryLower = strtolower($query);

        $protocols = Protocol::query()
            ->withCount(['reviews', 'threads'])
            ->withAvg('reviews', 'rating')
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('content', 'LIKE', "%{$term}%")
                            ->orWhere('author', 'LIKE', "%{$term}%")
                            ->orWhereHas('tags', function ($tagQ) use ($term) {
                                $tagQ->where('tag', 'LIKE', "%{$term}%");
                            });
                    });
                }
            })
            ->get()
            ->map(function ($protocol) use ($queryLower) {
                $tags = $protocol->tags()->get();
                $protocol->setRelation('tags', $tags);
                $protocol->relevance_score = $this->calculateRelevanceScore($protocol, $queryLower);
                return $protocol;
            })
            ->sortByDesc('relevance_score')
            ->values();

        $total = $protocols->count();
        $protocols = $protocols->slice(($page - 1) * $perPage, $perPage);

        return [
            'results' => $protocols->map(function ($protocol) use ($query) {
                return $this->formatProtocolResult($protocol, $query);
            })->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Search threads.
     *
     * @param string $query
     * @param int $perPage
     * @param int $page
     * @return array
     */
    protected function searchThreads(string $query, int $perPage, int $page): array
    {
        $searchTerms = $this->extractSearchTerms($query);
        $queryLower = strtolower($query);

        $threads = Thread::query()
            ->with(['protocol'])
            ->withCount([
                'votes as upvotes' => function ($q) {
                    $q->where('type', VoteType::UPVOTE->value);
                },
                'votes as downvotes' => function ($q) {
                    $q->where('type', VoteType::DOWNVOTE->value);
                },
            ])
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('title', 'LIKE', "%{$term}%")
                            ->orWhere('body', 'LIKE', "%{$term}%")
                            ->orWhere('author', 'LIKE', "%{$term}%");
                    });
                }
            })
            ->get()
            ->map(function ($thread) use ($queryLower) {
                $thread->relevance_score = $this->calculateRelevanceScore($thread, $queryLower);
                return $thread;
            })
            ->sortByDesc('relevance_score')
            ->values();

        $total = $threads->count();
        $threads = $threads->slice(($page - 1) * $perPage, $perPage);

        return [
            'results' => $threads->map(function ($thread) use ($query) {
                return $this->formatThreadResult($thread, $query);
            })->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Format protocol search result with highlighting.
     *
     * @param Protocol $protocol
     * @param string $query
     * @return array
     */
    protected function formatProtocolResult(Protocol $protocol, string $query): array
    {
        $tagsCollection = $protocol->getRelationValue('tags') ?? collect();
        $tags = $tagsCollection->pluck('tag')->toArray();

        return [
            'id' => (string) $protocol->id,
            'type' => 'protocol',
            'title' => $protocol->title,
            'content' => $this->truncateContent($protocol->content),
            'author' => $protocol->author,
            'tags' => $tags,
            'rating' => round($protocol->getAttribute('reviews_avg_rating') ?? 0, 2),
            'reviews_count' => $protocol->getAttribute('reviews_count') ?? 0,
            'threads_count' => $protocol->getAttribute('threads_count') ?? 0,
            'created_at' => $protocol->created_at->toIso8601String(),
            'highlight' => [
                'title' => $this->highlight($protocol->title, $query),
                'content' => $this->highlight($this->truncateContent($protocol->content), $query),
                'author' => $this->highlight($protocol->author, $query),
            ],
        ];
    }

    /**
     * Format thread search result with highlighting.
     *
     * @param Thread $thread
     * @param string $query
     * @return array
     */
    protected function formatThreadResult(Thread $thread, string $query): array
    {
        return [
            'id' => (string) $thread->id,
            'type' => 'thread',
            'title' => $thread->title,
            'body' => $this->truncateContent($thread->body),
            'author' => $thread->author,
            'protocol_id' => $thread->protocol_id ? (string) $thread->protocol_id : null,
            'protocol' => $thread->relationLoaded('protocol') && $thread->protocol
                ? [
                    'id' => (string) $thread->protocol->id,
                    'title' => $thread->protocol->title,
                ]
                : null,
            'upvotes' => $thread->getAttribute('upvotes') ?? 0,
            'downvotes' => $thread->getAttribute('downvotes') ?? 0,
            'vote_score' => ($thread->getAttribute('upvotes') ?? 0) - ($thread->getAttribute('downvotes') ?? 0),
            'created_at' => $thread->created_at->toIso8601String(),
            'highlight' => [
                'title' => $this->highlight($thread->title, $query),
                'body' => $this->highlight($this->truncateContent($thread->body), $query),
                'author' => $this->highlight($thread->author, $query),
            ],
        ];
    }

    /**
     * Extract search terms from query string.
     *
     * @param string $query
     * @return array
     */
    protected function extractSearchTerms(string $query): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $terms = array_filter(explode(' ', $query), fn($term) => !empty(trim($term)));

        return array_values($terms);
    }

    /**
     * Highlight search terms in text.
     *
     * @param string $text
     * @param string $query
     * @return string
     */
    protected function highlight(string $text, string $query): string
    {
        if (empty($text) || empty($query)) {
            return $text;
        }

        $terms = $this->extractSearchTerms($query);
        $highlighted = $text;

        foreach ($terms as $term) {
            $pattern = '/(' . preg_quote($term, '/') . ')/i';
            $highlighted = preg_replace($pattern, '<mark>$1</mark>', $highlighted);
        }

        return $highlighted;
    }

    /**
     * Truncate content to a reasonable length for search results.
     *
     * @param string|null $content
     * @param int $length
     * @return string
     */
    protected function truncateContent(?string $content, int $length = 200): string
    {
        if (empty($content)) {
            return '';
        }

        if (mb_strlen($content) <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length) . '...';
    }

    /**
     * Get search suggestions based on partial query.
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function suggestions(Request $request): array
    {
        try {
            $query = $request->get('q', '');
            $limit = min((int) $request->get('limit', 5), 10);

            if (empty(trim($query)) || mb_strlen($query) < 2) {
                return [
                    'protocols' => [],
                    'threads' => [],
                    'tags' => [],
                ];
            }

            $results = [];

            $protocolSuggestions = $this->getProtocolSuggestions($query, $limit);
            $results['protocols'] = $protocolSuggestions;

            $threadSuggestions = $this->getThreadSuggestions($query, $limit);
            $results['threads'] = $threadSuggestions;

            $tagSuggestions = $this->getTagSuggestions($query, $limit);
            $results['tags'] = $tagSuggestions;

            return $results;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t get suggestions due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'suggestions' => [$message],
            ]);
        }
    }

    /**
     * Get protocol suggestions.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    protected function getProtocolSuggestions(string $query, int $limit): array
    {
        $queryLower = strtolower($query);
        $searchTerms = $this->extractSearchTerms($query);

        $protocols = Protocol::query()
            ->where(function ($q) use ($queryLower, $searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term, $queryLower) {
                        $subQ->whereRaw('LOWER(title) LIKE ?', ["{$term}%"])
                            ->orWhereRaw('LOWER(title) LIKE ?', ["% {$term}%"])
                            ->orWhereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(author) LIKE ?', ["{$term}%"]);
                    });
                }
            })
            ->get(['id', 'title', 'author', 'created_at'])
            ->map(function ($protocol) use ($queryLower) {
                $protocol->relevance_score = $this->calculateSuggestionScore($protocol, $queryLower);
                return $protocol;
            })
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        return $protocols->map(function ($protocol) use ($query) {
            return [
                'id' => (string) $protocol->id,
                'type' => 'protocol',
                'title' => $protocol->title,
                'author' => $protocol->author,
                'highlight' => [
                    'title' => $this->highlight($protocol->title, $query),
                ],
            ];
        })->toArray();
    }

    /**
     * Get thread suggestions.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    protected function getThreadSuggestions(string $query, int $limit): array
    {
        $queryLower = strtolower($query);
        $searchTerms = $this->extractSearchTerms($query);

        $threads = Thread::query()
            ->with(['protocol:id,title'])
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->whereRaw('LOWER(title) LIKE ?', ["{$term}%"])
                            ->orWhereRaw('LOWER(title) LIKE ?', ["% {$term}%"])
                            ->orWhereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(author) LIKE ?', ["{$term}%"]);
                    });
                }
            })
            ->get(['id', 'title', 'author', 'protocol_id', 'created_at'])
            ->map(function ($thread) use ($queryLower) {
                $thread->relevance_score = $this->calculateSuggestionScore($thread, $queryLower);
                return $thread;
            })
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        return $threads->map(function ($thread) use ($query) {
            return [
                'id' => (string) $thread->id,
                'type' => 'thread',
                'title' => $thread->title,
                'author' => $thread->author,
                'protocol_id' => $thread->protocol_id ? (string) $thread->protocol_id : null,
                'protocol' => $thread->relationLoaded('protocol') && $thread->protocol
                    ? [
                        'id' => (string) $thread->protocol->id,
                        'title' => $thread->protocol->title,
                    ]
                    : null,
                'highlight' => [
                    'title' => $this->highlight($thread->title, $query),
                ],
            ];
        })->toArray();
    }

    /**
     * Get tag suggestions.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    protected function getTagSuggestions(string $query, int $limit): array
    {
        $queryLower = strtolower($query);
        $searchTerms = $this->extractSearchTerms($query);

        $tags = Tag::query()
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->whereRaw('LOWER(tag) LIKE ?', ["{$term}%"])
                            ->orWhereRaw('LOWER(tag) LIKE ?', ["% {$term}%"])
                            ->orWhereRaw('LOWER(tag) LIKE ?', ["%{$term}%"]);
                    });
                }
            })
            ->withCount('protocols')
            ->get(['id', 'tag'])
            ->map(function ($tag) use ($queryLower) {
                $tag->relevance_score = $this->calculateSuggestionScore($tag, $queryLower);
                return $tag;
            })
            ->sortByDesc('protocols_count')
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        return $tags->map(function ($tag) use ($query) {
            return [
                'id' => (string) $tag->id,
                'type' => 'tag',
                'tag' => $tag->tag,
                'count' => $tag->protocols_count ?? 0,
                'highlight' => [
                    'tag' => $this->highlight($tag->tag, $query),
                ],
            ];
        })->toArray();
    }

    /**
     * Calculate suggestion score for autocomplete (prioritizes prefix matches).
     *
     * @param Protocol|Thread|Tag $model
     * @param string $query
     * @return int
     */
    protected function calculateSuggestionScore($model, string $query): int
    {
        $score = 0;
        $queryLower = strtolower($query);

        $titleField = $model instanceof Tag ? 'tag' : 'title';
        if (isset($model->$titleField)) {
            $title = strtolower($model->$titleField);
            if (str_starts_with($title, $queryLower)) {
                $score += 10;
            } elseif (str_contains($title, " {$queryLower}")) {
                $score += 5;
            } elseif (str_contains($title, $queryLower)) {
                $score += 2;
            }
        }

        if (isset($model->author)) {
            $author = strtolower($model->author);
            if (str_starts_with($author, $queryLower)) {
                $score += 3;
            }
        }

        return $score;
    }

    /**
     * Calculate relevance score for a model based on query match.
     *
     * @param Protocol|Thread $model
     * @param string $query
     * @return int
     */
    protected function calculateRelevanceScore($model, string $query): int
    {
        $score = 0;
        $queryLower = strtolower($query);

        if (isset($model->title) && stripos($model->title, $queryLower) !== false) {
            $score += 10;
            if (strtolower($model->title) === $queryLower) {
                $score += 5;
            }
        }

        $contentField = $model instanceof Protocol ? 'content' : 'body';
        if (isset($model->$contentField) && stripos($model->$contentField, $queryLower) !== false) {
            $score += 5;
        }

        if (isset($model->author) && stripos($model->author, $queryLower) !== false) {
            $score += 2;
        }

        if ($model instanceof Protocol) {
            $tagsCollection = $model->getRelationValue('tags') ?? collect();
            if ($tagsCollection->isNotEmpty()) {
                $tags = $tagsCollection->pluck('tag')->implode(' ');
                if (stripos($tags, $queryLower) !== false) {
                    $score += 3;
                }
            }
        }

        return $score;
    }
}

