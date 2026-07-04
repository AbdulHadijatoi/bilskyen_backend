<?php

namespace App\Http\Controllers;

use App\Models\DealerDomain;
use App\Services\Branding\BrandedInventoryAuditService;
use App\Services\Branding\DealerDomainService;
use App\Services\DealerContextService;
use App\Services\Finance\FinanceCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerBrandingController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private DealerDomainService $dealerDomainService,
        private FinanceCalculatorService $financeCalculatorService,
        private BrandedInventoryAuditService $brandedInventoryAuditService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $domains = DealerDomain::where('dealer_id', $dealer->id)->orderByDesc('is_primary')->get();

        return $this->success([
            'finance_partner_url' => $dealer->finance_partner_url,
            'finance_calculator_enabled' => $dealer->finance_calculator_enabled,
            'platform_finance_calculator_enabled' => $this->financeCalculatorService->isPlatformCalculatorEnabled(),
            'google_review_url' => $dealer->google_review_url,
            'google_place_id' => $dealer->google_place_id,
            'theme_primary_color' => $dealer->theme_primary_color,
            'theme_secondary_color' => $dealer->theme_secondary_color,
            'domains' => $domains,
            'cname_target' => $this->dealerDomainService->expectedCnameTarget(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate([
            'finance_partner_url' => 'nullable|url|max:500',
            'finance_calculator_enabled' => 'nullable|boolean',
            'google_review_url' => 'nullable|url|max:500',
            'google_place_id' => 'nullable|string|max:128',
            'theme_primary_color' => 'nullable|string|max:16',
            'theme_secondary_color' => 'nullable|string|max:16',
        ]);

        $dealer->update($data);

        return $this->success($dealer->fresh());
    }

    public function addDomain(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate([
            'domain' => 'required|string|max:255',
            'is_primary' => 'sometimes|boolean',
        ]);

        $record = $this->dealerDomainService->addDomain($dealer, $data['domain'], (bool) ($data['is_primary'] ?? false));

        return $this->created($record);
    }

    public function verifyDomain(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $record = DealerDomain::where('dealer_id', $dealer->id)->findOrFail($id);
        $verified = $this->dealerDomainService->verifyDns($record);

        return $this->success([
            'verified' => $verified,
            'domain' => $record->fresh(),
        ]);
    }

    public function deleteDomain(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        DealerDomain::where('dealer_id', $dealer->id)->where('id', $id)->delete();

        return $this->noContent();
    }

    public function auditLink(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        if (trim((string) $dealer->slug) === '') {
            return $this->error('A dealer slug is required before generating a branded audit link.', [], 422);
        }

        try {
            return $this->success($this->brandedInventoryAuditService->brandingPayload($dealer));
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function reviewSummary(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        return $this->success([
            'google_review_url' => $dealer->google_review_url,
            'google_place_id' => $dealer->google_place_id,
            'configured' => ! empty($dealer->google_review_url) || ! empty($dealer->google_place_id),
            'widget_url' => $dealer->google_review_url,
        ]);
    }
}
