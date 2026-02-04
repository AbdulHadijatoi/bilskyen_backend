<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'trial_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get price history for this plan
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PlanPriceHistory::class);
    }

    /**
     * Get features for this plan
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot('value');
    }

    /**
     * Get plan features pivot records
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get dealer subscriptions for this plan
     */
    public function dealerSubscriptions(): HasMany
    {
        return $this->hasMany(DealerSubscription::class);
    }

    /**
     * Get plan availability rules
     */
    public function availability(): HasMany
    {
        return $this->hasMany(PlanAvailability::class);
    }

    /**
     * Check if plan is available to a specific dealer
     * 
     * @param int $dealerId
     * @param array $dealerRoleIds Array of role IDs that dealer's users have
     * @return bool
     */
    public function isAvailableToDealer(int $dealerId, array $dealerRoleIds = []): bool
    {
        // Check if dealer is specifically allowed
        $dealerAvailable = $this->availability()
            ->where('dealer_id', $dealerId)
            ->where('is_enabled', true)
            ->exists();

        if ($dealerAvailable) {
            return true;
        }

        // Check if any of dealer's roles are allowed
        if (!empty($dealerRoleIds)) {
            $roleAvailable = $this->availability()
                ->whereIn('allowed_role_id', $dealerRoleIds)
                ->where('is_enabled', true)
                ->exists();

            if ($roleAvailable) {
                return true;
            }
        }

        return false;
    }
}
