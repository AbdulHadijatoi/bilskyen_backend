<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrEmissionNorm extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_emission_norms';

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'emission_norm_id');
    }
}
