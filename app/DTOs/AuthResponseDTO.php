<?php

namespace App\DTOs;

/**
 * DTO for authentication responses (login, register)
 */
class AuthResponseDTO
{
    public function __construct(
        public readonly UserDTO $user,
        public readonly string $token,
        public readonly ?string $token_type = 'Bearer',
        public readonly ?int $expires_in = null,
    ) {}

    public static function fromUserAndToken($user, string $token, ?int $expiresIn = null): self
    {
        return new self(
            user: UserDTO::fromModel($user),
            token: $token,
            token_type: 'Bearer',
            expires_in: $expiresIn,
        );
    }

    public function toArray(): array
    {
        $data = [
            'user' => $this->user->toArray(),
            'token' => $this->token,
            'token_type' => $this->token_type,
        ];

        if ($this->expires_in !== null) {
            $data['expires_in'] = $this->expires_in;
        }

        return $data;
    }
}
