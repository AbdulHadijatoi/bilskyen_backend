<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    // use CachedLookup;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get models for this brand
     */
    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }
}
