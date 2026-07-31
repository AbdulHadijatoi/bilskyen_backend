<?php

namespace App\Providers;

use App\Models\Vehicle;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Enquiry;
use App\Observers\LeadObserver;
use App\Observers\EnquiryObserver;
use App\Observers\VehicleSyndicationObserver;
use App\Observers\VehicleDmsWebhookObserver;
use App\Observers\VehicleCityObserver;
use App\Observers\DealerCityObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        Enquiry::observe(EnquiryObserver::class);
        Vehicle::observe(VehicleSyndicationObserver::class);
        Vehicle::observe(VehicleDmsWebhookObserver::class);
        Vehicle::observe(VehicleCityObserver::class);
        Dealer::observe(DealerCityObserver::class);

        // Vehicle route model binding: resolve by id when numeric (API), by slug when not (web)
        Route::bind('vehicle', function (string $value) {
            return is_numeric($value)
                ? Vehicle::findOrFail((int) $value)
                : Vehicle::where('slug', $value)->firstOrFail();
        });

        // Validate JWT_SECRET is set, especially in production
        $jwtSecret = config('jwt.secret');
        if (empty($jwtSecret)) {
            if (app()->environment('production')) {
                throw new \RuntimeException(
                    'JWT_SECRET is not set in production environment. ' .
                    'Please run: php artisan jwt:secret to generate a secret key, ' .
                    'then add it to your .env file as JWT_SECRET=your_generated_secret'
                );
            } else {
                // In development, provide a helpful warning
                \Log::warning(
                    'JWT_SECRET is not set. Please run: php artisan jwt:secret to generate a secret key.'
                );
            }
        }

        // Configure named rate limiters for isolated endpoint rate limiting
        // This prevents one endpoint's rate limit from affecting others
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('public.listings', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('public.reads', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('public.writes', function (Request $request) {
            return Limit::perMinute(12)->by($request->ip());
        });

        RateLimiter::for('public.feeds', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('auth.login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth.refresh', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
