<?php

namespace App\DTOs;

class ReviewDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $protocol_id,
        public readonly int $rating,
        public readonly ?string $feedback,
        public readonly string $author,
        public readonly int $helpful_count,
        public readonly int $not_helpful_count,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?ProtocolDTO $protocol = null,
    ) {}

    public static function fromModel($review): self
    {
        return new self(
            id: $review->id,
            protocol_id: $review->protocol_id,
            rating: $review->rating,
            feedback: $review->feedback,
            author: $review->author,
            helpful_count: $review->helpful_count,
            not_helpful_count: $review->not_helpful_count,
            created_at: $review->created_at?->toISOString() ?? '',
            updated_at: $review->updated_at?->toISOString() ?? '',
            protocol: $review->protocol ? ProtocolDTO::fromModel($review->protocol) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'protocol_id' => $this->protocol_id,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
            'author' => $this->author,
            'helpful_count' => $this->helpful_count,
            'not_helpful_count' => $this->not_helpful_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'protocol' => $this->protocol?->toArray(),
        ];
    }
}
