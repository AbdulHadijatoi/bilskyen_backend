<?php

namespace App\Providers;

use App\Constants\CmsPostStatus;
use App\Models\LandingPage;
use App\Services\AuthService;
use App\Services\AiService;
use App\Services\PageContentService;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Route;
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
                'faqPageEnabled' => $settings->isFaqPageEnabled() && Route::has('faq'),
                'footerLandingPages' => LandingPage::query()
                    ->where('status', CmsPostStatus::PUBLISHED)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->orderByDesc('published_at')
                    ->limit(6)
                    ->get(['id', 'slug', 'title']),
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
                'publicAiEnabled' => app(AiService::class)->isGloballyEnabled(),
            ]);
        });

        View::composer('components.language-switcher', function ($view) {
            $view->with(
                'languageSwitcherEnabled',
                app(PlatformSettingService::class)->isLanguageSwitcherEnabled()
            );
        });

        View::composer(['layouts.app', 'layouts.auth'], function ($view) {
            $settings = app(PlatformSettingService::class);
            $locale = app()->getLocale();
            $view->with([
                'cookieConsent' => [
                    'enabled' => filter_var($settings->get('seo', 'cookie_consent_enabled', false), FILTER_VALIDATE_BOOLEAN),
                    'text' => $settings->get('seo', 'cookie_consent_text_'.$locale, '') ?: $settings->get('seo', 'cookie_consent_text_en', ''),
                ],
                'publicAiEnabled' => app(AiService::class)->isGloballyEnabled(),
            ]);
        });

        // Child page views (rendered before the layout) need the flag for @if gates in content.
        View::composer(['home', 'vehicles', 'faq', 'sell-your-car'], function ($view) {
            $view->with('publicAiEnabled', app(AiService::class)->isGloballyEnabled());
        });
    }
}

