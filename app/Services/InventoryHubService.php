<?php

namespace App\Services;

use App\Models\DmrBrand;
use App\Models\DmrDriveEnergy;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InventoryHubService
{
    public const MIN_VEHICLES_FOR_INDEX = 3;

    public const VOLKSWAGEN_SLUG = 'volkswagen';

    private const BRAND_ALIASES = [
        'vw' => self::VOLKSWAGEN_SLUG,
    ];

    private const ALLOWED_BRAND_SLUGS = [
        self::VOLKSWAGEN_SLUG,
    ];

    public function __construct(
        private VehicleService $vehicleService,
    ) {}

    public static function robotsForCount(int $count): string
    {
        return $count >= self::MIN_VEHICLES_FOR_INDEX ? 'index, follow' : 'noindex, follow';
    }

    public function isIndexable(int $count): bool
    {
        return $count >= self::MIN_VEHICLES_FOR_INDEX;
    }

    public function canonicalBrandSlug(string $slug): ?string
    {
        $normalized = Str::slug($slug);
        $canonical = self::BRAND_ALIASES[$normalized] ?? $normalized;

        return in_array($canonical, self::ALLOWED_BRAND_SLUGS, true) ? $canonical : null;
    }

    public function electricFuelTypeId(): ?int
    {
        return Cache::remember('inventory_hub.el_fuel_id', 3600, function () {
            $id = DmrDriveEnergy::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) = ?', ['el'])
                        ->orWhereRaw('LOWER(name) = ?', ['electric']);
                })
                ->value('id');

            return $id ? (int) $id : null;
        });
    }

    public function resolveBrand(string $slug): ?DmrBrand
    {
        $canonical = $this->canonicalBrandSlug($slug);
        if ($canonical === null) {
            return null;
        }

        foreach (DmrBrand::query()->get(['id', 'name']) as $brand) {
            if (Str::slug((string) $brand->name) === $canonical) {
                return $brand;
            }
        }

        return null;
    }

    public function countForFilters(array $filters): int
    {
        $key = 'inventory_hub.count.'.md5((string) json_encode($filters));

        return (int) Cache::remember($key, 3600, function () use ($filters) {
            return $this->vehicleService->countPublicVehiclesWithFilters($filters);
        });
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function listingsForFilters(array $filters, int $limit = 24): Collection
    {
        $page = $this->vehicleService->getPublicVehiclesWithAdvancedFilters([
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'dealer',
            'salesType',
            'brand',
            'model',
        ], $filters, $limit, 1);

        return $page->getCollection();
    }

    public function isElectricIndexable(): bool
    {
        $fuelId = $this->electricFuelTypeId();
        if (! $fuelId) {
            return false;
        }

        return $this->isIndexable($this->countForFilters(['fuel_type_id' => [$fuelId]]));
    }

    public function isBrandIndexable(string $slug): bool
    {
        $brand = $this->resolveBrand($slug);
        if (! $brand) {
            return false;
        }

        return $this->isIndexable($this->countForFilters(['brand_id' => [$brand->id]]));
    }

    public function listingFilterUrl(string $param, int $id): string
    {
        return route('vehicles').'?'.$param.'[]='.$id;
    }
}
