<?php

namespace App\Services\VehicleImport;

use App\Constants\VehicleListStatus;
use App\Models\Category;
use App\Models\Condition;
use App\Models\DmrBodyType;
use App\Models\DmrBrand;
use App\Models\DmrColour;
use App\Models\DmrDriveEnergy;
use App\Models\DmrEmissionNorm;
use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\DmrVehicleUse;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\MeasurementNorm;
use App\Models\PriceType;
use App\Models\SalesType;

/**
 * Resolves import labels to FK ids without loading full DMR hierarchies into memory.
 */
class VehicleImportLookupCache
{
    /** @var array<string, array<string, int>> */
    private array $flatMaps = [];

    /** @var array<string, int> */
    private array $brandCache = [];

    /** @var array<string, int> */
    private array $modelCache = [];

    /** @var array<string, int> */
    private array $variantCache = [];

    public function __construct()
    {
        $this->flatMaps = [
            'fuel_type_id' => $this->loadNameMap(DmrDriveEnergy::query()->select('id', 'name')),
            'body_type_id' => $this->loadNameMap(DmrBodyType::query()->select('id', 'name')),
            'colour_id' => $this->loadNameMap(DmrColour::query()->select('id', 'name')),
            'emission_norm_id' => $this->loadNameMap(DmrEmissionNorm::query()->select('id', 'name')),
            'vehicle_use_id' => $this->loadNameMap(DmrVehicleUse::query()->select('id', 'name')),
            'gear_type_id' => $this->loadNameMap(GearType::query()->select('id', 'name')),
            'condition_id' => $this->loadNameMap(Condition::query()->select('id', 'name')),
            'listing_type_id' => $this->loadNameMap(ListingType::query()->select('id', 'name')),
            'sales_type_id' => $this->loadNameMap(SalesType::query()->select('id', 'name')),
            'price_type_id' => $this->loadNameMap(PriceType::query()->select('id', 'name')),
            'category_id' => $this->loadNameMap(Category::query()->select('id', 'name')),
            'measurement_norm_id' => $this->loadNameMap(MeasurementNorm::query()->select('id', 'name')),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private function loadNameMap($query): array
    {
        $map = [];
        $query->orderBy('id')->chunk(500, function ($rows) use (&$map) {
            foreach ($rows as $row) {
                $key = $this->normalizeKey((string) $row->name);
                if ($key !== '') {
                    $map[$key] = (int) $row->id;
                }
            }
        });

        return $map;
    }

    public function resolveFlat(string $column, string $value): ?int
    {
        return self::resolveKeyInMap($column, $value, $this->flatMaps[$column] ?? []);
    }

    /**
     * Prefer an exact DB name match, then aliases (e.g. Excel "Leasing" → "Leasingdetaljer").
     *
     * @param  array<string, int>  $map
     */
    public static function resolveKeyInMap(string $column, string $value, array $map): ?int
    {
        $key = mb_strtolower(trim($value));
        if ($key === '') {
            return null;
        }

        if (isset($map[$key])) {
            return $map[$key];
        }

        $alias = self::aliasesForColumn($column)[$key] ?? null;
        if (is_string($alias) && isset($map[$alias])) {
            return $map[$alias];
        }

        return null;
    }

    /**
     * Common Bilbasen / extension labels → DB lookup names.
     *
     * @return array<string, string>
     */
    public static function aliasesForColumn(string $column): array
    {
        return match ($column) {
            'sales_type_id' => [
                'køb' => 'kontantpris',
                'koeb' => 'kontantpris',
                'kob' => 'kontantpris',
                'cash' => 'kontantpris',
                'purchase' => 'kontantpris',
                'leasing' => 'leasingdetaljer',
                'lease' => 'leasingdetaljer',
                'leasingdetaljer' => 'leasing',
            ],
            'fuel_type_id' => [
                'plugin hybrid (benzin + el)' => 'benzin',
                'plugin hybrid (benzin+el)' => 'benzin',
                'plugin hybrid' => 'benzin',
                'plugin-hybrid' => 'benzin',
                'plug-in hybrid' => 'benzin',
                'hybrid (benzin + el)' => 'benzin',
                'hybrid (benzin+el)' => 'benzin',
                'hybrid (diesel + el)' => 'diesel',
                'hybrid (diesel+el)' => 'diesel',
                'electricandbenzinplugin' => 'benzin',
                'electric and benzin plugin' => 'benzin',
                'hybrid' => 'benzin',
                'electric' => 'el',
                'elektrisk' => 'el',
                'ev' => 'el',
                'petrol' => 'benzin',
                'gasoline' => 'benzin',
            ],
            'gear_type_id' => [
                'manuel' => 'manual',
                'manuelt' => 'manual',
                'automatisk' => 'automatic',
                'automatgear' => 'automatic',
                'auto' => 'automatic',
            ],
            default => [],
        };
    }

    public function resolveBrand(string $value): ?int
    {
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return null;
        }

        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }

        $id = DmrBrand::query()
            ->whereRaw('LOWER(name) = ?', [$key])
            ->value('id');

        if ($id !== null) {
            $this->brandCache[$key] = (int) $id;
        }

        return $id !== null ? (int) $id : null;
    }

    public function resolveModel(string $value, int $brandId): ?int
    {
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return null;
        }

        $cacheKey = $brandId.':'.$key;
        if (isset($this->modelCache[$cacheKey])) {
            return $this->modelCache[$cacheKey];
        }

        $id = DmrModel::query()
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->value('id');

        if ($id !== null) {
            $this->modelCache[$cacheKey] = (int) $id;
        }

        return $id !== null ? (int) $id : null;
    }

    public function resolveVariant(string $value, int $modelId): ?int
    {
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return null;
        }

        $cacheKey = $modelId.':'.$key;
        if (isset($this->variantCache[$cacheKey])) {
            return $this->variantCache[$cacheKey];
        }

        $id = DmrVariant::query()
            ->where('model_id', $modelId)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->value('id');

        if ($id !== null) {
            $this->variantCache[$cacheKey] = (int) $id;
        }

        return $id !== null ? (int) $id : null;
    }

    public function normalizeKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
