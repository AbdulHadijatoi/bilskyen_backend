<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public const BUFFER_KEY = 'indexnow:url_set';

    public const BATCH_SIZE = 100;

    public static function isValidKey(string $key): bool
    {
        $key = trim($key);
        $len = strlen($key);

        return $len >= 8 && $len <= 128 && preg_match('/^[0-9a-fA-F-]+$/', $key) === 1;
    }

    public function isEnabled(): bool
    {
        return self::isValidKey($this->key());
    }

    public function key(): string
    {
        return trim((string) config('services.indexnow.key', ''));
    }

    public function host(): string
    {
        $host = trim((string) config('services.indexnow.host', 'bilskyen.dk'));

        return $host !== '' ? $host : 'bilskyen.dk';
    }

    public function keyLocation(): string
    {
        return 'https://'.$this->host().'/'.$this->key().'.txt';
    }

    public function queue(string $url): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $normalized = $this->httpsUrl($url);
        if ($normalized === null) {
            return;
        }

        $urls = Cache::get(self::BUFFER_KEY, []);
        if (! is_array($urls)) {
            $urls = [];
        }
        $urls[$normalized] = true;
        Cache::put(self::BUFFER_KEY, $urls, 86400);
    }

    /**
     * @return list<string>
     */
    public function queuedUrls(): array
    {
        $urls = Cache::get(self::BUFFER_KEY, []);
        if (! is_array($urls)) {
            return [];
        }

        return array_keys($urls);
    }

    /**
     * POST queued HTTPS URLs. Failures are logged and URLs are re-queued.
     *
     * @return int Number of URLs accepted by the endpoint
     */
    public function flush(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $urls = $this->queuedUrls();
        Cache::forget(self::BUFFER_KEY);
        if ($urls === []) {
            return 0;
        }

        $sent = 0;
        foreach (array_chunk($urls, self::BATCH_SIZE) as $chunk) {
            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->asJson()
                    ->post(self::ENDPOINT, [
                        'host' => $this->host(),
                        'key' => $this->key(),
                        'keyLocation' => $this->keyLocation(),
                        'urlList' => array_values($chunk),
                    ]);

                if ($response->successful() || $response->status() === 202) {
                    $sent += count($chunk);

                    continue;
                }

                Log::warning('IndexNow flush rejected', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'count' => count($chunk),
                ]);
                $this->requeue($chunk);
            } catch (\Throwable $e) {
                Log::warning('IndexNow flush failed: '.$e->getMessage(), [
                    'count' => count($chunk),
                ]);
                $this->requeue($chunk);
            }
        }

        return $sent;
    }

    /**
     * @param  list<string>  $urls
     */
    private function requeue(array $urls): void
    {
        foreach ($urls as $url) {
            $this->queue($url);
        }
    }

    private function httpsUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$this->host().'/'.ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return 'https://'.$parts['host'].$path;
    }
}
