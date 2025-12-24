<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'cvr',
        'address',
        'city',
        'postcode',
        'country_code',
        'logo_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get logo URL attribute
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Get users (staff) for this dealer
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dealer_users')
            ->withPivot('role_id')
            ->withPivot('created_at');
    }

    /**
     * Get dealer users pivot records
     */
    public function dealerUsers(): HasMany
    {
        return $this->hasMany(DealerUser::class);
    }

    /**
     * Get vehicles for this dealer
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get leads for this dealer
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get subscriptions for this dealer
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(DealerSubscription::class);
    }

    /**
     * Get plan overrides for this dealer
     */
    public function planOverrides(): HasMany
    {
        return $this->hasMany(DealerPlanOverride::class);
    }
}
