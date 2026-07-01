<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingUnsubscribe extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'dealer_id',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];
}
