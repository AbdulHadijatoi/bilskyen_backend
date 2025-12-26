<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GearType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}

