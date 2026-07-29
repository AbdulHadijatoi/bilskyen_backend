<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectAbusiveClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();
        $banKey = "security:abuse:ban:{$ip}";

        if (Cache::has($banKey)) {
            Log::info('security.abuse.banned_hit', [
                'ip' => $ip,
                'path' => $request->path(),
            ]);

            return $this->tooMany($request);
        }

        $score = 0;
        $ua = trim((string) $request->userAgent());

        if ($ua === '') {
            $score += 4;
        } elseif (preg_match('/(curl|wget|python-requests|scrapy|httpclient|libwww|go-http-client|java\/)/i', $ua)) {
            $score += 3;
        }

        if (! $request->headers->has('Accept')) {
            $score += 2;
        }

        if (! $request->headers->has('Accept-Language')) {
            $score += 1;
        }

        $window = (int) config('security.abuse.velocity_window_seconds', 60);
        $max = (int) config('security.abuse.velocity_max_requests', 40);
        $velocityKey = "security:abuse:velocity:{$ip}";
        $count = (int) Cache::get($velocityKey, 0) + 1;
        Cache::put($velocityKey, $count, $window);

        if ($count > $max) {
            $score += 5;
        }

        // Sequential pagination hammering on listing endpoints.
        $page = (int) $request->input('page', 0);
        if ($page > 1) {
            $pageKey = "security:abuse:page:{$ip}";
            $lastPage = (int) Cache::get($pageKey, 0);
            Cache::put($pageKey, $page, 30);
            if ($lastPage > 0 && $page === $lastPage + 1 && $count > 15) {
                $score += 2;
            }
        }

        $threshold = (int) config('security.abuse.score_threshold', 8);
        if ($score >= $threshold) {
            $banSeconds = (int) config('security.abuse.ban_seconds', 300);
            Cache::put($banKey, true, $banSeconds);

            Log::warning('security.abuse.banned', [
                'ip' => $ip,
                'path' => $request->path(),
                'score' => $score,
                'ua' => $ua,
                'ban_seconds' => $banSeconds,
            ]);

            return $this->tooMany($request);
        }

        if ($score >= 4) {
            Log::info('security.abuse.scored', [
                'ip' => $ip,
                'path' => $request->path(),
                'score' => $score,
            ]);
        }

        return $next($request);
    }

    private function tooMany(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'failed' => true,
                'message' => __('messages.security.too_many_requests'),
                'data' => null,
                'errors' => [],
            ], 429);
        }

        return response(__('messages.security.too_many_requests'), 429);
    }
}
