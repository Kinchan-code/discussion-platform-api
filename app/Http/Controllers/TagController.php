<?php

namespace App\Http\Controllers;

use App\Services\TagService;
use App\DTOs\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tag Management Controller
 *
 * Handles tag-related operations including retrieving popular tags and
 * reindexing searchable models for the platform's search engine.
 *
 * Features:
 * - Fetch popular tags for filtering and discovery
 * - Reindex all searchable models to Typesense for search accuracy
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\TagService
 */
class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Get popular tags.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching tags fails due to server error
     */
    public function popularTags(): JsonResponse
    {
        try {
            $tags = $this->tagService->getPopularTags();

            // Convert DTOs to arrays
            $tagsArray = array_map(function ($tagDto) {
                return $tagDto->toArray();
            }, $tags);

            return ApiResponse::success(
                data: $tagsArray,
                message: 'Popular tags fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to get popular tags',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Reindex all searchable models to Typesense.
     * Restricted to admin users only for security.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When rebuilding search index fails
     */
    public function reindex(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$request->user() || !$request->user()->is_admin) {
                return ApiResponse::error(
                    message: 'Access denied. Only administrators can reindex search data.',
                    statusCode: 403
                )->toJsonResponse();
            }

            $result = $this->tagService->reindexSearchModels();

            return ApiResponse::success(
                data: $result,
                message: 'Search index rebuilt successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to rebuild search index',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }
}
