<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use App\Http\Resources\ApiResponseResource;
use App\Http\Resources\ErrorResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search Controller
 *
 * Handles global search operations across multiple models (Protocols, Threads).
 * Provides a unified search endpoint for the discussion platform.
 *
 * Features:
 * - Multi-model search
 * - Query parameter support
 * - Pagination
 * - Type filtering
 *
 * @package App\Http\Controllers
 */
class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Perform a global search across all searchable models.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Query Parameters:
     * - q: Search query (required)
     * - type: Filter by type ('protocol' or 'thread', optional)
     * - per_page: Results per page (default: 10, max: 50)
     * - page: Page number (default: 1)
     */
    public function search(Request $request): JsonResponse
    {
        $results = $this->searchService->search($request);

        return ApiResponseResource::success(
            message: 'Search completed successfully.',
            data: $results
        )->toJsonResponse();
    }

    /**
     * Get search suggestions for autocomplete.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Query Parameters:
     * - q: Partial search query (required, min 2 characters)
     * - limit: Maximum suggestions per type (default: 5, max: 10)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $results = $this->searchService->suggestions($request);

        return ApiResponseResource::success(
            message: 'Suggestions retrieved successfully.',
            data: $results
        )->toJsonResponse();
    }
}

