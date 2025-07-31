<?php

namespace App\DTOs;

class PaginationDTO
{
    public function __construct(
        public readonly int $current_page,
        public readonly int $last_page,
        public readonly int $per_page,
        public readonly int $total,
        public readonly ?int $from,
        public readonly ?int $to,
        public readonly bool $has_more_pages,
    ) {}

    public static function fromPaginator($paginator): self
    {
        return new self(
            current_page: $paginator->currentPage(),
            last_page: $paginator->lastPage(),
            per_page: $paginator->perPage(),
            total: $paginator->total(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
            has_more_pages: $paginator->hasMorePages(),
        );
    }

    public function toArray(): array
    {
        return [
            'current_page' => $this->current_page,
            'last_page' => $this->last_page,
            'per_page' => $this->per_page,
            'total' => $this->total,
            'from' => $this->from,
            'to' => $this->to,
            'has_more_pages' => $this->has_more_pages,
        ];
    }
}
