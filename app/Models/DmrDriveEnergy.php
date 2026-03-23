<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrDriveEnergy extends Model
{
    protected $table = 'dmr_drive_energies';

    public $timestamps = false;

    protected $fillable = [
        'type_nummer',
        'name',
    ];

    public function drivmiddelLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleDrivmiddel::class, 'drive_energy_id');
    }
}
