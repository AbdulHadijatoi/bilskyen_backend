<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class VehicleSpecDefinition extends Model
{
    protected $fillable = [
        'brand_id',
        'model_id',
        'variant_ids',
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
            'variant_ids' => 'array',
            'model_year_from' => 'integer',
            'model_year_to' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (VehicleSpecDefinition $row): void {
            $ids = $row->normalizedVariantIds();
            $row->variant_ids = $ids === [] ? null : $ids;
        });
    }

    /**
     * @return list<int>
     */
    public function normalizedVariantIds(): array
    {
        $raw = $this->variant_ids;
        if ($raw === null || $raw === []) {
            return [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $ids[] = $n;
            }
        }

        return array_values(array_unique($ids));
    }

    public function isModelWide(): bool
    {
        return $this->normalizedVariantIds() === [];
    }

    /**
     * @return Collection<int, DmrVariant>
     */
    public function resolveVariants(): Collection
    {
        if ($this->isModelWide()) {
            return collect();
        }

        return DmrVariant::query()
            ->whereIn('id', $this->normalizedVariantIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DmrModel::class, 'model_id');
    }

    /**
     * Catalog rows for the same brand, model, and model year range.
     *
     * Model-wide definitions (no variant_ids) apply to every listing of that model year.
     * When variant_ids is set, the listing must have a variant and it must be included in that set.
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
            $vid = (int) $variantId;

            return $query->where(function (Builder $q) use ($vid): void {
                $q->whereNull('variant_ids')
                    ->orWhereJsonContains('variant_ids', $vid);
            });
        }

        return $query->whereNull('variant_ids');
    }
}
