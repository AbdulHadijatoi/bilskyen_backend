<?php

namespace App\Observers;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\Seo\IndexNowService;
use Illuminate\Support\Facades\Log;

class VehicleIndexNowObserver
{
    public function __construct(
        private IndexNowService $indexNow
    ) {}

    public function saved(Vehicle $vehicle): void
    {
        $slug = trim((string) $vehicle->slug);
        if ($slug === '' || ! $this->indexNow->isEnabled()) {
            return;
        }

        $published = VehicleListStatus::PUBLISHED;
        $isPublished = (int) $vehicle->list_status_id === $published;
        $wasPublished = (int) $vehicle->getOriginal('list_status_id') === $published;

        $shouldQueue = false;
        if ($vehicle->wasRecentlyCreated && $isPublished) {
            $shouldQueue = true;
        } elseif ($vehicle->wasChanged('list_status_id') && ($isPublished || $wasPublished)) {
            $shouldQueue = true;
        } elseif ($isPublished && $vehicle->wasChanged(['price', 'title', 'slug'])) {
            $shouldQueue = true;
        }

        if (! $shouldQueue) {
            return;
        }

        try {
            $this->indexNow->queue(route('vehicle.detail', $slug));
        } catch (\Throwable $e) {
            Log::warning('IndexNow queue skipped: '.$e->getMessage(), [
                'vehicle_id' => $vehicle->id,
                'slug' => $slug,
            ]);
        }
    }
}
