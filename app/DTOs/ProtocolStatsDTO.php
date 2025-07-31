<?php

namespace App\DTOs;

/**
 * DTO for protocol statistics
 */
class ProtocolStatsDTO
{
    public function __construct(
        public readonly int $protocol_id,
        public readonly int $total_reviews,
        public readonly int $total_threads,
        public readonly float $average_rating,
        public readonly array $rating_distribution,
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            protocol_id: $data['protocol_id'],
            total_reviews: $data['total_reviews'],
            total_threads: $data['total_threads'],
            average_rating: $data['average_rating'],
            rating_distribution: $data['rating_distribution'],
        );
    }

    public function toArray(): array
    {
        return [
            'protocol_id' => $this->protocol_id,
            'total_reviews' => $this->total_reviews,
            'total_threads' => $this->total_threads,
            'average_rating' => $this->average_rating,
            'rating_distribution' => $this->rating_distribution,
        ];
    }
}
