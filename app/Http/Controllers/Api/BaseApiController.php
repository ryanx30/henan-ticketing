<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Provides shared JSON response helpers for internal API controllers.
 */
class BaseApiController extends Controller
{
    /**
     * Standard success response for internal APIs.
     *
     * Project convention follows mentor preference:
     * - status: true/false
     * - message: string
     * - data: mixed
     * - meta/errors: optional
     */
    protected function success(
        mixed $data = null,
        string $message = 'OK',
        int $statusCode = 200,
        array $extra = []
    ): JsonResponse {
        return response()->json(array_merge([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $extra), $statusCode);
    }

    protected function successResponse(
        mixed $data = null,
        string $message = 'OK',
        array $meta = [],
        int $statusCode = 200
    ): JsonResponse {
        $extra = [];

        if (! empty($meta)) {
            $extra['meta'] = $meta;
        }

        return $this->success($data, $message, $statusCode, $extra);
    }

    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully.',
        array $meta = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $meta, 201);
    }

    protected function acceptedResponse(
        mixed $data = null,
        string $message = 'Request accepted.',
        array $meta = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $meta, 202);
    }

    /**
     * Keep a JSON body for delete responses so existing Blade/JS pages can still
     * read result.message after destructive actions. If a future page does not
     * need a body, use Laravel's response()->noContent() directly.
     */
    protected function deletedResponse(string $message = 'Resource deleted successfully.'): JsonResponse
    {
        return $this->successResponse(null, $message, [], 200);
    }

    protected function error(
        string $message = 'Something went wrong',
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        $payload = [
            'status' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    protected function errorResponse(
        string $message = 'Something went wrong',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        return $this->error($message, $statusCode, $errors);
    }

    protected function validationError(
        mixed $errors,
        string $message = 'Validation failed.'
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }

    protected function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'OK'
    ): JsonResponse {
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginator->items(),
            // Keep legacy pagination keys directly under meta so existing pages remain safe.
            // Also expose meta.pagination for newer endpoints that use a nested meta contract.
            'meta' => array_merge($pagination, [
                'pagination' => $pagination,
            ]),
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'OK'
    ): JsonResponse {
        return $this->paginated($paginator, $message);
    }
}
