<?php

namespace App\Services\VehicleImport;

use App\Jobs\ProcessVehicleImportBatchJob;
use App\Models\VehicleImportBatch;

class VehicleImportBatchMaintenanceService
{
    /** Buffer beyond job timeout (minutes) before marking a batch stale. */
    public const STALE_BUFFER_MINUTES = 5;

    public function expireStaleBatches(): int
    {
        $cutoff = now()->subMinutes(
            ProcessVehicleImportBatchJob::TIMEOUT_SECONDS / 60 + self::STALE_BUFFER_MINUTES
        );

        return VehicleImportBatch::query()
            ->whereIn('status', [
                VehicleImportBatch::STATUS_PENDING,
                VehicleImportBatch::STATUS_PROCESSING,
            ])
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('started_at')
                        ->where('started_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('started_at')
                        ->where('created_at', '<', $cutoff);
                });
            })
            ->update([
                'status' => VehicleImportBatch::STATUS_FAILED,
                'error_message' => __('messages.api.vehicle_import_batch_stale'),
                'completed_at' => now(),
            ]);
    }

    public function hasBlockingImport(int $dealerId): bool
    {
        $this->expireStaleBatches();

        return VehicleImportBatch::query()
            ->where('dealer_id', $dealerId)
            ->whereIn('status', [
                VehicleImportBatch::STATUS_PENDING,
                VehicleImportBatch::STATUS_PROCESSING,
            ])
            ->exists();
    }
}
