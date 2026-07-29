<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Keys that may contain intentional HTML (CMS / rich text). Skipped here;
     * purified on save via HtmlSanitizer.
     *
     * @var list<string>
     */
    private const HTML_ALLOWLIST_KEYS = [
        'content_html',
        'body_html',
        'privacy_body',
        'terms_body',
        'description_html',
        'og_description_html',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $clean = $this->sanitizeValue($request->all());
        $request->merge($clean);

        return $next($request);
    }

    private function sanitizeValue(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $item) {
                $value[$childKey] = $this->sanitizeValue($item, is_string($childKey) ? $childKey : $key);
            }

            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        if ($key !== null && in_array($key, self::HTML_ALLOWLIST_KEYS, true)) {
            return $this->sanitizeRichTextShell($value);
        }

        return $this->sanitizePlainString($value);
    }

    /**
     * Light pass for rich HTML fields: strip null bytes only.
     * Full allowlisting happens in HtmlSanitizer on write.
     */
    private function sanitizeRichTextShell(string $value): string
    {
        return str_replace("\0", '', $value);
    }

    /**
     * Neutralize common XSS vectors on plain-text inputs.
     * SQL injection is prevented by Eloquent/parameter bindings — do not strip SQL keywords.
     */
    private function sanitizePlainString(string $value): string
    {
        $clean = trim($value);
        $clean = str_replace("\0", '', $clean);

        // Remove full script/style blocks first.
        $clean = preg_replace('/<\s*(script|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $clean) ?? $clean;

        // Remove inline event handlers and javascript-like URI schemes.
        $clean = preg_replace('/on\w+\s*=\s*("|\').*?\1/is', '', $clean) ?? $clean;
        $clean = preg_replace('/\b(javascript|vbscript|data:text\/html)\s*:/i', '', $clean) ?? $clean;

        // Remove any remaining HTML tags from plain fields.
        $clean = strip_tags($clean);

        // Collapse excessive whitespace after cleaning.
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
