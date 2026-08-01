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

        $path = $request->getPathInfo();
        $to = $this->redirectService->destinationPath($redirect, $path);

        if (! str_starts_with($to, 'http://') && ! str_starts_with($to, 'https://')) {
            $to = url($to);
        }

        $query = $request->getQueryString();
        if ($query) {
            $to .= (str_contains($to, '?') ? '&' : '?').$query;
        }

        return redirect($to, (int) $redirect->redirect_type);
    }
}
