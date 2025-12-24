<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerPlanOverride extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'dealer_id',
        'feature_id',
        'override_value',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get dealer for this override
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get feature for this override
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
