<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Euronom extends Model
{

    public $timestamps = false;

    protected $table = 'euronorms';
    
    protected $fillable = [
        'name',
    ];
}
