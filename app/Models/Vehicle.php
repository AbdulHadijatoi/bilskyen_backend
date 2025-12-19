<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'registration_number',
        'make',
        'model',
        'variant',
        'year',
        'vehicle_type',
        'vin',
        'engine_number',
        'odometer',
        'status',
        'ownership_count',
        'transmission_type',
        'fuel_type',
        'color',
        'condition',
        'accident_history',
        'blacklist_flags',
        'inventory_date',
        'features',
        'pending_works',
        'listing_price',
        'images',
        'description',
        'remarks',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'year' => 'integer',
        'odometer' => 'integer',
        'ownership_count' => 'integer',
        'accident_history' => 'boolean',
        'blacklist_flags' => 'array',
        'inventory_date' => 'date',
        'features' => 'array',
        'pending_works' => 'array',
        'listing_price' => 'integer',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get purchases for this vehicle
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get sales for this vehicle
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get expenses for this vehicle
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get enquiries for this vehicle
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get purchases count attribute
     */
    public function getPurchasesCountAttribute(): int
    {
        return $this->purchases()->count();
    }

    /**
     * Get sales count attribute
     */
    public function getSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }
}

