<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    use CachedLookup;

    public $timestamps = false;
    protected $table = 'models';
    
    protected $fillable = [
        'brand_id',
        'name',
    ];

    /**
     * Get brand for this model
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get vehicles for this model
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'model_id');
    }
}

