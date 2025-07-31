<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\DTOs\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 *
 * Handles user authentication operations including registration, login, logout,
 * and profile retrieval. Uses Laravel Sanctum for token-based authentication.
 *
 * All endpoints return standardized JSON responses using the ApiResponse DTO.
 * Authentication tokens are Bearer tokens that should be included in the
 * Authorization header for protected routes.
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\AuthService
 * @see App\DTOs\ApiResponse
 * @see Laravel\Sanctum
 */
class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When registration fails due to server error
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $userDto = $this->authService->register($validated);

            return ApiResponse::success(
                data: [
                    'user' => $userDto->toArray(),
                    'next_step' => 'login',
                ],
                message: 'User registered successfully. Please login to continue.',
                statusCode: 201
            )->toJsonResponse();
        } catch (ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Registration failed',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Authenticate user and generate access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When authentication fails or server error occurs
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            $authResponseDto = $this->authService->login($validated);

            return ApiResponse::success(
                data: $authResponseDto->toArray(),
                message: 'Logged in successfully.'
            )->toJsonResponse();
        } catch (ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            // Check if this is an authentication error
            if (str_contains($e->getMessage(), 'Invalid credentials')) {
                return ApiResponse::error(
                    message: 'Authentication failed',
                    statusCode: 401,
                    data: $e->getMessage()
                )->toJsonResponse();
            }

            return ApiResponse::error(
                message: 'Login failed',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Logout the authenticated user and revoke access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When logout fails due to server error
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return ApiResponse::success(
                data: null,
                message: 'Logged out successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Logout failed',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Get the authenticated user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching user profile fails due to server error
     * @authenticated
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $userDto = $this->authService->getCurrentUser($request->user());

            return ApiResponse::success(
                data: $userDto->toArray(),
                message: 'User profile fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch user profile',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
