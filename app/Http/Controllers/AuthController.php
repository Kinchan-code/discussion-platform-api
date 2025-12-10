<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentication Controller
 *
 * Handles user authentication operations including registration, login, logout,
 * and profile retrieval. Uses Laravel Sanctum for token-based authentication.
 *
 * All endpoints return standardized JSON responses using the ApiResponseResource DTO.
 * Authentication tokens are Bearer tokens that should be included in the
 * Authorization header for protected routes.
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\AuthService
 * @see App\Http\Resources\ApiResponseResource
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
     * @param  \App\Http\Requests\RegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(\App\Http\Requests\RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request);
        $userResource = new UserResource($user);

        return ApiResponseResource::success(
            message: 'User registered successfully. Please login to continue.',
            data: [
                'user' => $userResource->toArray($request),
                'next_step' => 'login',
            ],
            statusCode: 201
        )->toJsonResponse();
    }

    /**
     * Authenticate user and generate access token.
     *
     * @param  \App\Http\Requests\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(\App\Http\Requests\LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request);
        $userResource = new UserResource($result['user']);

        return ApiResponseResource::success(
            message: 'Logged in successfully.',
            data: [
                'user' => $userResource->toArray($request),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
            ]
        )->toJsonResponse();
    }

    /**
     * Logout the authenticated user and revoke access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponseResource::success(
            message: 'Logged out successfully.',
            data: null
        )->toJsonResponse();
    }

    /**
     * Get the authenticated user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request);
        $userResource = new UserResource($user);

        return ApiResponseResource::success(
            message: 'User profile fetched successfully.',
            data: $userResource->toArray($request)
        )->toJsonResponse();
    }
}
