<?php

namespace App\DTOs;

class ThreadDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $protocol_id,
        public readonly string $title,
        public readonly string $body,
        public readonly string $author,
        public readonly int $votes_count,
        public readonly int $upvotes,
        public readonly int $downvotes,
        public readonly int $vote_score,
        public readonly ?int $comments_count = null,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?ProtocolDTO $protocol = null,
    ) {}

    public static function fromModel($thread): self
    {
        return new self(
            id: $thread->id,
            protocol_id: $thread->protocol_id,
            title: $thread->title,
            body: $thread->body,
            author: $thread->author,
            votes_count: $thread->votes_count ?? 0,
            upvotes: $thread->upvotes ?? 0,
            downvotes: $thread->downvotes ?? 0,
            vote_score: ($thread->upvotes ?? 0) - ($thread->downvotes ?? 0),
            comments_count: $thread->comments_count ?? 0,
            created_at: $thread->created_at?->toISOString() ?? '',
            updated_at: $thread->updated_at?->toISOString() ?? '',
            protocol: $thread->protocol ? ProtocolDTO::fromModel($thread->protocol) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'protocol_id' => $this->protocol_id,
            'title' => $this->title,
            'body' => $this->body,
            'author' => $this->author,
            'votes_count' => $this->votes_count,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'vote_score' => $this->vote_score,
            'comments_count' => $this->comments_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'protocol' => $this->protocol?->toArray(),
        ];
    }
}
