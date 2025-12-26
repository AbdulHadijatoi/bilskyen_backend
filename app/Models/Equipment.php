<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get vehicles with this equipment
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_equipment')
            ->withTimestamps();
    }
}
