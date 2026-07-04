<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\BulkPriceUpdateService;
use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerBulkPriceController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private BulkPriceUpdateService $bulkPriceUpdateService,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $data = $request->validate([
            'updates' => 'required|array|min:1|max:200',
            'updates.*.vehicle_id' => 'required|integer',
            'updates.*.price' => 'required|numeric|min:0',
        ]);

        $result = $this->bulkPriceUpdateService->applyUpdates(
            $dealer,
            $request->user(),
            $data['updates']
        );

        return $this->success($result);
    }
}
