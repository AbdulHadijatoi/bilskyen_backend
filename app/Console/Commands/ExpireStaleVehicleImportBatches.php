<?php

namespace App\Console\Commands;

use App\Services\VehicleImport\VehicleImportBatchMaintenanceService;
use Illuminate\Console\Command;

class ExpireStaleVehicleImportBatches extends Command
{
    protected $signature = 'vehicle-import:expire-stale-batches';

    protected $description = 'Mark vehicle import batches stuck in pending/processing as failed';

    public function handle(VehicleImportBatchMaintenanceService $maintenanceService): int
    {
        $count = $maintenanceService->expireStaleBatches();
        $this->info("Expired {$count} stale vehicle import batch(es).");

        return self::SUCCESS;
    }
}
