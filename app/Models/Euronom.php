<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;

class Euronom extends Model
{
    use FirstOrCreateInsensitive;

    public $timestamps = false;

    protected $table = 'euronorms';
    
    protected $fillable = [
        'name',
    ];
}
