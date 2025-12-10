<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use App\Http\Resources\ErrorResponseResource;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Handle ValidationException
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errorData = ErrorResponseResource::fromException($e, $request);
                return ErrorResponseResource::toJsonResponse($errorData);
            }
        });

        // Handle AuthenticationException
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errorData = ErrorResponseResource::fromException($e, $request, 401);
                return ErrorResponseResource::toJsonResponse($errorData);
            }
        });

        // Handle other exceptions for API routes
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errorData = ErrorResponseResource::fromException($e, $request);
                return ErrorResponseResource::toJsonResponse($errorData);
            }
        });
    })->create();
