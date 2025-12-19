<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CronAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $cronSecret = config('app.cron_secret');
            $authHeader = $request->header('Authorization');

            if ($authHeader !== "Bearer {$cronSecret}") {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid cron secret',
                ], 401);
            }
        }

        return $next($request);
    }
}

