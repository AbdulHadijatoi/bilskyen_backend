<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Services\PlatformSettingService;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentProvider implements PaymentProviderInterface
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function getName(): string
    {
        return 'stripe';
    }

    public function isEnabled(): bool
    {
        $enabled = $this->platformSettingService->get('payment', 'stripe_enabled', false);

        return $enabled === true || $enabled === 'true' || $enabled === '1';
    }

    public function testConnection(): array
    {
        if (! $this->configureClient()) {
            return [
                'success' => false,
                'message' => __('messages.api.stripe_not_configured'),
            ];
        }

        try {
            \Stripe\Balance::retrieve();

            return [
                'success' => true,
                'message' => __('messages.api.stripe_connection_ok'),
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createCheckoutSession(array $params): array
    {
        if (! $this->configureClient()) {
            throw new \RuntimeException(__('messages.api.stripe_not_configured'));
        }

        $session = Session::create([
            'mode' => 'payment',
            'customer' => $params['customer_id'] ?? null,
            'customer_email' => $params['customer_email'] ?? null,
            'client_reference_id' => (string) ($params['reference'] ?? ''),
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $params['currency'] ?? config('payments.currency', 'dkk'),
                        'unit_amount' => (int) $params['amount_cents'],
                        'product_data' => [
                            'name' => (string) $params['product_name'],
                            'description' => $params['product_description'] ?? null,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'metadata' => $params['metadata'] ?? [],
        ]);

        return [
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        if (! $this->configureClient()) {
            throw new \RuntimeException(__('messages.api.stripe_not_configured'));
        }

        return Session::retrieve($sessionId, ['expand' => ['payment_intent']]);
    }

    public function constructWebhookEvent(string $payload, string $signature): object
    {
        $secret = $this->platformSettingService->get('payment', 'webhook_secret');

        if (! $secret) {
            throw new \RuntimeException('Stripe webhook secret is not configured');
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }

    public function getPublishableKey(): ?string
    {
        $key = $this->platformSettingService->get('payment', 'publishable_key');

        return is_string($key) && $key !== '' && $key !== '********' ? $key : null;
    }

    public function configureClient(): bool
    {
        $secretKey = $this->platformSettingService->get('payment', 'secret_key');

        if (! is_string($secretKey) || $secretKey === '' || $secretKey === '********') {
            return false;
        }

        Stripe::setApiKey($secretKey);

        return true;
    }
}
