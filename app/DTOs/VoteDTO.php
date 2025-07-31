<?php

namespace App\DTOs;

class VoteDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $votable_type,
        public readonly int $votable_id,
        public readonly string $type,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?UserDTO $user = null,
        public readonly ?array $votable = null, // Can be Thread or Comment
    ) {}

    public static function fromModel($vote): self
    {
        // Handle polymorphic relationship
        $votable = null;
        if ($vote->votable) {
            $votable = match ($vote->votable_type) {
                'App\\Models\\Thread' => ThreadDTO::fromModel($vote->votable)->toArray(),
                'App\\Models\\Comment' => CommentDTO::fromModel($vote->votable)->toArray(),
                default => null
            };
        }

        return new self(
            id: $vote->id,
            user_id: $vote->user_id,
            votable_type: $vote->votable_type,
            votable_id: $vote->votable_id,
            type: $vote->type,
            created_at: $vote->created_at?->toISOString() ?? '',
            updated_at: $vote->updated_at?->toISOString() ?? '',
            user: $vote->user ? UserDTO::fromModel($vote->user) : null,
            votable: $votable,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'votable_type' => $this->votable_type,
            'votable_id' => $this->votable_id,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->user?->toArray(),
            'votable' => $this->votable,
        ];
    }
}
