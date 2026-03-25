<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrFactVehicle extends Model
{
    protected $table = 'dmr_fact_vehicles';

    const UPDATED_AT = null;

    protected $fillable = [
        'stel_nummer',
        'registrering_nummer',
        'variant_id',
        'vehicle_use_id',
        'body_type_id',
        'colour_id',
        'emission_norm_id',
        'registration_status_id',
        'foerste_registrering_dato',
        'registrering_status_dato',
        'emission_co',
        'emission_nox',
        'partikel_filter',
        'motor_stoerste_effekt',
        'motor_slag_volumen',
        'aksel_antal',
        'antal_doere',
        'antal_gear',
        'maksimum_hastighed',
        'model_aar',
        'ncap_test',
        'siddepladser_maksimum',
        'siddepladser_minimum',
        'teknisk_total_vaegt',
        'oevrigt_udstyr',
        'etl_load_id',
    ];

    protected $casts = [
        'foerste_registrering_dato' => 'datetime',
        'registrering_status_dato' => 'datetime',
        'emission_co' => 'decimal:6',
        'emission_nox' => 'decimal:6',
        'partikel_filter' => 'boolean',
        'motor_stoerste_effekt' => 'decimal:6',
        'motor_slag_volumen' => 'decimal:6',
        'aksel_antal' => 'integer',
        'antal_doere' => 'integer',
        'antal_gear' => 'integer',
        'maksimum_hastighed' => 'integer',
        'model_aar' => 'integer',
        'ncap_test' => 'boolean',
        'siddepladser_maksimum' => 'integer',
        'siddepladser_minimum' => 'integer',
        'teknisk_total_vaegt' => 'integer',
        'created_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(DmrVariant::class, 'variant_id');
    }

    public function vehicleUse(): BelongsTo
    {
        return $this->belongsTo(DmrVehicleUse::class, 'vehicle_use_id');
    }

    public function bodyType(): BelongsTo
    {
        return $this->belongsTo(DmrBodyType::class, 'body_type_id');
    }

    public function colour(): BelongsTo
    {
        return $this->belongsTo(DmrColour::class, 'colour_id');
    }

    public function emissionNorm(): BelongsTo
    {
        return $this->belongsTo(DmrEmissionNorm::class, 'emission_norm_id');
    }

    public function registrationStatus(): BelongsTo
    {
        return $this->belongsTo(DmrRegistrationStatus::class, 'registration_status_id');
    }

    public function equipmentLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleEquipment::class, 'vehicle_id');
    }

    public function drivmiddelLines(): HasMany
    {
        return $this->hasMany(DmrBridgeVehicleDrivmiddel::class, 'vehicle_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'dmr_fact_vehicle_id');
    }
}
