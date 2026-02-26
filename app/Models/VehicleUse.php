<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;

class VehicleUse extends Model
{
    use FirstOrCreateInsensitive;
    public $timestamps = false;

    protected $table = 'uses';

    protected $fillable = [
        'name',
    ];
}

