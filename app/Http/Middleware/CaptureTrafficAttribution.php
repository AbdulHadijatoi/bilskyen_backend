<?php

namespace App\Http\Middleware;

use App\Services\Marketing\TrafficAttributionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTrafficAttribution
{
    public function __construct(
        private TrafficAttributionService $trafficAttributionService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession()) {
            $this->trafficAttributionService->capture($request);
        }

        return $next($request);
    }
}
