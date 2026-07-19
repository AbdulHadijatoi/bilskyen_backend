<?php

namespace App\Services\VehicleImport\Bilbasen;

/**
 * Validates Bilbasen listing URLs and extracts the listing id.
 */
class BilbasenUrlValidator
{
    private const ALLOWED_HOSTS = ['bilbasen.dk', 'www.bilbasen.dk'];

    /**
     * @return array{url: string, listing_id: string, host: string}
     *
     * @throws \InvalidArgumentException
     */
    public function validate(string $url): array
    {
        $url = trim($url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(__('messages.api.bilbasen_import_invalid_url'));
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($scheme !== 'https') {
            throw new \InvalidArgumentException(__('messages.api.bilbasen_import_invalid_url'));
        }

        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new \InvalidArgumentException(__('messages.api.bilbasen_import_host_not_allowed'));
        }

        if (! preg_match('#^/(brugt|ny)/bil/[^/]+/[^/]+/[^/]+/(\d+)/?$#i', $path, $matches)
            && ! preg_match('#^/(brugt|ny)/bil/.+/(\d+)/?$#i', $path, $matches)) {
            throw new \InvalidArgumentException(__('messages.api.bilbasen_import_invalid_listing_url'));
        }

        $listingId = $matches[2];

        $normalized = 'https://'.$host.rtrim($path, '/');

        return [
            'url' => $normalized,
            'listing_id' => $listingId,
            'host' => $host,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedHosts(): array
    {
        return self::ALLOWED_HOSTS;
    }
}
