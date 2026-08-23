<?php

namespace App\Services;

use App\Models\ListingViewsLog;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehicleViewService
{
    public const RAIL_LIMIT = 4;

    public const STORAGE_LIMIT = 8;

    public function recordView(Vehicle $vehicle, ?int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($vehicle, $userId, $ipAddress, $userAgent) {
            ListingViewsLog::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);

            $vehicle->increment('views_count');
        });
    }

    /**
     * Published listings in the given id order. Skips missing, unpublished, and the current vehicle.
     *
     * @param  list<int|string>  $ids
     * @return Collection<int, Vehicle>
     */
    public function recentByIds(array $ids, int $limit = self::RAIL_LIMIT, ?int $excludeId = null): Collection
    {
        $ordered = [];
        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n <= 0) {
                continue;
            }
            if ($excludeId !== null && $n === (int) $excludeId) {
                continue;
            }
            if (! in_array($n, $ordered, true)) {
                $ordered[] = $n;
            }
        }
        $ordered = array_slice($ordered, 0, self::STORAGE_LIMIT);
        if ($ordered === []) {
            return collect();
        }

        $found = Vehicle::query()
            ->withoutGlobalScope('defaultOrder')
            ->published()
            ->whereIn('vehicles.id', $ordered)
            ->with($this->cardEagerLoads())
            ->get()
            ->keyBy('id');

        return collect($ordered)
            ->map(fn (int $id) => $found->get($id))
            ->filter()
            ->take(max(1, $limit))
            ->values();
    }

    /**
     * Distinct recent views for a signed-in user, newest first.
     *
     * @return Collection<int, Vehicle>
     */
    public function recentForUser(int $userId, int $limit = self::RAIL_LIMIT, ?int $excludeId = null): Collection
    {
        $ids = ListingViewsLog::query()
            ->where('user_id', $userId)
            ->select('vehicle_id')
            ->selectRaw('MAX(viewed_at) as last_viewed_at')
            ->groupBy('vehicle_id')
            ->orderByDesc('last_viewed_at')
            ->limit(self::STORAGE_LIMIT)
            ->pluck('vehicle_id')
            ->all();

        return $this->recentByIds($ids, $limit, $excludeId);
    }

    /**
     * @return array<int|string, mixed>
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
}
