<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class VehicleDetail extends Model
{
    /**
     * Static cache for lookup data (in-memory cache)
     */
    private static array $lookupCache = [];

    protected $fillable = [
        'vehicle_id',
        'description',
        'views_count',
        'vin_location',
        'type_id',
        'version',
        'type_name',
        'registration_status',
        'registration_status_updated_date',
        'expire_date',
        'status_updated_date',
        'total_weight',
        'vehicle_weight',
        'technical_total_weight',
        'coupling',
        'towing_weight_brakes',
        'minimum_weight',
        'gross_combination_weight',
        'fuel_efficiency',
        'engine_displacement',
        'engine_cylinders',
        'engine_code',
        'category',
        'last_inspection_date',
        'last_inspection_result',
        'last_inspection_odometer',
        'type_approval_code',
        'top_speed',
        'doors',
        'minimum_seats',
        'maximum_seats',
        'wheels',
        'extra_equipment',
        'axles',
        'drive_axles',
        'wheelbase',
        'leasing_period_start',
        'leasing_period_end',
        'use_id',
        'color_id',
        'body_type_id',
        'dispensations',
        'permits',
        'ncap_five',
        'airbags',
        'integrated_child_seats',
        'seat_belt_alarms',
        'euronorm',
        'price_type_id',
        'condition_id',
        'gear_type_id',
        'sales_type_id',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'total_weight' => 'integer',
        'vehicle_weight' => 'integer',
        'technical_total_weight' => 'integer',
        'coupling' => 'integer',
        'towing_weight_brakes' => 'integer',
        'minimum_weight' => 'integer',
        'gross_combination_weight' => 'integer',
        'fuel_efficiency' => 'decimal:2',
        'engine_displacement' => 'integer',
        'engine_cylinders' => 'integer',
        'last_inspection_odometer' => 'integer',
        'top_speed' => 'integer',
        'doors' => 'integer',
        'minimum_seats' => 'integer',
        'maximum_seats' => 'integer',
        'wheels' => 'integer',
        'axles' => 'integer',
        'drive_axles' => 'integer',
        'wheelbase' => 'integer',
        'ncap_five' => 'boolean',
        'airbags' => 'integer',
        'integrated_child_seats' => 'integer',
        'seat_belt_alarms' => 'integer',
        'registration_status_updated_date' => 'date',
        'expire_date' => 'date',
        'status_updated_date' => 'date',
        'last_inspection_date' => 'date',
        'leasing_period_start' => 'date',
        'leasing_period_end' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'type_name_resolved',
        'use_name',
        'color_name',
        'body_type_name',
        'price_type_name',
        'condition_name',
        'gear_type_name',
        'sales_type_name',
    ];

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
        $cached = Cache::remember("vehicle_detail_lookup_{$cacheKey}", 86400, function () use ($table, $id) {
            $model = match ($table) {
                'types' => Type::find($id),
                'uses' => \App\Models\VehicleUse::find($id),
                'colors' => Color::find($id),
                'body_types' => BodyType::find($id),
                'price_types' => PriceType::find($id),
                'conditions' => Condition::find($id),
                'gear_types' => GearType::find($id),
                'sales_types' => SalesType::find($id),
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
     * Get type name attribute (cached)
     */
    public function getTypeNameResolvedAttribute(): ?string
    {
        return self::getCachedLookup('types', $this->type_id);
    }

    /**
     * Get use name attribute (cached)
     */
    public function getUseNameAttribute(): ?string
    {
        return self::getCachedLookup('uses', $this->use_id);
    }

    /**
     * Get color name attribute (cached)
     */
    public function getColorNameAttribute(): ?string
    {
        return self::getCachedLookup('colors', $this->color_id);
    }

    /**
     * Get body type name attribute (cached)
     */
    public function getBodyTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('body_types', $this->body_type_id);
    }

    /**
     * Get price type name attribute (cached)
     */
    public function getPriceTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('price_types', $this->price_type_id);
    }

    /**
     * Get condition name attribute (cached)
     */
    public function getConditionNameAttribute(): ?string
    {
        return self::getCachedLookup('conditions', $this->condition_id);
    }

    /**
     * Get gear type name attribute (cached)
     */
    public function getGearTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('gear_types', $this->gear_type_id);
    }

    /**
     * Get sales type name attribute (cached)
     */
    public function getSalesTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('sales_types', $this->sales_type_id);
    }

    /**
     * Get price type for this detail
     */
    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }

    /**
     * Get condition for this detail
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(Condition::class);
    }

    /**
     * Get gear type for this detail
     */
    public function gearType(): BelongsTo
    {
        return $this->belongsTo(GearType::class);
    }

    /**
     * Get sales type for this detail
     */
    public function salesType(): BelongsTo
    {
        return $this->belongsTo(SalesType::class);
    }

    /**
     * Get vehicle for this detail
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
