<?php

namespace App\Services\VehicleImport\Bilbasen;

use App\Support\RemoteUrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Fetches Bilbasen listing HTML with host allowlisting and SSRF guards.
 */
class BilbasenListingFetcher
{
    public const MAX_HTML_BYTES = 2_000_000;

    public const TIMEOUT_SECONDS = 25;

    public const MAX_REDIRECTS = 3;

    public function __construct(
        private BilbasenUrlValidator $urlValidator,
    ) {}

    /**
     * @return array{url: string, listing_id: string, html: string}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function fetch(string $url): array
    {
        $validated = $this->urlValidator->validate($url);
        $targetUrl = $validated['url'];

        RemoteUrlGuard::assertPublicHttpUrl($targetUrl);

        $currentUrl = $targetUrl;
        $redirects = 0;

        try {
            while (true) {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language' => 'da-DK,da;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Cache-Control' => 'no-cache',
                    ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'http_errors' => false,
                    ])
                    ->get($currentUrl);

                if (! $response->redirect()) {
                    break;
                }

                if ($redirects >= self::MAX_REDIRECTS) {
                    throw new \RuntimeException(__('messages.api.bilbasen_import_redirect_blocked'));
                }

                $location = $response->header('Location');
                $location = is_array($location) ? ($location[0] ?? '') : (string) $location;
                $location = trim($location);
                if ($location === '') {
                    throw new \RuntimeException(__('messages.api.bilbasen_import_redirect_blocked'));
                }

                $nextUrl = $this->resolveRedirectUrl($currentUrl, $location);
                $this->assertRedirectAllowed($nextUrl);
                $currentUrl = $nextUrl;
                $redirects++;
            }
        } catch (ConnectionException $e) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_fetch_failed'), 0, $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_fetch_http_error', [
                'status' => $response->status(),
            ]));
        }

        $html = $response->body();
        if ($html === '' || strlen($html) > self::MAX_HTML_BYTES) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_fetch_failed'));
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml')) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_fetch_failed'));
        }

        return [
            'url' => $validated['url'],
            'listing_id' => $validated['listing_id'],
            'html' => $html,
        ];
    }

    private function assertRedirectAllowed(string $redirectUrl): void
    {
        try {
            $this->urlValidator->validate($redirectUrl);
            RemoteUrlGuard::assertPublicHttpUrl($redirectUrl);
        } catch (\InvalidArgumentException) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_redirect_blocked'));
        }
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \RuntimeException(__('messages.api.bilbasen_import_redirect_blocked'));
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $base = $scheme.'://'.$host.$port;

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $base.$location;
        }

        $path = $parts['path'] ?? '/';
        $dir = preg_replace('#/[^/]*$#', '/', $path) ?: '/';

        return $base.$dir.$location;
    }
}
