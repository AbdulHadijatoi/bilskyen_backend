<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrMeasurementNorm extends Model
{
    protected $table = 'dmr_measurement_norms';

    public $timestamps = false;

    protected $fillable = [
        'type_nummer',
        'name',
    ];

    public function drivmiddelLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleDrivmiddel::class, 'measurement_norm_id');
    }
}
