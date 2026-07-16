<?php

namespace App\Services\VehicleImport;

use App\Jobs\ProcessVehicleImportBatchJob;
use App\Mail\VehicleImportCompletedMail;
use App\Models\VehicleImportBatch;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

class VehicleImportBatchMaintenanceService
{
    /** Buffer beyond job timeout (minutes) before marking a batch stale. */
    public const STALE_BUFFER_MINUTES = 5;

    public function __construct(
        private MailService $mailService,
    ) {}

    public function expireStaleBatches(): int
    {
        $cutoff = now()->subMinutes(
            ProcessVehicleImportBatchJob::TIMEOUT_SECONDS / 60 + self::STALE_BUFFER_MINUTES
        );

        $staleBatches = VehicleImportBatch::query()
            ->with('user')
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
            ->get();

        $count = 0;
        foreach ($staleBatches as $batch) {
            $batch->update([
                'status' => VehicleImportBatch::STATUS_FAILED,
                'error_message' => __('messages.api.vehicle_import_batch_stale'),
                'completed_at' => now(),
            ]);
            $count++;

            if ($batch->dry_run || ! $batch->user?->email) {
                continue;
            }

            try {
                $this->mailService->sendMailable(
                    $batch->user->email,
                    new VehicleImportCompletedMail($batch->fresh()),
                    ['batch_id' => $batch->id],
                    true,
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to email stale vehicle import batch', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
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
