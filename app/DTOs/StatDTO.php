<?php

namespace App\DTOs;

class StatDTO
{
    public function __construct(
        public readonly int $active_protocols,
        public readonly int $community_members,
        public readonly int $discussions,
        public readonly float $avg_rating
    ) {}

    public static function fromModel($review): self
    {
        return new self(
            active_protocols: $review->active_protocols,
            community_members: $review->community_members,
            discussions: $review->discussions,
            avg_rating: $review->avg_rating
        );
    }

    public function toArray(): array
    {
        return [
            'active_protocols' => $this->active_protocols,
            'community_members' => $this->community_members,
            'discussions' => $this->discussions,
            'avg_rating' => $this->avg_rating
        ];
    }
}
