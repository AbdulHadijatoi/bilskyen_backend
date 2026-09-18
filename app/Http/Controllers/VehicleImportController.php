<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVehicleImportBatchJob;
use App\Models\Dealer;
use App\Models\User;
use App\Models\VehicleImportBatch;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use App\Services\VehicleImport\VehicleImportBatchMaintenanceService;
use App\Services\VehicleImport\VehicleImportBatchService;
use App\Services\VehicleImport\VehicleImportColumnDefinitions;
use App\Services\VehicleImport\VehicleImportService;
use App\Services\VehicleImport\VehicleImportTemplateBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleImportController extends Controller
{
    public function __construct(
        private VehicleImportService $vehicleImportService,
        private VehicleImportTemplateBuilder $templateBuilder,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private DealerContextService $dealerContextService,
        private AuditLogService $auditLogService,
        private VehicleImportBatchService $vehicleImportBatchService,
        private VehicleImportBatchMaintenanceService $vehicleImportBatchMaintenanceService,
    ) {}

    public function downloadTemplate(): BinaryFileResponse
    {
        $path = $this->templateBuilder->buildXlsx();

        return response()->download(
            $path,
            'vehicle-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function sample(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        $usageNotice = null;

        if ($dealer && $this->subscriptionFeatureService->isUsageDailyPlan($dealer)) {
            $plan = $this->subscriptionFeatureService->getActiveSubscription($dealer)?->plan;
            $cents = (int) ($plan?->price_per_listing_per_day ?? 0);
            $usageNotice = [
                'billing_model' => 'usage_daily',
                'price_per_day_cents' => $cents,
                'message' => __('messages.api.vehicle_import_payg_notice', [
                    'amount' => number_format($cents / 100, 2, ',', '.'),
                ]),
            ];
        }

        return $this->success([
            'headers' => VehicleImportColumnDefinitions::TEMPLATE_HEADERS,
            'row' => VehicleImportColumnDefinitions::SAMPLE_ROW,
            'usage_notice' => $usageNotice,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'dry_run' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->getCurrentDealer($user);
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        if ($this->vehicleImportBatchMaintenanceService->hasBlockingImport((int) $dealer->id)) {
            return $this->error(__('messages.api.vehicle_import_already_running'), [], 409);
        }

        $file = $request->file('file');
        $dryRun = $request->boolean('dry_run');

        try {
            $rowCount = $this->vehicleImportService->countRows($file);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }

        if ($rowCount > VehicleImportColumnDefinitions::MAX_ROWS) {
            return $this->error(__('messages.api.vehicle_import_too_many_rows', [
                'max' => VehicleImportColumnDefinitions::MAX_ROWS,
            ]), [], 422);
        }

        if ($dryRun || $rowCount <= VehicleImportColumnDefinitions::SYNC_ROW_LIMIT) {
            return $this->runSyncImport($request, $dealer, $user, $dryRun);
        }

        return $this->runQueuedImport($file, $dealer, $user);
    }

    public function batches(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        $batches = VehicleImportBatch::query()
            ->where('dealer_id', $dealer->id)
            ->where('dry_run', false)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'original_filename', 'status', 'summary', 'error_message', 'created_at', 'started_at', 'completed_at']);

        return $this->success($batches);
    }

    public function showBatch(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        $batch = VehicleImportBatch::query()
            ->where('dealer_id', $dealer->id)
            ->where('id', $id)
            ->first();

        if ($batch === null) {
            return $this->notFound(__('messages.api.vehicle_import_batch_not_found'));
        }

        return $this->success([
            'id' => $batch->id,
            'original_filename' => $batch->original_filename,
            'status' => $batch->status,
            'summary' => $batch->summary,
            'rows' => $batch->rows,
            'error_message' => $batch->error_message,
            'created_at' => $batch->created_at,
            'started_at' => $batch->started_at,
            'completed_at' => $batch->completed_at,
        ]);
    }

    private function runSyncImport(Request $request, $dealer, $user, bool $dryRun): JsonResponse
    {
        set_time_limit(600);

        try {
            $result = $this->vehicleImportService->importFromFile(
                $request->file('file'),
                (int) $dealer->id,
                (int) $user->id,
                $dealer,
                $dryRun,
            );

            if (! $dryRun && ($result['summary']['created'] ?? 0) > 0) {
                try {
                    $this->auditLogService->logCreate(
                        $user,
                        'VehicleImport',
                        0,
                        $result['summary'],
                        $request,
                        'Dealer',
                        $dealer->id,
                        'Bulk vehicle import completed',
                        ['vehicle', 'dealer', 'import']
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to audit log vehicle import', [
                        'dealer_id' => $dealer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            Log::error('Vehicle bulk import failed', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(__('messages.api.vehicle_import_failed'), [], 500);
        }

        return $this->success($result);
    }

    private function runQueuedImport(UploadedFile $file, Dealer $dealer, User $user): JsonResponse
    {
        $batch = $this->vehicleImportBatchService->queueImport($file, $dealer, $user, false);
        ProcessVehicleImportBatchJob::dispatch($batch->id);

        return $this->success([
            'queued' => true,
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'message' => __('messages.api.vehicle_import_queued'),
        ]);
    }
}
