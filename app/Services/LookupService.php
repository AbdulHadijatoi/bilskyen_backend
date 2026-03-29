<?php

namespace App\Services;

use App\Models\DmrBrand;
use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\DmrDriveEnergy;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\PriceType;
use App\Models\VehicleListStatus;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Category;
use App\Models\DmrBodyType;
use App\Models\DmrColour;
use App\Models\DmrEmissionNorm;
use App\Models\DmrVehicleUse;
use App\Models\Permit;
use App\Models\LeadIntent;
use App\Models\LeadCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Lookup Service
 * Centralizes fetching and caching of lookup/constants data used by
 * LookupController (public), DealerLookupController, and AdminConstantsController.
 */
class LookupService
{
    public const CACHE_TTL = 86400; // 24 hours

    /**
     * Namespace for serialized lookup payloads. Bump if stored model classes or shape change
     * (avoids __PHP_Incomplete_Class after Dmr migrations / renames).
     */
    private const CACHE_KEY_PREFIX = 'lookup_v2_';

    private static function cacheKey(string $suffix): string
    {
        return self::CACHE_KEY_PREFIX . $suffix;
    }

    /**
     * Drop current and legacy cache keys for a logical group (admin CRUD, etc.).
     * $group matches ConstantsCacheTrait names (e.g. body_types, colors, euronorms).
     */
    public static function forgetLookupCacheGroup(string $group): void
    {
        $segment = match ($group) {
            'body_types' => 'dmr_body_types',
            'colors' => 'dmr_colours',
            default => $group,
        };

        Cache::forget(self::cacheKey($segment));

        $legacyKeys = match ($group) {
            'body_types' => ['constants_body_types', 'constants_dmr_body_types'],
            'colors' => ['constants_colors'],
            default => ['constants_' . $group],
        };

        foreach ($legacyKeys as $key) {
            Cache::forget($key);
        }
    }

    public static function forgetFuelTypesLookupCache(): void
    {
        Cache::forget(self::cacheKey('dmr_drive_energies'));
        Cache::forget('constants_fuel_types');
        Cache::forget('constants_dmr_drive_energies');
    }

    /**
     * Get brands (id, name, ...). Same cache key used by all APIs.
     */
    public function getBrands(): Collection
    {
        return Cache::remember(self::cacheKey('brands'), self::CACHE_TTL, function () {
            return DmrBrand::orderBy('name')->get();
        });
    }

    public function getFuelTypes(): Collection
    {
        return Cache::remember(self::cacheKey('dmr_drive_energies'), self::CACHE_TTL, function () {
            return DmrDriveEnergy::orderBy('name')->get();
        });
    }

    public function getGearTypes(): Collection
    {
        return Cache::remember(self::cacheKey('gear_types'), self::CACHE_TTL, function () {
            return GearType::orderBy('name')->get();
        });
    }

    public function getListingTypes(): Collection
    {
        return Cache::remember(self::cacheKey('listing_types'), self::CACHE_TTL, function () {
            return ListingType::orderBy('name')->get();
        });
    }

    public function getBodyTypes(): Collection
    {
        return Cache::remember(self::cacheKey('dmr_body_types'), self::CACHE_TTL, function () {
            return DmrBodyType::orderBy('name')->get();
        });
    }

    public function getColors(): Collection
    {
        return Cache::remember(self::cacheKey('dmr_colours'), self::CACHE_TTL, function () {
            return DmrColour::orderBy('name')->get();
        });
    }

    public function getVariants(): Collection
    {
        return Cache::remember(self::cacheKey('variants'), self::CACHE_TTL, function () {
            return DmrVariant::with('model')->orderBy('name')->get();
        });
    }

    public function getConditions(): Collection
    {
        return Cache::remember(self::cacheKey('conditions'), self::CACHE_TTL, function () {
            return Condition::orderBy('name')->get();
        });
    }

    public function getSalesTypes(): Collection
    {
        return Cache::remember(self::cacheKey('sales_types'), self::CACHE_TTL, function () {
            return SalesType::orderBy('name')->get();
        });
    }

    public function getPriceTypes(): Collection
    {
        return Cache::remember(self::cacheKey('price_types'), self::CACHE_TTL, function () {
            return PriceType::orderBy('name')->get();
        });
    }

    public function getEuronorms(): Collection
    {
        return Cache::remember(self::cacheKey('euronorms'), self::CACHE_TTL, function () {
            return DmrEmissionNorm::orderBy('name')->get();
        });
    }

    public function getVehicleModels(): Collection
    {
        return Cache::remember(self::cacheKey('vehicle_models'), self::CACHE_TTL, function () {
            return DmrModel::with('brand')->orderBy('name')->get();
        });
    }

    public function getVehicleUses(): Collection
    {
        return Cache::remember(self::cacheKey('vehicle_uses'), self::CACHE_TTL, function () {
            return DmrVehicleUse::orderBy('name')->get();
        });
    }

    public function getVehicleListStatuses(): Collection
    {
        return Cache::rememberForever(self::cacheKey('vehicle_list_statuses'), function () {
            return VehicleListStatus::orderBy('name')->get();
        });
    }

    public function getEquipmentTypes(): Collection
    {
        return Cache::remember(self::cacheKey('equipment_types'), self::CACHE_TTL, function () {
            return EquipmentType::with(['equipments' => function ($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        });
    }

    public function getEquipments(): Collection
    {
        return Cache::remember(self::cacheKey('equipments'), self::CACHE_TTL, function () {
            return Equipment::with('equipmentType')->orderBy('name')->get();
        });
    }

    /** Public API only: transmissions, categories, permits */

    public function getCategories(): Collection
    {
        return Cache::remember(self::cacheKey('categories'), self::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });
    }

    public function getPermits(): Collection
    {
        return Cache::remember(self::cacheKey('permits'), self::CACHE_TTL, function () {
            return Permit::orderBy('name')->get();
        });
    }

    /** Dealer API only: lead intents and categories */
    public function getLeadIntents(): Collection
    {
        return Cache::rememberForever(self::cacheKey('lead_intents'), function () {
            return LeadIntent::orderBy('id')->get();
        });
    }

    public function getLeadCategories(): Collection
    {
        return Cache::remember(self::cacheKey('lead_categories'), self::CACHE_TTL, function () {
            return LeadCategory::orderBy('name')->get();
        });
    }

    /**
     * Brands dropdown search (no full-table caching).
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchBrands(?string $search): array
    {
        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrBrand::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->get()
            ->map(fn (DmrBrand $b) => ['id' => $b->id, 'name' => $b->name])
            ->values()
            ->all();
    }

    /**
     * Models dropdown search (optionally constrained by brand_ids).
     *
     * @param array<int,int> $brandIds
     * @return array<int, array{id:int,name:string,brand_id:int}>
     */
    public function searchModels(?string $search, array $brandIds): array
    {
        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrModel::query()
            ->select(['id', 'name', 'brand_id'])
            ->whereNotIn('name', ['-', '.'])
            ->orderBy('name');
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->get()
            ->map(fn (DmrModel $m) => ['id' => $m->id, 'name' => $m->name, 'brand_id' => $m->brand_id])
            ->values()
            ->all();
    }

    /**
     * Variants dropdown search (optionally constrained by model_ids).
     *
     * @param array<int,int> $modelIds
     * @return array<int, array{id:int,name:string,model_id:int}>
     */
    public function searchVariants(?string $search, array $modelIds): array
    {
        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrVariant::query()->select(['id', 'name', 'model_id'])->orderBy('name');
        if (!empty($modelIds)) {
            $query->whereIn('model_id', $modelIds);
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->get()
            ->map(fn (DmrVariant $v) => ['id' => $v->id, 'name' => $v->name, 'model_id' => $v->model_id])
            ->values()
            ->all();
    }

    /**
     * Return all constants for the public API (minimal fields: id, name; models with brand_id).
     */
    public function getPublicConstants(): array
    {
        return [
            // `{column}_asc` / `{column}_desc` for each `vehicles` table column (see VehicleService::publicListingSortOptionKeys()).
            'vehicle_sort_keys' => VehicleService::publicListingSortOptionKeys(),
            'fuel_types' => $this->getFuelTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'gear_types' => $this->getGearTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'listing_types' => $this->getListingTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'body_types' => $this->getBodyTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'colors' => $this->getColors()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'conditions' => $this->getConditions()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'sales_types' => $this->getSalesTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'price_types' => $this->getPriceTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'euronorms' => $this->getEuronorms()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'vehicle_uses' => $this->getVehicleUses()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'equipment_types' => $this->getEquipmentTypes()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'equipments' => $this->getEquipments()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'equipment_type_id' => $m->equipment_type_id]),
            'categories' => $this->getCategories()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'permits' => $this->getPermits()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
        ];
    }

    /**
     * Return all constants for dealer API (includes lead intents/categories).
     * Model years: derive on the client (e.g. 1975–current). Models/variants: use GET /api/v1/models|variants.
     */
    public function getDealerConstants(): array
    {
        return [
            'brands' => $this->getBrands(),
            'fuel_types' => $this->getFuelTypes(),
            'gear_types' => $this->getGearTypes(),
            'listing_types' => $this->getListingTypes(),
            'body_types' => $this->getBodyTypes(),
            'colors' => $this->getColors(),
            'conditions' => $this->getConditions(),
            'sales_types' => $this->getSalesTypes(),
            'price_types' => $this->getPriceTypes(),
            'euronorms' => $this->getEuronorms(),
            'vehicle_uses' => $this->getVehicleUses(),
            'vehicle_list_statuses' => $this->getVehicleListStatuses(),
            'equipment_types' => $this->getEquipmentTypes(),
            'equipments' => $this->getEquipments(),
            'lead_intents' => $this->getLeadIntents(),
            'lead_categories' => $this->getLeadCategories(),
        ];
    }

    /**
     * Return all constants for admin API (full models).
     */
    public function getAdminConstants(): array
    {
        return [
            'brands' => $this->getBrands(),
            'fuel_types' => $this->getFuelTypes(),
            'gear_types' => $this->getGearTypes(),
            'listing_types' => $this->getListingTypes(),
            'body_types' => $this->getBodyTypes(),
            'colors' => $this->getColors(),
            'variants' => $this->getVariants(),
            'conditions' => $this->getConditions(),
            'sales_types' => $this->getSalesTypes(),
            'price_types' => $this->getPriceTypes(),
            'euronorms' => $this->getEuronorms(),
            'vehicle_models' => $this->getVehicleModels(),
            'vehicle_uses' => $this->getVehicleUses(),
            'vehicle_list_statuses' => $this->getVehicleListStatuses(),
            'equipment_types' => $this->getEquipmentTypes(),
            'equipments' => $this->getEquipments(),
        ];
    }
}
