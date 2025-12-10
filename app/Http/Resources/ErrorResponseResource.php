<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Error Response Resource
 *
 * Provides a standardized error response format following Laravel best practices.
 * Includes comprehensive error information for debugging while maintaining security
 * in production environments.
 *
 * Features:
 * - Standardized error message format
 * - HTTP method and path information
 * - Validation errors support
 * - Debug information (only in debug mode)
 * - Timestamp for error tracking
 *
 * @package App\Http\Resources
 */
class ErrorResponseResource
{
    /**
     * Create an error response from an exception.
     *
     * @param Throwable $exception
     * @param Request $request
     * @param int|null $statusCode
     * @return array
     */
    public static function fromException(Throwable $exception, Request $request, ?int $statusCode = null): array
    {
        $statusCode = $statusCode ?? self::getStatusCodeFromException($exception);

        $response = [
            'message' => self::getErrorMessage($exception),
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ];

        // Add validation errors if it's a ValidationException
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
        }

        // Add debug information only in debug mode
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::formatTrace($exception->getTrace()),
            ];
        }

        return $response;
    }

    /**
     * Create an error response from validation errors.
     *
     * @param array $errors
     * @param Request $request
     * @param string|null $message
     * @param int $statusCode
     * @return array
     */
    public static function fromValidationErrors(
        array $errors,
        Request $request,
        ?string $message = null,
        int $statusCode = 422
    ): array {
        return [
            'message' => $message ?? 'The given data was invalid.',
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $statusCode,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Create a simple error response.
     *
     * @param string $message
     * @param Request $request
     * @param int $statusCode
     * @param array|null $errors
     * @return array
     */
    public static function fromMessage(
        string $message,
        Request $request,
        int $statusCode = 500,
        ?array $errors = null
    ): array {
        $response = [
            'message' => $message,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Get the appropriate status code from an exception.
     *
     * @param Throwable $exception
     * @return int
     */
    protected static function getStatusCodeFromException(Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if (method_exists($exception, 'getCode') && $exception->getCode() >= 400 && $exception->getCode() < 600) {
            return (int) $exception->getCode();
        }

        return 500;
    }

    /**
     * Get a user-friendly error message from an exception.
     *
     * @param Throwable $exception
     * @return string
     */
    protected static function getErrorMessage(Throwable $exception): string
    {
        // For ValidationException, use a standard message
        if ($exception instanceof ValidationException) {
            return 'The given data was invalid.';
        }

        // In production, use generic messages for security
        if (!config('app.debug')) {
            return match (true) {
                $exception instanceof \Illuminate\Auth\AuthenticationException => 'Unauthenticated.',
                $exception instanceof \Illuminate\Auth\Access\AuthorizationException => 'This action is unauthorized.',
                $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 'Resource not found.',
                $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 'The requested resource was not found.',
                $exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => 'The requested method is not allowed.',
                default => 'An error occurred while processing your request.',
            };
        }

        // In debug mode, return the actual exception message
        return $exception->getMessage() ?: 'An error occurred while processing your request.';
    }

    /**
     * Format the exception trace for display.
     *
     * @param array $trace
     * @return array
     */
    protected static function formatTrace(array $trace): array
    {
        return array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ];
        }, array_slice($trace, 0, 10)); // Limit to first 10 frames
    }

    /**
     * Convert the error response to a JSON response.
     *
     * @param array $errorData
     * @return \Illuminate\Http\JsonResponse
     */
    public static function toJsonResponse(array $errorData): \Illuminate\Http\JsonResponse
    {
        $statusCode = $errorData['status_code'] ?? 500;

        return response()->json($errorData, $statusCode);
    }
}

