<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSpecDefinition extends Model
{
    protected $fillable = [
        'brand_id',
        'model_id',
        'variant_id',
        'model_year',
        'name',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'brand_id' => 'integer',
            'model_id' => 'integer',
            'variant_id' => 'integer',
            'model_year' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DmrModel::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(DmrVariant::class);
    }

    /**
     * Rows scoped to the same brand, model, variant, and model year as the listing.
     *
     * @param  Builder<VehicleSpecDefinition>  $query
     * @return Builder<VehicleSpecDefinition>
     */
    public function scopeMatchingVehicle(Builder $query, Vehicle $vehicle): Builder
    {
        return $query
            ->where('brand_id', $vehicle->brand_id)
            ->where('model_id', $vehicle->model_id)
            ->where('variant_id', $vehicle->variant_id)
            ->where('model_year', (int) $vehicle->model_year);
    }
}
