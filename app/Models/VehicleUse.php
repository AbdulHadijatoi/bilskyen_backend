<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;

class VehicleUse extends Model
{
    // use CachedLookup;

    public $timestamps = false;

    protected $table = 'uses';

    protected $fillable = [
        'name',
    ];
}

