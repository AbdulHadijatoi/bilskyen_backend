<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;

class Euronom extends Model
{
    use CachedLookup;

    public $timestamps = false;

    protected $table = 'euronorms';
    
    protected $fillable = [
        'name',
    ];
}
