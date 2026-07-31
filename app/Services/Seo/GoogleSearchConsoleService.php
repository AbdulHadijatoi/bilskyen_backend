<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Search Console API client (URL Inspection + Sitemaps).
 *
 * Uses a service account JWT — not the Indexing API (restricted to JobPosting/BroadcastEvent).
 */
class GoogleSearchConsoleService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/webmasters';

    private const INSPECTION_URL = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

    public function isConfigured(): bool
    {
        try {
            $this->credentials();
            $property = (string) config('services.google_search_console.property', '');

            return $property !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public function property(): string
    {
        $property = trim((string) config('services.google_search_console.property', ''));
        if ($property === '') {
            throw new RuntimeException(
                'GOOGLE_SEARCH_CONSOLE_PROPERTY is not set (e.g. https://bilskyen.dk/ or sc-domain:bilskyen.dk).'
            );
        }

        return $property;
    }

    /**
     * Inspect a single URL via the URL Inspection API.
     *
     * @return array{
     *     url: string,
     *     coverageState: string|null,
     *     indexingState: string|null,
     *     verdict: string|null,
     *     lastCrawlTime: string|null,
     *     robotsTxtState: string|null,
     *     pageFetchState: string|null,
     *     crawledAs: string|null,
     *     referringUrls: list<string>,
     *     sitemap: list<string>,
     *     raw: array<string, mixed>
     * }
     */
    public function inspectUrl(string $inspectionUrl, ?string $languageCode = 'da-DK'): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(self::INSPECTION_URL, [
                'inspectionUrl' => $inspectionUrl,
                'siteUrl' => $this->property(),
                'languageCode' => $languageCode ?? 'da-DK',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'URL Inspection failed for '.$inspectionUrl.': HTTP '.$response->status().' '.$response->body()
            );
        }

        $raw = $response->json() ?? [];
        $result = $raw['inspectionResult'] ?? [];
        $indexStatus = $result['indexStatusResult'] ?? [];

        return [
            'url' => $inspectionUrl,
            'coverageState' => $indexStatus['coverageState'] ?? null,
            'indexingState' => $indexStatus['indexingState'] ?? null,
            'verdict' => $indexStatus['verdict'] ?? ($result['verdict'] ?? null),
            'lastCrawlTime' => $indexStatus['lastCrawlTime'] ?? null,
            'robotsTxtState' => $indexStatus['robotsTxtState'] ?? null,
            'pageFetchState' => $indexStatus['pageFetchState'] ?? null,
            'crawledAs' => $indexStatus['crawledAs'] ?? null,
            'referringUrls' => array_values($indexStatus['referringUrls'] ?? []),
            'sitemap' => array_values($indexStatus['sitemap'] ?? []),
            'raw' => $raw,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSitemaps(): array
    {
        $siteUrl = rawurlencode($this->property());
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get("https://www.googleapis.com/webmasters/v3/sites/{$siteUrl}/sitemaps");

        if (! $response->successful()) {
            throw new RuntimeException(
                'List sitemaps failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        return array_values($response->json('sitemap') ?? []);
    }

    public function submitSitemap(string $sitemapUrl): void
    {
        $siteUrl = rawurlencode($this->property());
        $feedpath = rawurlencode($sitemapUrl);
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->put("https://www.googleapis.com/webmasters/v3/sites/{$siteUrl}/sitemaps/{$feedpath}");

        if (! $response->successful()) {
            throw new RuntimeException(
                'Submit sitemap failed: HTTP '.$response->status().' '.$response->body()
            );
        }
    }

    private function accessToken(): string
    {
        $cacheKey = 'gsc_access_token:'.md5($this->credentials()['client_email'] ?? 'default');

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $creds = $this->credentials();
        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $jwtHeader.'.'.$jwtClaim;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Failed to sign Google service account JWT.');
        }

        $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google OAuth token exchange failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        $token = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        if ($token === '') {
            throw new RuntimeException('Google OAuth response missing access_token.');
        }

        Cache::put($cacheKey, $token, max(60, $expiresIn - 60));

        return $token;
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function credentials(): array
    {
        $raw = (string) config('services.google_search_console.credentials', '');
        if ($raw === '') {
            throw new RuntimeException(
                'GOOGLE_SERVICE_ACCOUNT_JSON is not set (file path or base64/raw JSON).'
            );
        }

        if (is_file($raw)) {
            $json = file_get_contents($raw);
            if ($json === false) {
                throw new RuntimeException('Unable to read service account JSON at '.$raw);
            }
        } else {
            $decoded = base64_decode($raw, true);
            $json = ($decoded !== false && str_starts_with(ltrim($decoded), '{')) ? $decoded : $raw;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($json, true);
        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            throw new RuntimeException('Invalid Google service account JSON (need client_email and private_key).');
        }

        return [
            'client_email' => (string) $data['client_email'],
            'private_key' => (string) $data['private_key'],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
