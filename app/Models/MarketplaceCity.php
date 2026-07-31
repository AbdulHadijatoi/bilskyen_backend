<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCity extends Model
{
    public const MIN_VEHICLES_FOR_INDEX = 3;

    public const MIN_DEALERS_FOR_INDEX = 1;

    protected $fillable = [
        'name',
        'slug',
        'region',
        'aliases',
        'published_vehicle_count',
        'dealer_count',
        'min_price',
        'max_price',
        'top_brands',
        'is_active',
        'last_computed_at',
    ];

    protected $casts = [
        'aliases' => 'array',
        'top_brands' => 'array',
        'min_price' => 'float',
        'max_price' => 'float',
        'is_active' => 'boolean',
        'last_computed_at' => 'datetime',
        'published_vehicle_count' => 'integer',
        'dealer_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class, 'marketplace_city_id');
    }

    public function isCarsIndexable(): bool
    {
        return $this->is_active
            && $this->published_vehicle_count >= self::MIN_VEHICLES_FOR_INDEX;
    }

    public function isDealersIndexable(): bool
    {
        return $this->is_active
            && $this->dealer_count >= self::MIN_DEALERS_FOR_INDEX;
    }

    /**
     * @return list<string>
     */
    public function matchNames(): array
    {
        $names = array_filter(array_merge(
            [$this->name],
            is_array($this->aliases) ? $this->aliases : []
        ));

        return array_values(array_unique(array_map(
            static fn ($n) => mb_strtolower(trim((string) $n)),
            $names
        )));
    }
}
