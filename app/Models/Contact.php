<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'type',
        'phone',
        'email',
        'source',
        'name',
        'address',
        'company_name',
        'contact_person',
        'images',
        'remarks',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get purchases for this contact
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get sales for this contact
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get expenses for this contact
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get enquiries for this contact
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get purchased vehicles count attribute
     */
    public function getPurchasedVehiclesCountAttribute(): int
    {
        return $this->purchases()->count();
    }

    /**
     * Get sold vehicles count attribute
     */
    public function getSoldVehiclesCountAttribute(): int
    {
        return $this->sales()->count();
    }
}


