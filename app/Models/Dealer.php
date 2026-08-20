<?php

namespace App\Models;

use App\Constants\VehicleListStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dealer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'cvr',
        'address',
        'city',
        'marketplace_city_id',
        'postcode',
        'country_code',
        'logo_path',
        'finance_partner_url',
        'finance_calculator_enabled',
        'google_review_url',
        'google_place_id',
        'theme_primary_color',
        'theme_secondary_color',
        'onboarding_step',
        'onboarding_completed_at',
        'stripe_customer_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'finance_calculator_enabled' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    /**
     * Get logo URL attribute
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Get owner (user who owns this dealer)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get staff members for this dealer
     */
    public function staff(): HasMany
    {
        return $this->hasMany(DealerStaff::class);
    }

    /**
     * Get vehicles for this dealer
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Slugs that must never be a public dealer profile (placeholder / default values).
     */
    public static function isPublicProfileSlug(?string $slug): bool
    {
        $slug = strtolower(trim((string) $slug));

        return $slug !== '' && ! in_array($slug, ['dealer'], true);
    }

    /**
     * Whether this dealer has at least one published vehicle (no eager-load of inventory).
     */
    public function hasPublishedVehicles(): bool
    {
        return $this->vehicles()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->exists();
    }

    public function marketplaceCity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCity::class, 'marketplace_city_id');
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

    public function subscriptionChangeRequests(): HasMany
    {
        return $this->hasMany(DealerSubscriptionChangeRequest::class);
    }

    public function pendingSubscriptionChangeRequest(): ?DealerSubscriptionChangeRequest
    {
        return $this->subscriptionChangeRequests()->pending()->latest('id')->first();
    }

    /**
     * Get plan overrides for this dealer
     */
    public function planOverrides(): HasMany
    {
        return $this->hasMany(DealerPlanOverride::class);
    }

    /**
     * Get plan availability records for this dealer
     */
    public function planAvailability(): HasMany
    {
        return $this->hasMany(PlanAvailability::class);
    }

    /**
     * Generate slug from owner's name if not provided
     */
    public function setSlugAttribute($value): void
    {
        if (empty($value) && $this->owner && !empty($this->owner->name)) {
            $this->attributes['slug'] = Str::slug($this->owner->name);
        } else {
            $this->attributes['slug'] = $value;
        }
    }

    /**
     * Scope to find dealer by slug
     */
    public function scopeFindBySlug($query, string $slug)
    {
        return $query->where('slug', $slug)->first();
    }
}
