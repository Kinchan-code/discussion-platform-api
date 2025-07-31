<?php

namespace App\DTOs;

class ReplyDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $thread_id,
        public readonly int $parent_id,
        public readonly string $body,
        public readonly string $author,
        public readonly ?string $replying_to,
        public readonly int $upvotes,
        public readonly int $downvotes,
        public readonly int $vote_score,
        public readonly array $nested_replies,
        public readonly int $nested_replies_count,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function fromModel($reply, ?string $replyingTo = null, array $nestedReplies = [], int $nestedCount = 0): self
    {
        return new self(
            id: $reply->id,
            thread_id: $reply->thread_id,
            parent_id: $reply->parent_id,
            body: $reply->body,
            author: $reply->author,
            replying_to: $replyingTo,
            upvotes: $reply->upvotes ?? 0,
            downvotes: $reply->downvotes ?? 0,
            vote_score: $reply->vote_score ?? 0,
            nested_replies: $nestedReplies,
            nested_replies_count: $nestedCount,
            created_at: $reply->created_at?->toISOString() ?? $reply->created_at,
            updated_at: $reply->updated_at?->toISOString() ?? $reply->updated_at,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            thread_id: $data['thread_id'] ?? 0,
            parent_id: $data['parent_id'] ?? 0,
            body: $data['body'],
            author: $data['author'],
            replying_to: $data['replying_to'] ?? null,
            upvotes: $data['upvotes'],
            downvotes: $data['downvotes'],
            vote_score: $data['vote_score'],
            nested_replies: $data['nested_replies'] ?? [],
            nested_replies_count: $data['nested_replies_count'] ?? 0,
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
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
            'replying_to' => $this->replying_to,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'vote_score' => $this->vote_score,
            'nested_replies' => $this->nested_replies,
            'nested_replies_count' => $this->nested_replies_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
