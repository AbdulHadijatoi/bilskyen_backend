<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\PriceHistory;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class BulkPriceUpdateService
{
    public function __construct(
        private AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<int, array{vehicle_id: int, price: int|float}>  $updates
     * @return array{updated: int, skipped: int, errors: array<int, string>}
     */
    public function applyUpdates(Dealer $dealer, User $user, array $updates): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($dealer, $user, $updates, &$updated, &$skipped, &$errors) {
            foreach ($updates as $index => $row) {
                $vehicleId = (int) ($row['vehicle_id'] ?? 0);
                $price = (int) round((float) ($row['price'] ?? 0));

                if ($vehicleId <= 0 || $price < 0) {
                    $errors[$index] = 'Invalid vehicle or price.';
                    continue;
                }

                $vehicle = Vehicle::where('dealer_id', $dealer->id)->find($vehicleId);
                if (! $vehicle) {
                    $errors[$index] = "Vehicle #{$vehicleId} not found.";
                    continue;
                }

                if ((int) $vehicle->price === $price) {
                    $skipped++;
                    continue;
                }

                $oldPrice = $vehicle->price;
                $vehicle->price = $price;
                $vehicle->save();

                PriceHistory::create([
                    'vehicle_id' => $vehicle->id,
                    'old_price' => $oldPrice,
                    'new_price' => $price,
                    'changed_by_user_id' => $user->id,
                    'changed_at' => now(),
                ]);

                try {
                    $this->auditLogService->logUpdate(
                        $user,
                        'Vehicle',
                        $vehicle->id,
                        ['price' => $oldPrice],
                        ['price' => $price],
                        request(),
                        'Dealer',
                        $dealer->id,
                        "Bulk price update: {$oldPrice} -> {$price}",
                        ['vehicle', 'dealer', 'price', 'bulk_update']
                    );
                } catch (\Throwable) {
                    // Non-blocking audit failure
                }

                $updated++;
            }
        });

        return compact('updated', 'skipped', 'errors');
    }
}
