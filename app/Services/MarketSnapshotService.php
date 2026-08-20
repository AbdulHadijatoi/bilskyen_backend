<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\DmrDriveEnergy;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MarketSnapshotService
{
    public const CACHE_TTL_SECONDS = 21600; // 6 hours

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return Cache::remember('market_snapshot.v1', self::CACHE_TTL_SECONDS, fn () => $this->buildFromDatabase());
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFromDatabase(): array
    {
        return $this->assemble($this->publishedRows());
    }

    /**
     * Published listings only. Unpublished / sold / draft rows are excluded.
     *
     * @return Collection<int, object{price: mixed, published_at: mixed, fuel_name: mixed}>
     */
    public function publishedRows(): Collection
    {
        $fuelNames = DmrDriveEnergy::query()->pluck('name', 'id');

        return Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->get(['id', 'price', 'published_at', 'fuel_type_id'])
            ->map(function (Vehicle $vehicle) use ($fuelNames) {
                return (object) [
                    'price' => $vehicle->price,
                    'published_at' => $vehicle->published_at,
                    'fuel_name' => $fuelNames[$vehicle->fuel_type_id] ?? null,
                ];
            })
            ->values();
    }

    /**
     * @param  iterable<mixed>  $rows
     * @return array<string, mixed>
     */
    public function assemble(iterable $rows): array
    {
        $prices = [];
        $daysOnMarket = [];
        $byFuelPrices = [
            'el' => [],
            'benzin' => [],
            'diesel' => [],
            'hybrid' => [],
        ];
        $byFuelCounts = [
            'el' => 0,
            'benzin' => 0,
            'diesel' => 0,
            'hybrid' => 0,
        ];
        $count = 0;

        foreach ($rows as $row) {
            $count++;
            $price = isset($row->price) ? (float) $row->price : null;
            if ($price !== null && $price > 0) {
                $prices[] = $price;
            }

            $publishedAt = $row->published_at ?? null;
            if ($publishedAt instanceof CarbonInterface) {
                $daysOnMarket[] = max(0, (int) $publishedAt->diffInDays(now()));
            }

            $group = $this->fuelGroup(isset($row->fuel_name) ? (string) $row->fuel_name : null);
            if ($group !== null) {
                $byFuelCounts[$group]++;
                if ($price !== null && $price > 0) {
                    $byFuelPrices[$group][] = $price;
                }
            }
        }

        $labels = [
            'el' => __('messages.pages.market.fuel_el'),
            'benzin' => __('messages.pages.market.fuel_benzin'),
            'diesel' => __('messages.pages.market.fuel_diesel'),
            'hybrid' => __('messages.pages.market.fuel_hybrid'),
        ];

        $byFuel = [];
        foreach ($byFuelPrices as $key => $groupPrices) {
            $byFuel[$key] = [
                'label' => $labels[$key],
                'count' => $byFuelCounts[$key],
                'median_price' => $this->median($groupPrices),
            ];
        }

        return [
            'generated_at' => now()->toAtomString(),
            'listing_count' => $count,
            'median_price' => $this->median($prices),
            'median_days_on_market' => $this->median($daysOnMarket),
            'by_fuel' => $byFuel,
        ];
    }

    /**
     * @param  list<int|float>  $values
     */
    public function median(array $values): ?float
    {
        $values = array_values(array_filter($values, static fn ($n) => is_numeric($n)));
        $n = count($values);
        if ($n === 0) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $mid = intdiv($n, 2);
        if ($n % 2 === 1) {
            return (float) $values[$mid];
        }

        return ((float) $values[$mid - 1] + (float) $values[$mid]) / 2;
    }

    public function fuelGroup(?string $name): ?string
    {
        $n = mb_strtolower(trim((string) $name));
        if ($n === '') {
            return null;
        }
        if (str_contains($n, 'hybrid')) {
            return 'hybrid';
        }
        if ($n === 'el' || $n === 'electric') {
            return 'el';
        }
        if ($n === 'benzin' || $n === 'petrol' || $n === 'gasoline') {
            return 'benzin';
        }
        if ($n === 'diesel') {
            return 'diesel';
        }

        return null;
    }
}
