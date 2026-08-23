<?php

namespace App\Services;

use App\Helpers\FormatHelper;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VehicleMapLocationService
{
    public function __construct(
        private DawaGeocodeService $dawaGeocodeService,
    ) {}

    /**
     * Best WGS84 point for the vehicle detail map: stored street coords, DAWA
     * from the listing/dealer address, DAWA postcode centroid, then seeded locations.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function pointFor(Vehicle $vehicle, ?string $fallbackAddress = null, ?string $fallbackPostcode = null): ?array
    {
        if ($this->hasStoredPoint($vehicle)) {
            return [
                'latitude' => (float) $vehicle->latitude,
                'longitude' => (float) $vehicle->longitude,
            ];
        }

        $address = $this->streetQuery($vehicle->address) ?? $this->streetQuery($fallbackAddress);
        $postcode = trim((string) ($vehicle->postcode ?: $fallbackPostcode));

        $coords = $this->dawaGeocodeService->resolve($address, $postcode !== '' ? $postcode : null)
            ?? FormatHelper::coordsForPostcode($postcode !== '' ? $postcode : null);

        if ($coords === null) {
            return null;
        }

        $this->persist($vehicle, $coords);

        return $coords;
    }

    private function hasStoredPoint(Vehicle $vehicle): bool
    {
        return is_numeric($vehicle->latitude) && is_numeric($vehicle->longitude);
    }

    private function streetQuery(?string $address): ?string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        $first = trim(explode(',', $address)[0]);
        if ($first === '' || ! FormatHelper::isUsableCityName($first)) {
            return null;
        }

        return $address;
    }

    /**
     * @param  array{latitude: float, longitude: float}  $coords
     */
    private function persist(Vehicle $vehicle, array $coords): void
    {
        $vehicle->forceFill($coords);
        if (! $vehicle->exists) {
            return;
        }

        try {
            if (! Schema::hasColumn($vehicle->getTable(), 'latitude')) {
                return;
            }
            $vehicle->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Could not persist vehicle map coordinates', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
