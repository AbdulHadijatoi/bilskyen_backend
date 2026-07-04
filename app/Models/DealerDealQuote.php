<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerDealQuote extends Model
{
    protected $fillable = [
        'dealer_id',
        'lead_id',
        'vehicle_id',
        'created_by_user_id',
        'list_price',
        'discount_amount',
        'trade_in_value',
        'finance_apr',
        'finance_term_months',
        'monthly_payment',
        'notes',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'finance_apr' => 'float',
        'sent_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getNetPriceAttribute(): int
    {
        return max(0, (int) $this->list_price - (int) $this->discount_amount - (int) $this->trade_in_value);
    }
}
