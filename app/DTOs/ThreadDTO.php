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
        public readonly ?array $protocol = null,
    ) {}

    public static function fromModel($thread): self
    {
        // OPTIMIZED FOR UI REQUIREMENTS - Protocol ID, protocol name, vote score, comments count
        $protocol = null;
        if (isset($thread->protocol_title)) {
            $protocol = [
                'id' => $thread->protocol_id_data ?? $thread->protocol_id,
                'title' => $thread->protocol_title,
                'author' => null, // Not needed for UI
            ];
        }

        $voteScore = $thread->vote_score ?? 0;

        return new self(
            id: $thread->id,
            protocol_id: $thread->protocol_id,
            title: $thread->title,
            body: $thread->body,
            author: $thread->author,
            votes_count: abs($voteScore), // Total engagement
            upvotes: max($voteScore, 0), // Positive score as upvotes
            downvotes: abs(min($voteScore, 0)), // Negative score as downvotes
            vote_score: $voteScore,
            comments_count: $thread->comments_count ?? 0,
            created_at: $thread->created_at?->toISOString() ?? '',
            updated_at: $thread->updated_at?->toISOString() ?? '',
            protocol: $protocol,
        );
    }

    /**
     * Create protocol data - minimal for listings, full for individual views
     * REVERTED TO PREVIOUS APPROACH (JOIN approach caused pagination issues)
     */
    private static function createProtocolData($protocol): array
    {
        // PREVIOUS APPROACH: Check if this is a full protocol (has content) or minimal (just id, title, author)
        if (isset($protocol->content)) {
            // Full protocol data for individual thread view
            return ProtocolDTO::fromModel($protocol)->toArray();
        } else {
            // Minimal protocol data for thread listings
            return [
                'id' => $protocol->id,
                'title' => $protocol->title,
                'author' => $protocol->author,
            ];
        }
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
            'protocol' => $this->protocol,
        ];
    }
}
