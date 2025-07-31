<?php

namespace App\Http\Controllers;

use App\Services\StatService;
use App\DTOs\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Statistics and Analytics Controller
 *
 * Provides comprehensive statistics and analytics for the discussion platform
 * including dashboard metrics, user engagement data, content statistics,
 * and platform-wide analytics for administrators and users.
 *
 * Features:
 * - Dashboard statistics and overview metrics
 * - User engagement and activity analytics
 * - Content statistics (protocols, threads, comments, reviews)
 * - Platform growth and performance metrics
 * - Real-time activity tracking
 * - Comparative analytics and trends
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\StatService
 * @see App\DTOs\StatsDTO
 */
class StatsController extends Controller
{
    protected StatService $statService;

    public function __construct(StatService $statService)
    {
        $this->statService = $statService;
    }

    /**
     * Retrieve comprehensive dashboard statistics and metrics.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching statistics fails due to server error
     */
    public function dashboard(): JsonResponse
    {
        try {
            $statsDto = $this->statService->getDashboardStats();

            return ApiResponse::success(
                data: $statsDto->toArray(),
                message: 'Dashboard stats fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch dashboard stats',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
