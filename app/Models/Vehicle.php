<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dealer_id',
        'user_id',
        'location_id',
        'registration',
        'vin',
        'title',
        'description',
        'price',
        'mileage',
        'year',
        'fuel_type_id',
        'transmission_id',
        'body_type',
        'has_carplay',
        'has_adaptive_cruise',
        'is_electric',
        'specs',
        'equipment',
        'vehicle_list_status_id',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'price' => 'integer',
        'mileage' => 'integer',
        'year' => 'integer',
        'has_carplay' => 'boolean',
        'has_adaptive_cruise' => 'boolean',
        'is_electric' => 'boolean',
        'specs' => 'array',
        'equipment' => 'array',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Get fuel type for this vehicle
     */
    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    /**
     * Get transmission for this vehicle
     */
    public function transmission(): BelongsTo
    {
        return $this->belongsTo(Transmission::class);
    }

    /**
     * Get vehicle list status for this vehicle
     */
    public function vehicleListStatus(): BelongsTo
    {
        return $this->belongsTo(VehicleListStatus::class);
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
}
