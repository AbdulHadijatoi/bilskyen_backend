<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmrColour extends Model
{
    protected $table = 'dmr_colours';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(DmrFactVehicle::class, 'colour_id');
    }
}
