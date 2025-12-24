<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionStatus extends Model
{
    use HasFactory;

    public const TRIAL = 1;
    public const ACTIVE = 2;
    public const EXPIRED = 3;
    public const CANCELED = 4;
    public const SCHEDULED = 5;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get dealer subscriptions with this status
     */
    public function dealerSubscriptions(): HasMany
    {
        return $this->hasMany(DealerSubscription::class, 'subscription_status_id');
    }
}
