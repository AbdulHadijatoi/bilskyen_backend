<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrDriveEnergy extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_drive_energies';

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'type_nummer',
        'name',
    ];

    public function drivmiddelLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleDrivmiddel::class, 'drive_energy_id');
    }
}
