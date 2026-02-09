<?php

namespace App\Http\Responses;

use App\Constants\ApiStatusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Centralized API response helper trait
 * Unified format: All responses include success, failed, message, data, and errors fields
 */
trait ApiResponse
{
    /**
     * Normalize errors field - always return array or object, never null
     */
    private function normalizeErrors($errors)
    {
        if ($errors === null) {
            return [];
        }
        return $errors;
    }

    /**
     * Return successful response with data
     */
    protected function success($data = null, int $statusCode = ApiStatusCode::OK, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.operation_completed_successfully'),
            'data' => $data,
            'errors' => [],
        ], $statusCode);
    }

    /**
     * Return error response
     */
    protected function error(
        string $message,
        $errors = null,
        int $statusCode = ApiStatusCode::BAD_REQUEST,
        string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'failed' => true,
            'message' => $message,
            'data' => null,
            'errors' => $this->normalizeErrors($errors),
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return paginated response
     * totalDocs is optional for performance (can be skipped for complex queries)
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        int $statusCode = ApiStatusCode::OK,
        bool $includeTotal = true,
        ?string $message = null
    ): JsonResponse {
        $paginationData = [
            'docs' => $paginator->items(),
            'limit' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'hasPrevPage' => $paginator->currentPage() > 1,
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];

        if ($includeTotal) {
            $paginationData['totalDocs'] = $paginator->total();
            $paginationData['totalPages'] = $paginator->lastPage();
        }

        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.data_retrieved_successfully'),
            'data' => $paginationData,
            'errors' => [],
        ], $statusCode);
    }

    /**
     * Return created response (201)
     */
    protected function created($data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.resource_created_successfully'),
            'data' => $data,
            'errors' => [],
        ], ApiStatusCode::CREATED);
    }

    /**
     * Return no content response (now returns JSON with unified format)
     */
    protected function noContent(?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.operation_completed_successfully'),
            'data' => null,
            'errors' => [],
        ], ApiStatusCode::NO_CONTENT);
    }

    /**
     * Return unauthorized response (401)
     */
    protected function unauthorized(?string $message = null, string $errorCode = 'UNAUTHORIZED'): JsonResponse
    {
        return $this->error($message ?? __('messages.api.unauthorized'), [], ApiStatusCode::UNAUTHORIZED, $errorCode);
    }

    /**
     * Return forbidden response (403)
     */
    protected function forbidden(?string $message = null, string $errorCode = 'FORBIDDEN'): JsonResponse
    {
        return $this->error($message ?? __('messages.api.forbidden'), [], ApiStatusCode::FORBIDDEN, $errorCode);
    }

    /**
     * Return not found response (404)
     */
    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->error($message ?? __('messages.api.resource_not_found'), [], ApiStatusCode::NOT_FOUND);
    }

    /**
     * Return validation error response (422)
     */
    protected function validationError($errors, ?string $message = null): JsonResponse
    {
        return $this->error($message ?? __('messages.api.validation_failed'), $this->normalizeErrors($errors), ApiStatusCode::UNPROCESSABLE_ENTITY);
    }

    /**
     * Return data with metadata
     * Useful for feature flags, limits, or app configuration data
     */
    protected function withMeta($data, array $meta, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.operation_completed_successfully'),
            'data' => $data,
            'meta' => $meta,
            'errors' => [],
        ]);
    }

    /**
     * Return metadata-only response
     */
    protected function meta(array $meta, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'failed' => false,
            'message' => $message ?? __('messages.api.operation_completed_successfully'),
            'data' => null,
            'meta' => $meta,
            'errors' => [],
        ]);
    }
}

