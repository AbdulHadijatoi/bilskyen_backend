<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceType extends Model
{
    use FirstOrCreateInsensitive;
    use SoftDeletes;

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];
}

