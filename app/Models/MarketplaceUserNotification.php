<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceUserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'vehicle_id',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
