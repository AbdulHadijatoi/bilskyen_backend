<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmrBridgeVehicleEquipment extends Model
{
    protected $table = 'dmr_bridge_vehicle_equipment';

    const UPDATED_AT = null;

    protected $fillable = [
        'vehicle_id',
        'line_order',
        'equipment_type_id',
        'antal',
        'vises_ved_syn',
        'vises_ved_forespoergsel',
        'vises_ved_standard_oprettelse',
    ];

    protected $casts = [
        'line_order' => 'integer',
        'antal' => 'integer',
        'vises_ved_syn' => 'boolean',
        'vises_ved_forespoergsel' => 'boolean',
        'vises_ved_standard_oprettelse' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(DmrFactVehicle::class, 'vehicle_id');
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(DmrEquipmentType::class, 'equipment_type_id');
    }
}
