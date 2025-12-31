<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transmission extends Model
{
    use HasFactory, CachedLookup;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get vehicles with this transmission
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'transmission_id');
    }
}
