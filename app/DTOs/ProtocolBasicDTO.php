<?php

namespace App\DTOs;

/**
 * Simple DTO for basic protocol information (used in filters, dropdowns, etc.)
 */
class ProtocolBasicDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
    ) {}

    public static function fromModel($protocol): self
    {
        return new self(
            id: $protocol->id,
            title: $protocol->title,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}
