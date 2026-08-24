<?php

use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // All API routes are prefixed with /api/v1 for versioning
            // Admin routes are prefixed with /api/v1/admin
            Route::middleware('api')
                ->prefix('api/v1/admin')
                ->group(base_path('routes/admin-apis.php'));
            
            // Dealer routes are prefixed with /api/v1/dealer
            Route::middleware('api')
                ->prefix('api/v1/dealer')
                ->group(base_path('routes/dealer-apis.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([
            \App\Http\Middleware\CanonicalUrlMiddleware::class,
            \App\Http\Middleware\SeoRedirectMiddleware::class,
        ]);

        $middleware->append([
            SanitizeInput::class,
            SecurityHeaders::class,
            \App\Http\Middleware\PublicHtmlCache::class,
        ]);

        // Trust Cloudflare / reverse-proxy headers when behind a CDN.
        $middleware->trustProxies(at: '*');

        // Set locale after session/cookies are available on web requests
        $middleware->web(prepend: [
            \App\Http\Middleware\ResolveCustomDomain::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            \App\Http\Middleware\CaptureTrafficAttribution::class,
        ]);

        $middleware->api(prepend: [
            SetLocale::class,
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
            'dealer.feature' => \App\Http\Middleware\RequireDealerFeature::class,
            'cron.auth' => \App\Http\Middleware\CronAuth::class,
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'auth.web' => \App\Http\Middleware\AuthenticateWeb::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
            'dealer.api.key' => \App\Http\Middleware\AuthenticateDealerApiKey::class,
            'turnstile' => \App\Http\Middleware\VerifyTurnstile::class,
            'honeypot' => \App\Http\Middleware\RejectHoneypot::class,
            'abuse.detect' => \App\Http\Middleware\DetectAbusiveClient::class,
            'feed.ip' => \App\Http\Middleware\RestrictFeedIpAllowlist::class,
        ]);
        
        // Modest global API ceiling; named limiters still isolate hot endpoints.
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\AiGenerationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'failed' => true,
                    'message' => $e->getMessage(),
                    'data' => null,
                    'errors' => [],
                ], $e->statusCode(), [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        });
    })->create();
