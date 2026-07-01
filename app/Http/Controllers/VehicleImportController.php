<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\SubscriptionFeatureService;
use App\Services\VehicleImport\VehicleImportColumnDefinitions;
use App\Services\VehicleImport\VehicleImportService;
use App\Services\VehicleImport\VehicleImportTemplateBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleImportController extends Controller
{
    public function __construct(
        private VehicleImportService $vehicleImportService,
        private VehicleImportTemplateBuilder $templateBuilder,
        private AuditLogService $auditLogService,
        private SubscriptionFeatureService $subscriptionFeatureService,
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
        $dealer = $request->user()?->dealer;
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
        $dealer = $user?->dealer;
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        $dryRun = $request->boolean('dry_run');

        try {
            $result = $this->vehicleImportService->importFromFile(
                $request->file('file'),
                (int) $dealer->id,
                (int) $user->id,
                $dealer,
                $dryRun,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            Log::error('Vehicle bulk import failed', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : __('messages.api.vehicle_import_failed');

            return $this->error($message, [], 500);
        }

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
                Log::warning('Failed to audit log vehicle import', ['error' => $e->getMessage()]);
            }
        }

        return $this->success($result);
    }
}
