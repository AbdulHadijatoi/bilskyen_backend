<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerInvoice extends Model
{
    protected $fillable = [
        'dealer_id',
        'period_start',
        'period_end',
        'total_cents',
        'currency',
        'status',
        'notes',
        'approved_by',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_cents' => 'integer',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DealerInvoiceLine::class);
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(ListingBillingPeriod::class);
    }
}
