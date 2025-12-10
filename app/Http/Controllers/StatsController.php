<?php

namespace App\Http\Controllers;

use App\Services\StatService;
use App\Http\Resources\ApiResponseResource;
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
     */
    public function dashboard(): JsonResponse
    {
        $stats = $this->statService->index();

        return ApiResponseResource::success(
            message: 'Dashboard stats fetched successfully.',
            data: $stats
        )->toJsonResponse();
    }
}
