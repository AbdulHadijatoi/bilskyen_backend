<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'feature_value_type_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get feature value type for this feature
     */
    public function featureValueType(): BelongsTo
    {
        return $this->belongsTo(FeatureValueType::class);
    }

    /**
     * Get plans that have this feature
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
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
     * Get user plan overrides for this feature
     */
    public function userPlanOverrides(): HasMany
    {
        return $this->hasMany(UserPlanOverride::class);
    }

    /**
     * Get dealer plan overrides for this feature
     */
    public function dealerPlanOverrides(): HasMany
    {
        return $this->hasMany(DealerPlanOverride::class);
    }
}
