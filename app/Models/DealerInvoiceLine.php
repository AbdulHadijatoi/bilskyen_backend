<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerInvoiceLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dealer_invoice_id',
        'vehicle_id',
        'description',
        'days',
        'unit_price_cents',
        'line_total_cents',
        'created_at',
    ];

    protected $casts = [
        'days' => 'integer',
        'unit_price_cents' => 'integer',
        'line_total_cents' => 'integer',
        'created_at' => 'datetime',
    ];

    public function dealerInvoice(): BelongsTo
    {
        return $this->belongsTo(DealerInvoice::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
