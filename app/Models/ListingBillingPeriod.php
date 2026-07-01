<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingBillingPeriod extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'dealer_id',
        'plan_id',
        'billing_date',
        'amount_cents',
        'status',
        'dealer_invoice_id',
        'created_at',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'amount_cents' => 'integer',
        'created_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function dealerInvoice(): BelongsTo
    {
        return $this->belongsTo(DealerInvoice::class);
    }
}
