<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $email_verified_at,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?array $activity_stats = null,
    ) {}

    public static function fromModel($user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at ?
                (is_string($user->email_verified_at) ? $user->email_verified_at : $user->email_verified_at->toISOString())
                : null,
            created_at: is_string($user->created_at) ? $user->created_at : $user->created_at->toISOString(),
            updated_at: is_string($user->updated_at) ? $user->updated_at : $user->updated_at->toISOString(),
            activity_stats: $user->activity_stats ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activity_stats' => $this->activity_stats,
        ];
    }
}
