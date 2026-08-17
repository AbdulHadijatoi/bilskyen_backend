<?php

namespace App\Services\Syndication;

use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MetaVehicleCatalogMapper
{
    public const MAX_IMAGES = 20;

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
        $year = $vehicle->model_year ?? $vehicle->first_registration_year;
        $mileage = (int) round((float) ($vehicle->km_driven ?? 0));
        $state = $mileage <= 50 ? 'New' : 'Used';

        $row = [
            'vehicle_id' => (string) $vehicle->id,
            'title' => (string) ($vehicle->title ?? ''),
            'description' => $this->plainDescription($vehicle),
            'url' => self::forceHttps(route('vehicle.detail', $vehicle)),
            'make' => (string) ($vehicle->brand?->name ?? ''),
            'model' => (string) ($vehicle->model?->name ?? ''),
            'year' => $year ? (string) $year : '',
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
            'address.addr1' => (string) ($dealer?->address ?? ''),
            'address.city' => (string) ($dealer?->city ?? ''),
            'address.region' => '',
            'address.postal_code' => (string) ($dealer?->postcode ?? ''),
            'address.country' => strtoupper((string) ($dealer?->country_code ?: 'DK')),
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
            $lines[] = implode(',', array_map(
                fn ($key) => $this->csvEscape($row[$key] ?? ''),
                $headers
            ));
        }

        return implode("\n", $lines);
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
            ['key' => 'address', 'ok' => $row['address.city'] !== '' || $row['address.addr1'] !== '', 'label' => 'address'],
            ['key' => 'marketplace_images', 'ok' => ($row['image[1].url'] ?? '') !== '', 'label' => 'marketplace_images'],
        ];

        $requiredKeys = ['title', 'make', 'model', 'year', 'price', 'image', 'url'];
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
        return ['images', 'brand', 'model', 'fuelType', 'gearType', 'bodyType', 'colour', 'condition', 'dealer'];
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
     * @return list<string>
     */
    private function imageUrls(Vehicle $vehicle): array
    {
        if (! $vehicle->relationLoaded('images')) {
            $vehicle->load(['images' => fn ($q) => $q->orderBy('sort_order')]);
        }

        return $vehicle->images
            ->sortBy('sort_order')
            ->take(self::MAX_IMAGES)
            ->map(fn ($img) => self::forceHttps(url(Storage::disk('public')->url($img->image_path))))
            ->values()
            ->all();
    }

    private function plainDescription(Vehicle $vehicle): string
    {
        $text = strip_tags((string) ($vehicle->description ?? ''));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return mb_substr(trim($text), 0, 5000);
    }

    private function csvEscape(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }
}
