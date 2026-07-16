<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'da'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'da';
        }

        App::setLocale($locale);
        config(['app.locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        if ($request->is('api/*')) {
            $headerLocale = $this->parseAcceptLanguage($request->header('Accept-Language'));
            if ($headerLocale !== null) {
                return $headerLocale;
            }
        }

        if ($request->hasSession() && $request->session()->has('locale')) {
            return (string) $request->session()->get('locale');
        }

        $cookieLocale = $request->cookie('app_locale');
        if (is_string($cookieLocale) && in_array($cookieLocale, self::SUPPORTED_LOCALES, true)) {
            if ($request->hasSession()) {
                $request->session()->put('locale', $cookieLocale);
            }

            return $cookieLocale;
        }

        return config('app.locale', 'da');
    }

    private function parseAcceptLanguage(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        $primary = strtolower(trim(explode(',', $header)[0]));
        $primary = explode(';', $primary)[0];
        $primary = explode('-', $primary)[0];

        if (in_array($primary, self::SUPPORTED_LOCALES, true)) {
            return $primary;
        }

        return null;
    }
}
