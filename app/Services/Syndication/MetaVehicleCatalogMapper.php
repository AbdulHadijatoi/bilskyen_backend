<?php

namespace App\Services\Syndication;

use App\Models\Dealer;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MetaVehicleCatalogMapper
{
    public const MAX_IMAGES = 20;

    /** @var array<string, Location|null> */
    private array $locationsByPostcode = [];

    /**
     * Stable CSV column order for Meta automotive catalogs.
     *
     * @return list<string>
     */
    public function headers(): array
    {
        $headers = [
            'vehicle_id',
            'title',
            'description',
            'url',
            'make',
            'model',
            'year',
            'mileage.value',
            'mileage.unit',
            'price',
            'availability',
            'condition',
            'body_style',
            'exterior_color',
            'state_of_vehicle',
            'vin',
            'fuel_type',
            'transmission',
            'vehicle_type',
            'address.addr1',
            'address.city',
            'address.region',
            'address.postal_code',
            'address.country',
            'dealer_id',
            'dealer_name',
        ];

        for ($i = 0; $i < self::MAX_IMAGES; $i++) {
            $headers[] = "image[{$i}].url";
        }

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    public function toRow(Vehicle $vehicle): array
    {
        $images = $this->imageUrls($vehicle);
        $dealer = $vehicle->relationLoaded('dealer') ? $vehicle->dealer : $vehicle->dealer()->first();
        $year = $this->catalogYear($vehicle);
        $mileage = (int) round((float) ($vehicle->km_driven ?? 0));
        $state = $mileage <= 50 ? 'New' : 'Used';
        $address = $this->resolveAddress($vehicle, $dealer);

        $row = [
            'vehicle_id' => (string) $vehicle->id,
            'title' => (string) ($vehicle->title ?? ''),
            'description' => $this->plainDescription($vehicle, $year, $mileage),
            'url' => self::forceHttps(route('vehicle.detail', $vehicle)),
            'make' => (string) ($vehicle->brand?->name ?? ''),
            'model' => (string) ($vehicle->model?->name ?? ''),
            'year' => $year,
            'mileage.value' => (string) $mileage,
            'mileage.unit' => 'KM',
            'price' => number_format((float) ($vehicle->price ?? 0), 2, '.', '').' DKK',
            'availability' => 'IN_STOCK',
            'condition' => $this->mapCondition($vehicle->condition?->name),
            'body_style' => $this->mapBodyStyle($vehicle->bodyType?->name),
            'exterior_color' => (string) ($vehicle->colour?->name ?? 'Other'),
            'state_of_vehicle' => $state,
            'vin' => (string) ($vehicle->vin ?? ''),
            'fuel_type' => $this->mapFuelType($vehicle->fuelType?->name),
            'transmission' => $this->mapTransmission($vehicle->gearType?->name),
            'vehicle_type' => 'CAR_TRUCK',
            'address.addr1' => $address['street'],
            'address.city' => $address['city'],
            'address.region' => $address['region'],
            'address.postal_code' => $address['postcode'],
            'address.country' => $address['country'],
            'dealer_id' => $dealer ? (string) $dealer->id : '',
            'dealer_name' => (string) ($dealer?->slug ?? ''),
        ];

        for ($i = 0; $i < self::MAX_IMAGES; $i++) {
            $row["image[{$i}].url"] = $images[$i] ?? '';
        }

        return $row;
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    public function toCsv(Collection $vehicles): string
    {
        $headers = $this->headers();
        $lines = [implode(',', array_map(fn ($h) => $this->csvEscape($h), $headers))];

        foreach ($vehicles as $vehicle) {
            $row = $this->toRow($vehicle);
            if (! $this->isCatalogEligible($row)) {
                continue;
            }
            $lines[] = implode(',', array_map(
                fn ($key) => $this->csvEscape($row[$key] ?? ''),
                $headers
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $row
     */
    public function isCatalogEligible(array $row): bool
    {
        return ($row['year'] ?? '') !== '' && ($row['image[0].url'] ?? '') !== '';
    }

    /**
     * @return array{
     *   row: array<string, string>,
     *   readiness: list<array{key: string, ok: bool, label: string}>,
     *   ready: bool
     * }
     */
    public function preview(Vehicle $vehicle): array
    {
        $row = $this->toRow($vehicle);
        $checks = [
            ['key' => 'title', 'ok' => $row['title'] !== '', 'label' => 'title'],
            ['key' => 'make', 'ok' => $row['make'] !== '', 'label' => 'make'],
            ['key' => 'model', 'ok' => $row['model'] !== '', 'label' => 'model'],
            ['key' => 'year', 'ok' => $row['year'] !== '', 'label' => 'year'],
            ['key' => 'price', 'ok' => (float) ($vehicle->price ?? 0) > 0, 'label' => 'price'],
            ['key' => 'image', 'ok' => ($row['image[0].url'] ?? '') !== '', 'label' => 'image'],
            ['key' => 'url', 'ok' => $row['url'] !== '', 'label' => 'url'],
            ['key' => 'availability', 'ok' => ($row['availability'] ?? '') === 'IN_STOCK', 'label' => 'availability'],
            ['key' => 'condition', 'ok' => ($row['condition'] ?? '') !== '', 'label' => 'condition'],
            ['key' => 'body_style', 'ok' => $row['body_style'] !== '' && $row['body_style'] !== 'OTHER', 'label' => 'body_style'],
            ['key' => 'exterior_color', 'ok' => $row['exterior_color'] !== '' && $row['exterior_color'] !== 'Other', 'label' => 'exterior_color'],
            ['key' => 'mileage', 'ok' => true, 'label' => 'mileage'],
            ['key' => 'city', 'ok' => $row['address.city'] !== '', 'label' => 'city'],
            ['key' => 'street_address', 'ok' => $row['address.addr1'] !== '', 'label' => 'street_address'],
            ['key' => 'region', 'ok' => $row['address.region'] !== '', 'label' => 'region'],
            ['key' => 'address', 'ok' => $row['address.city'] !== '' && $row['address.addr1'] !== '' && $row['address.region'] !== '', 'label' => 'address'],
            ['key' => 'marketplace_images', 'ok' => ($row['image[1].url'] ?? '') !== '', 'label' => 'marketplace_images'],
        ];

        $requiredKeys = ['title', 'make', 'model', 'year', 'price', 'image', 'url', 'city', 'street_address', 'region'];
        $ready = collect($checks)
            ->filter(fn ($c) => in_array($c['key'], $requiredKeys, true))
            ->every(fn ($c) => $c['ok']);

        return [
            'row' => $row,
            'readiness' => $checks,
            'ready' => $ready,
        ];
    }

    /**
     * Eager-load relations needed for catalog rows.
     *
     * @return list<string>
     */
    public function eagerLoads(): array
    {
        return ['images', 'brand', 'model', 'fuelType', 'gearType', 'bodyType', 'colour', 'condition', 'dealer.marketplaceCity'];
    }

    public static function forceHttps(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (str_starts_with(strtolower($url), 'http://')) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    public function mapCondition(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        if ($n === '') {
            return 'GOOD';
        }

        if (str_contains($n, 'excellent') || str_contains($n, 'fremrag') || str_contains($n, 'udmærk') || str_contains($n, 'udmaerk')) {
            return 'EXCELLENT';
        }
        if (str_contains($n, 'fair') || str_contains($n, 'rimelig') || str_contains($n, 'middel')) {
            return 'FAIR';
        }
        if (str_contains($n, 'poor') || str_contains($n, 'dårlig') || str_contains($n, 'daarlig') || str_contains($n, 'slidt')) {
            return 'POOR';
        }
        if (str_contains($n, 'new') || $n === 'ny' || str_starts_with($n, 'ny ')) {
            return 'EXCELLENT';
        }

        return 'GOOD';
    }

    public function mapBodyStyle(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        if ($n === '') {
            return 'OTHER';
        }

        $map = [
            'sedan' => 'SEDAN',
            'saloon' => 'SEDAN',
            'hatchback' => 'HATCHBACK',
            'stationcar' => 'WAGON',
            'station wagon' => 'WAGON',
            'wagon' => 'WAGON',
            'estate' => 'WAGON',
            'suv' => 'SUV',
            'crossover' => 'CROSSOVER',
            'coupe' => 'COUPE',
            'coupé' => 'COUPE',
            'convertible' => 'CONVERTIBLE',
            'cabriolet' => 'CONVERTIBLE',
            'cabrio' => 'CONVERTIBLE',
            'van' => 'VAN',
            'minivan' => 'MINIVAN',
            'mpv' => 'MINIVAN',
            'pickup' => 'TRUCK',
            'truck' => 'TRUCK',
            'other' => 'OTHER',
        ];

        foreach ($map as $needle => $value) {
            if (str_contains($n, $needle)) {
                return $value;
            }
        }

        return 'OTHER';
    }

    public function mapFuelType(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        if ($n === '') {
            return 'OTHER';
        }

        if (str_contains($n, 'plugin') || str_contains($n, 'plug-in')) {
            return 'PLUGIN_HYBRID';
        }
        if (str_contains($n, 'hybrid')) {
            return 'HYBRID';
        }
        if (str_contains($n, 'electric') || $n === 'el' || str_contains($n, 'ev')) {
            return 'ELECTRIC';
        }
        if (str_contains($n, 'diesel')) {
            return 'DIESEL';
        }
        if (str_contains($n, 'petrol') || str_contains($n, 'benzin') || str_contains($n, 'gasoline') || str_contains($n, 'gas')) {
            return 'GASOLINE';
        }
        if (str_contains($n, 'flex')) {
            return 'FLEX';
        }

        return 'OTHER';
    }

    public function mapTransmission(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        if ($n === '') {
            return 'OTHER';
        }
        if (str_contains($n, 'auto') || str_contains($n, 'dsg') || str_contains($n, 'cvt')) {
            return 'AUTOMATIC';
        }
        if (str_contains($n, 'manual') || str_contains($n, 'manuel')) {
            return 'MANUAL';
        }

        return 'OTHER';
    }

    /**
     * @return array{street: string, city: string, postcode: string, region: string, country: string}
     */
    private function resolveAddress(Vehicle $vehicle, ?Dealer $dealer): array
    {
        $street = trim((string) ($dealer?->address ?: $vehicle->address ?: ''));
        $postcode = trim((string) ($dealer?->postcode ?: $vehicle->postcode ?: ''));
        $city = trim((string) ($dealer?->city ?: ''));
        $location = $postcode !== '' ? $this->locationForPostcode($postcode) : null;

        if ($city === '') {
            $city = trim((string) ($location?->city ?? ''));
        }

        $region = trim((string) ($dealer?->marketplaceCity?->region ?? ''));
        if ($region === '') {
            $region = trim((string) ($location?->region ?? ''));
        }
        if ($region === '' && $city !== '') {
            $region = $city;
        }

        $country = strtoupper((string) ($dealer?->country_code ?: $location?->country_code ?: 'DK'));
        if ($country === '') {
            $country = 'DK';
        }

        return [
            'street' => $street,
            'city' => $city,
            'postcode' => $postcode,
            'region' => $region,
            'country' => $country,
        ];
    }

    private function locationForPostcode(string $postcode): ?Location
    {
        $key = preg_replace('/\s+/', '', $postcode) ?? $postcode;
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->locationsByPostcode)) {
            return $this->locationsByPostcode[$key];
        }

        $location = null;
        try {
            if (Schema::hasTable('locations')) {
                $location = Location::query()
                    ->where(function ($query) use ($postcode, $key) {
                        $query->where('postcode', $postcode)->orWhere('postcode', $key);
                    })
                    ->first();
            }
        } catch (\Throwable) {
            $location = null;
        }

        $this->locationsByPostcode[$key] = $location;

        return $location;
    }

    private function catalogYear(Vehicle $vehicle): string
    {
        $year = $vehicle->model_year ?? $vehicle->first_registration_year;
        if ($year) {
            return (string) (int) $year;
        }

        $date = $vehicle->first_registration_date;
        if ($date) {
            return (string) $date->year;
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function imageUrls(Vehicle $vehicle): array
    {
        if (! $vehicle->relationLoaded('images')) {
            $vehicle->load(['images' => fn ($q) => $q->orderBy('sort_order')]);
        }

        return $vehicle->images
            ->sortBy('sort_order')
            ->map(fn ($img) => $this->publicImageUrl($img))
            ->filter()
            ->take(self::MAX_IMAGES)
            ->values()
            ->all();
    }

    private function publicImageUrl(mixed $img): ?string
    {
        $url = trim((string) ($img->image_url ?? ''));
        if ($url === '') {
            $path = trim((string) ($img->image_path ?? ''));
            if ($path === '' || str_contains($path, 'placeholder-vehicle')) {
                return null;
            }
            $url = asset('storage/'.$path);
        }

        $url = self::forceHttps($url);
        if ($url === '' || str_contains($url, 'placeholder-vehicle')) {
            return null;
        }

        return $url;
    }

    private function plainDescription(Vehicle $vehicle, string $year, int $mileage): string
    {
        $text = strip_tags((string) ($vehicle->description ?? ''));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);
        if ($text !== '') {
            return mb_substr($text, 0, 5000);
        }

        $parts = array_values(array_filter([
            trim((string) ($vehicle->title ?? '')),
            $year !== '' ? $year : null,
            $mileage > 0 ? number_format($mileage, 0, ',', '.').' km' : null,
            (float) ($vehicle->price ?? 0) > 0
                ? number_format((float) $vehicle->price, 0, ',', '.').' kr'
                : null,
        ]));

        if ($parts === []) {
            return '';
        }

        return mb_substr(implode(', ', $parts).'. Brugt bil til salg på Bilskyen.', 0, 5000);
    }

    private function csvEscape(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }
}
