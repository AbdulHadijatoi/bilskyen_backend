<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;

class ModelYear extends Model
{
    // use CachedLookup;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
