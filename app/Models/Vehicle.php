<?php

namespace App\Models;

use App\ViewModels\VehicleDetailPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
    ];

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
}
