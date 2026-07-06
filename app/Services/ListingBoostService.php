<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\ListingBoost;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListingBoostService
{
    public const BOOST_DAYS = 7;

    public const MAX_ACTIVE_BOOSTS = 5;

    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function canBoost(Vehicle $vehicle): bool
    {
        if (! $vehicle->dealer_id || ! $vehicle->dealer) {
            return false;
        }

        if (! $this->subscriptionFeatureService->hasFeature($vehicle->dealer, 'listing_boost')) {
            return false;
        }

        if ($vehicle->list_status_id !== VehicleListStatus::PUBLISHED) {
            return false;
        }

        if ($this->activeBoostForVehicle($vehicle->id)) {
            return false;
        }

        return $this->activeBoostCount($vehicle->dealer_id) < self::MAX_ACTIVE_BOOSTS;
    }

    public function boostVehicle(Vehicle $vehicle, ?int $userId, string $source = 'manual'): ListingBoost
    {
        $vehicle->loadMissing('dealer');

        return DB::transaction(function () use ($vehicle, $userId, $source) {
            if (ListingBoost::query()->where('vehicle_id', $vehicle->id)->active()->exists()) {
                throw new \InvalidArgumentException(__('messages.errors.vehicle_cannot_be_boosted'));
            }

            $activeCount = ListingBoost::query()
                ->where('dealer_id', $vehicle->dealer_id)
                ->active()
                ->lockForUpdate()
                ->count();

            if ($activeCount >= self::MAX_ACTIVE_BOOSTS) {
                throw new \InvalidArgumentException(__('messages.errors.vehicle_cannot_be_boosted'));
            }

            if (! $this->subscriptionFeatureService->hasFeature($vehicle->dealer, 'listing_boost')
                || $vehicle->list_status_id !== VehicleListStatus::PUBLISHED) {
                throw new \InvalidArgumentException(__('messages.errors.vehicle_cannot_be_boosted'));
            }

            return ListingBoost::create([
                'vehicle_id' => $vehicle->id,
                'dealer_id' => $vehicle->dealer_id,
                'source' => $source,
                'started_at' => now(),
                'expires_at' => now()->addDays(self::BOOST_DAYS),
                'created_by_user_id' => $userId,
            ]);
        });
    }

    public function activeBoostForVehicle(int $vehicleId): ?ListingBoost
    {
        return ListingBoost::query()
            ->where('vehicle_id', $vehicleId)
            ->active()
            ->latest('expires_at')
            ->first();
    }

    public function activeBoostCount(int $dealerId): int
    {
        return ListingBoost::query()
            ->where('dealer_id', $dealerId)
            ->active()
            ->count();
    }

    /**
     * @return Collection<int, int>
     */
    public function activeBoostedVehicleIds(): Collection
    {
        return ListingBoost::query()
            ->active()
            ->pluck('vehicle_id');
    }

    public function expireStaleBoosts(): int
    {
        return ListingBoost::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function boostStatusForVehicle(int $vehicleId): ?array
    {
        $boost = $this->activeBoostForVehicle($vehicleId);
        if (! $boost) {
            return null;
        }

        return [
            'expires_at' => $boost->expires_at->format('Y-m-d H:i:s'),
            'days_remaining' => max(0, (int) now()->diffInDays($boost->expires_at, false)),
            'source' => $boost->source,
        ];
    }
}
