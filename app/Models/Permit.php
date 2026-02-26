<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;

class Permit extends Model
{
    use FirstOrCreateInsensitive;
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
