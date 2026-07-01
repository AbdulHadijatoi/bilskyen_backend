<?php

namespace App\Http\Controllers;

use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerOnboardingController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        return $this->success([
            'step' => (int) $dealer->onboarding_step,
            'completed_at' => $dealer->onboarding_completed_at,
            'is_complete' => $dealer->onboarding_completed_at !== null,
        ]);
    }

    public function advance(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $data = $request->validate([
            'step' => 'required|integer|min:0|max:4',
            'complete' => 'sometimes|boolean',
        ]);

        $dealer->onboarding_step = $data['step'];
        if (! empty($data['complete'])) {
            $dealer->onboarding_completed_at = now();
        }
        $dealer->save();

        return $this->success([
            'step' => (int) $dealer->onboarding_step,
            'completed_at' => $dealer->onboarding_completed_at,
            'is_complete' => $dealer->onboarding_completed_at !== null,
        ]);
    }
}
