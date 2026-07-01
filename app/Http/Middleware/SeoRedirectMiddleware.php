<?php

namespace App\Http\Middleware;

use App\Services\Seo\SeoRedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoRedirectMiddleware
{
    public function __construct(
        private SeoRedirectService $redirectService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $redirect = $this->redirectService->resolve($request);
        if (! $redirect) {
            return $next($request);
        }

        $this->redirectService->recordHit($redirect);

        $to = $redirect->to_path;
        if (! str_starts_with($to, 'http')) {
            $to = url($to);
        }

        return redirect($to, (int) $redirect->redirect_type);
    }
}
