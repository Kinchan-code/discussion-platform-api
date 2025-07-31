<?php

namespace App\DTOs;

class ProtocolDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $title = null,
        public readonly ?string $content = null,
        public readonly array $tags = [],
        public readonly ?string $author = null,
        public readonly ?int $reviews_count = null,
        public readonly ?int $threads_count = null,
        public readonly ?float $reviews_avg_rating = null,
        public readonly string $created_at = '',
        public readonly string $updated_at = '',
    ) {}

    public static function fromModel($protocol): self
    {
        return new self(
            id: $protocol->id,
            title: $protocol->title ?? '',
            content: $protocol->content ?? '',
            tags: is_array($protocol->tags) ? collect($protocol->tags)->map(function ($tag, $index) {
                return [
                    'id' => $index + 1,
                    'tag' => $tag,
                ];
            })->values()->toArray() : [],
            author: $protocol->author ?? '',
            reviews_count: $protocol->reviews_count ?? null,
            threads_count: $protocol->threads_count ?? null,
            reviews_avg_rating: $protocol->reviews_avg_rating ? round($protocol->reviews_avg_rating, 2) : null,
            created_at: $protocol->created_at?->toISOString() ?? '',
            updated_at: $protocol->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'tags' => $this->tags,
            'author' => $this->author,
            'reviews_count' => $this->reviews_count,
            'threads_count' => $this->threads_count,
            'reviews_avg_rating' => $this->reviews_avg_rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
