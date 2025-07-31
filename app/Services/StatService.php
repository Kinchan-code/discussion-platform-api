<?php

namespace App\Services;

use App\Models\User;
use App\Models\Protocol;
use App\Models\Thread;
use App\Models\Review;
use App\DTOs\StatDTO;
use Illuminate\Support\Facades\DB;

/**
 * Statistics Managment Service
 *
 * Handles dashboard and user activity statistics for the platform.
 * Provides methods for retrieving overall counts and averages for protocols, users, threads, and reviews.
 *
 * Features:
 * - Dashboard statistics aggregation
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
 * @see App\DTOs\StatDTO
 */
class StatService
{
    /**
     * Get dashboard statistics.
     *
     * @return StatDTO
     */
    public function getDashboardStats(): StatDTO
    {
        // Use a single query to get all counts efficiently
        $stats = DB::select("
            SELECT 
                (SELECT COUNT(*) FROM protocols) as active_protocols,
                (SELECT COUNT(*) FROM users) as community_members,
                (SELECT COUNT(*) FROM threads) as discussions,
                (SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM reviews) as avg_rating
        ")[0];

        return new StatDTO(
            active_protocols: (int) $stats->active_protocols,
            community_members: (int) $stats->community_members,
            discussions: (int) $stats->discussions,
            avg_rating: (float) $stats->avg_rating
        );
    }

    /**
     * Calculate user activity stats (for future use).
     *
     * @return StatDTO
     */
    public function calculateUserActivityStats(): StatDTO
    {
        // This can be expanded for more complex user activity calculations
        return $this->getDashboardStats();
    }
}
