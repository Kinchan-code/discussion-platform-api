<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use App\DTOs\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * User Profile Management Controller
 *
 * Handles user profile operations including retrieving profile information,
 * updating profile details, managing user activity history, and providing
 * comprehensive user statistics and engagement metrics.
 *
 * Features:
 * - Complete user profile management
 * - Profile statistics and activity tracking
 * - User contribution history (protocols, threads, comments)
 * - Review and rating history
 * - User engagement analytics
 * - Profile privacy and visibility controls
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ProfileService
 * @see App\Models\User
 * @see App\DTOs\ProfileDTO
 */
class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Retrieve the authenticated user's complete profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching profile fails due to server error
     * @authenticated
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $profileDTO = $this->profileService->getUserProfile($user);

            return ApiResponse::success(
                message: 'Profile fetched successfully.',
                data: $profileDTO->toArray()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch profile',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Update the authenticated user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When profile update fails due to server error
     * @authenticated
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'current_password' => ['sometimes', 'required', 'string'],
                'new_password' => ['sometimes', 'required', 'string', 'min:8'],
                'new_password_confirmation' => ['sometimes', 'required_with:new_password', 'same:new_password'],
            ]);

            $profileDTO = $this->profileService->updateUserProfile($user, $request->all());

            return ApiResponse::success(
                message: 'Profile updated successfully.',
                data: $profileDTO->toArray()
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to update profile',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve comprehensive activity statistics for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching statistics fails due to server error
     * @authenticated
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $statisticsDTO = $this->profileService->getUserStatistics($user);


            return ApiResponse::success(
                message: 'User statistics fetched successfully.',
                data: $statisticsDTO->toArray()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch user statistics',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve all replies created by the authenticated user with deep linking support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching replies fails due to server error
     * @authenticated
     */
    public function replies(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $perPage = $request->get('per_page', 10);
            $sort = $request->get('sort', 'recent');

            $replies = $this->profileService->getUserReplies($user, $perPage, $sort);

            $paginationDto = [
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
            ];

            return ApiResponse::successWithPagination(
                message: 'User replies fetched successfully.',
                data: $replies->items(),
                pagination: $paginationDto,

            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch user replies',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve all top-level comments created by the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching comments fails due to server error
     * @authenticated
     */
    public function comments(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $perPage = min($request->get('per_page', 10), 50);
            $sort = $request->get('sort', 'recent');

            $comments = $this->profileService->getUserComments($user, $perPage, $sort);

            $pagination = [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ];

            return ApiResponse::successWithPagination(
                message: 'User comments fetched successfully.',
                data: $comments->items(),
                pagination: $pagination
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch user comments',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve all reviews created by the authenticated user with smart highlighting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching reviews fails due to server error
     * @authenticated
     */
    public function reviews(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error(
                    message: 'User not authenticated.',
                    statusCode: 401
                )->toJsonResponse();
            }

            $reviews = $this->profileService->getUserReviews($user, $request);

            $pagination = [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ];

            $message = 'User reviews fetched successfully.';

            // Add highlighting info to response if applicable
            if ($request->has('highlight_review')) {
                $highlightedReview = collect($reviews->items())->firstWhere('is_highlighted', true);
                if ($highlightedReview) {
                    $message .= ' Highlighted review found and included.';
                }
            }

            return ApiResponse::successWithPagination(
                message: $message,
                data: $reviews->items(),
                pagination: $pagination
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch user reviews',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
