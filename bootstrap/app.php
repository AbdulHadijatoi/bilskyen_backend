<?php

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
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
            'cron.auth' => \App\Http\Middleware\CronAuth::class,
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'auth.web' => \App\Http\Middleware\AuthenticateWeb::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
        
        // Global rate limiting baseline: 60 requests per minute per IP
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
