<?php

namespace App\Services\TradeIn;

use App\Models\Enquiry;
use App\Models\TradeInRequest;

class TradeInService
{
    public function createFromExchangeEnquiry(Enquiry $enquiry, array $validated): TradeInRequest
    {
        $vehicle = $enquiry->vehicle;

        return TradeInRequest::create([
            'dealer_id' => $vehicle?->dealer_id ?? $enquiry->lead?->dealer_id,
            'vehicle_id' => $enquiry->vehicle_id,
            'lead_id' => $enquiry->lead_id,
            'enquiry_id' => $enquiry->id,
            'licence_plate' => $validated['licence_plate'] ?? null,
            'kilometers' => isset($validated['kilometers']) ? (int) $validated['kilometers'] : null,
            'expected_price' => $validated['expected_price'] ?? null,
            'condition_notes' => $validated['message'] ?? null,
            'appraisal_status' => TradeInRequest::STATUS_PENDING,
        ]);
    }

    public function updateAppraisal(TradeInRequest $request, array $data): TradeInRequest
    {
        $request->fill([
            'appraisal_status' => $data['appraisal_status'] ?? $request->appraisal_status,
            'offered_value_cents' => $data['offered_value_cents'] ?? $request->offered_value_cents,
            'appraisal_notes' => $data['appraisal_notes'] ?? $request->appraisal_notes,
            'appraised_at' => now(),
        ]);
        $request->save();

        return $request->fresh(['vehicle', 'lead', 'enquiry']);
    }
}
