<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\VehicleImport\Bilbasen\BilbasenVehicleImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BilbasenVehicleImportController extends Controller
{
    public function __construct(
        private BilbasenVehicleImportService $bilbasenVehicleImportService,
        private DealerContextService $dealerContextService,
        private AuditLogService $auditLogService,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:2048',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->getCurrentDealer($user);
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        try {
            $preview = $this->bilbasenVehicleImportService->preview(
                (string) $request->input('url'),
                (int) $dealer->id,
            );

            return $this->success($preview, 200, __('messages.api.bilbasen_import_preview_success'));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            Log::error('Bilbasen import preview failed', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(__('messages.api.bilbasen_import_fetch_failed'), [], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:2048',
            'sales_type_id' => 'required|integer|exists:sales_types,id',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->getCurrentDealer($user);
        if ($dealer === null) {
            return $this->error(__('messages.errors.dealer_not_found'), [], 403);
        }

        try {
            $result = $this->bilbasenVehicleImportService->publish(
                (string) $request->input('url'),
                (int) $dealer->id,
                (int) $user->id,
                $dealer,
                (int) $request->input('sales_type_id'),
            );

            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Vehicle',
                    $result['vehicle_id'],
                    ['source' => 'bilbasen_url', 'url' => $request->input('url')],
                    $request,
                    'Dealer',
                    $dealer->id,
                    __('messages.audit.vehicle_imported_from_bilbasen'),
                    ['vehicle', 'dealer', 'import', 'bilbasen']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for Bilbasen import', [
                    'vehicle_id' => $result['vehicle_id'],
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->created($result, __('messages.api.bilbasen_import_publish_success'));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $status = str_contains($message, (string) __('messages.api.dealer_overdue_invoice_block'))
                || str_contains($message, 'max_listings')
                || str_contains($message, 'max_equipment')
                || str_contains($message, 'max_vehicle_images')
                ? 403
                : 422;

            return $this->error($message, [], $status);
        } catch (\Throwable $e) {
            Log::error('Bilbasen import publish failed', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(__('messages.api.bilbasen_import_publish_failed'), [], 500);
        }
    }
}
