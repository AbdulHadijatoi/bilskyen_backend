<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/admin-apis.php'));
            
            Route::middleware('api')
                ->prefix('api')
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
