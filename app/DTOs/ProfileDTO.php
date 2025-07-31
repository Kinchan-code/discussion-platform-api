<?php

namespace App\DTOs;

class ProfileDTO
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $email_verified_at;
    public string $created_at;
    public string $updated_at;

    public function __construct($user)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->email_verified_at = $user->email_verified_at?->toISOString();
        $this->created_at = $user->created_at?->toISOString() ?? '';
        $this->updated_at = $user->updated_at?->toISOString() ?? '';
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
        ];
    }
}
