<?php

namespace App\Http\Controllers;

use App\Services\TagService;
use App\Http\Resources\ApiResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tag Management Controller
 *
 * Handles tag-related operations including retrieving popular tags.
 *
 * Features:
 * - Fetch popular tags for filtering and discovery
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
     */
    public function popularTags(): JsonResponse
    {
        $tags = $this->tagService->index();

        return ApiResponseResource::success(
            message: 'Popular tags fetched successfully.',
            data: $tags
        )->toJsonResponse();
    }

}
