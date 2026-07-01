<?php

namespace App\Services;

use App\Constants\BillingModel;
use App\Constants\DealerInvoiceStatus;
use App\Constants\PaymentPurpose;
use App\Constants\PaymentStatus;
use App\Contracts\PaymentProviderInterface;
use App\Models\Dealer;
use App\Models\DealerInvoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Plan;
use App\Models\PlanPriceHistory;
use App\Models\User;
use App\Services\Payments\StripePaymentProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private StripePaymentProvider $stripeProvider,
        private DealerInvoiceService $dealerInvoiceService,
        private DealerSubscriptionApplicationService $subscriptionApplicationService,
        private DealerContextService $dealerContextService,
        private PlatformSettingService $platformSettingService,
    ) {}

    public function provider(): PaymentProviderInterface
    {
        return $this->stripeProvider;
    }

    public function isStripeEnabled(): bool
    {
        return $this->stripeProvider->isEnabled();
    }

    public function instantSubscriptionCheckoutEnabled(): bool
    {
        if (! $this->isStripeEnabled()) {
            return false;
        }

        $value = $this->platformSettingService->get('payment', 'instant_subscription_checkout', true);

        return $value === true || $value === 'true' || $value === '1';
    }

    /**
     * @return array{checkout_url: string, payment_id: int}
     */
    public function createInvoiceCheckout(Dealer $dealer, DealerInvoice $invoice, User $user): array
    {
        $this->assertStripeReady();

        if ($invoice->dealer_id !== $dealer->id) {
            throw new \RuntimeException('Invoice does not belong to dealer');
        }

        if (! in_array($invoice->status, [DealerInvoiceStatus::SENT, DealerInvoiceStatus::OVERDUE], true)) {
            throw new \RuntimeException(__('messages.api.invoice_not_payable'));
        }

        if ($invoice->total_cents <= 0) {
            throw new \RuntimeException(__('messages.api.invoice_zero_amount'));
        }

        return $this->createCheckout(
            dealer: $dealer,
            user: $user,
            purpose: PaymentPurpose::INVOICE,
            amountCents: $invoice->total_cents,
            currency: strtolower($invoice->currency ?? 'dkk'),
            productName: __('messages.payments.invoice_product', ['id' => $invoice->id]),
            productDescription: __('messages.payments.invoice_period', [
                'start' => $invoice->period_start?->format('Y-m-d'),
                'end' => $invoice->period_end?->format('Y-m-d'),
            ]),
            successPath: '/billing?payment=success&invoice='.$invoice->id,
            cancelPath: '/billing?payment=cancelled',
            payable: $invoice,
            metadata: [
                'payment_purpose' => PaymentPurpose::INVOICE,
                'dealer_invoice_id' => $invoice->id,
                'dealer_id' => $dealer->id,
            ],
        );
    }

    /**
     * @return array{checkout_url: string, payment_id: int}
     */
    public function createSubscriptionCheckout(Dealer $dealer, Plan $plan, string $billingCycle, User $user): array
    {
        $this->assertStripeReady();

        if ($plan->billing_model === BillingModel::USAGE_DAILY) {
            throw new \RuntimeException(__('messages.api.usage_plan_no_upfront_payment'));
        }

        $price = $this->resolvePlanPriceCents($plan, $billingCycle);

        if ($price <= 0) {
            throw new \RuntimeException(__('messages.api.plan_has_no_price'));
        }

        return $this->createCheckout(
            dealer: $dealer,
            user: $user,
            purpose: PaymentPurpose::SUBSCRIPTION,
            amountCents: $price,
            currency: 'dkk',
            productName: $plan->name,
            productDescription: __('messages.payments.subscription_product', ['cycle' => $billingCycle]),
            successPath: '/subscription?payment=success',
            cancelPath: '/subscription?payment=cancelled',
            payable: null,
            metadata: [
                'payment_purpose' => PaymentPurpose::SUBSCRIPTION,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'dealer_id' => $dealer->id,
                'user_id' => $user->id,
            ],
        );
    }

    public function handleStripeWebhook(string $payload, ?string $signature): void
    {
        if (! $signature) {
            throw new \RuntimeException('Missing Stripe signature');
        }

        $event = $this->stripeProvider->constructWebhookEvent($payload, $signature);

        $stored = PaymentWebhookEvent::firstOrCreate(
            ['event_id' => $event->id],
            [
                'provider' => 'stripe',
                'type' => $event->type,
                'status' => 'received',
                'payload' => json_decode($payload, true),
            ]
        );

        if ($stored->processed_at) {
            return;
        }

        try {
            if ($event->type === 'checkout.session.completed') {
                $this->handleCheckoutSessionCompleted($event->data->object);
            }

            $stored->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $stored->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function markPaymentSucceeded(Payment $payment, ?string $paymentIntentId = null): void
    {
        $payment->update([
            'status' => PaymentStatus::SUCCEEDED,
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);
    }

    private function createCheckout(
        Dealer $dealer,
        User $user,
        string $purpose,
        int $amountCents,
        string $currency,
        string $productName,
        ?string $productDescription,
        string $successPath,
        string $cancelPath,
        $payable,
        array $metadata,
    ): array {
        $panelUrl = rtrim(config('payments.panel_url'), '/');
        $customerId = $this->ensureStripeCustomer($dealer, $user);

        $session = $this->stripeProvider->createCheckoutSession([
            'customer_id' => $customerId,
            'customer_email' => $user->email,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'product_name' => $productName,
            'product_description' => $productDescription,
            'success_url' => $panelUrl.$successPath,
            'cancel_url' => $panelUrl.$cancelPath,
            'metadata' => $metadata,
            'reference' => $dealer->id.'-'.time(),
        ]);

        $payment = Payment::create([
            'dealer_id' => $dealer->id,
            'provider' => 'stripe',
            'purpose' => $purpose,
            'payable_type' => $payable ? $payable::class : null,
            'payable_id' => $payable?->id,
            'amount_cents' => $amountCents,
            'currency' => strtoupper($currency),
            'status' => PaymentStatus::PENDING,
            'stripe_checkout_session_id' => $session['session_id'],
            'metadata' => $metadata,
        ]);

        $this->platformSettingService->logIntegration(
            'stripe',
            'checkout.created',
            'success',
            "Checkout session {$session['session_id']} for {$purpose}",
            $user->id,
            ['payment_id' => $payment->id]
        );

        return [
            'checkout_url' => $session['checkout_url'],
            'payment_id' => $payment->id,
        ];
    }

    private function handleCheckoutSessionCompleted(object $session): void
    {
        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if (! $payment) {
            Log::warning('Payment not found for checkout session', ['session_id' => $session->id]);

            return;
        }

        if ($payment->status === PaymentStatus::SUCCEEDED) {
            return;
        }

        $intentId = is_string($session->payment_intent ?? null)
            ? $session->payment_intent
            : ($session->payment_intent->id ?? null);

        DB::transaction(function () use ($payment, $intentId, $session) {
            $this->markPaymentSucceeded($payment, $intentId);

            $purpose = $payment->metadata['payment_purpose'] ?? $payment->purpose;

            if ($purpose === PaymentPurpose::INVOICE) {
                $invoice = $payment->payable;
                if ($invoice instanceof DealerInvoice) {
                    $this->dealerInvoiceService->markPaid($invoice);
                }
            }

            if ($purpose === PaymentPurpose::SUBSCRIPTION) {
                $this->activateSubscriptionFromPayment($payment);
            }
        });

        $this->platformSettingService->logIntegration(
            'stripe',
            'checkout.completed',
            'success',
            "Payment {$payment->id} succeeded",
            null,
            ['session_id' => $session->id]
        );
    }

    private function activateSubscriptionFromPayment(Payment $payment): void
    {
        $meta = $payment->metadata ?? [];
        $planId = (int) ($meta['plan_id'] ?? 0);
        $billingCycle = (string) ($meta['billing_cycle'] ?? 'monthly');
        $userId = (int) ($meta['user_id'] ?? 0);

        $dealer = Dealer::findOrFail($payment->dealer_id);
        $plan = Plan::findOrFail($planId);
        $user = $userId ? User::find($userId) : $dealer->owner;

        if (! $user) {
            throw new \RuntimeException('User not found for subscription activation');
        }

        $fakeRequest = Request::create('/webhooks/stripe', 'POST');

        $this->subscriptionApplicationService->applyPlanToDealer(
            $dealer,
            $plan,
            $billingCycle,
            Carbon::now(),
            $user,
            $fakeRequest,
            __('messages.audit.subscription_change_cancelled'),
            __('messages.audit.subscription_created_stripe', ['payment_id' => $payment->id]),
        );
    }

    private function ensureStripeCustomer(Dealer $dealer, User $user): ?string
    {
        if ($dealer->stripe_customer_id) {
            return $dealer->stripe_customer_id;
        }

        $this->stripeProvider->configureClient();

        $customer = \Stripe\Customer::create([
            'email' => $user->email,
            'name' => $dealer->owner?->name ?? $user->name,
            'metadata' => [
                'dealer_id' => $dealer->id,
            ],
        ]);

        $dealer->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    private function resolvePlanPriceCents(Plan $plan, string $billingCycle): int
    {
        $row = PlanPriceHistory::query()
            ->where('plan_id', $plan->id)
            ->where('billing_cycle', $billingCycle)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('starts_at')
            ->first();

        return (int) ($row?->price ?? 0);
    }

    private function assertStripeReady(): void
    {
        if (! $this->stripeProvider->isEnabled()) {
            throw new \RuntimeException(__('messages.api.stripe_not_enabled'));
        }
    }
}
