<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrEquipmentType extends Model
{
    protected $table = 'dmr_equipment_types';

    public $timestamps = false;

    protected $fillable = [
        'type_nummer',
        'name',
    ];

    public function equipmentLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleEquipment::class, 'equipment_type_id');
    }
}
