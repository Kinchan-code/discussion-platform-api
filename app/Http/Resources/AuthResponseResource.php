<?php

namespace App\Http\Resources;

/**
 * Resource for authentication responses (login, register)
 */
class AuthResponseResource
{
    public function __construct(
        public readonly UserResource $user,
        public readonly string $token,
        public readonly ?string $token_type = 'Bearer',
        public readonly ?int $expires_in = null,
    ) {}

    public static function fromUserAndToken($user, string $token, ?int $expiresIn = null): self
    {
        return new self(
            user: new UserResource($user),
            token: $token,
            token_type: 'Bearer',
            expires_in: $expiresIn,
        );
    }

    public function toArray(): array
    {
        $data = [
            'user' => $this->user->toArray(request()),
            'token' => $this->token,
            'token_type' => $this->token_type,
        ];

        if ($this->expires_in !== null) {
            $data['expires_in'] = $this->expires_in;
        }

        return $data;
    }
}
