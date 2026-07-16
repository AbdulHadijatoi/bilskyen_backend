<?php

namespace App\Http\Controllers;

use App\Services\PlatformSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    private const SUPPORTED_LOCALES = ['en', 'da'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! $this->platformSettingService->isLanguageSwitcherEnabled()) {
            return redirect()->back(fallback: '/');
        }

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            abort(400);
        }

        $request->session()->put('locale', $locale);
        $request->session()->save();

        App::setLocale($locale);

        $redirectTo = $request->headers->get('referer');
        if (! is_string($redirectTo) || $redirectTo === '' || str_contains($redirectTo, '/locale/')) {
            $redirectTo = '/';
        }

        return redirect()
            ->to($redirectTo)
            ->withCookie(cookie()->forever('app_locale', $locale, '/'))
            ->withHeaders(['Cache-Control' => 'no-store']);
    }
}
