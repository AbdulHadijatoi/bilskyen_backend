<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\Finance\FinanceCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFinanceController extends Controller
{
    public function __construct(
        private FinanceCalculatorService $financeCalculatorService,
    ) {}

    public function settings(Request $request): JsonResponse
    {
        if (! $this->financeCalculatorService->isPlatformCalculatorEnabled()) {
            return $this->error(__('messages.finance.calculator_disabled'), [], 403);
        }

        return $this->success($this->financeCalculatorService->settingsForLocale($request->get('locale')));
    }

    public function calculate(Request $request): JsonResponse
    {
        if (! $this->financeCalculatorService->isPlatformCalculatorEnabled()) {
            return $this->error(__('messages.finance.calculator_disabled'), [], 403);
        }

        $data = $request->validate([
            'price' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'annual_rate_pct' => 'nullable|numeric|min:0|max:50',
            'term_months' => 'nullable|integer|min:1|max:120',
        ]);

        $result = $this->financeCalculatorService->calculateMonthlyPayment(
            (float) $data['price'],
            (float) ($data['down_payment'] ?? 0),
            isset($data['annual_rate_pct']) ? (float) $data['annual_rate_pct'] : null,
            isset($data['term_months']) ? (int) $data['term_months'] : null,
        );

        return $this->success($result);
    }

    public function vehicleWidget(Vehicle $vehicle): JsonResponse
    {
        if (! $this->financeCalculatorService->shouldShowCalculatorForVehicle($vehicle)) {
            return $this->error(__('messages.finance.calculator_disabled'), [], 403);
        }

        $vehicle->loadMissing('dealer');

        return $this->success([
            'vehicle_id' => $vehicle->id,
            'price' => $vehicle->price,
            'settings' => $this->financeCalculatorService->settingsForLocale(),
            'finance_partner_url' => $this->financeCalculatorService->dealerFinanceUrl($vehicle->dealer),
            'estimate' => $this->financeCalculatorService->calculateMonthlyPayment((float) $vehicle->price),
        ]);
    }
}
