<?php

namespace App\Http\Controllers;

use App\Models\DealerInvoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\DealerContextService;
use App\Services\PaymentService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerBillingController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function config(): JsonResponse
    {
        return $this->success([
            'stripe_enabled' => $this->paymentService->isStripeEnabled(),
            'publishable_key' => $this->paymentService->provider()->getPublishableKey(),
            'instant_subscription_checkout' => $this->paymentService->instantSubscriptionCheckoutEnabled(),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $invoices = DealerInvoice::query()
            ->where('dealer_id', $dealer->id)
            ->with('lines')
            ->orderByDesc('id')
            ->paginate($request->integer('limit', 15));

        return $this->paginated($invoices);
    }

    public function showInvoice(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $invoice = DealerInvoice::query()
            ->where('dealer_id', $dealer->id)
            ->with('lines')
            ->findOrFail($id);

        return $this->success($invoice);
    }

    public function checkoutInvoice(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $invoice = DealerInvoice::where('dealer_id', $dealer->id)->findOrFail($id);

        $result = $this->paymentService->createInvoiceCheckout($dealer, $invoice, $request->user());

        return $this->success($result);
    }

    public function checkoutSubscription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $dealer = $this->dealerContextService->requireDealer($request->user());
        $plan = Plan::findOrFail($data['plan_id']);

        if (! $this->paymentService->instantSubscriptionCheckoutEnabled()) {
            return $this->error(__('messages.api.stripe_subscription_checkout_disabled'), [], 403);
        }

        $result = $this->paymentService->createSubscriptionCheckout(
            $dealer,
            $plan,
            $data['billing_cycle'],
            $request->user()
        );

        return $this->success($result);
    }

    public function paymentHistory(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $payments = Payment::query()
            ->where('dealer_id', $dealer->id)
            ->orderByDesc('id')
            ->paginate($request->integer('limit', 20));

        return $this->paginated($payments);
    }
}
