<?php

namespace App\Http\Controllers;

use App\Models\TradeInRequest;
use App\Services\DealerContextService;
use App\Services\TradeIn\TradeInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerTradeInController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private TradeInService $tradeInService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $items = TradeInRequest::with(['vehicle', 'lead', 'enquiry'])
            ->where('dealer_id', $dealer->id)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success($items);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $tradeIn = TradeInRequest::where('dealer_id', $dealer->id)->findOrFail($id);
        $data = $request->validate([
            'appraisal_status' => 'sometimes|string|in:pending,in_review,offered,accepted,rejected',
            'offered_value_cents' => 'nullable|integer|min:0',
            'appraisal_notes' => 'nullable|string|max:5000',
        ]);

        return $this->success($this->tradeInService->updateAppraisal($tradeIn, $data));
    }
}
