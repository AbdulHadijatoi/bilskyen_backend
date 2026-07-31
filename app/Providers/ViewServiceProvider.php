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
        View::composer('components.marketplace-notifications', function ($view) {
            $authService = app(AuthService::class);
            $user = $authService->getAuthenticatedUser(request());

            $view->with([
                'showNotifications' => $user !== null,
            ]);
        });

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
        View::composer('components.footer', function ($view) {
            $settings = app(PlatformSettingService::class);
            $view->with([
                'homePageContent' => app(PageContentService::class)->getHomePageContent('home'),
                'footerCities' => app(\App\Services\CityIndexService::class)->topCities(8),
                'faqPageEnabled' => $settings->isFaqPageEnabled(),
            ]);
        });

        View::composer('components.navbar', function ($view) {
            $authService = app(AuthService::class);
            $user = $authService->getAuthenticatedUser(request());

            $sellerToken = null;
            if ($user && $user->hasRole('seller')) {
                $sellerTokenService = app(\App\Services\SellerTokenService::class);
                $sellerToken = $sellerTokenService->generateToken($user);
            }

            $view->with([
                'user' => $user,
                'hasSellerRole' => $user?->hasRole('seller') ?? false,
                'sellerToken' => $sellerToken,
                'faqPageEnabled' => app(PlatformSettingService::class)->isFaqPageEnabled(),
            ]);
        });

        View::composer('components.language-switcher', function ($view) {
            $view->with(
                'languageSwitcherEnabled',
                app(PlatformSettingService::class)->isLanguageSwitcherEnabled()
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

