<?php

namespace App\Support;

class RemoteUrlGuard
{
    /**
     * Reject URLs that resolve to private, reserved, or loopback addresses (SSRF mitigation).
     *
     * @throws \InvalidArgumentException
     */
    public static function assertPublicHttpUrl(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_invalid_url'));
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_invalid_url'));
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_invalid_url'));
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! self::isPublicIp($host)) {
                throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
            }

            return;
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if ($records === false || $records === []) {
            $resolved = @gethostbynamel($host);
            if ($resolved === false || $resolved === []) {
                throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
            }
            foreach ($resolved as $ip) {
                if (! self::isPublicIp($ip)) {
                    throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
                }
            }

            return;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip !== null && ! self::isPublicIp($ip)) {
                throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
            }
        }
    }

    public static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
