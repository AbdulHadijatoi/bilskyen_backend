<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DealerWebhookEndpoint extends Model
{
    protected $fillable = [
        'dealer_id',
        'url',
        'secret',
        'events',
        'enabled',
    ];

    protected $casts = [
        'events' => 'array',
        'enabled' => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DealerWebhookDelivery::class, 'webhook_endpoint_id');
    }

    public static function generateSecret(): string
    {
        return Str::random(32);
    }
}
