<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider',
        'event_id',
        'type',
        'status',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
