<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'city',
        'postcode',
        'region',
        'country_code',
        'latitude',
        'longitude',
    ];
}
