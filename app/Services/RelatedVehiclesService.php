<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Content-based "more like this" listings for the public vehicle detail page.
 *
 * Ranking follows marketplace practice: same brand+model first, then similar
 * price/year/fuel, with location as a soft boost and a fallback chain so thin
 * inventory still fills the grid.
 */
class RelatedVehiclesService
{
    public const DEFAULT_LIMIT = 8;

    private const CACHE_MINUTES = 10;

    private const CANDIDATE_POOL = 24;

    private const PRICE_BAND = 0.20;

    private const PRICE_SCORE_BAND = 0.15;

    /**
     * @return Collection<int, Vehicle>
     */
    public function forVehicle(Vehicle $vehicle, int $limit = self::DEFAULT_LIMIT): Collection
    {
        $limit = max(1, $limit);

        return Cache::remember(
            $this->cacheKey($vehicle, $limit),
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => $this->resolve($vehicle, $limit)
        );
    }

    /**
     * @return Collection<int, Vehicle>
     */
    private function resolve(Vehicle $vehicle, int $limit): Collection
    {
        $candidateIds = $this->candidateIds($vehicle, $limit);
        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return $this->scoredQuery($vehicle)
            ->whereIn('vehicles.id', $candidateIds->all())
            ->with($this->cardEagerLoads())
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, int>
     */
    private function candidateIds(Vehicle $vehicle, int $limit): Collection
    {
        $ids = collect();

        foreach ($this->tierFilters($vehicle) as $applyTier) {
            if ($ids->count() >= $limit) {
                break;
            }

            $query = $this->publishedOthers($vehicle);
            $applyTier($query);
            if ($ids->isNotEmpty()) {
                $query->whereNotIn('vehicles.id', $ids->all());
            }

            $found = $query->limit(self::CANDIDATE_POOL)->pluck('id');
            $ids = $ids->concat($found)->unique()->values();
        }

        return $ids;
    }

    /**
     * @return list<callable(Builder): void>
     */
    private function tierFilters(Vehicle $vehicle): array
    {
        $tiers = [];

        if ($vehicle->brand_id && $vehicle->model_id) {
            $tiers[] = function (Builder $query) use ($vehicle): void {
                $query->where('brand_id', $vehicle->brand_id)
                    ->where('model_id', $vehicle->model_id);
            };
        }

        if ($vehicle->brand_id) {
            $tiers[] = function (Builder $query) use ($vehicle): void {
                $query->where('brand_id', $vehicle->brand_id);
                $this->applyPriceBand($query, $vehicle);
                $this->applySameFuel($query, $vehicle);
            };
        }

        if ($vehicle->body_type_id) {
            $tiers[] = function (Builder $query) use ($vehicle): void {
                $query->where('body_type_id', $vehicle->body_type_id);
                $this->applyPriceBand($query, $vehicle);
                $this->applySameFuel($query, $vehicle);
            };
        }

        return $tiers;
    }

    private function applyPriceBand(Builder $query, Vehicle $vehicle): void
    {
        $price = $vehicle->price !== null ? (float) $vehicle->price : 0.0;
        if ($price <= 0) {
            return;
        }

        $query->whereNotNull('price')
            ->whereBetween('price', [
                $price * (1 - self::PRICE_BAND),
                $price * (1 + self::PRICE_BAND),
            ]);
    }

    private function applySameFuel(Builder $query, Vehicle $vehicle): void
    {
        if ($vehicle->fuel_type_id) {
            $query->where('fuel_type_id', $vehicle->fuel_type_id);
        }
    }

    private function publishedOthers(Vehicle $vehicle): Builder
    {
        return Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->where('vehicles.id', '!=', $vehicle->id);
    }

    private function scoredQuery(Vehicle $vehicle): Builder
    {
        [$sql, $bindings] = $this->scoreExpression($vehicle);

        return Vehicle::query()
            ->withoutGlobalScope('defaultOrder')
            ->selectRaw('vehicles.*, '.$sql, $bindings)
            ->orderByDesc('related_score')
            ->orderByDesc('vehicles.id');
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function scoreExpression(Vehicle $vehicle): array
    {
        $parts = [];
        $bindings = [];

        if ($vehicle->model_id) {
            $parts[] = 'CASE WHEN vehicles.model_id = ? THEN 40 ELSE 0 END';
            $bindings[] = $vehicle->model_id;
        }

        if ($vehicle->brand_id) {
            $parts[] = 'CASE WHEN vehicles.brand_id = ? THEN 20 ELSE 0 END';
            $bindings[] = $vehicle->brand_id;
        }

        $price = $vehicle->price !== null ? (float) $vehicle->price : 0.0;
        if ($price > 0) {
            $parts[] = 'CASE WHEN vehicles.price IS NOT NULL AND vehicles.price BETWEEN ? AND ? THEN 15 ELSE 0 END';
            $bindings[] = $price * (1 - self::PRICE_SCORE_BAND);
            $bindings[] = $price * (1 + self::PRICE_SCORE_BAND);
        }

        $year = $vehicle->model_year ?? $vehicle->first_registration_year;
        if ($year !== null) {
            $yearInt = (int) $year;
            $parts[] = 'CASE WHEN COALESCE(vehicles.model_year, vehicles.first_registration_year)'
                .' BETWEEN ? AND ? THEN 10 ELSE 0 END';
            $bindings[] = $yearInt - 2;
            $bindings[] = $yearInt + 2;
        }

        if ($vehicle->fuel_type_id) {
            $parts[] = 'CASE WHEN vehicles.fuel_type_id = ? THEN 8 ELSE 0 END';
            $bindings[] = $vehicle->fuel_type_id;
        }

        if ($vehicle->body_type_id) {
            $parts[] = 'CASE WHEN vehicles.body_type_id = ? THEN 5 ELSE 0 END';
            $bindings[] = $vehicle->body_type_id;
        }

        $postcode = is_string($vehicle->postcode) ? trim($vehicle->postcode) : '';
        if ($postcode !== '') {
            $parts[] = 'CASE WHEN vehicles.postcode = ? THEN 7 ELSE 0 END';
            $bindings[] = $postcode;
        }

        if ($parts === []) {
            return ['0 as related_score', []];
        }

        return ['('.implode(' + ', $parts).') as related_score', $bindings];
    }

    /**
     * @return list<string>
     */
    private function cardEagerLoads(): array
    {
        return [
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
            'salesType',
            'fuelType',
            'gearType',
            'dealer',
            'brand',
            'model',
            'variant',
        ];
    }

    private function cacheKey(Vehicle $vehicle, int $limit): string
    {
        return sprintf('related_vehicles:%d:%d', (int) $vehicle->id, $limit);
    }
}
