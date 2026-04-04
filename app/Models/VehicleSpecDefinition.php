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
        'model_year_from',
        'model_year_to',
        'name',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'brand_id' => 'integer',
            'model_id' => 'integer',
            'variant_id' => 'integer',
            'model_year_from' => 'integer',
            'model_year_to' => 'integer',
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
     * Catalog rows for the same brand, model, and model year range.
     *
     * If the listing has a variant, only definitions with the same variant_id match.
     * If the listing has no variant, only definitions with null variant_id match (model-wide specs).
     *
     * @param  Builder<VehicleSpecDefinition>  $query
     * @return Builder<VehicleSpecDefinition>
     */
    public function scopeMatchingVehicle(Builder $query, Vehicle $vehicle): Builder
    {
        $year = (int) $vehicle->model_year;

        $query = $query
            ->where('brand_id', $vehicle->brand_id)
            ->where('model_id', $vehicle->model_id)
            ->where('model_year_from', '<=', $year)
            ->where('model_year_to', '>=', $year);

        $variantId = $vehicle->variant_id;
        if ($variantId !== null && $variantId !== '') {
            return $query->where('variant_id', (int) $variantId);
        }

        return $query->whereNull('variant_id');
    }
}
