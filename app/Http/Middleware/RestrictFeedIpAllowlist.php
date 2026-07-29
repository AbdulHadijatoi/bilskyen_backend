<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictFeedIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = config('security.feed_ip_allowlist', []);

        if (! is_array($allowlist) || $allowlist === []) {
            return $next($request);
        }

        if (! in_array($request->ip(), $allowlist, true)) {
            return response()->json([
                'success' => false,
                'failed' => true,
                'message' => __('messages.security.feed_ip_denied'),
                'data' => null,
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
