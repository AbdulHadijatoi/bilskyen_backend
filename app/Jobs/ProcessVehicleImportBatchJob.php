<?php

namespace App\Jobs;

use App\Mail\VehicleImportCompletedMail;
use App\Models\VehicleImportBatch;
use App\Services\AuditLogService;
use App\Services\MailService;
use App\Services\VehicleImport\VehicleImportBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVehicleImportBatchJob implements ShouldQueue
{
    use Queueable;

    public const TIMEOUT_SECONDS = 1800;

    public int $tries = 2;

    public int $timeout = self::TIMEOUT_SECONDS;

    public function __construct(
        public int $batchId,
    ) {}

    public function handle(
        VehicleImportBatchService $batchService,
        AuditLogService $auditLogService,
        MailService $mailService,
    ): void {
        $batch = VehicleImportBatch::with(['dealer', 'user'])->find($this->batchId);
        if ($batch === null) {
            return;
        }

        if ($batch->status !== VehicleImportBatch::STATUS_PENDING) {
            return;
        }

        $batch->update([
            'status' => VehicleImportBatch::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $result = null;

        try {
            $result = $batchService->processBatch($batch);

            $batch->update([
                'status' => VehicleImportBatch::STATUS_COMPLETED,
                'summary' => $result['summary'],
                'rows' => $result['rows'],
                'completed_at' => now(),
            ]);

            if (! $batch->dry_run && ($result['summary']['created'] ?? 0) > 0 && $batch->user) {
                try {
                    $auditLogService->logCreate(
                        $batch->user,
                        'VehicleImport',
                        $batch->id,
                        $result['summary'],
                        null,
                        'Dealer',
                        $batch->dealer_id,
                        'Bulk vehicle import completed',
                        ['vehicle', 'dealer', 'import']
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to audit log vehicle import batch', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
                }
            }

            if (! $batch->dry_run && $batch->user?->email) {
                $mailService->sendMailable(
                    $batch->user->email,
                    new VehicleImportCompletedMail($batch->fresh()),
                    ['batch_id' => $batch->id],
                    true,
                );
            }
        } catch (\Throwable $e) {
            Log::error('Vehicle import batch failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $freshBatch = $batch->fresh();
            $update = [
                'status' => VehicleImportBatch::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ];

            if ($freshBatch !== null && $freshBatch->rows !== null) {
                $update['summary'] = $freshBatch->summary;
                $update['rows'] = $freshBatch->rows;
            } elseif ($result !== null) {
                $update['summary'] = $result['summary'];
                $update['rows'] = $result['rows'];
            }

            $batch->update($update);

            if (! $batch->dry_run && $batch->user?->email) {
                $mailService->sendMailable(
                    $batch->user->email,
                    new VehicleImportCompletedMail($batch->fresh()),
                    ['batch_id' => $batch->id],
                    true,
                );
            }

            // Do not rethrow — batch is already marked failed and the user notified.
            // Rethrowing would trigger a useless retry (status is no longer pending).
        } finally {
            if (Storage::disk('local')->exists($batch->file_path)) {
                Storage::disk('local')->delete($batch->file_path);
            }
        }
    }
}
