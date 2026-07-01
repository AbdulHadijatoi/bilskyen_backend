<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerWebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'status',
        'response_code',
        'response_body',
        'attempted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempted_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(DealerWebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
