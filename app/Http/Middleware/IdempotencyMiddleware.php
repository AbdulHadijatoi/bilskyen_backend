<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency Middleware
 * Prevents duplicate requests using Idempotency-Key header
 * Stores responses in Redis with 24-hour TTL
 * Fails fast if Redis is unavailable (critical operation)
 */
class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST requests
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        // If no idempotency key provided, continue normally
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Normalize the key
        $idempotencyKey = trim($idempotencyKey);

        if (empty($idempotencyKey)) {
            return $next($request);
        }

        // Create cache key
        $cacheKey = "idempotency:{$idempotencyKey}";

        try {
            // Check if we have a cached response
            $cachedResponse = Cache::get($cacheKey);

            if ($cachedResponse !== null) {
                // Return cached response
                return response()->json($cachedResponse['data'], $cachedResponse['status_code'])
                    ->withHeaders($cachedResponse['headers'] ?? []);
            }

            // Process the request
            $response = $next($request);

            // Only cache successful responses (2xx status codes)
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                // Get response content
                $responseData = json_decode($response->getContent(), true);
                $statusCode = $response->getStatusCode();
                $headers = $response->headers->all();

                // Store in cache for 24 hours
                Cache::put($cacheKey, [
                    'data' => $responseData,
                    'status_code' => $statusCode,
                    'headers' => $headers,
                ], now()->addHours(24));
            }

            return $response;
        } catch (\Exception $e) {
            // If Redis is unavailable, fail fast for critical operations
            Log::error('Idempotency middleware error', [
                'error' => $e->getMessage(),
                'idempotency_key' => $idempotencyKey,
            ]);

            // Check if it's a Redis connection error
            if (str_contains($e->getMessage(), 'Redis') || str_contains($e->getMessage(), 'Connection')) {
                throw new \RuntimeException(
                    'Idempotency service unavailable. Redis connection failed.',
                    503
                );
            }

            // For other errors, continue without idempotency
            return $next($request);
        }
    }
}

