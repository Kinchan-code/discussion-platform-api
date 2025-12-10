<?php

namespace App\Services;

use App\Models\User;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\Review;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Statistics Management Service
 *
 * Handles dashboard and user activity statistics for the platform.
 * Provides methods for retrieving overall counts and averages for protocols, users, threads, and reviews.
 *
 * Features:
 * - Dashboard statistics aggregation using Eloquent
 * - User activity statistics (extensible)
 *
 * @package App\Services
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Models\User
 * @see App\Models\Protocol
 * @see App\Models\Thread
 * @see App\Models\Review
 */
class StatService
{
    /**
     * Get dashboard statistics.
     *
     * @return array
     * @throws ValidationException
     */
    public function index(): array
    {
        try {
            return [
                'active_protocols' => Protocol::count(),
                'community_members' => User::count(),
                'discussions' => Thread::count(),
                'avg_rating' => round(Review::avg('rating') ?? 0, 1),
            ];
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'We couldn\'t load statistics due to a server error. Please try again.';

            throw ValidationException::withMessages([
                'statistics' => [$message],
            ]);
        }
    }
}
