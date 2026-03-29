<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\ViewModels\VehicleDetailPresenter;
use App\Models\DmrBrand;
use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\DmrDriveEnergy;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    private static array $lookupCache = [];

    protected $fillable = [
        'registration',
        'dmr_fact_vehicle_id',
        'vin',
        'title',
        'slug',
        'dealer_id',
        'user_id',
        'km_per_liter',
        'co2_emission',
        'electrical_consumption',
        'engine_power_kw',
        'engine_power_hp',
        'engine_size_cc',
        'engine_displacement_litres',
        'first_registration_date',
        'first_registration_year',
        'last_inspection_date',
        'nox_emission',
        'particle_filter',
        'axle_count',
        'door_count',
        'gear_count',
        'max_speed',
        'model_year',
        'ncap_test',
        'seats_min',
        'seats_max',
        'maximum_weight_kg',
        'registration_status',
        'last_registration_change',
        'measurement_norm_id',
        'body_type_id',
        'colour_id',
        'emission_norm_id',
        'model_id',
        'variant_id',
        'fuel_type_id',
        'vehicle_use_id',
        'brand_id',
        'listing_type_id',
        'sales_type_id',
        'price_type_id',
        'category_id',
        'price',
        'calculated_ownership_tax',
        'km_driven',
        'towing_weight',
        'is_import',
        'is_factory_new',
        'charging_type',
        'gear_type_id',
        'list_status_id',
        'published_at',
        'address',
        'postcode',
        'description',
        'condition_id',
        'servicebog',
        'annual_tax',
        'seller_phone',
        'wholesale_price',
        'internal_cost_price',
        'price_without_tax',
        'wholesale_price_includes_delivery',
        'fuel_consumption_wltp',
        'fuel_consumption_nedc',
        'production_date',
        'cover_image_index',
        'engine_type',
        'views_count',
        'leasing_enabled',
        'leasing_type',
        'leasing_customer_type',
        'leasing_monthly_payment',
        'leasing_first_payment',
        'leasing_residual_value',
        'leasing_duration',
        'leasing_annual_mileage',
        'leasing_total_cost',
        'battery_capacity',
        'range_km',
    ];

    protected $casts = [
        'km_per_liter' => 'float',
        'co2_emission' => 'float',
        'electrical_consumption' => 'float',
        'engine_power_kw' => 'float',
        'engine_power_hp' => 'float',
        'engine_size_cc' => 'integer',
        'engine_displacement_litres' => 'float',
        'first_registration_date' => 'date',
        'first_registration_year' => 'integer',
        'last_inspection_date' => 'date',
        'nox_emission' => 'float',
        'particle_filter' => 'boolean',
        'axle_count' => 'integer',
        'door_count' => 'integer',
        'gear_count' => 'integer',
        'max_speed' => 'integer',
        'model_year' => 'integer',
        'ncap_test' => 'boolean',
        'seats_min' => 'integer',
        'seats_max' => 'integer',
        'maximum_weight_kg' => 'integer',
        'last_registration_change' => 'date',
        'measurement_norm_id' => 'integer',
        'body_type_id' => 'integer',
        'colour_id' => 'integer',
        'emission_norm_id' => 'integer',
        'model_id' => 'integer',
        'variant_id' => 'integer',
        'fuel_type_id' => 'integer',
        'vehicle_use_id' => 'integer',
        'brand_id' => 'integer',
        'listing_type_id' => 'integer',
        'sales_type_id' => 'integer',
        'price_type_id' => 'integer',
        'category_id' => 'integer',
        'price' => 'integer',
        'calculated_ownership_tax' => 'integer',
        'km_driven' => 'integer',
        'towing_weight' => 'integer',
        'is_import' => 'boolean',
        'is_factory_new' => 'boolean',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'annual_tax' => 'decimal:2',
        'fuel_consumption_wltp' => 'decimal:2',
        'fuel_consumption_nedc' => 'decimal:2',
        'production_date' => 'date',
        'wholesale_price_includes_delivery' => 'boolean',
        'leasing_enabled' => 'boolean',
        'leasing_monthly_payment' => 'decimal:2',
        'leasing_first_payment' => 'decimal:2',
        'leasing_residual_value' => 'decimal:2',
        'leasing_total_cost' => 'decimal:2',
        'views_count' => 'integer',
        'battery_capacity' => 'integer',
        'range_km' => 'integer',
    ];

    /**
     * Legacy Blade templates use {@see $vehicle->details}; backed by {@see VehicleDetailPresenter}.
     */
    public function getDetailsAttribute(): VehicleDetailPresenter
    {
        return new VehicleDetailPresenter($this);
    }

    /**
     * Legacy alias: column is {@see $list_status_id}.
     */
    public function getVehicleListStatusIdAttribute(): ?int
    {
        $v = $this->attributes['list_status_id'] ?? null;

        return $v !== null ? (int) $v : null;
    }

    public function getVehicleListStatusNameAttribute(): ?string
    {
        if ($this->relationLoaded('vehicleListStatus') && $this->vehicleListStatus) {
            return $this->vehicleListStatus->name;
        }

        $id = $this->attributes['list_status_id'] ?? null;
        if ($id === null) {
            return null;
        }

        return self::getCachedLookup('vehicle_list_statuses', (int) $id);
    }

    /** @deprecated Column removed; maps to {@see $km_driven}. */
    public function getMileageAttribute(): ?int
    {
        $v = $this->attributes['km_driven'] ?? null;

        return $v !== null ? (int) $v : null;
    }

    /** @deprecated Use {@see $address}; kept for seller/dealer Blade/API. */
    public function getSellerAddressAttribute(): ?string
    {
        $v = $this->attributes['address'] ?? null;

        return $v !== null && $v !== '' ? (string) $v : null;
    }

    /** @deprecated Use {@see $postcode}. */
    public function getSellerPostcodeAttribute(): ?string
    {
        $v = $this->attributes['postcode'] ?? null;

        return $v !== null && $v !== '' ? (string) $v : null;
    }

    /**
     * Trim/variant label (column `version` was dropped).
     */
    public function getVersionAttribute(): ?string
    {
        if ($this->relationLoaded('variant') && $this->variant) {
            return $this->variant->name;
        }
        if (! empty($this->attributes['variant_id'])) {
            return DmrVariant::query()->whereKey($this->attributes['variant_id'])->value('name');
        }
        $dmr = $this->relationLoaded('dmrFactVehicle') ? $this->dmrFactVehicle : null;
        if ($dmr?->relationLoaded('variant') && $dmr->variant) {
            return $dmr->variant->name;
        }

        return null;
    }

    public function getBrandNameAttribute(): ?string
    {
        if ($this->relationLoaded('brand') && $this->brand) {
            return $this->brand->name;
        }
        $dmr = $this->relationLoaded('dmrFactVehicle') ? $this->dmrFactVehicle : null;
        if ($dmr?->relationLoaded('variant') && $dmr->variant?->relationLoaded('model') && $dmr->variant->model?->relationLoaded('brand') && $dmr->variant->model->brand) {
            return $dmr->variant->model->brand->name;
        }
        if (! empty($this->attributes['brand_id'])) {
            return DmrBrand::query()->whereKey($this->attributes['brand_id'])->value('name');
        }

        return null;
    }

    public function getModelNameAttribute(): ?string
    {
        if ($this->relationLoaded('model') && $this->model) {
            return $this->model->name;
        }
        $dmr = $this->relationLoaded('dmrFactVehicle') ? $this->dmrFactVehicle : null;
        if ($dmr?->relationLoaded('variant') && $dmr->variant?->relationLoaded('model') && $dmr->variant->model) {
            return $dmr->variant->model->name;
        }
        if (! empty($this->attributes['model_id'])) {
            return DmrModel::query()->whereKey($this->attributes['model_id'])->value('name');
        }

        return null;
    }

    public function getFuelTypeNameAttribute(): ?string
    {
        if ($this->relationLoaded('fuelType') && $this->fuelType) {
            return $this->fuelType->name;
        }
        if (! empty($this->attributes['fuel_type_id'])) {
            return DmrDriveEnergy::query()->whereKey($this->attributes['fuel_type_id'])->value('name');
        }

        return null;
    }

    public function getGearTypeNameAttribute(): ?string
    {
        if ($this->relationLoaded('gearType') && $this->gearType) {
            return $this->gearType->name;
        }
        if (! empty($this->attributes['gear_type_id'])) {
            return self::getCachedLookup('gear_types', (int) $this->attributes['gear_type_id']);
        }

        return null;
    }

    public function getModelYearNameAttribute(): ?string
    {
        $y = $this->attributes['model_year'] ?? null;

        return $y !== null ? (string) (int) $y : null;
    }

    // protected $appends = [
    //     'brand_name',
    //     'model_name',
    //     'variant_name',
    //     'color_name',
    //     'body_type_name',
    //     'use_name',
    //     'emission_norm_name',
    //     'fuel_type_name',
    //     'gear_type_name',
    //     'measurement_norm_name',
    //     'listing_type_name',
    //     'sales_type_name',
    //     'price_type_name',
    //     'category_name',
    //     'condition_name',
    //     'list_status_name',
    //     'equipment_names',
    //     'specifications'
    // ];

    /**
     * Column names on the {@see vehicles} table that may be used for public listing ORDER BY.
     * Prefer live schema; {@see self::sortableTableColumnsFallback()} when the table is unavailable.
     *
     * @return list<string>
     */
    public static function listingSortableTableColumns(): array
    {
        $table = (new static)->getTable();

        if (! Schema::hasTable($table)) {
            return self::sortableTableColumnsFallback();
        }

        $columns = Schema::getColumnListing($table);
        sort($columns);

        return array_values($columns);
    }

    /**
     * Fallback when migrations have not run (e.g. some unit tests). Mirrors the vehicles table.
     *
     * @return list<string>
     */
    private static function sortableTableColumnsFallback(): array
    {
        $cols = [
            'id',
            'registration',
            'dmr_fact_vehicle_id',
            'vin',
            'title',
            'slug',
            'dealer_id',
            'user_id',
            'km_per_liter',
            'co2_emission',
            'electrical_consumption',
            'engine_power_kw',
            'engine_power_hp',
            'engine_size_cc',
            'engine_displacement_litres',
            'first_registration_date',
            'first_registration_year',
            'last_inspection_date',
            'nox_emission',
            'particle_filter',
            'axle_count',
            'door_count',
            'gear_count',
            'max_speed',
            'model_year',
            'ncap_test',
            'seats_min',
            'seats_max',
            'maximum_weight_kg',
            'registration_status',
            'last_registration_change',
            'measurement_norm_id',
            'body_type_id',
            'colour_id',
            'emission_norm_id',
            'model_id',
            'variant_id',
            'fuel_type_id',
            'vehicle_use_id',
            'brand_id',
            'listing_type_id',
            'sales_type_id',
            'price_type_id',
            'category_id',
            'type_id',
            'price',
            'calculated_ownership_tax',
            'km_driven',
            'towing_weight',
            'battery_capacity',
            'range_km',
            'is_import',
            'is_factory_new',
            'charging_type',
            'gear_type_id',
            'list_status_id',
            'published_at',
            'address',
            'postcode',
            'description',
            'condition_id',
            'servicebog',
            'airbags',
            'wheels',
            'drive_axles',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
        sort($cols);

        return array_values(array_unique($cols));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('defaultOrder', function (Builder $query) {
            if (empty($query->getQuery()->orders)) {
                $query->orderBy('id', 'desc');
            }
        });

        static::creating(function (Vehicle $vehicle) {
            if (empty($vehicle->slug)) {
                $vehicle->slug = $vehicle->generateUniqueSlug();
            }
        });

        static::saving(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('title') && $vehicle->exists) {
                $vehicle->slug = $vehicle->generateUniqueSlug();
            }
        });

        static::created(function () {
            Cache::forget('sitemap_xml');
        });
        static::updated(function () {
            Cache::forget('sitemap_xml');
        });
        static::deleted(function () {
            Cache::forget('sitemap_xml');
        });
        static::restored(function () {
            Cache::forget('sitemap_xml');
        });
        static::forceDeleted(function () {
            Cache::forget('sitemap_xml');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function generateUniqueSlug(): string
    {
        $title = $this->title ?? '';
        if ($title === '' && ! empty($this->registration)) {
            $title = 'listing-' . preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $this->registration);
        }
        if ($title === '') {
            $title = 'vehicle';
        }
        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'vehicle';
        }
        $base = $slug;
        $query = self::withoutGlobalScopes()->where('slug', $slug);
        if ($this->id !== null) {
            $query->where('id', '!=', $this->id);
        }
        $counter = 1;
        while ($query->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
            $query = self::withoutGlobalScopes()->where('slug', $slug);
            if ($this->id !== null) {
                $query->where('id', '!=', $this->id);
            }
        }

        return $slug;
    }

    public static function getCachedLookup(string $table, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $cacheKey = "{$table}_{$id}";

        if (isset(self::$lookupCache[$cacheKey])) {
            return self::$lookupCache[$cacheKey];
        }

        $cached = Cache::remember("vehicle_lookup_{$cacheKey}", 86400, function () use ($table, $id) {
            $model = match ($table) {
                'gear_types' => GearType::withoutGlobalScopes()->find($id),
                'vehicle_list_statuses' => VehicleListStatus::withoutGlobalScopes()->select('id', 'name')->find($id),
                default => null,
            };

            return $model?->name;
        });

        if ($cached !== null) {
            self::$lookupCache[$cacheKey] = $cached;
        }

        return $cached;
    }

    public function dmrFactVehicle(): BelongsTo
    {
        return $this->belongsTo(DmrFactVehicle::class, 'dmr_fact_vehicle_id');
    }

    public function bodyType(): BelongsTo
    {
        return $this->belongsTo(DmrBodyType::class, 'body_type_id');
    }

    public function colour(): BelongsTo
    {
        return $this->belongsTo(DmrColour::class, 'colour_id');
    }

    public function emissionNorm(): BelongsTo
    {
        return $this->belongsTo(DmrEmissionNorm::class, 'emission_norm_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DmrModel::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(DmrVariant::class, 'variant_id');
    }

    /** DMR drive energy (“fuel type”) — column {@see $fuel_type_id} references {@see DmrDriveEnergy}. */
    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(DmrDriveEnergy::class, 'fuel_type_id');
    }

    public function vehicleUse(): BelongsTo
    {
        return $this->belongsTo(DmrVehicleUse::class, 'vehicle_use_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class, 'brand_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function viewLogs(): HasMany
    {
        return $this->hasMany(ListingViewsLog::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'vehicle_equipment');
    }

    public function specifications(): BelongsToMany
    {
        return $this->belongsToMany(Specification::class, 'vehicle_specifications')
            ->using(VehicleSpecification::class)
            ->withPivot('count')
            ->withTimestamps();
    }

    public function gearType(): BelongsTo
    {
        return $this->belongsTo(GearType::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(Condition::class, 'condition_id');
    }

    public function measurementNorm(): BelongsTo
    {
        return $this->belongsTo(MeasurementNorm::class, 'measurement_norm_id');
    }

    public function vehicleListStatus(): BelongsTo
    {
        return $this->belongsTo(VehicleListStatus::class, 'list_status_id');
    }

    public function listingType(): BelongsTo
    {
        return $this->belongsTo(ListingType::class, 'listing_type_id');
    }

    public function salesType(): BelongsTo
    {
        return $this->belongsTo(SalesType::class, 'sales_type_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class, 'price_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
