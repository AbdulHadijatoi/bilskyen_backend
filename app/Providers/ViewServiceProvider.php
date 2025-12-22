<?php

namespace App\Providers;

use App\Services\AuthService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share authenticated user data to user-auth-status component
        View::composer('components.user-auth-status', function ($view) {
            $authService = app(AuthService::class);
            $user = $authService->getAuthenticatedUser(request());
            
            $view->with([
                'user' => $user,
                'initials' => $user?->initials ?? 'U',
                'showUserMenu' => $user !== null,
            ]);
        });

        // Share authenticated user data to dealer sidebar component
        // View::composer('components.dealer.sidebar', function ($view) {
        //     $authService = app(AuthService::class);
        //     $user = $authService->getAuthenticatedUser(request());
            
        //     $view->with([
        //         'user' => $user,
        //         'currentRoute' => request()->path(),
        //     ]);
        // });
    }
}

