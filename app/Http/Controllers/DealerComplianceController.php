<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealerComplianceController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
    ) {}

    public function exportLeadPiiAccess(Request $request): StreamedResponse|JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $logs = AuditLog::query()
            ->where('dealer_id', $dealer->id)
            ->where(function ($q) {
                $q->where('entity_type', 'Lead')
                    ->orWhereJsonContains('tags', 'lead');
            })
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'lead-pii-audit-'.$dealer->id.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'actor_type', 'user_id', 'action', 'entity_type', 'entity_id', 'created_at']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->actor_type,
                    $log->user_id,
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->created_at,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
