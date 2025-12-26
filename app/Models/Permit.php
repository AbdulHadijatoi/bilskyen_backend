<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
