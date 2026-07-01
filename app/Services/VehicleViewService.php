<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class VehicleViewService
{
    public function recordView(Vehicle $vehicle, ?int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($vehicle, $userId, $ipAddress, $userAgent) {
            \App\Models\ListingViewsLog::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);

            $vehicle->increment('views_count');
        });
    }
}
