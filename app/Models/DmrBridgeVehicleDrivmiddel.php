<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmrBridgeVehicleDrivmiddel extends Model
{
    protected $table = 'dmr_bridge_vehicle_drivmiddel';

    const UPDATED_AT = null;

    protected $fillable = [
        'vehicle_id',
        'line_order',
        'drive_energy_id',
        'measurement_norm_id',
        'drivmiddel_primaer',
        'motor_km_per_liter',
        'miljoe_co2_udslip',
        'motor_elektrisk_forbrug',
        'motor_braendselscelle',
    ];

    protected $casts = [
        'line_order' => 'integer',
        'drivmiddel_primaer' => 'boolean',
        'motor_km_per_liter' => 'decimal:6',
        'miljoe_co2_udslip' => 'decimal:6',
        'motor_elektrisk_forbrug' => 'decimal:6',
        'motor_braendselscelle' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(DmrFactVehicle::class, 'vehicle_id');
    }

    public function driveEnergy(): BelongsTo
    {
        return $this->belongsTo(DmrDriveEnergy::class, 'drive_energy_id');
    }

    public function measurementNorm(): BelongsTo
    {
        return $this->belongsTo(DmrMeasurementNorm::class, 'measurement_norm_id');
    }
}
