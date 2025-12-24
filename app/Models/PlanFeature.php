<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
    ];

    /**
     * Get plan for this plan feature
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get feature for this plan feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
