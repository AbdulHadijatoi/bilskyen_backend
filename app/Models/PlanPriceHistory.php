<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPriceHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'price',
        'currency',
        'billing_cycle',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get plan for this price history
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
