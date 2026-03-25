<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipTaxRule extends Model
{
    protected $table = 'ownership_tax_rules';

    protected $fillable = [
        'registration_year_from',
        'registration_year_to',
        'km_per_liter_from',
        'km_per_liter_to',
        'dmr_drive_energy_id',
        'tax_amount',
    ];

    protected $casts = [
        'registration_year_from' => 'integer',
        'registration_year_to' => 'integer',
        'km_per_liter_from' => 'decimal:3',
        'km_per_liter_to' => 'decimal:3',
        'dmr_drive_energy_id' => 'integer',
        'tax_amount' => 'integer',
    ];

    public function driveEnergy(): BelongsTo
    {
        return $this->belongsTo(DmrDriveEnergy::class, 'dmr_drive_energy_id');
    }
}

