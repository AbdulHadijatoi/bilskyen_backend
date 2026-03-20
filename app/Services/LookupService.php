<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Variant;
use App\Models\Type;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\PriceType;
use App\Models\Euronom;
use App\Models\VehicleModel;
use App\Models\VehicleUse;
use App\Models\VehicleListStatus;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Transmission;
use App\Models\Category;
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
     * Get brands (id, name, ...). Same cache key used by all APIs.
     */
    public function getBrands(): Collection
    {
        return Cache::remember('constants_brands', self::CACHE_TTL, function () {
            return Brand::orderBy('name')->get();
        });
    }

    public function getModelYears(): Collection
    {
        return Cache::remember('constants_model_years', self::CACHE_TTL, function () {
            return ModelYear::orderBy('name')->get();
        });
    }

    public function getFuelTypes(): Collection
    {
        return Cache::remember('constants_fuel_types', self::CACHE_TTL, function () {
            return FuelType::orderBy('name')->get();
        });
    }

    public function getGearTypes(): Collection
    {
        return Cache::remember('constants_gear_types', self::CACHE_TTL, function () {
            return GearType::orderBy('name')->get();
        });
    }

    public function getListingTypes(): Collection
    {
        return Cache::remember('constants_listing_types', self::CACHE_TTL, function () {
            return ListingType::orderBy('name')->get();
        });
    }

    public function getBodyTypes(): Collection
    {
        return Cache::remember('constants_body_types', self::CACHE_TTL, function () {
            return BodyType::orderBy('name')->get();
        });
    }

    public function getColors(): Collection
    {
        return Cache::remember('constants_colors', self::CACHE_TTL, function () {
            return Color::orderBy('name')->get();
        });
    }

    public function getVariants(): Collection
    {
        return Cache::remember('constants_variants', self::CACHE_TTL, function () {
            return Variant::with('model')->orderBy('name')->get();
        });
    }

    public function getTypes(): Collection
    {
        return Cache::remember('constants_types', self::CACHE_TTL, function () {
            return Type::orderBy('name')->get();
        });
    }

    public function getConditions(): Collection
    {
        return Cache::remember('constants_conditions', self::CACHE_TTL, function () {
            return Condition::orderBy('name')->get();
        });
    }

    public function getSalesTypes(): Collection
    {
        return Cache::remember('constants_sales_types', self::CACHE_TTL, function () {
            return SalesType::orderBy('name')->get();
        });
    }

    public function getPriceTypes(): Collection
    {
        return Cache::remember('constants_price_types', self::CACHE_TTL, function () {
            return PriceType::orderBy('name')->get();
        });
    }

    public function getEuronorms(): Collection
    {
        return Cache::remember('constants_euronorms', self::CACHE_TTL, function () {
            return Euronom::orderBy('name')->get();
        });
    }

    public function getVehicleModels(): Collection
    {
        return Cache::remember('constants_vehicle_models', self::CACHE_TTL, function () {
            return VehicleModel::with('brand')->orderBy('name')->get();
        });
    }

    public function getVehicleUses(): Collection
    {
        return Cache::remember('constants_vehicle_uses', self::CACHE_TTL, function () {
            return VehicleUse::orderBy('name')->get();
        });
    }

    public function getVehicleListStatuses(): Collection
    {
        return Cache::rememberForever('constants_vehicle_list_statuses', function () {
            return VehicleListStatus::orderBy('name')->get();
        });
    }

    public function getEquipmentTypes(): Collection
    {
        return Cache::remember('constants_equipment_types', self::CACHE_TTL, function () {
            return EquipmentType::with(['equipments' => function ($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        });
    }

    public function getEquipments(): Collection
    {
        return Cache::remember('constants_equipments', self::CACHE_TTL, function () {
            return Equipment::with('equipmentType')->orderBy('name')->get();
        });
    }

    /** Public API only: transmissions, categories, permits */
    public function getTransmissions(): Collection
    {
        return Cache::remember('constants_transmissions', self::CACHE_TTL, function () {
            return Transmission::orderBy('name')->get();
        });
    }

    public function getCategories(): Collection
    {
        return Cache::remember('constants_categories', self::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });
    }

    public function getPermits(): Collection
    {
        return Cache::remember('constants_permits', self::CACHE_TTL, function () {
            return Permit::orderBy('name')->get();
        });
    }

    /** Dealer API only: lead intents and categories */
    public function getLeadIntents(): Collection
    {
        return Cache::rememberForever('constants_lead_intents', function () {
            return LeadIntent::orderBy('id')->get();
        });
    }

    public function getLeadCategories(): Collection
    {
        return Cache::remember('constants_lead_categories', self::CACHE_TTL, function () {
            return LeadCategory::orderBy('name')->get();
        });
    }

    private function normalizeSearchLimit(int $limit): int
    {
        $limit = max(1, $limit);
        // Hard cap to keep dropdown searches cheap.
        return min(50, $limit);
    }

    /**
     * Brands dropdown search (no full-table caching).
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchBrands(?string $search, int $limit): array
    {
        $limit = $this->normalizeSearchLimit($limit);
        $searchTerm = $search !== null ? trim($search) : '';

        $query = Brand::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (Brand $b) => ['id' => $b->id, 'name' => $b->name])
            ->values()
            ->all();
    }

    /**
     * Models dropdown search (optionally constrained by brand_ids).
     *
     * @param array<int,int> $brandIds
     * @return array<int, array{id:int,name:string,brand_id:int}>
     */
    public function searchModels(?string $search, array $brandIds, int $limit): array
    {
        $limit = $this->normalizeSearchLimit($limit);
        $searchTerm = $search !== null ? trim($search) : '';

        $query = VehicleModel::query()->select(['id', 'name', 'brand_id'])->orderBy('name');
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (VehicleModel $m) => ['id' => $m->id, 'name' => $m->name, 'brand_id' => $m->brand_id])
            ->values()
            ->all();
    }

    /**
     * Types dropdown search.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchTypes(?string $search, int $limit): array
    {
        $limit = $this->normalizeSearchLimit($limit);
        $searchTerm = $search !== null ? trim($search) : '';

        $query = Type::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (Type $t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->all();
    }

    /**
     * Variants dropdown search (optionally constrained by model_ids).
     *
     * @param array<int,int> $modelIds
     * @return array<int, array{id:int,name:string,model_id:int}>
     */
    public function searchVariants(?string $search, array $modelIds, int $limit): array
    {
        $limit = $this->normalizeSearchLimit($limit);
        $searchTerm = $search !== null ? trim($search) : '';

        $query = Variant::query()->select(['id', 'name', 'model_id'])->orderBy('name');
        if (!empty($modelIds)) {
            $query->whereIn('model_id', $modelIds);
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (Variant $v) => ['id' => $v->id, 'name' => $v->name, 'model_id' => $v->model_id])
            ->values()
            ->all();
    }

    /**
     * Return all constants for the public API (minimal fields: id, name; models with brand_id).
     */
    public function getPublicConstants(): array
    {
        return [
            'model_years' => $this->getModelYears()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
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
            'transmissions' => $this->getTransmissions()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'categories' => $this->getCategories()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'permits' => $this->getPermits()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
        ];
    }

    /**
     * Return all constants for dealer API (full models, includes lead intents/categories).
     */
    public function getDealerConstants(): array
    {
        return [
            'brands' => $this->getBrands(),
            'model_years' => $this->getModelYears(),
            'fuel_types' => $this->getFuelTypes(),
            'gear_types' => $this->getGearTypes(),
            'listing_types' => $this->getListingTypes(),
            'body_types' => $this->getBodyTypes(),
            'colors' => $this->getColors(),
            'variants' => $this->getVariants(),
            'types' => $this->getTypes(),
            'conditions' => $this->getConditions(),
            'sales_types' => $this->getSalesTypes(),
            'price_types' => $this->getPriceTypes(),
            'euronorms' => $this->getEuronorms(),
            'vehicle_models' => $this->getVehicleModels(),
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
            'model_years' => $this->getModelYears(),
            'fuel_types' => $this->getFuelTypes(),
            'gear_types' => $this->getGearTypes(),
            'listing_types' => $this->getListingTypes(),
            'body_types' => $this->getBodyTypes(),
            'colors' => $this->getColors(),
            'variants' => $this->getVariants(),
            'types' => $this->getTypes(),
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
