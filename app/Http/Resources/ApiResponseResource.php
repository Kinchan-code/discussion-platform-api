<?php

namespace App\Http\Resources;

class ApiResponseResource
{
    public function __construct(
        public readonly int $status_code,
        public readonly string $message,
        public readonly mixed $data,
        public readonly ?array $pagination = null
    ) {}

    public static function success(mixed $data, string $message = 'Success', int $statusCode = 200): self
    {
        return new self($statusCode, $message, $data);
    }

    public static function successWithPagination(
        mixed $data,
        array $pagination,
        string $message = 'Success',
        int $statusCode = 200
    ): self {
        return new self($statusCode, $message, $data, $pagination);
    }

    public static function error(string $message, int $statusCode = 500, mixed $data = null): self
    {
        return new self($statusCode, $message, $data);
    }

    /**
     * Create an error response using ErrorResponseResource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function errorResponse(
        \Illuminate\Http\Request $request,
        string $message,
        int $statusCode = 500,
        ?array $errors = null
    ): \Illuminate\Http\JsonResponse {
        $errorData = ErrorResponseResource::fromMessage($message, $request, $statusCode, $errors);

        return ErrorResponseResource::toJsonResponse($errorData);
    }

    public function toArray(): array
    {
        $response = [
            'status_code' => $this->status_code,
            'message' => $this->message,
            'data' => $this->data,
        ];

        if ($this->pagination !== null) {
            $response['pagination'] = $this->pagination;
        }

        return $response;
    }

    public function toJsonResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->toArray(), $this->status_code);
    }
}

