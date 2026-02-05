<?php

if (!function_exists('trans')) {
    /**
     * Get translation for a key
     * 
     * @param string $key Translation key
     * @param array $replace Placeholder replacements
     * @param string|null $locale Locale (defaults to app locale)
     * @return string
     */
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $translationService = app(\App\Services\TranslationService::class);
        return $translationService->get($key, $locale, $replace);
    }
}

if (!function_exists('__')) {
    /**
     * Alias for trans() function
     * 
     * @param string $key Translation key
     * @param array $replace Placeholder replacements
     * @param string|null $locale Locale (defaults to app locale)
     * @return string
     */
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return trans($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Get translation with pluralization support
     * 
     * @param string $key Translation key
     * @param int $number Number for pluralization
     * @param array $replace Placeholder replacements
     * @param string|null $locale Locale (defaults to app locale)
     * @return string
     */
    function trans_choice(string $key, int $number, array $replace = [], ?string $locale = null): string
    {
        $translationService = app(\App\Services\TranslationService::class);
        $translation = $translationService->get($key, $locale, $replace);
        
        // Simple pluralization: if translation contains |, use it for pluralization
        if (strpos($translation, '|') !== false) {
            $parts = explode('|', $translation);
            if ($number === 1) {
                return $parts[0];
            } else {
                return $parts[1] ?? $parts[0];
            }
        }
        
        return $translation;
    }
}
