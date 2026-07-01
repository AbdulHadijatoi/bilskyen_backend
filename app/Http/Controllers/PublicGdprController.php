<?php

namespace App\Http\Controllers;

use App\Services\Compliance\GdprComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicGdprController extends Controller
{
    public function __construct(
        private GdprComplianceService $gdprComplianceService,
    ) {}

    public function requestExport(Request $request): JsonResponse
    {
        if (! $this->gdprComplianceService->exportEnabled()) {
            return $this->error(__('messages.api.gdpr_export_disabled'), [], 403);
        }

        $data = $request->validate([
            'email' => 'required|email|max:150',
        ]);

        $record = $this->gdprComplianceService->requestExport($request->user(), $data['email']);

        return $this->created([
            'id' => $record->id,
            'message' => __('messages.api.gdpr_export_requested'),
        ]);
    }
}
