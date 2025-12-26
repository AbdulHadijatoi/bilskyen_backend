<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}

