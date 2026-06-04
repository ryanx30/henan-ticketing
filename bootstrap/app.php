<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api-internal.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isInternalApiRequest = static function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($isInternalApiRequest) {
            if (! $isInternalApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($isInternalApiRequest) {
            if (! $isInternalApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage() ?: 'Forbidden.',
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isInternalApiRequest) {
            if (! $isInternalApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($isInternalApiRequest) {
            if (! $isInternalApiRequest($request)) {
                return null;
            }

            return response()->json([
                'status' => false,
                'message' => 'Resource not found.',
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isInternalApiRequest) {
            if (! $isInternalApiRequest($request)) {
                return null;
            }

            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: match ($statusCode) {
                401 => 'Unauthenticated.',
                403 => 'Forbidden.',
                404 => 'Resource not found.',
                405 => 'Method not allowed.',
                409 => 'Conflict.',
                410 => 'Resource is no longer available.',
                419 => 'Session expired. Please refresh and try again.',
                422 => 'Validation failed.',
                429 => 'Too many requests. Please try again later.',
                500 => 'Server error.',
                default => 'Request failed.',
            };

            return response()->json([
                'status' => false,
                'message' => $message,
            ], $statusCode);
        });
    })
    ->create();
