<?php

namespace App\Services;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
 * @see App\Http\Resources\UserResource
 * @see App\Http\Resources\AuthResponseResource
 */
class AuthService
{
    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return User
     * @throws ValidationException
     */
    public function register(RegisterRequest $request): User
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();

                return User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t register the user due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'registration' => [$message],
            ]);
        }
    }

    /**
     * Login a user and return user with token.
     *
     * @param LoginRequest $request
     * @return array
     * @throws ValidationException
     */
    public function login(LoginRequest $request): array
    {
        try {
            $credentials = $request->validated();

            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t log you in due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'login' => [$message],
            ]);
        }
    }

    /**
     * Logout the current user.
     *
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    public function logout(Request $request): void
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t log you out due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'logout' => [$message],
            ]);
        }
    }

    /**
     * Get the current authenticated user.
     *
     * @param Request $request
     * @return User
     * @throws ValidationException
     */
    public function me(Request $request): User
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new \Exception('User not authenticated.');
            }

            return $user;
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load the user profile due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'user' => [$message],
            ]);
        }
    }
}
