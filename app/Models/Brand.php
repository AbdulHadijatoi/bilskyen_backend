<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Legacy `brands` table (admin/seeders). Public listings use {@see DmrBrand}.
 */
class Brand extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }
}
