<?php

namespace App\Services;

use App\Models\DmrBrand;
use App\Models\DmrFactVehicle;
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
use Illuminate\Support\Facades\DB;

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
     *
     * Segment names must match {@see self::cacheKey()} suffixes used in the getters
     * (e.g. vehicle models use "vehicle_models", not "dmr_models").
     */
    public static function forgetLookupCacheGroup(string $group): void
    {
        $segment = match ($group) {
            'body_types' => 'dmr_body_types',
            'colors' => 'dmr_colours',
            'dmr_models' => 'vehicle_models',
            'types' => 'categories',
            default => $group,
        };

        Cache::forget(self::cacheKey($segment));

        $legacyKeys = match ($group) {
            'body_types' => ['constants_body_types', 'constants_dmr_body_types'],
            'colors' => ['constants_colors'],
            'types', 'categories' => ['constants_categories', 'constants_types'],
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
     * Brand name changes affect cached vehicle_models (with brand) and variants (with model).
     */
    public static function forgetBrandAndDependentLookupCaches(): void
    {
        self::forgetLookupCacheGroup('brands');
        self::forgetLookupCacheGroup('vehicle_models');
        self::forgetLookupCacheGroup('variants');
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

    /**
     * Distinct model years from DMR fact data (read-only reference for admin UI).
     *
     * @return Collection<int, array{id:int,name:string}>
     */
    public function getModelYears(): Collection
    {
        return Cache::remember(self::cacheKey('model_years'), self::CACHE_TTL, function () {
            $years = DmrFactVehicle::query()
                ->whereNotNull('model_aar')
                ->distinct()
                ->orderByDesc('model_aar')
                ->pluck('model_aar');

            return $years->values()->map(fn ($y) => [
                'id' => (int) $y,
                'name' => (string) $y,
            ]);
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
     * Distinct {@see DmrModel} ids that appear on published, non-deleted vehicles.
     * When {@code $brandIds} is non-empty, only vehicles with those {@code brand_id} values are considered.
     *
     * @param  array<int,int>  $brandIds
     * @return array<int,int>
     */
    public function publishedListingModelIds(array $brandIds): array
    {
        $q = DB::table('vehicles')
            ->whereNull('deleted_at')
            ->whereNotNull('model_id')
            ->where('list_status_id', VehicleListStatus::PUBLISHED);

        if ($brandIds !== []) {
            $q->whereIn('brand_id', $brandIds);
        }

        $data = $q->distinct()
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $data;
    }

    /**
     * Models dropdown search (full DMR catalog; optionally constrained by brand_ids).
     * For public listing page filters (published inventory + short labels), use {@see self::searchModelsForListingFilters} via GET /api/v1/listing-models.
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
     * Models for home / vehicles listing filters: only {@see DmrModel} ids with a published, non-deleted vehicle;
     * optional brand filter; {@code name} shortened for dropdowns via {@see DmrModel::dropdownDisplayName}.
     *
     * @param array<int,int> $brandIds
     * @return array<int, array{id:int,name:string,brand_id:int}>
     */
    public function searchModelsForListingFilters(?string $search, array $brandIds): array
    {
        $searchTerm = $search !== null ? trim($search) : '';

        $publishedModelIds = $this->publishedListingModelIds($brandIds);

        $query = DmrModel::query()
            ->select(['id', 'name', 'brand_id'])
            ->whereNotIn('name', ['-', '.'])
            ->orderBy('name');
        if ($publishedModelIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('id', $publishedModelIds);
        }
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->get()
            ->map(fn (DmrModel $m) => [
                'id' => $m->id,
                'name' => DmrModel::dropdownDisplayName((string) $m->name),
                'brand_id' => $m->brand_id,
            ])
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
            // Curated sort keys for the public listing UI (see VehicleService::curatedPublicListingSortKeys()).
            'vehicle_sort_keys' => VehicleService::curatedPublicListingSortKeys(),
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
     * Model years: derive on the client (e.g. 1975–current). Models/variants: use GET /api/v1/models|variants (full catalog). Listing filters on the site use GET /api/v1/listing-models.
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
            'conditions' => $this->getConditions(),
            'sales_types' => $this->getSalesTypes(),
            'price_types' => $this->getPriceTypes(),
            'euronorms' => $this->getEuronorms(),
            'vehicle_uses' => $this->getVehicleUses(),
            'vehicle_list_statuses' => $this->getVehicleListStatuses(),
            'equipment_types' => $this->getEquipmentTypes(),
            'equipments' => $this->getEquipments(),
        ];
    }
}
