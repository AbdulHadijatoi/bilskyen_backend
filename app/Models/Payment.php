<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'dealer_id',
        'provider',
        'purpose',
        'payable_type',
        'payable_id',
        'amount_cents',
        'currency',
        'status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
