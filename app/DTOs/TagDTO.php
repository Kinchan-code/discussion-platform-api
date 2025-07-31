<?php

namespace App\DTOs;

class TagDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $tag,
        public readonly int $count,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            tag: $model->tag,
            count: $model->count,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            tag: $data['tag'],
            count: $data['count'],
        );
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "tag" => $this->tag,
            "count" => $this->count,
        ];
    }
}
