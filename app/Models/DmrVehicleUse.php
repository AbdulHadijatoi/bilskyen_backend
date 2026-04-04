<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrVehicleUse extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_vehicle_uses';

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'vehicle_use_id');
    }
}
