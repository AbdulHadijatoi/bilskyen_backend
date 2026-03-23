<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrVehicleUse extends Model
{
    protected $table = 'dmr_vehicle_uses';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'vehicle_use_id');
    }
}
