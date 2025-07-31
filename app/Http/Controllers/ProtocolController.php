<?php

namespace App\Http\Controllers;

use App\Models\Protocol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use App\Services\ProtocolService;
use App\DTOs\ApiResponse;
use App\DTOs\ProtocolDTO;
use App\DTOs\PaginationDTO;
use App\DTOs\ProtocolStatsDTO;

/**
 * Protocol Management Controller
 *
 * Handles all protocol-related operations including creating, reading, updating,
 * and deleting research/discussion protocols. Protocols serve as the main
 * organizational structure for discussion threads and include metadata,
 * tags, reviews, and engagement statistics.
 *
 * Features:
 * - Complete CRUD operations for protocols
 * - Review and rating system integration
 * - Tag-based categorization and filtering
 * - Author-based filtering and search capabilities
 * - Protocol statistics and engagement metrics
 * - Public and private protocol visibility controls
 *
 * @package App\Http\Controllers
 * @author Christian Bangay
 * @version 1.0.0
 * @since 2025-07-31
 *
 * @see App\Services\ProtocolService
 * @see App\Models\Protocol
 * @see App\Models\Review
 * @see App\DTOs\ProtocolDTO
 */
class ProtocolController extends Controller

{

    /**
     * Format rating value to standardized decimal precision.
     *
     * @param  float|null  $rating  Raw rating value from database
     * @return float Formatted rating rounded to 2 decimal places
     * @internal
     */
    private function formatRating($rating)
    {
        return round($rating, 2);
    }

    /**
     * Retrieve a paginated list of all research protocols.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching protocols fails due to server error
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $service = new ProtocolService();

            // Add author filter support
            $filters = $request->all();

            // Handle 'current_user' special case for authenticated requests
            if ($request->has('author') && $request->get('author') === 'current_user') {
                if (!$request->user()) {
                    return ApiResponse::error(
                        message: 'Authentication required for current_user filter',
                        statusCode: 401
                    )->toJsonResponse();
                }
                $filters['author'] = $request->user()->name;
            }

            $protocols = $service->listProtocols($filters);

            // Transform protocols to DTOs
            $protocolDTOs = $protocols->getCollection()->map(function ($protocol) {
                return ProtocolDTO::fromModel($protocol)->toArray();
            })->toArray();

            // Create pagination DTO
            $paginationDTO = PaginationDTO::fromPaginator($protocols);

            // Create typed response: ApiResponse<ProtocolDTO[]>
            return ApiResponse::successWithPagination(
                data: $protocolDTOs,
                pagination: $paginationDTO->toArray(),
                message: 'Protocols fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch protocols: ' . $e->getMessage(),
                statusCode: 500
            )->toJsonResponse();
        }
    }


    /**
     * Retrieve protocol data for filtering and selection purposes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching protocols fails due to server error
     */
    public function filters(Request $request): JsonResponse
    {
        try {
            $protocols = Protocol::select('id', 'title')->get();

            // Transform protocols to DTOs
            return ApiResponse::success(
                data: $protocols,
                message: 'Protocols fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch protocols: ' . $e->getMessage(),
                statusCode: 500
            )->toJsonResponse();
        }
    }


    /**
     * Retrieve detailed information for a specific protocol.
     *
     * @param  int  $id  The ID of the protocol to retrieve
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Exception When fetching protocol fails due to server error
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = new ProtocolService();
            $protocol = $service->getProtocol($id);

            // Create typed response: ApiResponse<ProtocolDTO>
            return ApiResponse::success(
                data: ProtocolDTO::fromModel($protocol)->toArray(),
                message: 'Protocol fetched successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch protocol: ' . $e->getMessage(),
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Create a new research protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When protocol creation fails due to server error
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string'],
                'tags' => ['nullable', 'array'],
            ]);

            if (!Auth::user()) {
                return ApiResponse::error(
                    message: 'Unauthorized',
                    statusCode: 401
                )->toJsonResponse();
            }

            $service = new ProtocolService();
            $protocol = $service->createProtocol($request->only(['title', 'content', 'tags']), Auth::user());



            return ApiResponse::success(
                data: ProtocolDTO::fromModel($protocol)->toArray(),
                message: 'Protocol created successfully',
                statusCode: 201
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to create protocol',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Update an existing protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Protocol  $protocol  The protocol instance to update
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When validation fails
     * @throws \Exception When protocol update fails due to server error
     * @authenticated
     */
    public function update(Request $request, Protocol $protocol): JsonResponse
    {
        try {
            // Check if the authenticated user is the author
            if ($protocol->author !== $request->user()->name) {
                return response()->json([
                    'status_code' => 403,
                    'error' => 'Unauthorized',
                    'message' => 'You can only update protocols that you created.',
                ], 403);
            }

            $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'content' => ['sometimes', 'required', 'string'],
                'tags' => ['nullable', 'array'],
            ]);

            $protocol->fill($request->only(['title', 'content', 'tags']));
            $protocol->save();

            return ApiResponse::success(
                data: ProtocolDTO::fromModel($protocol)->toArray(),
                message: 'Protocol updated successfully.'
            )->toJsonResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error(
                message: 'Validation failed',
                statusCode: 422,
                data: $e->errors()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to update protocol',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Delete a protocol permanently.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  The ID of the protocol to delete
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Exception When protocol deletion fails due to server error
     * @authenticated
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $protocol = Protocol::findOrFail($id);

            // Check if the authenticated user is the author
            if ($protocol->author !== $request->user()->name) {
                return response()->json([
                    'status_code' => 403,
                    'error' => 'Unauthorized',
                    'message' => 'You can only delete protocols that you created.',
                ], 403);
            }

            $protocol->delete();

            return ApiResponse::success(
                message: 'Protocol deleted successfully.',
                data: ProtocolDTO::fromModel($protocol)->toArray()
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404,
                data: 'The requested protocol does not exist.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to delete protocol',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve comprehensive statistics for a specific protocol.
     *
     * @param  int  $id  The ID of the protocol to get statistics for
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When protocol not found
     * @throws \Exception When fetching statistics fails due to server error
     */
    public function stats(int $id): JsonResponse
    {
        try {
            $protocol = Protocol::findOrFail($id);

            $reviewsCount = $protocol->reviews()->count();
            $threadsCount = $protocol->threads()->count();
            $averageRating = $protocol->getAverageRatingAttribute();

            $averageRating = $this->formatRating($averageRating);

            $data = [
                'protocol_id' => $protocol->getKey(),
                'total_reviews' => $reviewsCount,
                'total_threads' => $threadsCount,
                'average_rating' => $averageRating,
                'rating_distribution' => $this->getRatingDistribution($id),
            ];

            return ApiResponse::success(
                message: 'Protocol stats fetched successfully.',
                data: ProtocolStatsDTO::fromData($data)
            )->toJsonResponse();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error(
                message: 'Protocol not found',
                statusCode: 404,
                data: $e->getMessage()
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch protocol stats',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Retrieve a curated collection of featured protocols.
     *
     * @param  \Illuminate\Http\Request  $request  HTTP request with optional pagination
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When fetching featured protocols fails due to server error
     * @unauthenticated
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 3);

            $query = Protocol::withCount(['threads', 'reviews'])
                ->withAvg('reviews', 'rating');

            // Order by a combination of factors for "featured" quality
            $protocols = $query->orderBy('reviews_avg_rating', 'desc')
                ->orderBy('reviews_count', 'desc')
                ->orderBy('threads_count', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);


            // Transform protocols to DTOs
            $protocolDTOs = $protocols->getCollection()->map(function ($protocol) {
                return ProtocolDTO::fromModel($protocol)->toArray();
            })->toArray();

            $pagination = PaginationDTO::fromPaginator($protocols);

            return ApiResponse::successWithPagination(
                data: $protocolDTOs,
                pagination: $pagination->toArray(),
                message: 'Featured protocols fetched successfully.'
            )->toJsonResponse();
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to fetch featured protocols',
                statusCode: 500,
                data: $e->getMessage()
            )->toJsonResponse();
        }
    }

    /**
     * Calculate rating distribution for a specific protocol.
     *
     * @param  int  $protocolId  The ID of the protocol to analyze
     * @return array Rating distribution with counts for each rating (1-5)
     * @internal
     */
    private function getRatingDistribution(int $protocolId): array
    {
        $reviews = Review::where('protocol_id', $protocolId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->get();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviews->where('rating', $i)->first()?->count ?? 0;
        }

        return $distribution;
    }
}
