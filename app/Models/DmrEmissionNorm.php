<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrEmissionNorm extends Model
{
    protected $table = 'dmr_emission_norms';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'emission_norm_id');
    }
}
