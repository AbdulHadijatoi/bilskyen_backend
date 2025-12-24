<?php

namespace App\Http\Responses;

use App\Constants\ApiStatusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Centralized API response helper trait
 * RESTful format: Success responses return data directly, errors use {status, message, errors}
 */
trait ApiResponse
{
    /**
     * Return successful response with data
     * All success responses are wrapped in a data object for consistency
     */
    protected function success($data = null, int $statusCode = ApiStatusCode::OK): JsonResponse
    {
        return response()->json(['data' => $data], $statusCode);
    }

    /**
     * Return error response
     */
    protected function error(
        string $message,
        $errors = null,
        int $statusCode = ApiStatusCode::BAD_REQUEST
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return paginated response
     * All paginated responses are wrapped in a data object for consistency
     * totalDocs is optional for performance (can be skipped for complex queries)
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        int $statusCode = ApiStatusCode::OK,
        bool $includeTotal = true
    ): JsonResponse {
        $response = [
            'docs' => $paginator->items(),
            'limit' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'hasPrevPage' => $paginator->currentPage() > 1,
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];

        if ($includeTotal) {
            $response['totalDocs'] = $paginator->total();
            $response['totalPages'] = $paginator->lastPage();
        }

        return response()->json(['data' => $response], $statusCode);
    }

    /**
     * Return created response (201)
     * All success responses are wrapped in a data object for consistency
     */
    protected function created($data = null): JsonResponse
    {
        return response()->json(['data' => $data], ApiStatusCode::CREATED);
    }

    /**
     * Return no content response (204)
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, ApiStatusCode::NO_CONTENT);
    }

    /**
     * Return unauthorized response (401)
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, null, ApiStatusCode::UNAUTHORIZED);
    }

    /**
     * Return forbidden response (403)
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, null, ApiStatusCode::FORBIDDEN);
    }

    /**
     * Return not found response (404)
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, ApiStatusCode::NOT_FOUND);
    }

    /**
     * Return validation error response (422)
     */
    protected function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, $errors, ApiStatusCode::UNPROCESSABLE_ENTITY);
    }

    /**
     * Return data with metadata
     * Useful for feature flags, limits, or app configuration data
     * Already follows architecture format with data and meta
     */
    protected function withMeta($data, array $meta): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    /**
     * Return metadata-only response
     */
    protected function meta(array $meta): JsonResponse
    {
        return response()->json(['meta' => $meta]);
    }
}

