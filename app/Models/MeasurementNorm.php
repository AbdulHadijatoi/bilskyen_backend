<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementNorm extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'measurement_norm_id');
    }
}
