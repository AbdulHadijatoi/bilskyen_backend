<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingHealthScore extends Model
{
    protected $fillable = [
        'vehicle_id',
        'dealer_id',
        'score',
        'grade',
        'priority_score',
        'issues',
        'metrics',
        'pricing',
        'computed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'priority_score' => 'integer',
        'issues' => 'array',
        'metrics' => 'array',
        'pricing' => 'array',
        'computed_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
