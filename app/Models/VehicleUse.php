<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleUse extends Model
{
    public $timestamps = false;

    protected $table = 'uses';

    protected $fillable = [
        'name',
    ];
}

