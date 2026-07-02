<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;

class MarketPricingService
{
    private const KM_BAND_SIZE = 25000;

    /**
     * @return array<string, mixed>|null
     */
    public function evaluateVehicle(Vehicle $vehicle): ?array
    {
        $price = $vehicle->price !== null ? (float) $vehicle->price : null;
        if ($price === null || $price <= 0) {
            return null;
        }

        $cohort = $this->cohortStats($vehicle);
        if ($cohort === null || $cohort['median'] <= 0 || $cohort['count'] < 3) {
            return null;
        }

        $median = (float) $cohort['median'];
        $diffPercent = round((($price - $median) / $median) * 100, 1);

        return [
            'median_price' => $median,
            'cohort_count' => $cohort['count'],
            'diff_percent' => $diffPercent,
            'label' => $this->labelForDiff($diffPercent),
            'suggested_min' => round($median * 0.95),
            'suggested_max' => round($median * 1.05),
        ];
    }

    /**
     * @return array{median: float, count: int}|null
     */
    private function cohortStats(Vehicle $vehicle): ?array
    {
        if (! $vehicle->brand_id || ! $vehicle->model_id) {
            return null;
        }

        $year = $vehicle->model_year ?? $vehicle->first_registration_year;
        if ($year === null) {
            return null;
        }

        $yearInt = (int) $year;
        $km = $vehicle->km_driven !== null ? (int) $vehicle->km_driven : null;
        $kmBand = $km !== null ? (int) (floor($km / self::KM_BAND_SIZE) * self::KM_BAND_SIZE) : null;

        $cacheKey = sprintf(
            'market_pricing:%d:%d:%d:%s',
            $vehicle->brand_id,
            $vehicle->model_id,
            $yearInt,
            $kmBand ?? 'any'
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($vehicle, $yearInt, $kmBand) {
            $publishedId = VehicleListStatus::nameToId('published');
            if ($publishedId === null) {
                return null;
            }

            $query = Vehicle::query()
                ->where('list_status_id', $publishedId)
                ->where('brand_id', $vehicle->brand_id)
                ->where('model_id', $vehicle->model_id)
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->where(function ($q) use ($yearInt) {
                    $q->where('model_year', $yearInt)
                        ->orWhere('first_registration_year', $yearInt);
                });

            if ($kmBand !== null) {
                $query->whereBetween('km_driven', [$kmBand, $kmBand + self::KM_BAND_SIZE]);
            }

            $prices = $query->pluck('price')->map(fn ($p) => (float) $p)->sort()->values();
            if ($prices->count() < 3) {
                if ($kmBand !== null) {
                    return $this->cohortStatsWithoutKm($vehicle, $yearInt, $publishedId);
                }

                return null;
            }

            return [
                'median' => $this->median($prices->all()),
                'count' => $prices->count(),
            ];
        });
    }

    /**
     * @return array{median: float, count: int}|null
     */
    private function cohortStatsWithoutKm(Vehicle $vehicle, int $yearInt, int $publishedId): ?array
    {
        $prices = Vehicle::query()
            ->where('list_status_id', $publishedId)
            ->where('brand_id', $vehicle->brand_id)
            ->where('model_id', $vehicle->model_id)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->where(function ($q) use ($yearInt) {
                $q->where('model_year', $yearInt)
                    ->orWhere('first_registration_year', $yearInt);
            })
            ->pluck('price')
            ->map(fn ($p) => (float) $p)
            ->sort()
            ->values();

        if ($prices->count() < 3) {
            return null;
        }

        return [
            'median' => $this->median($prices->all()),
            'count' => $prices->count(),
        ];
    }

    /**
     * @param  array<int, float>  $values
     */
    private function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        $mid = (int) floor($count / 2);
        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2;
        }

        return $values[$mid];
    }

    private function labelForDiff(float $diffPercent): string
    {
        if ($diffPercent <= -5) {
            return 'below_market';
        }
        if ($diffPercent >= 5) {
            return 'above_market';
        }

        return 'fair_price';
    }
}
