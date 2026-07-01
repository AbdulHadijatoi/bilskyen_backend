<?php

namespace App\Models;

use App\Constants\SubscriptionChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerSubscriptionChangeRequest extends Model
{
    protected $fillable = [
        'dealer_id',
        'requested_plan_id',
        'billing_cycle',
        'starts_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', SubscriptionChangeRequestStatus::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === SubscriptionChangeRequestStatus::PENDING;
    }
}
