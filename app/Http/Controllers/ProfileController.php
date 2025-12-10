<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use App\Http\Resources\ApiResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
 * @see App\Http\Resources\ProfileResource
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
        $user = $this->profileService->show($request);
        $profileResource = new \App\Http\Resources\ProfileResource($user);

        return ApiResponseResource::success(
            message: 'Profile fetched successfully.',
            data: $profileResource->toArray($request)
        )->toJsonResponse();
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
    public function update(\App\Http\Requests\ProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update($request);
        $profileResource = new \App\Http\Resources\ProfileResource($user);

        return ApiResponseResource::success(
            message: 'Profile updated successfully.',
            data: $profileResource->toArray($request)
        )->toJsonResponse();
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
        $statistics = $this->profileService->statistics($request);

        return ApiResponseResource::success(
            message: 'User statistics fetched successfully.',
            data: $statistics
        )->toJsonResponse();
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
        $replies = $this->profileService->indexReplies($request);

        $paginationDto = [
            'current_page' => $replies->currentPage(),
            'last_page' => $replies->lastPage(),
            'per_page' => $replies->perPage(),
            'total' => $replies->total(),
        ];

        return ApiResponseResource::successWithPagination(
            message: 'User replies fetched successfully.',
            data: $replies->items(),
            pagination: $paginationDto,
        )->toJsonResponse();
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
        $comments = $this->profileService->indexComments($request);

        $pagination = [
            'current_page' => $comments->currentPage(),
            'last_page' => $comments->lastPage(),
            'per_page' => $comments->perPage(),
            'total' => $comments->total(),
        ];

        return ApiResponseResource::successWithPagination(
            message: 'User comments fetched successfully.',
            data: $comments->items(),
            pagination: $pagination
        )->toJsonResponse();
    }

    /**
     * Retrieve all reviews created by the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching reviews fails due to server error
     * @authenticated
     */
    public function reviews(Request $request): JsonResponse
    {
        $reviews = $this->profileService->indexReviews($request);

        $pagination = [
            'current_page' => $reviews->currentPage(),
            'last_page' => $reviews->lastPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
        ];

        return ApiResponseResource::successWithPagination(
            message: 'User reviews fetched successfully.',
            data: $reviews->items(),
            pagination: $pagination
        )->toJsonResponse();
    }
}
