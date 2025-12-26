<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}

