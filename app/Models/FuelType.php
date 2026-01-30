<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get vehicles with this fuel type
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'fuel_type_id');
    }
}
