<?php

namespace App\DTOs;

class NestedReplyDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $body,
        public readonly string $author,
        public readonly string $replying_to,
        public readonly int $upvotes,
        public readonly int $downvotes,
        public readonly int $vote_score,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function fromModel($nestedReply, string $replyingTo): self
    {
        return new self(
            id: $nestedReply->id,
            body: $nestedReply->body,
            author: $nestedReply->author,
            replying_to: $replyingTo,
            upvotes: $nestedReply->upvotes ?? 0,
            downvotes: $nestedReply->downvotes ?? 0,
            vote_score: $nestedReply->vote_score ?? 0,
            created_at: $nestedReply->created_at?->toISOString() ?? $nestedReply->created_at,
            updated_at: $nestedReply->updated_at?->toISOString() ?? $nestedReply->updated_at,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            body: $data['body'],
            author: $data['author'],
            replying_to: $data['replying_to'],
            upvotes: $data['upvotes'],
            downvotes: $data['downvotes'],
            vote_score: $data['vote_score'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->author,
            'replying_to' => $this->replying_to,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'vote_score' => $this->vote_score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
