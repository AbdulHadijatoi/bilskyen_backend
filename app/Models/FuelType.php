<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    use FirstOrCreateInsensitive;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
