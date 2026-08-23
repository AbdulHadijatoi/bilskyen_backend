<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DawaGeocodeService
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(?string $address, ?string $postcode): ?array
    {
        $parts = array_values(array_filter([
            trim((string) $address),
            trim((string) $postcode),
        ], fn (string $part) => $part !== ''));
        if ($parts === []) {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://api.dataforsyningen.dk/adresser', [
                    'q' => implode(', ', $parts),
                    'per_side' => 1,
                    'struktur' => 'mini',
                ]);
            if (! $response->ok()) {
                return null;
            }
            $row = $response->json()[0] ?? null;
            if (! is_array($row)) {
                return null;
            }
            $lng = $row['x'] ?? null;
            $lat = $row['y'] ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return null;
            }

            return [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        } catch (\Throwable $e) {
            Log::warning('DAWA geocode failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Postcode centroid (visueltcenter). Danish postcodes are four digits.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocodePostcode(?string $postcode): ?array
    {
        $nr = $this->normalizeDanishPostcode($postcode);
        if ($nr === null) {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://api.dataforsyningen.dk/postnumre/'.$nr, [
                    'struktur' => 'mini',
                ]);
            if (! $response->ok()) {
                return null;
            }
            $row = $response->json();
            if (! is_array($row)) {
                return null;
            }

            return $this->pointFromDawaPostcode($row);
        } catch (\Throwable $e) {
            Log::warning('DAWA postcode geocode failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Street match first, then postcode centroid.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolve(?string $address, ?string $postcode): ?array
    {
        $address = trim((string) $address);
        if ($address !== '') {
            $street = $this->geocode($address, $postcode);
            if ($street !== null) {
                return $street;
            }
        }

        return $this->geocodePostcode($postcode);
    }

    public function normalizeDanishPostcode(?string $postcode): ?string
    {
        if (preg_match('/(?<!\d)(\d{4})(?!\d)/', (string) $postcode, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{latitude: float, longitude: float}|null
     */
    private function pointFromDawaPostcode(array $row): ?array
    {
        $lng = $row['visueltcenter_x'] ?? $row['x'] ?? null;
        $lat = $row['visueltcenter_y'] ?? $row['y'] ?? null;
        if ((! is_numeric($lng) || ! is_numeric($lat)) && isset($row['visueltcenter']) && is_array($row['visueltcenter'])) {
            $lng = $row['visueltcenter'][0] ?? null;
            $lat = $row['visueltcenter'][1] ?? null;
        }
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function applyToPayload(array $payload): array
    {
        $coords = $this->resolve(
            isset($payload['address']) ? (string) $payload['address'] : null,
            isset($payload['postcode']) ? (string) $payload['postcode'] : null,
        );
        if ($coords === null) {
            return $payload;
        }
        $payload['latitude'] = $coords['latitude'];
        $payload['longitude'] = $coords['longitude'];

        return $payload;
    }
}
