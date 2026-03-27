<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clean = $this->sanitizeValue($request->all());
        $request->merge($clean);

        return $next($request);
    }

    /**
     * Sanitize recursively for arrays and string values.
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeValue($item);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->sanitizeString($value);
    }

    /**
     * Remove common XSS and SQL injection payload patterns.
     */
    private function sanitizeString(string $value): string
    {
        $clean = trim($value);
        $clean = str_replace("\0", '', $clean);

        // Remove full script/style blocks first.
        $clean = preg_replace('/<\s*(script|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $clean) ?? $clean;

        // Remove inline event handlers and javascript-like URI schemes.
        $clean = preg_replace('/on\w+\s*=\s*("|\').*?\1/is', '', $clean) ?? $clean;
        $clean = preg_replace('/\b(javascript|vbscript|data:text\/html)\s*:/i', '', $clean) ?? $clean;

        // Remove any remaining HTML tags.
        $clean = strip_tags($clean);

        // Neutralize common SQL injection control sequences.
        $clean = preg_replace('/(--|#|\/\*|\*\/|;)/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\b(union|select|insert|update|delete|drop|alter|truncate|exec|sleep|benchmark)\b/i', '', $clean) ?? $clean;

        // Collapse excessive whitespace after cleaning.
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
