<?php

namespace App\Services\VehicleImport\Bilbasen;

/**
 * Parses Bilbasen listing HTML into a normalized vehicle DTO.
 */
class BilbasenListingParser
{
    /**
     * @return array{
     *   external_listing_id: string,
     *   source_url: string,
     *   registration: ?string,
     *   vin: ?string,
     *   price: ?float,
     *   mileage: ?int,
     *   title: ?string,
     *   description: ?string,
     *   brand: ?string,
     *   model: ?string,
     *   variant: ?string,
     *   fuel_type: ?string,
     *   year: ?int,
     *   image_urls: list<string>,
     *   blocked: bool,
     *   warnings: list<string>
     * }
     */
    public function parse(string $html, string $sourceUrl, string $listingId): array
    {
        $warnings = [];
        $blocked = $this->isChallengePage($html);

        $base = [
            'external_listing_id' => $listingId,
            'source_url' => $sourceUrl,
            'registration' => null,
            'vin' => null,
            'price' => null,
            'mileage' => null,
            'title' => null,
            'description' => null,
            'brand' => null,
            'model' => null,
            'variant' => null,
            'fuel_type' => null,
            'year' => null,
            'image_urls' => [],
            'blocked' => $blocked,
            'warnings' => $warnings,
        ];

        if ($blocked) {
            $base['warnings'][] = __('messages.api.bilbasen_import_blocked');

            return $base;
        }

        $fromJsonLd = $this->parseJsonLd($html);
        $fromNextData = $this->parseNextData($html);
        $fromDom = $this->parseDomFallbacks($html);
        $fromUrl = $this->parseUrlPath($sourceUrl);

        $merged = $this->mergeLayers($base, $fromJsonLd, $fromNextData, $fromDom, $fromUrl);
        $merged['image_urls'] = $this->normalizeImageUrls($merged['image_urls'] ?? []);
        $merged['registration'] = $this->normalizeRegistration($merged['registration'] ?? null)
            ?? $this->extractRegistrationFromText(($merged['description'] ?? '').' '.($merged['title'] ?? '').' '.$html);
        $merged['vin'] = $this->normalizeVin($merged['vin'] ?? null);

        if ($merged['price'] === null) {
            $merged['warnings'][] = __('messages.api.bilbasen_import_price_missing');
        }

        if ($merged['registration'] === null && $merged['vin'] === null) {
            $merged['warnings'][] = __('messages.api.bilbasen_import_identity_missing');
        }

        if ($merged['image_urls'] === []) {
            $merged['warnings'][] = __('messages.api.bilbasen_import_images_missing');
        }

        return $merged;
    }

    public function isChallengePage(string $html): bool
    {
        $lower = strtolower($html);

        return str_contains($lower, 'javascript is disabled')
            || str_contains($lower, 'verify that you\'re not a robot')
            || str_contains($lower, 'verify that you are not a robot')
            || str_contains($lower, 'cf-browser-verification')
            || str_contains($lower, 'challenge-platform')
            || (str_contains($lower, 'captcha') && str_contains($lower, 'bilbasen'));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonLd(string $html): array
    {
        $result = [];
        if (! preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return $result;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim($json)), true);
            if (! is_array($decoded)) {
                continue;
            }

            $nodes = $this->flattenJsonLdNodes($decoded);
            foreach ($nodes as $node) {
                $type = $node['@type'] ?? null;
                $types = is_array($type) ? $type : [$type];
                $types = array_map(static fn ($t) => is_string($t) ? strtolower($t) : '', $types);

                if (array_intersect($types, ['car', 'vehicle', 'product'])) {
                    $result = array_merge($result, $this->mapSchemaVehicle($node));
                }

                if (array_intersect($types, ['offer'])) {
                    if (isset($node['price']) && is_numeric($node['price'])) {
                        $result['price'] = (float) $node['price'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return list<array<string, mixed>>
     */
    private function flattenJsonLdNodes(array $decoded): array
    {
        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            return array_values(array_filter($decoded['@graph'], 'is_array'));
        }

        if (array_is_list($decoded)) {
            return array_values(array_filter($decoded, 'is_array'));
        }

        return [$decoded];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function mapSchemaVehicle(array $node): array
    {
        $result = [];

        if (! empty($node['name']) && is_string($node['name'])) {
            $result['title'] = trim($node['name']);
        }
        if (! empty($node['description']) && is_string($node['description'])) {
            $result['description'] = trim($node['description']);
        }
        if (! empty($node['brand'])) {
            $result['brand'] = is_array($node['brand'])
                ? trim((string) ($node['brand']['name'] ?? ''))
                : trim((string) $node['brand']);
        }
        if (! empty($node['model']) && is_string($node['model'])) {
            $result['model'] = trim($node['model']);
        }
        if (! empty($node['vehicleModelDate']) && is_numeric($node['vehicleModelDate'])) {
            $result['year'] = (int) $node['vehicleModelDate'];
        }
        if (! empty($node['vehicleIdentificationNumber']) && is_string($node['vehicleIdentificationNumber'])) {
            $result['vin'] = trim($node['vehicleIdentificationNumber']);
        }
        if (! empty($node['mileageFromOdometer'])) {
            $mileage = $node['mileageFromOdometer'];
            if (is_array($mileage) && isset($mileage['value']) && is_numeric($mileage['value'])) {
                $result['mileage'] = (int) $mileage['value'];
            } elseif (is_numeric($mileage)) {
                $result['mileage'] = (int) $mileage;
            }
        }
        if (! empty($node['fuelType']) && is_string($node['fuelType'])) {
            $result['fuel_type'] = trim($node['fuelType']);
        }
        if (! empty($node['offers']) && is_array($node['offers'])) {
            $offer = isset($node['offers'][0]) ? $node['offers'][0] : $node['offers'];
            if (isset($offer['price']) && is_numeric($offer['price'])) {
                $result['price'] = (float) $offer['price'];
            }
        }
        if (! empty($node['image'])) {
            $images = is_array($node['image']) ? $node['image'] : [$node['image']];
            $result['image_urls'] = array_values(array_filter(array_map(static function ($img) {
                if (is_string($img)) {
                    return $img;
                }
                if (is_array($img) && isset($img['url']) && is_string($img['url'])) {
                    return $img['url'];
                }

                return null;
            }, $images)));
        }

        return array_filter($result, static fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseNextData(string $html): array
    {
        if (! preg_match('#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#is', $html, $match)) {
            return [];
        }

        $decoded = json_decode(trim($match[1]), true);
        if (! is_array($decoded)) {
            return [];
        }

        $flat = [];
        $this->walkNextData($decoded, $flat);

        $result = [];
        foreach ([
            'registrationNumber' => 'registration',
            'registration' => 'registration',
            'licensePlate' => 'registration',
            'nummerplade' => 'registration',
            'vin' => 'vin',
            'vehicleIdentificationNumber' => 'vin',
            'price' => 'price',
            'mileage' => 'mileage',
            'kilometer' => 'mileage',
            'km' => 'mileage',
            'description' => 'description',
            'make' => 'brand',
            'brand' => 'brand',
            'model' => 'model',
            'variant' => 'variant',
            'fuelType' => 'fuel_type',
            'fuel' => 'fuel_type',
            'year' => 'year',
            'modelYear' => 'year',
            'heading' => 'title',
            'title' => 'title',
        ] as $sourceKey => $targetKey) {
            if (! array_key_exists($sourceKey, $flat)) {
                continue;
            }
            $value = $flat[$sourceKey];
            if ($targetKey === 'price' && is_numeric($value)) {
                $result['price'] = (float) $value;
            } elseif ($targetKey === 'mileage' && is_numeric($value)) {
                $result['mileage'] = (int) $value;
            } elseif ($targetKey === 'year' && is_numeric($value)) {
                $result['year'] = (int) $value;
            } elseif (is_string($value) && trim($value) !== '') {
                $result[$targetKey] = trim($value);
            }
        }

        if (! empty($flat['images']) && is_array($flat['images'])) {
            $result['image_urls'] = array_values(array_filter($flat['images'], 'is_string'));
        } elseif (! empty($flat['imageUrls']) && is_array($flat['imageUrls'])) {
            $result['image_urls'] = array_values(array_filter($flat['imageUrls'], 'is_string'));
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $flat
     */
    private function walkNextData(array $node, array &$flat, int $depth = 0): void
    {
        if ($depth > 12) {
            return;
        }

        foreach ($node as $key => $value) {
            if (is_string($key) && ! array_key_exists($key, $flat) && (is_scalar($value) || $value === null)) {
                $flat[$key] = $value;
            }

            if (is_array($value)) {
                if (in_array($key, ['images', 'imageUrls', 'pictures', 'media'], true) && array_is_list($value)) {
                    $urls = [];
                    foreach ($value as $item) {
                        if (is_string($item)) {
                            $urls[] = $item;
                        } elseif (is_array($item)) {
                            foreach (['url', 'src', 'large', 'original'] as $urlKey) {
                                if (! empty($item[$urlKey]) && is_string($item[$urlKey])) {
                                    $urls[] = $item[$urlKey];
                                    break;
                                }
                            }
                        }
                    }
                    if ($urls !== []) {
                        $flat[$key] = $urls;
                    }
                }
                $this->walkNextData($value, $flat, $depth + 1);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDomFallbacks(string $html): array
    {
        $result = [];
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        $ogTitle = $this->firstMetaContent($xpath, 'og:title');
        if ($ogTitle) {
            $result['title'] = $ogTitle;
        }
        $ogDescription = $this->firstMetaContent($xpath, 'og:description');
        if ($ogDescription) {
            $result['description'] = $ogDescription;
        }
        $ogImage = $this->firstMetaContent($xpath, 'og:image');
        if ($ogImage) {
            $result['image_urls'] = [$ogImage];
        }

        $priceText = $this->firstXPathText($xpath, '//*[@data-testid="price" or contains(@class,"Price") or contains(@class,"price")]');
        if ($priceText !== null) {
            $price = $this->parseDanishNumber($priceText);
            if ($price !== null) {
                $result['price'] = $price;
            }
        }

        $kmText = $this->firstXPathText($xpath, '//*[contains(translate(., "KILOMETER", "kilometer"), "km") or contains(@data-testid,"mileage")]');
        if ($kmText !== null) {
            if (preg_match('/([\d.\s]+)\s*km/i', $kmText, $m)) {
                $km = $this->parseDanishNumber($m[1]);
                if ($km !== null) {
                    $result['mileage'] = (int) $km;
                }
            }
        }

        $imageNodes = $xpath->query('//img[@src]');
        if ($imageNodes !== false) {
            $images = $result['image_urls'] ?? [];
            foreach ($imageNodes as $img) {
                if (! $img instanceof \DOMElement) {
                    continue;
                }
                $src = trim($img->getAttribute('src'));
                if ($src !== '' && (str_contains($src, 'bilbasen') || str_contains($src, 'cloudfront') || str_contains($src, 'amazonaws'))) {
                    $images[] = $src;
                }
            }
            if ($images !== []) {
                $result['image_urls'] = $images;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseUrlPath(string $url): array
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        // /brugt/bil/{brand}/{model}/{slug}/{id}
        if (! preg_match('#^/(?:brugt|ny)/bil/([^/]+)/([^/]+)/([^/]+)/(\d+)/?#i', $path, $m)) {
            return [];
        }

        return [
            'brand' => $this->humanizeSlug($m[1]),
            'model' => $this->humanizeSlug($m[2]),
            'variant' => $this->humanizeSlug($m[3]),
            'title' => trim($this->humanizeSlug($m[1]).' '.$this->humanizeSlug($m[2]).' '.$this->humanizeSlug($m[3])),
        ];
    }

    private function humanizeSlug(string $slug): string
    {
        $slug = str_replace(['-', '_'], ' ', urldecode($slug));

        return trim(preg_replace('/\s+/', ' ', $slug) ?? $slug);
    }

    /**
     * @param  array<string, mixed>  ...$layers
     * @return array<string, mixed>
     */
    private function mergeLayers(array $base, array ...$layers): array
    {
        $merged = $base;
        foreach ($layers as $layer) {
            foreach ($layer as $key => $value) {
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
                if ($key === 'image_urls' && is_array($value)) {
                    $merged['image_urls'] = array_values(array_unique(array_merge(
                        $merged['image_urls'] ?? [],
                        $value
                    )));
                    continue;
                }
                if (($merged[$key] ?? null) === null || $merged[$key] === '' || $merged[$key] === []) {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private function normalizeImageUrls(array $urls): array
    {
        $normalized = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            if (str_starts_with($url, '//')) {
                $url = 'https:'.$url;
            }
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $normalized[] = $url;
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeRegistration(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
        if (preg_match('/^[A-Z]{2}\d{2,5}[A-Z]?$/i', $value) || preg_match('/^[A-Z]{1,3}\d{1,5}$/i', $value)) {
            return $value;
        }

        return null;
    }

    private function normalizeVin(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
        if (strlen($value) === 17 && preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function extractRegistrationFromText(string $text): ?string
    {
        if (preg_match('/\b([A-Z]{2}\s?\d{2}\s?\d{3})\b/i', $text, $m)) {
            return $this->normalizeRegistration($m[1]);
        }
        if (preg_match('/\b([A-Z]{2}\d{5})\b/i', $text, $m)) {
            return $this->normalizeRegistration($m[1]);
        }

        return null;
    }

    private function parseDanishNumber(string $text): ?float
    {
        $clean = preg_replace('/[^\d.,]/', '', $text) ?? '';
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function firstMetaContent(\DOMXPath $xpath, string $property): ?string
    {
        $nodes = $xpath->query(sprintf(
            '//meta[@property="%1$s" or @name="%1$s"]/@content',
            $property
        ));
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value !== '' ? $value : null;
    }

    private function firstXPathText(\DOMXPath $xpath, string $expression): ?string
    {
        $nodes = $xpath->query($expression);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $value = trim(preg_replace('/\s+/', ' ', (string) $nodes->item(0)?->textContent) ?? '');

        return $value !== '' ? $value : null;
    }
}
