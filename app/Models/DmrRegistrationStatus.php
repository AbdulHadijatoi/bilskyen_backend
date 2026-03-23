<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrRegistrationStatus extends Model
{
    protected $table = 'dmr_registration_statuses';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'registration_status_id');
    }
}
