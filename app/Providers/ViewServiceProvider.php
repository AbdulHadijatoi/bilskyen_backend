<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\PageContentService;
use App\Services\PlatformSettingService;
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

        // Share authenticated user data to navbar component
        View::composer('components.navbar', function ($view) {
            $authService = app(AuthService::class);
            $user = $authService->getAuthenticatedUser(request());
            
            // Generate seller token if user is a seller
            $sellerToken = null;
            if ($user && $user->hasRole('seller')) {
                $sellerTokenService = app(\App\Services\SellerTokenService::class);
                $sellerToken = $sellerTokenService->generateToken($user);
            }
            
            $view->with([
                'user' => $user,
                'hasSellerRole' => $user?->hasRole('seller') ?? false,
                'sellerToken' => $sellerToken,
            ]);
        });

        View::composer('components.footer', function ($view) {
            $view->with(
                'homePageContent',
                app(PageContentService::class)->getHomePageContent('home')
            );
        });

        View::composer('layouts.app', function ($view) {
            $settings = app(PlatformSettingService::class);
            $locale = app()->getLocale();
            $view->with('cookieConsent', [
                'enabled' => filter_var($settings->get('seo', 'cookie_consent_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'text' => $settings->get('seo', 'cookie_consent_text_'.$locale, '') ?: $settings->get('seo', 'cookie_consent_text_en', ''),
            ]);
        });
    }
}

