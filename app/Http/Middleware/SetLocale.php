<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    private const SUPPORTED_LOCALES = ['en', 'da'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Set the application locale
        App::setLocale($locale);
        
        // Also set it in the config to ensure it persists
        config(['app.locale' => $locale]);

        // Store in cookie for persistence (1 year expiration)
        $response = $next($request);
        
        // Always set cookie to persist the language preference
        $response->cookie('app_locale', $locale, 525600, '/', null, false, false); // 1 year in minutes

        return $response;
    }

    /**
     * Determine the locale from request
     */
    private function determineLocale(Request $request): string
    {
        // 1. Check query parameter (highest priority)
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if (in_array($lang, self::SUPPORTED_LOCALES)) {
                return $lang;
            }
        }

        // 2. Check cookie
        $cookieLocale = $request->cookie('app_locale');
        if ($cookieLocale && in_array($cookieLocale, self::SUPPORTED_LOCALES)) {
            return $cookieLocale;
        }

        // 3. Check Accept-Language header (optional fallback)
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLocale = $this->parseAcceptLanguage($acceptLanguage);
            if ($preferredLocale && in_array($preferredLocale, self::SUPPORTED_LOCALES)) {
                return $preferredLocale;
            }
        }

        // 4. Default to config locale
        return config('app.locale', 'da');
    }

    /**
     * Parse Accept-Language header to get preferred locale
     */
    private function parseAcceptLanguage(string $acceptLanguage): ?string
    {
        $languages = explode(',', $acceptLanguage);
        
        foreach ($languages as $language) {
            $parts = explode(';', trim($language));
            $locale = strtolower(trim($parts[0]));
            
            // Check if it's a supported locale
            if (in_array($locale, self::SUPPORTED_LOCALES)) {
                return $locale;
            }
            
            // Check if it starts with a supported locale (e.g., 'en-US' -> 'en')
            foreach (self::SUPPORTED_LOCALES as $supported) {
                if (str_starts_with($locale, $supported . '-') || $locale === $supported) {
                    return $supported;
                }
            }
        }

        return null;
    }
}
