<?php

namespace App\Services\Marketing;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TrafficAttributionService
{
    public const COOKIE = 'bilskyen_attr';

    public const SESSION_KEY = 'traffic_attribution';

    public const SESSION_ID_KEY = 'funnel_session_id';

    public const COOKIE_MINUTES = 90 * 24 * 60;

    public const SOURCE_META = 'meta';

    public const SOURCE_OTHER = 'other';

    /**
     * @var list<string>
     */
    private const META_SOURCES = [
        'facebook', 'fb', 'ig', 'instagram', 'meta', 'an', 'fbads', 'facebookads',
    ];

    /**
     * Capture inbound Meta/UTM signals, persist first-touch (cookie) and last-touch (session).
     *
     * @return array{
     *     session_id: string,
     *     traffic_source: string,
     *     utm_source: ?string,
     *     utm_medium: ?string,
     *     utm_campaign: ?string,
     *     utm_content: ?string,
     *     utm_term: ?string,
     *     fbclid: ?string,
     *     gclid: ?string,
     *     referrer_url: ?string
     * }
     */
    public function capture(Request $request): array
    {
        $incoming = $this->fromRequest($request);
        $firstTouch = $this->decodeCookie($request);
        if (! is_array($firstTouch) && $request->hasSession()) {
            $firstTouch = $request->session()->get(self::SESSION_KEY);
        }
        $hasIncoming = $this->hasAttribution($incoming);

        if ($hasIncoming) {
            $lastTouch = $incoming;
            if (! is_array($firstTouch) || ! $this->hasAttribution($firstTouch)) {
                $firstTouch = $incoming;
            }
        } else {
            $lastTouch = is_array($firstTouch) ? $firstTouch : $this->emptySnapshot();
            $firstTouch = $lastTouch;
        }

        $sessionId = $this->sessionId($request);
        $lastTouch['session_id'] = $sessionId;
        $firstTouch['session_id'] = $firstTouch['session_id'] ?? $sessionId;

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $lastTouch);
            $request->session()->put(self::SESSION_ID_KEY, $sessionId);
        }

        if ($hasIncoming || ! $request->cookies->has(self::COOKIE)) {
            cookie()->queue($this->makeCookie($firstTouch));
        }

        return $lastTouch;
    }

    /**
     * Last-touch snapshot for the current visit (session, then cookie).
     *
     * @return array<string, mixed>
     */
    public function lastTouch(Request $request): array
    {
        $fromSession = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if (is_array($fromSession) && $this->hasAttribution($fromSession)) {
            $fromSession['session_id'] = $this->sessionId($request);

            return $fromSession;
        }

        $fromCookie = $this->decodeCookie($request);
        if (is_array($fromCookie)) {
            $fromCookie['session_id'] = $this->sessionId($request);

            return $fromCookie;
        }

        return array_merge($this->emptySnapshot(), ['session_id' => $this->sessionId($request)]);
    }

    /**
     * Columns to stamp on a new lead.
     *
     * @return array{utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, referrer_url: ?string}
     */
    public function leadAttributes(Request $request): array
    {
        $touch = $this->lastTouch($request);

        return [
            'utm_source' => $this->limit($touch['utm_source'] ?? null, 191),
            'utm_medium' => $this->limit($touch['utm_medium'] ?? null, 191),
            'utm_campaign' => $this->limit($touch['utm_campaign'] ?? null, 191),
            'referrer_url' => $this->limit($touch['referrer_url'] ?? null, 512),
            'traffic_source' => $this->classify(
                $touch['utm_source'] ?? null,
                $touch['fbclid'] ?? null,
                $touch['referrer_url'] ?? null
            ),
        ];
    }

    /**
     * @return array{session_id: string, traffic_source: string, utm_source: ?string, utm_campaign: ?string}
     */
    public function viewAttributes(Request $request): array
    {
        $touch = $this->lastTouch($request);

        return [
            'session_id' => $this->sessionId($request),
            'traffic_source' => (string) ($touch['traffic_source'] ?? self::SOURCE_OTHER),
            'utm_source' => $this->limit($touch['utm_source'] ?? null, 191),
            'utm_campaign' => $this->limit($touch['utm_campaign'] ?? null, 191),
        ];
    }

    public function sessionId(Request $request): string
    {
        if ($request->hasSession()) {
            $existing = $request->session()->get(self::SESSION_ID_KEY);
            if (is_string($existing) && $existing !== '') {
                return $existing;
            }

            $id = $request->session()->getId();
            if (is_string($id) && $id !== '') {
                $request->session()->put(self::SESSION_ID_KEY, $id);

                return $id;
            }
        }

        $fromCookie = $this->decodeCookie($request);
        if (is_array($fromCookie) && ! empty($fromCookie['session_id'])) {
            return (string) $fromCookie['session_id'];
        }

        return Str::lower(Str::uuid()->toString());
    }

    public function isMeta(?string $trafficSource): bool
    {
        return $trafficSource === self::SOURCE_META;
    }

    /**
     * @return array<string, mixed>
     */
    public function fromRequest(Request $request): array
    {
        $utmSource = $this->queryString($request, 'utm_source');
        $fbclid = $this->queryString($request, 'fbclid');
        $gclid = $this->queryString($request, 'gclid');
        $referrer = $this->limit($request->headers->get('referer'), 512);

        return [
            'session_id' => '',
            'traffic_source' => $this->classify($utmSource, $fbclid, $referrer),
            'utm_source' => $utmSource,
            'utm_medium' => $this->queryString($request, 'utm_medium'),
            'utm_campaign' => $this->queryString($request, 'utm_campaign'),
            'utm_content' => $this->queryString($request, 'utm_content'),
            'utm_term' => $this->queryString($request, 'utm_term'),
            'fbclid' => $this->limit($fbclid, 255),
            'gclid' => $this->limit($gclid, 255),
            'referrer_url' => $referrer,
        ];
    }

    public function classify(?string $utmSource, ?string $fbclid, ?string $referrer): string
    {
        if (is_string($fbclid) && $fbclid !== '') {
            return self::SOURCE_META;
        }

        $source = strtolower(trim((string) $utmSource));
        if (in_array($source, self::META_SOURCES, true)) {
            return self::SOURCE_META;
        }

        $host = strtolower((string) parse_url((string) $referrer, PHP_URL_HOST));
        if ($host !== '' && (
            str_contains($host, 'facebook.')
            || str_contains($host, 'instagram.')
            || str_contains($host, 'fb.')
            || $host === 'l.facebook.com'
            || $host === 'lm.facebook.com'
            || $host === 'm.facebook.com'
        )) {
            return self::SOURCE_META;
        }

        return self::SOURCE_OTHER;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hasAttribution(array $payload): bool
    {
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'] as $key) {
            if (! empty($payload[$key])) {
                return true;
            }
        }

        $referrer = strtolower((string) ($payload['referrer_url'] ?? ''));
        if ($referrer !== '' && (
            str_contains($referrer, 'facebook.')
            || str_contains($referrer, 'instagram.')
        )) {
            return true;
        }

        return ($payload['traffic_source'] ?? '') === self::SOURCE_META;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySnapshot(): array
    {
        return [
            'session_id' => '',
            'traffic_source' => self::SOURCE_OTHER,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_content' => null,
            'utm_term' => null,
            'fbclid' => null,
            'gclid' => null,
            'referrer_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeCookie(Request $request): ?array
    {
        $raw = $request->cookies->get(self::COOKIE);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function makeCookie(array $payload): Cookie
    {
        return cookie(
            self::COOKIE,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            self::COOKIE_MINUTES,
            '/',
            null,
            $this->secureCookies(),
            true,
            false,
            'lax'
        );
    }

    private function secureCookies(): bool
    {
        return (bool) config('session.secure', false);
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $this->limit($value, 191);
    }

    private function limit(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
