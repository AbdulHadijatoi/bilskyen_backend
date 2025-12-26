<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Static cache for lookup data (in-memory cache)
     */
    private static array $lookupCache = [];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'registration',
        'vin',
        'dealer_id',
        'user_id',
        'category_id',
        'location_id',
        'brand_id',
        'model_year_id',
        'km_driven',
        'fuel_type_id',
        'price',
        'mileage',
        'battery_capacity',
        'engine_power',
        'towing_weight',
        'ownership_tax',
        'first_registration_date',
        'vehicle_list_status_id',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'integer',
        'mileage' => 'integer',
        'km_driven' => 'integer',
        'battery_capacity' => 'integer',
        'engine_power' => 'integer',
        'towing_weight' => 'integer',
        'ownership_tax' => 'integer',
        'first_registration_date' => 'date',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'category_name',
        'brand_name',
        'model_year_name',
        'fuel_type_name',
        'vehicle_list_status_name',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('defaultOrder', function (Builder $query) {
            // Only apply default ordering if no explicit orderBy is set
            if (empty($query->getQuery()->orders)) {
                $query->orderBy('id', 'desc');
            }
        });
    }

    /**
     * Get cached lookup value
     * Checks static cache first, then Laravel cache, then database
     */
    public static function getCachedLookup(string $table, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $cacheKey = "{$table}_{$id}";

        // Check static cache first
        if (isset(self::$lookupCache[$cacheKey])) {
            return self::$lookupCache[$cacheKey];
        }

        // Check Laravel cache (24 hour TTL)
        $cached = Cache::remember("vehicle_lookup_{$cacheKey}", 86400, function () use ($table, $id) {
            $model = match ($table) {
                'categories' => Category::find($id),
                'brands' => Brand::find($id),
                'model_years' => ModelYear::find($id),
                'fuel_types' => FuelType::find($id),
                'vehicle_list_statuses' => VehicleListStatus::find($id),
                default => null,
            };

            return $model?->name;
        });

        // Store in static cache for this request
        if ($cached !== null) {
            self::$lookupCache[$cacheKey] = $cached;
        }

        return $cached;
    }

    /**
     * Get category name attribute (cached)
     */
    public function getCategoryNameAttribute(): ?string
    {
        return self::getCachedLookup('categories', $this->category_id);
    }

    /**
     * Get brand name attribute (cached)
     */
    public function getBrandNameAttribute(): ?string
    {
        return self::getCachedLookup('brands', $this->brand_id);
    }

    /**
     * Get model year name attribute (cached)
     */
    public function getModelYearNameAttribute(): ?string
    {
        return self::getCachedLookup('model_years', $this->model_year_id);
    }

    /**
     * Get fuel type name attribute (cached)
     */
    public function getFuelTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('fuel_types', $this->fuel_type_id);
    }

    /**
     * Get vehicle list status name attribute (cached)
     */
    public function getVehicleListStatusNameAttribute(): ?string
    {
        return self::getCachedLookup('vehicle_list_statuses', $this->vehicle_list_status_id);
    }

    /**
     * Get dealer for this vehicle
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get user (creator) for this vehicle
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get location for this vehicle
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get vehicle details for this vehicle
     */
    public function details(): HasOne
    {
        return $this->hasOne(VehicleDetail::class);
    }

    /**
     * Get images for this vehicle
     */
    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    /**
     * Get favorites for this vehicle
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get leads for this vehicle
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get price history for this vehicle
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * Get view logs for this vehicle
     */
    public function viewLogs(): HasMany
    {
        return $this->hasMany(ListingViewsLog::class);
    }

    /**
     * Get equipment for this vehicle (many-to-many)
     */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'vehicle_equipment')
            ->withTimestamps();
    }
}
