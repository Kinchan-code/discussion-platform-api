<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\UserDTO;
use App\DTOs\AuthResponseDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication Service
 *
 * Handles user registration, login, logout, and retrieval of the current authenticated user.
 * Provides authentication logic and user token management for the API.
 *
 * Features:
 * - User registration with auto email verification
 * - User login and token generation
 * - User logout and token revocation
 * - Retrieve current authenticated user
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\User
 * @see App\DTOs\UserDTO
 * @see App\DTOs\AuthResponseDTO
 */
class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return UserDTO
     */
    public function register(array $data): UserDTO
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(), // Auto-verify for traditional registration
        ]);

        return UserDTO::fromModel($user);
    }

    /**
     * Login a user and return authentication response.
     *
     * @param array $credentials
     * @return AuthResponseDTO
     * @throws \Exception When credentials are invalid
     */
    public function login(array $credentials): AuthResponseDTO
    {
        if (!Auth::attempt($credentials)) {
            throw new \Exception('Invalid credentials.');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();
        $token = $user->createToken('auth-token')->plainTextToken;

        return AuthResponseDTO::fromUserAndToken($user, $token);
    }

    /**
     * Logout the current user.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    /**
     * Get the current authenticated user.
     *
     * @param User $user
     * @return UserDTO
     */
    public function getCurrentUser(User $user): UserDTO
    {
        return UserDTO::fromModel($user);
    }
}
