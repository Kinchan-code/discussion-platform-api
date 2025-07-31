<?php

namespace App\DTOs;

class CommentDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $thread_id,
        public readonly ?int $parent_id,
        public readonly string $body,
        public readonly string $author,
        public readonly int $upvotes,
        public readonly int $downvotes,
        public readonly int $vote_score,
        public readonly int $replies_count,
        public readonly array $replies,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?ThreadDTO $thread = null,
        public readonly ?CommentDTO $parent = null,
        public readonly bool $is_highlighted = false,
    ) {}

    public static function fromModel($comment, array $replies = [], bool $isHighlighted = false): self
    {
        return new self(
            id: $comment->id,
            thread_id: $comment->thread_id,
            parent_id: $comment->parent_id,
            body: $comment->body,
            author: $comment->author,
            upvotes: $comment->upvotes ?? 0,
            downvotes: $comment->downvotes ?? 0,
            vote_score: $comment->vote_score ?? 0,
            replies_count: $comment->replies_count ?? 0,
            replies: $replies,
            created_at: $comment->created_at?->toISOString() ?? $comment->created_at,
            updated_at: $comment->updated_at?->toISOString() ?? $comment->updated_at,
            thread: $comment->thread ? ThreadDTO::fromModel($comment->thread) : null,
            parent: $comment->parent ? self::fromModel($comment->parent) : null,
            is_highlighted: $isHighlighted,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            thread_id: $data['thread_id'],
            parent_id: $data['parent_id'],
            body: $data['body'],
            author: $data['author'],
            upvotes: $data['upvotes'],
            downvotes: $data['downvotes'],
            vote_score: $data['vote_score'],
            replies_count: $data['replies_count'],
            replies: $data['replies'] ?? [],
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
            thread: null, // Skip thread for fromArray to avoid complexity
            parent: isset($data['parent']) ? self::fromArray($data['parent']) : null,
            is_highlighted: $data['is_highlighted'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'author' => $this->author,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'vote_score' => $this->vote_score,
            'replies_count' => $this->replies_count,
            'replies' => $this->replies,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'thread' => $this->thread?->toArray(),
            'parent' => $this->parent?->toArray(),
            'is_highlighted' => $this->is_highlighted,
        ];
    }
}
