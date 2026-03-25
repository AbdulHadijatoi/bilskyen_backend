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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    private static array $lookupCache = [];

    protected $fillable = [
        'dmr_fact_vehicle_id',
        'user_id',
        'dealer_id',
        'title',
        'slug',
        'registration',
        'price',
        'calculated_ownership_tax',
        'vehicle_list_status_id',
        'published_at',
        'description',
        'address',
        'postcode',
        'gear_type_id',
        'km_driven',
        'battery_capacity',
        'range_km',
        'charging_type',
        'condition_id',
        'servicebog',
    ];

    protected $casts = [
        'price' => 'integer',
        'calculated_ownership_tax' => 'integer',
        'km_driven' => 'integer',
        'battery_capacity' => 'integer',
        'range_km' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'brand_name',
        'model_name',
        'model_year_name',
        'fuel_type_name',
        'gear_type_name',
        'engine_power_hp',
        'vehicle_list_status_name',
        'first_registration_date',
        'seller_address',
        'seller_postcode',
    ];

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

    public function getBrandNameAttribute(): ?string
    {
        return $this->dmrFactVehicle?->variant?->model?->brand?->name;
    }

    public function getModelNameAttribute(): ?string
    {
        return $this->dmrFactVehicle?->variant?->model?->name;
    }

    public function getFuelTypeNameAttribute(): ?string
    {
        $fv = $this->dmrFactVehicle;
        if (! $fv) {
            return null;
        }
        $lines = $fv->relationLoaded('drivmiddelLines')
            ? $fv->drivmiddelLines
            : $fv->drivmiddelLines()->orderBy('line_order')->get();
        $sorted = $lines->sortBy('line_order')->values();
        $primary = $sorted->first(fn ($line) => (bool) $line->drivmiddel_primaer) ?? $sorted->first();

        return $primary?->driveEnergy?->name;
    }

    public function getGearTypeNameAttribute(): ?string
    {
        return self::getCachedLookup('gear_types', $this->gear_type_id);
    }

    public function getEnginePowerHpAttribute(): ?float
    {
        $kw = $this->dmrFactVehicle?->motor_stoerste_effekt;
        if ($kw === null) {
            return null;
        }

        return round((float) $kw * 1.36, 2);
    }

    public function getModelYearNameAttribute(): ?string
    {
        $y = $this->dmrFactVehicle?->model_aar;

        return $y !== null ? (string) $y : null;
    }

    /** DMR variant label (legacy "version" field in views). */
    public function getVersionAttribute(): ?string
    {
        return $this->dmrFactVehicle?->variant?->name;
    }

    public function getFirstRegistrationDateAttribute(): ?Carbon
    {
        return $this->dmrFactVehicle?->foerste_registrering_dato;
    }

    public function getVehicleListStatusNameAttribute(): ?string
    {
        return self::getCachedLookup('vehicle_list_statuses', $this->vehicle_list_status_id);
    }

    public function getSellerAddressAttribute(): ?string
    {
        return $this->attributes['address'] ?? null;
    }

    public function getSellerPostcodeAttribute(): ?string
    {
        return $this->attributes['postcode'] ?? null;
    }

    /**
     * Blade parity: legacy templates used vehicle_details; expose DMR-backed read model.
     */
    public function getDetailsAttribute(): VehicleDetailPresenter
    {
        return new VehicleDetailPresenter($this);
    }

    public function getTitleAttribute($value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        $parts = array_filter([
            $this->getAttribute('brand_name'),
            $this->getAttribute('model_name'),
        ]);

        return ! empty($parts) ? implode(' ', $parts) : null;
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

    public function vehicleListStatus(): BelongsTo
    {
        return $this->belongsTo(VehicleListStatus::class);
    }
}
