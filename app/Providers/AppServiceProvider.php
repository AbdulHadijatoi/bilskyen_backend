<?php

namespace App\Providers;

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
    }
}
