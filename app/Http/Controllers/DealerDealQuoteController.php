<?php

namespace App\Http\Controllers;

use App\Models\DealerDealQuote;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Services\DealerContextService;
use App\Services\DealerDealQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DealerDealQuoteController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private DealerDealQuoteService $dealQuoteService,
    ) {}

    public function index(Request $request, int $leadId): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $this->findLead($dealer->id, $leadId);

        return $this->success($this->dealQuoteService->listForLead($dealer, $leadId));
    }

    public function store(Request $request, int $leadId): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $lead = $this->findLead($dealer->id, $leadId);

        $data = $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'list_price' => 'required|integer|min:0',
            'discount_amount' => 'sometimes|integer|min:0',
            'trade_in_value' => 'sometimes|integer|min:0',
            'finance_apr' => 'nullable|numeric|min:0|max:100',
            'finance_term_months' => 'nullable|integer|min:1|max:120',
            'monthly_payment' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $vehicleId = $data['vehicle_id'] ?? $lead->vehicle_id;
        if ($vehicleId && ! $this->vehicleBelongsToDealer((int) $vehicleId, $dealer->id)) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['The selected vehicle does not belong to your inventory.'],
            ]);
        }

        $quote = $this->dealQuoteService->create($dealer, $request->user(), $lead, $data);

        return $this->created($quote->load(['vehicle', 'createdBy']));
    }

    public function update(Request $request, int $leadId, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $this->findLead($dealer->id, $leadId);
        $quote = $this->findQuote($dealer->id, $leadId, $id);

        $data = $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'list_price' => 'sometimes|integer|min:0',
            'discount_amount' => 'sometimes|integer|min:0',
            'trade_in_value' => 'sometimes|integer|min:0',
            'finance_apr' => 'nullable|numeric|min:0|max:100',
            'finance_term_months' => 'nullable|integer|min:1|max:120',
            'monthly_payment' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('vehicle_id', $data) && $data['vehicle_id'] !== null
            && ! $this->vehicleBelongsToDealer((int) $data['vehicle_id'], $dealer->id)) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['The selected vehicle does not belong to your inventory.'],
            ]);
        }

        try {
            return $this->success($this->dealQuoteService->update($quote, $data));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function send(Request $request, int $leadId, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $lead = $this->findLead($dealer->id, $leadId);
        $quote = $this->findQuote($dealer->id, $leadId, $id);

        if ($quote->status !== 'draft') {
            return $this->error('Only draft quotes can be sent.', [], 422);
        }

        $quote = $this->dealQuoteService->markSent($quote);

        return $this->success($quote);
    }

    public function destroy(Request $request, int $leadId, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $this->findLead($dealer->id, $leadId);
        $quote = $this->findQuote($dealer->id, $leadId, $id);

        if ($quote->status !== 'draft') {
            return $this->error('Only draft quotes can be deleted.', [], 422);
        }

        $quote->delete();

        return $this->noContent();
    }

    private function findLead(int $dealerId, int $leadId): Lead
    {
        return Lead::with(['buyerUser', 'enquiry'])
            ->where('dealer_id', $dealerId)
            ->findOrFail($leadId);
    }

    private function findQuote(int $dealerId, int $leadId, int $id): DealerDealQuote
    {
        return DealerDealQuote::where('dealer_id', $dealerId)
            ->where('lead_id', $leadId)
            ->findOrFail($id);
    }

    private function vehicleBelongsToDealer(int $vehicleId, int $dealerId): bool
    {
        return Vehicle::where('dealer_id', $dealerId)->where('id', $vehicleId)->exists();
    }
}
