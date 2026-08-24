<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingFunnelEvent extends Model
{
    public $timestamps = false;

    protected $table = 'listing_funnel_events';

    protected $fillable = [
        'session_id',
        'vehicle_id',
        'traffic_source',
        'event_name',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
